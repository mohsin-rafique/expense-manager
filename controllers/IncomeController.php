<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace app\controllers;

use Yii;
use app\components\ApiResponse;
use app\models\Income;
use app\models\IncomeSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\UploadedFile;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

/**
 * IncomeController implements the CRUD actions for Income model.
 *
 * Provides comprehensive income management including:
 * - List view with advanced filtering
 * - AJAX-based CRUD operations via modals
 * - File attachment support
 * - Excel export with formatting
 * - Statistics and summary views
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */
class IncomeController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors(): array
    {
        return [
            'workspaceWrite' => [
                'class' => \app\components\RequireWorkspaceCapability::class,
                'capability' => \app\models\WorkspaceMember::CAN_MANAGE_DATA,
                'only' => ['create', 'update', 'delete', 'bulk-delete'],
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
                    'delete' => ['POST'],
                    'bulk-delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lists all incomes for the current user
     *
     * @return string
     */
    public function actionIndex(): string
    {
        $searchModel = new IncomeSearch();

        // Set default date range to current month if not specified
        $params = Yii::$app->request->queryParams;
        if (empty($params['IncomeSearch']['start_date']) && empty($params['IncomeSearch']['end_date'])) {
            $searchModel->start_date = date('Y-m-01');
            $searchModel->end_date = date('Y-m-t');
        }

        $dataProvider = $searchModel->search($params);

        // Get statistics for the current filter
        $statistics = $searchModel->getStatistics($dataProvider);

        // Get category breakdown for chart
        $categoryBreakdown = $searchModel->getCategoryBreakdown();

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'statistics' => $statistics,
            'categoryBreakdown' => $categoryBreakdown,
        ]);
    }

    /**
     * Displays a single expense
     *
     * @param int $id
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionView(int $id): string
    {
        $model = $this->findModel($id);

        if (Yii::$app->request->isAjax) {
            return $this->renderAjax('view', ['model' => $model]);
        }

        return $this->render('view', ['model' => $model]);
    }

    /**
     * Creates a new income.
     *
     * @return string|Response
     */
    public function actionCreate()
    {
        $model = new Income();
        $model->user_id = Yii::$app->user->id;
        $model->entry_date = date('Y-m-d');

        if (Yii::$app->request->isAjax && $model->load(Yii::$app->request->post())) {
            return $this->processForm($model);
        }

        if (Yii::$app->request->isAjax) {
            return $this->renderAjax('_form', ['model' => $model]);
        }
    }

    /**
     *  Updates an existing income
     *
     * @param int $id
     * @return string|Response
     * @throws NotFoundHttpException
     */
    public function actionUpdate(int $id)
    {
        $model = $this->findModel($id);
        $oldFile = $model->getAbsoluteFilePath();
        $oldFileName = $model->filename;
        $oldFilePath = $model->filepath;

        if (Yii::$app->request->isAjax && $model->load(Yii::$app->request->post())) {
            return $this->processForm($model, $oldFile, $oldFileName, $oldFilePath);
        }

        if (Yii::$app->request->isAjax) {
            return $this->renderAjax('_form', ['model' => $model]);
        }

        return $this->renderAjax('update', ['model' => $model]);
    }

    /**
     * Process form submission for create/update
     *
     * @param Income $model
     * @param string|null $oldFile Old file path for update
     * @param string|null $oldFileName Old file name for update
     * @param string|null $oldFilePath Old file path for update
     *
     * @return array
     */
    protected function processForm(
        Income $model,
        ?string $oldFile = null,
        ?string $oldFileName = null,
        ?string $oldFilePath = null
    ) {
        Yii::$app->response->format = Response::FORMAT_JSON;

        // Handle file upload
        $file = UploadedFile::getInstance($model, 'myFile');

        if ($file !== null) {
            $model->filename = $file->name;
            $ext = $file->extension;
            $uploadPath = Yii::$app->params['uploadPath'] ?? 'uploads/incomes/';
            $model->filepath = $uploadPath . Yii::$app->security->generateRandomString(16) . '.' . $ext;
        } elseif ($oldFileName !== null) {
            // Keep old file if no new file uploaded
            $model->filename = $oldFileName;
            $model->filepath = $oldFilePath;
        }

        if ($model->validate() && $model->save(false)) {
            // Save uploaded file
            if ($file !== null) {
                $savePath = Yii::getAlias('@webroot/' . $model->filepath);
                $saveDir = dirname($savePath);

                if (!is_dir($saveDir)) {
                    mkdir($saveDir, 0755, true);
                }

                $file->saveAs($savePath);

                // Delete old file if exists
                if ($oldFile !== null && file_exists($oldFile)) {
                    @unlink($oldFile);
                }
            }

            return ApiResponse::success(
                $model->isNewRecord
                    ? Yii::t('app', 'Income created successfully.')
                    : Yii::t('app', 'Income updated successfully.'),
                ['id' => $model->id]
            );
        }

        return ApiResponse::error(Yii::t('app', 'Failed to save income.'), $model->errors);
    }

    /**
     * Deletes an existing Income model.
     *
     * @param int $id
     * @return Response
     * @throws NotFoundHttpException
     */
    public function actionDelete(int $id): Response
    {
        $model = $this->findModel($id);

        try {
            // Delete attachment file
            $model->deleteAttachment();

            if ($model->delete()) {
                if (Yii::$app->request->isAjax) {
                    Yii::$app->response->format = Response::FORMAT_JSON;
                    return $this->asJson(ApiResponse::success(Yii::t('app', 'Income deleted successfully.')));
                }
                Yii::$app->session->setFlash('success', Yii::t('app', 'Income deleted successfully.'));
            }
        } catch (\Exception $e) {
            Yii::error('Failed to delete income: ' . $e->getMessage(), __METHOD__);

            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return $this->asJson(ApiResponse::error(Yii::t('app', 'Failed to delete income.')));
            }
            Yii::$app->session->setFlash('error', Yii::t('app', 'Failed to delete income.'));
        }

        return $this->redirect(['index']);
    }

    /**
     * Bulk delete multiple income records.
     *
     * @return Response
     */
    public function actionBulkDelete(): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $ids = Yii::$app->request->post('ids', []);

        if (empty($ids)) {
            return $this->asJson(ApiResponse::error(Yii::t('app', 'No records selected.')));
        }

        $deleted = 0;
        $failed = 0;

        foreach ($ids as $id) {
            try {
                $model = $this->findModel((int) $id);
                $model->deleteAttachment();
                if ($model->delete()) {
                    $deleted++;
                } else {
                    $failed++;
                }
            } catch (\Exception $e) {
                $failed++;
                Yii::error('Bulk delete failed for ID ' . $id . ': ' . $e->getMessage(), __METHOD__);
            }
        }

        $message = Yii::t('app', '{deleted} record(s) deleted, {failed} failed.', [
            'deleted' => $deleted,
            'failed' => $failed,
        ]);

        $response = $failed === 0 ? ApiResponse::success($message) : ApiResponse::error($message);
        return $this->asJson($response);
    }

    /**
     * Exports income data to Excel.
     *
     * @return Response
     */
    public function actionExport(): Response
    {
        $params = Yii::$app->request->queryParams['IncomeSearch'] ?? [];

        $searchModel = new IncomeSearch();
        $dataProvider = $searchModel->search($params, true);
        $incomes = $dataProvider->getModels();

        return $this->exportToExcel($incomes, $searchModel);
    }

    /**
     * Generate Excel file from income data.
     *
     * @param array $incomes Income models to export
     * @param IncomeSearch $searchModel Search model with filter info
     * @return Response
     */
    protected function exportToExcel(array $incomes, IncomeSearch $searchModel): Response
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Income Report');

        // Report header
        $dateRange = '';
        if ($searchModel->start_date && $searchModel->end_date) {
            $dateRange = Yii::$app->formatter->asDate($searchModel->start_date) . ' - ' .
                Yii::$app->formatter->asDate($searchModel->end_date);
        }

        // Title block (rows 1–3)
        $sheet->setCellValue('A1', 'Income Report');
        $sheet->setCellValue('A2', 'Generated: ' . Yii::$app->formatter->asDatetime(time()));
        if ($dateRange) {
            $sheet->setCellValue('A3', 'Period: ' . $dateRange);
        }

        $sheet->mergeCells('A1:E1');
        $sheet->mergeCells('A2:E2');
        $sheet->mergeCells('A3:E3');

        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['argb' => '198754']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        ]);
        $sheet->getStyle('A2:A3')->applyFromArray([
            'font' => ['italic' => true, 'size' => 10, 'color' => ['argb' => '888888']],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(22);
        $sheet->getRowDimension(4)->setRowHeight(6); // spacer row

        // Column headers (row 5): Date | Category | Reference | Description | Amount
        $headerRow = 5;
        $sheet->fromArray(['Date', 'Category', 'Reference', 'Description', 'Amount'], null, 'A' . $headerRow);

        $sheet->getStyle('A' . $headerRow . ':E' . $headerRow)->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => '198754']],
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFF'], 'size' => 11],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => '198754']],
            ],
        ]);
        $sheet->getStyle('E' . $headerRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getRowDimension($headerRow)->setRowHeight(20);

        // Freeze pane: keep header visible when scrolling
        $sheet->freezePane('A' . ($headerRow + 1));

        // Data rows
        $firstDataRow = $headerRow + 1;
        $row = $firstDataRow;
        $totalAmount = 0;

        foreach ($incomes as $income) {
            // Format date as "Feb 25, 2026"
            $date = Yii::$app->formatter->asDate($income->entry_date, 'MMM d, yyyy');

            // Normalize line breaks in Reference and Description
            $reference    = str_ireplace(['<br />', '<br>'], "\n", $income->reference ?? '');
            $description  = str_ireplace(['<br />', '<br>'], "\n", $income->description ?? '');

            // Currency-formatted amount string
            $amountFormatted = Yii::$app->currency->format($income->amount);

            $sheet->setCellValue('A' . $row, $date);
            $sheet->setCellValue('B' . $row, $income->incomeCategory->name ?? 'N/A');
            $sheet->setCellValue('C' . $row, $reference);
            $sheet->setCellValue('D' . $row, $description);
            $sheet->setCellValue('E' . $row, $amountFormatted);

            // Zebra striping: light blue-grey on even rows
            if (($row % 2) === 0) {
                $sheet->getStyle('A' . $row . ':E' . $row)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'EAFAF1']],
                ]);
            }

            $totalAmount += (float) $income->amount;
            $row++;
        }

        $lastDataRow = $row - 1;

        // Apply to all data rows: thin borders, top vertical align, left-align text
        $sheet->getStyle('A' . $firstDataRow . ':E' . $lastDataRow)->applyFromArray([
            'alignment' => [
                'vertical'   => Alignment::VERTICAL_TOP,
                'horizontal' => Alignment::HORIZONTAL_LEFT,
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'D5D8DC']],
            ],
        ]);

        // Wrap + vertical top for Reference (C) and Description (D)
        $sheet->getStyle('C' . $firstDataRow . ':D' . $lastDataRow)->getAlignment()
            ->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);

        // Right-align Amount column data
        $sheet->getStyle('E' . $firstDataRow . ':E' . $lastDataRow)
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // Total row
        $sheet->setCellValue('D' . $row, 'Total:');
        $sheet->setCellValue('E' . $row, Yii::$app->currency->format($totalAmount));
        $sheet->getStyle('D' . $row . ':E' . $row)->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            'borders' => [
                'top'    => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => '198754']],
                'bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => '198754']],
            ],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'D5F5E3']],
        ]);
        $sheet->getStyle('E' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getRowDimension($row)->setRowHeight(18);

        // Column widths
        $sheet->getColumnDimension('A')->setAutoSize(true);  // Date
        $sheet->getColumnDimension('B')->setAutoSize(true);  // Category
        $sheet->getColumnDimension('C')->setAutoSize(false)->setWidth(42); // Reference (capped)
        $sheet->getColumnDimension('D')->setAutoSize(false)->setWidth(38); // Description (capped)
        $sheet->getColumnDimension('E')->setAutoSize(true);  // Amount

        // Output
        $filename = 'income-report-' . date('Y-m-d-His') . '.xlsx';

        Yii::$app->response->format = Response::FORMAT_RAW;
        Yii::$app->response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        Yii::$app->response->headers->set('Content-Disposition', 'attachment;filename="' . $filename . '"');
        Yii::$app->response->headers->set('Cache-Control', 'max-age=0');

        $writer = new Xlsx($spreadsheet);

        $tmpFile = tempnam(sys_get_temp_dir(), 'income_export_');
        $writer->save($tmpFile);
        $content = file_get_contents($tmpFile);
        unlink($tmpFile);

        Yii::$app->response->content = $content;

        return Yii::$app->response;
    }

    /**
     * Get summary data for dashboard widgets.
     *
     * @return Response
     */
    public function actionSummary(): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $currentMonthTotal = Income::getTotalIncome(
            null,
            date('Y-m-01'),
            date('Y-m-t')
        );

        $lastMonthTotal = Income::getTotalIncome(
            null,
            date('Y-m-01', strtotime('-1 month')),
            date('Y-m-t', strtotime('-1 month'))
        );

        $change = $lastMonthTotal > 0
            ? (($currentMonthTotal - $lastMonthTotal) / $lastMonthTotal) * 100
            : 0;

        return $this->asJson([
            'currentMonth' => $currentMonthTotal,
            'lastMonth' => $lastMonthTotal,
            'change' => round($change, 1),
            'count' => Income::getIncomeCount(null, date('Y-m-01'), date('Y-m-t')),
        ]);
    }

    /**
     * Finds the Income model based on its primary key value.
     *
     * @param int $id
     * @return Income
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel(int $id): Income
    {
        $model = Income::find()
            ->where([
                'id' => $id,
                'workspace_id' => Yii::$app->workspace->getId(),
            ])
            ->one();

        if ($model === null) {
            throw new NotFoundHttpException(Yii::t('app', 'The requested income record does not exist.'));
        }

        return $model;
    }
}
