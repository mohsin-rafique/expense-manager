<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 - 2026 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace app\controllers;

use Yii;
use app\components\ApiResponse;
use app\helpers\PdfText;
use app\models\ImportForm;
use app\services\BankStatementParser;
use app\services\ImportService;
use yii\helpers\FileHelper;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;

/**
 * ImportController handles bulk import of transactions from CSV/Excel files.
 *
 * Flow: the user uploads a file (`preview`) and receives a validated, row-level
 * preview without anything being written. They then confirm (`run`) to persist
 * the valid rows. Uploaded files are staged under @runtime/imports and removed
 * once the import completes.
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */
class ImportController extends Controller
{
    /** @var string Alias of the staging directory for uploads */
    private const STAGE_DIR = '@runtime/imports';

    /**
     * {@inheritdoc}
     */
    public function behaviors(): array
    {
        return [
            'workspaceWrite' => [
                'class' => \app\components\RequireWorkspaceCapability::class,
                'capability' => \app\models\WorkspaceMember::CAN_MANAGE_DATA,
                'only' => ['run'],
            ],
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'preview' => ['POST'],
                    'run' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Displays the import wizard.
     *
     * @param string $type Preselected import type ('expense' | 'income')
     * @return string
     */
    public function actionIndex(string $type = ImportService::TYPE_EXPENSE): string
    {
        $model = new ImportForm();
        $model->type = in_array($type, [ImportService::TYPE_EXPENSE, ImportService::TYPE_INCOME], true)
            ? $type
            : ImportService::TYPE_EXPENSE;

        return $this->render('index', ['model' => $model]);
    }

    /**
     * Downloads a blank import template for the given type.
     *
     * @param string $type
     * @return Response
     */
    public function actionTemplate(string $type = ImportService::TYPE_EXPENSE): Response
    {
        if (!in_array($type, [ImportService::TYPE_EXPENSE, ImportService::TYPE_INCOME], true)) {
            $type = ImportService::TYPE_EXPENSE;
        }

        $columns = ImportService::columnsFor($type);

        // Header row + one illustrative example row
        $example = $type === ImportService::TYPE_INCOME
            ? [date('Y-m-d'), 'Salary', 'INV-001', 'Monthly salary', '2500.00']
            : [date('Y-m-d'), 'Groceries', 'Cash', 'REC-001', 'Weekly groceries', '85.50'];

        $csv = $this->toCsvLine($columns) . $this->toCsvLine($example);

        return Yii::$app->response->sendContentAsFile(
            "\xEF\xBB\xBF" . $csv, // UTF-8 BOM so Excel reads accents correctly
            "import-template-{$type}.csv",
            ['mimeType' => 'text/csv']
        );
    }

    /**
     * Parses an uploaded file and returns a validated preview (no DB writes).
     *
     * @return Response
     */
    public function actionPreview(): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $model = new ImportForm();
        $model->load(Yii::$app->request->post());
        $model->loadUploadedFile();

        if (!$model->validate()) {
            return $this->asJson(ApiResponse::error(
                Yii::t('app', 'Please correct the errors below.'),
                $model->errors
            ));
        }

        // Stage the uploaded file
        $token = $this->stageFile($model);
        if ($token === null) {
            // stageFile() adds a field error when it knows why (a wrong PDF
            // password, say); fall back to a generic message when it does not.
            $reason = $model->getFirstError('password')
                ?: $model->getFirstError('file')
                ?: Yii::t('app', 'Failed to process the uploaded file.');

            return $this->asJson(ApiResponse::error($reason, $model->errors));
        }

        $path = $this->stagePath($token);
        if ($path === null || !is_file($path)) {
            Yii::error('Staged import file could not be resolved for token: ' . $token, __METHOD__);
            return $this->asJson(ApiResponse::error(Yii::t('app', 'Failed to process the uploaded file.')));
        }

        $options = $model->options();
        $service = new ImportService();
        $parsed = $service->parse($path, $options);

        if ($parsed['error'] !== null) {
            $this->discard($token);
            return $this->asJson(ApiResponse::error($parsed['error']));
        }

        if (empty($parsed['rows'])) {
            $this->discard($token);
            return $this->asJson(ApiResponse::error($model->isStatement()
                ? Yii::t('app', 'No {direction} lines were found in this statement.', [
                    'direction' => $model->type === ImportService::TYPE_INCOME
                        ? Yii::t('app', 'credit')
                        : Yii::t('app', 'debit'),
                ])
                : Yii::t('app', 'No data rows were found in the file.')));
        }

        $preview = $service->validateRows($parsed['rows'], $model->type, Yii::$app->user->id, $options);

        $html = $this->renderAjax('_preview', [
            'type' => $model->type,
            'rows' => $preview['rows'],
            'summary' => $preview['summary'],
            'isStatement' => $model->isStatement(),
            'format' => $parsed['format'] ?? null,
        ]);

        $message = $model->isStatement() && !empty($parsed['format'])
            ? Yii::t('app', 'Recognized as {format}.', [
                'format' => BankStatementParser::formatList()[$parsed['format']] ?? $parsed['format'],
            ])
            : Yii::t('app', 'File parsed successfully.');

        return $this->asJson(ApiResponse::success($message, [
            'token' => $token,
            'summary' => $preview['summary'],
            'html' => $html,
        ]));
    }

    /**
     * Imports the valid rows from a previously staged file.
     *
     * @return Response
     */
    public function actionRun(): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $request = Yii::$app->request;
        $token = (string) $request->post('token', '');
        $type = (string) $request->post('type', ImportService::TYPE_EXPENSE);

        if (!in_array($type, [ImportService::TYPE_EXPENSE, ImportService::TYPE_INCOME], true)) {
            return $this->asJson(ApiResponse::error(Yii::t('app', 'Invalid import type.')));
        }

        // Re-validate the options through the form rather than trusting the
        // posted values: the bank and fallback category are foreign keys and
        // must still belong to this workspace at import time. The staged file
        // is already plain text for statements, so no password is needed here.
        $model = new ImportForm();
        $model->type = $type;
        $model->source = (string) $request->post('source', ImportForm::SOURCE_SPREADSHEET);
        $model->statementFormat = (string) $request->post('statementFormat', '');
        $model->autoCreateCategories = (bool) $request->post('autoCreateCategories', 1);
        $model->skipDuplicates = (bool) $request->post('skipDuplicates', 1);
        $model->includeTransfers = (bool) $request->post('includeTransfers', 0);
        $model->bank_id = $request->post('bankId') ?: null;
        $model->fallbackCategoryId = $request->post('fallbackCategoryId') ?: null;

        if (!$model->validate(['type', 'source', 'statementFormat', 'bank_id', 'fallbackCategoryId'])) {
            return $this->asJson(ApiResponse::error(
                Yii::t('app', 'Please correct the errors below.'),
                $model->errors
            ));
        }

        $options = $model->options();

        $path = $this->stagePath($token);
        if ($path === null || !is_file($path)) {
            return $this->asJson(ApiResponse::error(Yii::t('app', 'The upload session expired. Please upload the file again.')));
        }

        $service = new ImportService();
        $parsed = $service->parse($path, $options);

        if ($parsed['error'] !== null) {
            $this->discard($token);
            return $this->asJson(ApiResponse::error($parsed['error']));
        }

        $result = $service->import($parsed['rows'], $type, Yii::$app->user->id, $options);
        $this->discard($token);

        $message = Yii::t('app', '{count} record(s) imported.', ['count' => $result['imported']]);
        if ($result['createdCategories'] > 0) {
            $message .= ' ' . Yii::t('app', '{count} category(s) created.', ['count' => $result['createdCategories']]);
        }
        if ($result['skipped'] > 0) {
            $message .= ' ' . Yii::t('app', '{count} row(s) skipped.', ['count' => $result['skipped']]);
        }
        if ($result['failed'] > 0) {
            $message .= ' ' . Yii::t('app', '{count} row(s) failed.', ['count' => $result['failed']]);
        }

        $envelope = $result['failed'] > 0 && $result['imported'] === 0
            ? ApiResponse::error($message, [], $result)
            : ApiResponse::success($message, $result);

        return $this->asJson($envelope);
    }

