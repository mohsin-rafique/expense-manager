<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 - 2026 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace app\controllers;

use Yii;
use app\components\ApiResponse;
use app\models\Expense;
use app\models\ExpenseSearch;
use yii\web\Response;
use yii\web\UploadedFile;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

/**
 * ExpenseController - Handles CRUD operations for expense management
 *
 * This controller provides:
 * - AJAX-based CRUD operations
 * - File upload handling
 * - Excel export functionality
 * - Access control and ownership verification
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */
class ExpenseController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'workspaceWrite' => [
                'class' => \app\components\RequireWorkspaceCapability::class,
                'capability' => \app\models\WorkspaceMember::CAN_MANAGE_DATA,
                'only' => ['create', 'update', 'delete'],
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
                ],
            ],
        ];
    }

    /**
     * Lists all expenses for the current user
     *
     * @return string
     */
    public function actionIndex()
    {
        $searchModel = new ExpenseSearch();

        // Set default date range to current month
        $searchModel->start_date = date('Y-m-01');
        $searchModel->end_date = date('Y-m-t');

        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        // Calculate summary statistics
        $summary = $this->calculateSummary($dataProvider);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'summary' => $summary,
        ]);
    }

    /**
     * Displays a single expense
     *
     * @param int $id
     * @return string
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        $this->checkOwnership($model);

        if (Yii::$app->request->isAjax) {
            return $this->renderAjax('view', ['model' => $model]);
        }

        return $this->render('view', ['model' => $model]);
    }

    /**
     * Creates a new expense
     *
     * @return string|Response
     */
    public function actionCreate()
    {
        $model = new Expense();
        $model->user_id = Yii::$app->user->id;
        $model->expense_date = date('Y-m-d');

        if (Yii::$app->request->isAjax && $model->load(Yii::$app->request->post())) {
            return $this->processExpenseForm($model);
        }

        return $this->renderAjax('create', ['model' => $model]);
    }

    /**
     * Updates an existing expense
     *
     * @param int $id
     * @return string|Response
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $this->checkOwnership($model);

        $oldFile = $model->getImageFile();
        $oldFileName = $model->filename;
        $oldFilePath = $model->filepath;

        if (Yii::$app->request->isAjax && $model->load(Yii::$app->request->post())) {
            return $this->processExpenseForm($model, $oldFile, $oldFileName, $oldFilePath);
        }

        return $this->renderAjax('update', ['model' => $model]);
    }

    /**
     * Process expense form submission (create/update)
     *
     * @param Expense $model
     * @param string|null $oldFile Old file path for update
     * @param string|null $oldFileName Old file name for update
     * @param string|null $oldFilePath Old file path for update
     *
     * @return array
     */
    protected function processExpenseForm(
        $model,
        ?string $oldFile = null,
        ?string $oldFileName = null,
        ?string $oldFilePath = null
    ) {
        Yii::$app->response->format = Response::FORMAT_JSON;

        // Clean amount format
        if (!empty($model->amount)) {
            $model->amount = str_replace(',', '', $model->amount);
        }

        // Handle file upload
        $file = UploadedFile::getInstance($model, 'myFile');

        if (!empty($file)) {
            $model->filename = $file->name;
            $ext = $file->extension;
            $model->filepath = Yii::$app->params['uploadPath'] . Yii::$app->security->generateRandomString() . ".{$ext}";
        } elseif ($oldFileName !== null) {
            // Preserve old file if no new file uploaded (update mode)
            $model->filename = $oldFileName;
            $model->filepath = $oldFilePath;
        }

        if ($model->save()) {
            // Handle file operations
            if (!empty($file)) {
                // Delete old file if exists (update mode)
                if (!empty($oldFile) && file_exists($oldFile)) {
                    @unlink($oldFile);
                }
                // Save new file
                $path = $model->getImageFile();
                $file->saveAs($path);
            }

            $response = ApiResponse::success(
                $model->isNewRecord
                    ? Yii::t('app', 'Expense created successfully.')
                    : Yii::t('app', 'Expense updated successfully.')
            );

            // Raise an alert if this expense pushed a budget over its threshold
            $alert = (new \app\services\BudgetService())->evaluateExpense($model);
            if ($alert !== null) {
                $response['alert'] = $alert;
            }

            return $response;
        }

        return ApiResponse::error(Yii::t('app', 'Failed to save expense.'), $model->errors);
    }

    /**
     * Deletes an expense
     *
     * @param int $id
     * @return Response
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        $this->checkOwnership($model);

        Yii::$app->response->format = Response::FORMAT_JSON;

        // Delete associated file if exists
        if (!empty($model->filepath)) {
            $filePath = Yii::getAlias('@webroot/' . $model->filepath);
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }

        if ($model->delete()) {
            return ApiResponse::success(Yii::t('app', 'Expense deleted successfully.'));
        }

        return ApiResponse::error(Yii::t('app', 'Failed to delete expense.'));
    }

    /**
     * Exports expenses to Excel
     *
     * @return Response
     */
    public function actionExport()
    {
        $searchModel = new ExpenseSearch();

        // Apply same default date range as actionIndex so bare export
        // (no filters in URL) mirrors what the user sees on the list page.
        $searchModel->start_date = date('Y-m-01');
        $searchModel->end_date = date('Y-m-t');

        $dataProvider = $searchModel->search(Yii::$app->request->queryParams, true);

        return $this->exportToExcel($dataProvider->getModels(), $searchModel);
    }

    /**
     * Quick stats endpoint for AJAX requests
     *
     * @return array
     */
    public function actionStats()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $startDate = Yii::$app->request->get('start_date', date('Y-m-01'));
        $endDate = Yii::$app->request->get('end_date', date('Y-m-t'));

        return Expense::getSummary($startDate, $endDate);
    }

    /**
     * Calculate summary statistics for the data provider
     *
     * @param \yii\data\ActiveDataProvider $dataProvider
     * @return array
     */
    protected function calculateSummary($dataProvider)
    {
        $models = $dataProvider->query->all();
        $total = array_sum(array_column($models, 'amount'));
        $count = count($models);

        // Group by payment method
        $byPaymentMethod = [];
        foreach ($models as $model) {
            $method = $model->payment_method ?: 'Unknown';
            if (!isset($byPaymentMethod[$method])) {
                $byPaymentMethod[$method] = 0;
            }
            $byPaymentMethod[$method] += $model->amount;
        }

        // Group by category
        $byCategory = [];
        foreach ($models as $model) {
            $categoryName = $model->expenseCategory->name ?? 'Uncategorized';
            if (!isset($byCategory[$categoryName])) {
                $byCategory[$categoryName] = 0;
            }
            $byCategory[$categoryName] += $model->amount;
        }

        return [
            'total' => $total,
            'count' => $count,
            'average' => $count > 0 ? $total / $count : 0,
            'byPaymentMethod' => $byPaymentMethod,
            'byCategory' => $byCategory,
        ];
    }

    /**
     * Export expenses to Excel file
     *
     * @param array $expenses
     * @return Response
     */
    protected function exportToExcel(array $expenses, ExpenseSearch $searchModel): Response
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Expense Report');

        // Title block (rows 1-3)
        $sheet->setCellValue('A1', 'Expense Report');
        $sheet->setCellValue('A2', 'Generated: ' . Yii::$app->formatter->asDatetime(time()));

        $dateRange = '';
        if ($searchModel->start_date && $searchModel->end_date) {
            $dateRange = Yii::$app->formatter->asDate($searchModel->start_date, 'dd MMMM, yyyy')
                . ' - '
                . Yii::$app->formatter->asDate($searchModel->end_date, 'dd MMMM, yyyy');
        }
        if ($dateRange) {
            $sheet->setCellValue('A3', 'Period: ' . $dateRange);
        }

        $sheet->mergeCells('A1:F1');
        $sheet->mergeCells('A2:F2');
        $sheet->mergeCells('A3:F3');

        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 16, 'color' => ['argb' => 'FFdc3545']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        ]);
        $sheet->getStyle('A2:A3')->applyFromArray([
            'font' => ['italic' => true, 'size' => 10, 'color' => ['argb' => 'FF888888']],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(22);
        $sheet->getRowDimension(4)->setRowHeight(6); // spacer row

        // Column headers (row 5)
        $headerRow = 5;
        $headers = ['Date', 'Category', 'Payment Method', 'Reference', 'Description', 'Amount'];
        $sheet->fromArray($headers, null, 'A' . $headerRow);

        // Apply header styling
        $sheet->getStyle('A' . $headerRow . ':F' . $headerRow)->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color'    => ['argb' => 'FFdc3545'],
            ],
            'font' => [
                'bold'  => true,
                'color' => ['argb' => 'FFFFFFFF'],
                'size'  => 11,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFdc3545']],
            ],
        ]);
        $sheet->getStyle('F' . $headerRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getRowDimension($headerRow)->setRowHeight(20);

        // Freeze pane below header
        $sheet->freezePane('A' . ($headerRow + 1));

        // Populate data - A=Date, B=Category, C=Payment Method, D=Reference, E=Description, F=Amount
        $firstDataRow = $headerRow + 1;
        $row = $firstDataRow;
        $totalAmount = 0;

        foreach ($expenses as $expense) {
            $date        = Yii::$app->formatter->asDate($expense->expense_date, 'MMM d, yyyy');
            $reference   = str_ireplace(['<br />', '<br>'], "\n", $expense->reference ?? '');
            $description = str_ireplace(['<br />', '<br>'], "\n", $expense->description ?? '');
            $amount      = Yii::$app->currency->format($expense->amount);

            $sheet->setCellValue('A' . $row, $date);
            $sheet->setCellValue('B' . $row, $expense->expenseCategory->name ?? 'N/A');
            $sheet->setCellValue('C' . $row, $expense->payment_method);
            $sheet->setCellValue('D' . $row, $reference);
            $sheet->setCellValue('E' . $row, $description);
            $sheet->setCellValue('F' . $row, $amount);

            // Zebra striping on even rows
            if (($row % 2) === 0) {
                $sheet->getStyle('A' . $row . ':F' . $row)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FFFDF2F2']],
                ]);
            }

            $totalAmount += (float) $expense->amount;
            $row++;
        }

        $lastDataRow = $row - 1;

        // Apply to all data rows: borders, top alignment, left-align
        $sheet->getStyle('A' . $firstDataRow . ':F' . $lastDataRow)->applyFromArray([
            'alignment' => [
                'vertical'   => Alignment::VERTICAL_TOP,
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'wrapText'   => false,
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD5D8DC']],
            ],
        ]);

        // Wrap text for Reference and Description
        $sheet->getStyle('D' . $firstDataRow . ':E' . $lastDataRow)->getAlignment()
            ->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);

        // Right-align Amount column
        $sheet->getStyle('F' . $firstDataRow . ':F' . $lastDataRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // Total row
        $sheet->setCellValue('E' . $row, 'Total:');
        $sheet->setCellValue('F' . $row, Yii::$app->currency->format($totalAmount));
        $sheet->getStyle('E' . $row . ':F' . $row)->applyFromArray([
            'font'      => ['bold' => true, 'size' => 11],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => [
                'top'    => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FFdc3545']],
                'bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FFdc3545']],
            ],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FFFDECEA']],
        ]);
        $sheet->getStyle('F' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // Column widths
        $sheet->getColumnDimension('A')->setAutoSize(true);         // Date
        $sheet->getColumnDimension('B')->setAutoSize(true);         // Category
        $sheet->getColumnDimension('C')->setAutoSize(true);         // Payment Method
        $sheet->getColumnDimension('D')->setAutoSize(false)->setWidth(36); // Reference
        $sheet->getColumnDimension('E')->setAutoSize(false)->setWidth(40); // Description
        $sheet->getColumnDimension('F')->setAutoSize(true);         // Amount

        // Generate file
        $filename = 'expenses-report-' . date('Y-m-d-His') . '.xlsx';

        Yii::$app->response->format = Response::FORMAT_RAW;
        Yii::$app->response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        Yii::$app->response->headers->set('Content-Disposition', 'attachment;filename="' . $filename . '"');
        Yii::$app->response->headers->set('Cache-Control', 'max-age=0');

        $writer = new Xlsx($spreadsheet);
        $tmpFile = tempnam(sys_get_temp_dir(), 'expense_export_');
        $writer->save($tmpFile);
        Yii::$app->response->content = file_get_contents($tmpFile);
        unlink($tmpFile);

        return Yii::$app->response;
    }

    /**
     * Finds the Expense model based on its primary key value
     *
     * @param int $id
     * @return Expense
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Expense::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested expense does not exist.'));
    }

    /**
     * Check if the current user owns the expense
     *
     * @param Expense $model
     * @throws ForbiddenHttpException if user doesn't own the expense
     */
    protected function checkOwnership($model)
    {
        if ((int) $model->workspace_id !== (int) Yii::$app->workspace->getId()) {
            throw new ForbiddenHttpException(Yii::t('app', 'You are not authorized to access this expense.'));
        }
    }
}
