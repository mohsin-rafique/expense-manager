<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
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

            return ApiResponse::success(
                $model->isNewRecord
                    ? Yii::t('app', 'Expense created successfully.')
                    : Yii::t('app', 'Expense updated successfully.')
            );
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
        $params = Yii::$app->request->queryParams['ExpenseSearch'] ?? [];

        $searchModel = new ExpenseSearch();
        $dataProvider = $searchModel->search($params, true);

        return $this->exportToExcel($dataProvider->getModels());
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
    protected function exportToExcel($expenses)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Expenses');

        // Set column headers
        $headers = ['#', 'Date', 'Category', 'Payment Method', 'Amount', 'Reference', 'Description'];
        $sheet->fromArray($headers, null, 'A1');

        // Apply header styling
        $headerStyle = [
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => ['argb' => '2563eb'],
            ],
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'ffffff'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ];
        $sheet->getStyle('A1:G1')->applyFromArray($headerStyle);

        // Populate data
        $row = 2;
        $totalAmount = 0;

        foreach ($expenses as $index => $expense) {
            $sheet->setCellValue('A' . $row, $index + 1);
            $sheet->setCellValue('B' . $row, $expense->expense_date);
            $sheet->setCellValue('C' . $row, $expense->expenseCategory->name ?? 'N/A');
            $sheet->setCellValue('D' . $row, $expense->payment_method);
            $sheet->setCellValue('E' . $row, $expense->amount);
            $sheet->setCellValue('F' . $row, $expense->reference);
            $sheet->setCellValue('G' . $row, $expense->description);

            $totalAmount += $expense->amount;
            $row++;
        }

        // Add total row
        $sheet->setCellValue('D' . $row, 'Total:');
        $sheet->setCellValue('E' . $row, $totalAmount);
        $sheet->getStyle('D' . $row . ':E' . $row)->getFont()->setBold(true);
        $sheet->getStyle('D' . $row . ':E' . $row)->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);

        // Format amount column
        $sheet->getStyle('E2:E' . $row)->getNumberFormat()->setFormatCode('#,##0.00');

        // Auto-size columns
        foreach (range('A', 'G') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Center align ID column
        $sheet->getStyle('A1:A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Right align amount column
        $sheet->getStyle('E1:E' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // Generate file
        $filename = 'expenses-' . date('Y-m-d-His') . '.xlsx';

        Yii::$app->response->format = Response::FORMAT_RAW;
        Yii::$app->response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        Yii::$app->response->headers->set('Content-Disposition', 'attachment;filename="' . $filename . '"');
        Yii::$app->response->headers->set('Cache-Control', 'max-age=0');

        $writer = new Xlsx($spreadsheet);

        ob_start();
        $writer->save('php://output');
        Yii::$app->response->content = ob_get_clean();

        return Yii::$app->response->send();
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
        if ($model->user_id !== Yii::$app->user->id) {
            throw new ForbiddenHttpException(Yii::t('app', 'You are not authorized to access this expense.'));
        }
    }
}
