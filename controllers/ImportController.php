<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace app\controllers;

use Yii;
use app\components\ApiResponse;
use app\models\ImportForm;
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
            return $this->asJson(ApiResponse::error(Yii::t('app', 'Failed to process the uploaded file.')));
        }

        $service = new ImportService();
        $parsed = $service->parse($this->stagePath($token));

        if ($parsed['error'] !== null) {
            $this->discard($token);
            return $this->asJson(ApiResponse::error($parsed['error']));
        }

        if (empty($parsed['rows'])) {
            $this->discard($token);
            return $this->asJson(ApiResponse::error(Yii::t('app', 'No data rows were found in the file.')));
        }

        $preview = $service->validateRows($parsed['rows'], $model->type, Yii::$app->user->id, $model->options());

        $html = $this->renderAjax('_preview', [
            'type' => $model->type,
            'rows' => $preview['rows'],
            'summary' => $preview['summary'],
        ]);

        return $this->asJson(ApiResponse::success(Yii::t('app', 'File parsed successfully.'), [
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
        $options = [
            'autoCreateCategories' => (bool) $request->post('autoCreateCategories', 1),
            'skipDuplicates' => (bool) $request->post('skipDuplicates', 1),
        ];

        if (!in_array($type, [ImportService::TYPE_EXPENSE, ImportService::TYPE_INCOME], true)) {
            return $this->asJson(ApiResponse::error(Yii::t('app', 'Invalid import type.')));
        }

        $path = $this->stagePath($token);
        if ($path === null || !is_file($path)) {
            return $this->asJson(ApiResponse::error(Yii::t('app', 'The upload session expired. Please upload the file again.')));
        }

        $service = new ImportService();
        $parsed = $service->parse($path);

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

        $ext = strtolower($model->file->extension ?: 'csv');
        $token = Yii::$app->security->generateRandomString(24) . '.' . preg_replace('/[^a-z0-9]/', '', $ext);
        $path = $dir . DIRECTORY_SEPARATOR . $token;

        return $model->file->saveAs($path) ? $token : null;
    }

    /**
     * Resolves a staging token to an absolute path, guarding against traversal.
     *
     * @param string $token
     * @return string|null
     */
    private function stagePath(string $token): ?string
    {
        // Token format: <random>.<ext> — strictly alphanumeric + single dot
        if (!preg_match('/^[A-Za-z0-9]+\.[a-z0-9]+$/', $token)) {
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