    // ─── Staging helpers ─────────────────────────────────────────────

    /**
     * Stores the uploaded file under the staging directory and returns a token.
     *
     * A statement PDF is converted to text here and only the text is staged.
     * That way the confirm step re-reads plain text, and the PDF password is
     * never held on disk nor sent back through the browser to be reposted.
     *
     * @param ImportForm $model
     * @return string|null The staging token, or null on failure
     */
    private function stageFile(ImportForm $model): ?string
    {
        $dir = Yii::getAlias(self::STAGE_DIR);

        try {
            FileHelper::createDirectory($dir, 0775);
            $this->cleanupStale($dir);
        } catch (\Throwable $e) {
            Yii::error('Failed to create import staging dir: ' . $e->getMessage(), __METHOD__);
            return null;
        }

        if ($model->isStatement() && $model->isPdf()) {
            return $this->stageStatementText($model, $dir);
        }

        $ext = strtolower($model->file->extension ?: 'csv');
        $token = Yii::$app->security->generateRandomString(24) . '.' . preg_replace('/[^a-z0-9]/', '', $ext);
        $path = $dir . DIRECTORY_SEPARATOR . $token;

        return $model->file->saveAs($path) ? $token : null;
    }

    /**
     * Extracts a statement PDF to text and stages the text.
     *
     * Empty output means either the wrong open password or a PDF with no text
     * layer at all (a scan); the two are told apart by whether the file is
     * encrypted, so the user gets an error they can act on.
     *
     * @param ImportForm $model
     * @param string $dir Staging directory
     * @return string|null
     */
    private function stageStatementText(ImportForm $model, string $dir): ?string
    {
        $temp = (string) $model->file->tempName;
        $text = PdfText::extract($temp, $model->password);

        if (trim($text) === '') {
            if (PdfText::isEncrypted($temp)) {
                $model->addError('password', (string) $model->password === ''
                    ? Yii::t('app', 'This PDF is password-protected. Enter its open password and try again.')
                    : Yii::t('app', 'The PDF password appears to be incorrect. Please check it and try again.'));
            } else {
                $model->addError('file', Yii::t('app', 'Could not read any text from this PDF. If it is a scanned image, export a CSV from your bank instead.'));
            }

            return null;
        }

        $token = Yii::$app->security->generateRandomString(24) . '.txt';
        $path = $dir . DIRECTORY_SEPARATOR . $token;

        return file_put_contents($path, $text) === false ? null : $token;
    }

    /**
     * Resolves a staging token to an absolute path, guarding against traversal.
     *
     * @param string $token
     * @return string|null
     */
    private function stagePath(string $token): ?string
    {
        // Token format: <random>.<ext> - a single dot, and only the characters
        // generateRandomString() can emit (base64url, so "-" and "_" included).
        // No slashes and no second dot, which is what keeps traversal out.
        if (!preg_match('/^[A-Za-z0-9_-]+\.[a-z0-9]+$/', $token)) {
            return null;
        }

        return Yii::getAlias(self::STAGE_DIR) . DIRECTORY_SEPARATOR . $token;
    }

    /**
     * Deletes a staged file.
     *
     * @param string $token
     */
    private function discard(string $token): void
    {
        $path = $this->stagePath($token);
        if ($path !== null && is_file($path)) {
            @unlink($path);
        }
    }

    /**
     * Removes staged files older than one hour.
     *
     * @param string $dir
     */
    private function cleanupStale(string $dir): void
    {
        foreach (glob($dir . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
            if (is_file($file) && (time() - filemtime($file)) > 3600) {
                @unlink($file);
            }
        }
    }

    /**
     * Builds a single CSV line (RFC-4180-ish) from an array of values.
     *
     * @param array $fields
     * @return string
     */
    private function toCsvLine(array $fields): string
    {
        $escaped = array_map(static function ($v) {
            $v = (string) $v;
            if (preg_match('/[",\r\n]/', $v)) {
                $v = '"' . str_replace('"', '""', $v) . '"';
            }
            return $v;
        }, $fields);

        return implode(',', $escaped) . "\r\n";
    }
}
