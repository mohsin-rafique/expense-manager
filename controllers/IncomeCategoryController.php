<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace app\controllers;

use Yii;
use app\components\ApiResponse;
use app\models\IncomeCategory;
use app\models\IncomeCategorySearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

/**
 * IncomeCategoryController implements the CRUD actions for IncomeCategory model.
 *
 * This controller handles all income category management operations including:
 * - Listing categories with search and pagination
 * - Creating new categories via AJAX modal
 * - Viewing category details
 * - Updating existing categories
 * - Deleting categories with validation
 * - Bulk operations
 *
 * ## Access Control
 *
 * All actions require authentication. Users can only access their own categories.
 *
 * ## AJAX Support
 *
 * Create, Update, View, and Delete actions support AJAX requests for modal dialogs.
 * Successful AJAX operations return JSON responses for client-side handling.
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */
class IncomeCategoryController extends Controller
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
                'only' => ['create', 'update', 'delete', 'bulk-delete', 'toggle-status'],
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
     * Lists all IncomeCategory models for the authenticated user.
     *
     * Provides a paginated, searchable grid view of income categories.
     * Supports PJAX for seamless updates without full page reload.
     *
     * ## Features
     * - Search by category name
     * - Sortable columns
     * - Configurable pagination
     * - PJAX support
     *
     * @return string The rendered index view
     */
    public function actionIndex(): string
    {
        $searchModel = new IncomeCategorySearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        // Ensure user can only see their own categories
        $dataProvider->query->andWhere(['workspace_id' => Yii::$app->workspace->getId()]);

        // Configure pagination
        $dataProvider->pagination->pageSize = Yii::$app->request->get('per-page', 10);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single IncomeCategory model.
     *
     * Supports both regular and AJAX requests. For AJAX requests,
     * renders the view partial for modal display.
     *
     * @param int $id The category ID
     * @return string|Response The rendered view or JSON response
     * @throws NotFoundHttpException if the model cannot be found or doesn't belong to user
     */
    public function actionView(int $id)
    {
        $model = $this->findModel($id);

        if (Yii::$app->request->isAjax) {
            return $this->renderAjax('view', [
                'model' => $model,
            ]);
        }

        return $this->render('view', [
            'model' => $model,
        ]);
    }

    /**
     * Creates a new IncomeCategory model.
     *
     * Handles both regular form submission and AJAX modal submission.
     * On successful creation via AJAX, returns JSON with success status.
     *
     * ## AJAX Response Format
     * ```json
     * {
     *     "success": true,
     *     "message": "Category created successfully",
     *     "id": 123
     * }
     * ```
     *
     * @return string|Response The rendered form or redirect/JSON response
     */
    public function actionCreate()
    {
        $model = new IncomeCategory();
        $model->user_id = Yii::$app->user->id;
        $model->status = IncomeCategory::STATUS_ACTIVE;

        if ($model->load(Yii::$app->request->post())) {
            if ($model->save()) {
                if (Yii::$app->request->isAjax) {
                    Yii::$app->response->format = Response::FORMAT_JSON;
                    return ApiResponse::success(
                        Yii::t('app', 'Income category created successfully.'),
                        ['id' => $model->id]
                    );
                }

                Yii::$app->session->setFlash('success', Yii::t('app', 'Income category created successfully.'));
                return $this->redirect(['index']);
            }

            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ApiResponse::error(Yii::t('app', 'Failed to create category.'), $model->errors);
            }
        }

        if (Yii::$app->request->isAjax) {
            return $this->renderAjax('_form', [
                'model' => $model,
            ]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing IncomeCategory model.
     *
     * Handles both regular form submission and AJAX modal submission.
     * Validates that the category belongs to the authenticated user.
     *
     * @param int $id The category ID
     * @return string|Response The rendered form or redirect/JSON response
     * @throws NotFoundHttpException if the model cannot be found or doesn't belong to user
     */
    public function actionUpdate(int $id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post())) {
            if ($model->save()) {
                if (Yii::$app->request->isAjax) {
                    Yii::$app->response->format = Response::FORMAT_JSON;
                    return ApiResponse::success(Yii::t('app', 'Income category updated successfully.'));
                }

                Yii::$app->session->setFlash('success', Yii::t('app', 'Income category updated successfully.'));
                return $this->redirect(['index']);
            }

            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ApiResponse::error(Yii::t('app', 'Failed to update category.'), $model->errors);
            }
        }

        if (Yii::$app->request->isAjax) {
            return $this->renderAjax('_form', [
                'model' => $model,
            ]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing IncomeCategory model.
     *
     * Performs soft delete by setting status to inactive if the category
     * has associated income records. Otherwise, performs hard delete.
     *
     * @param int $id The category ID
     * @return Response Redirect to index or JSON response for AJAX
     * @throws NotFoundHttpException if the model cannot be found or doesn't belong to user
     */
    public function actionDelete(int $id)
    {
        $model = $this->findModel($id);

        try {
            // Check if category has associated income records
            $hasIncomes = $model->getIncomes()->exists();

            if ($hasIncomes) {
                // Soft delete - set to inactive
                $model->status = IncomeCategory::STATUS_INACTIVE;
                $model->save(false, ['status']);
                $message = Yii::t('app', 'Category deactivated (has associated records).');
            } else {
                // Hard delete
                $model->delete();
                $message = Yii::t('app', 'Category deleted successfully.');
            }

            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ApiResponse::success($message);
            }

            Yii::$app->session->setFlash('success', $message);
        } catch (\Exception $e) {
            Yii::error('Failed to delete income category: ' . $e->getMessage(), __METHOD__);

            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ApiResponse::error(Yii::t('app', 'Failed to delete category. Please try again.'));
            }

            Yii::$app->session->setFlash('error', Yii::t('app', 'Failed to delete category.'));
        }

        return $this->redirect(['index']);
    }

    /**
     * Bulk delete multiple categories.
     *
     * Accepts an array of category IDs and deletes them.
     * Only deletes categories belonging to the authenticated user.
     *
     * @return Response JSON response with operation results
     */
    public function actionBulkDelete(): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $ids = Yii::$app->request->post('ids', []);

        if (empty($ids)) {
            return $this->asJson(ApiResponse::error(Yii::t('app', 'No categories selected.')));
        }

        $deleted = 0;
        $deactivated = 0;
        $failed = 0;

        foreach ($ids as $id) {
            try {
                $model = $this->findModel((int) $id);

                if ($model->getIncomes()->exists()) {
                    $model->status = IncomeCategory::STATUS_INACTIVE;
                    $model->save(false, ['status']);
                    $deactivated++;
                } else {
                    $model->delete();
                    $deleted++;
                }
            } catch (\Exception $e) {
                $failed++;
                Yii::error('Bulk delete failed for ID ' . $id . ': ' . $e->getMessage(), __METHOD__);
            }
        }

        $messages = [];
        if ($deleted > 0) {
            $messages[] = Yii::t('app', '{count} category(s) deleted.', ['count' => $deleted]);
        }
        if ($deactivated > 0) {
            $messages[] = Yii::t('app', '{count} category(s) deactivated.', ['count' => $deactivated]);
        }
        if ($failed > 0) {
            $messages[] = Yii::t('app', '{count} category(s) failed to process.', ['count' => $failed]);
        }

        $summary = implode(' ', $messages);
        $extra = ['deleted' => $deleted, 'deactivated' => $deactivated, 'failed' => $failed];
        $result = $failed === 0 ? ApiResponse::success($summary, $extra) : ApiResponse::error($summary, [], $extra);
        return $this->asJson($result);
    }

    /**
     * Exports income categories to Excel (XLSX) format.
     *
     * Generates a downloadable XLSX file with professional styling.
     * Columns: Category Name, Status, Records, Created At
     *
     * @return Response The XLSX file download response
     */
    public function actionExport(): Response
    {
        $searchModel = new IncomeCategorySearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams, true);

        // Restrict to authenticated user
        $dataProvider->query->andWhere(['workspace_id' => Yii::$app->workspace->getId()]);

        $categories = $dataProvider->getModels();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Income Categories');

        // Title block (rows 1–2)
        $sheet->setCellValue('A1', 'Income Category Report');
        $sheet->setCellValue('A2', 'Generated: ' . Yii::$app->formatter->asDatetime(time()));

        $sheet->mergeCells('A1:E1');
        $sheet->mergeCells('A2:E2');

        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['argb' => 'FF198754']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        ]);
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['italic' => true, 'size' => 10, 'color' => ['argb' => 'FF888888']],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(22);
        $sheet->getRowDimension(3)->setRowHeight(6); // spacer row

        // Column headers (row 4)
        $headerRow = 4;
        $sheet->fromArray(['Category Name', 'Description', 'Status', 'Records', 'Created At'], null, 'A' . $headerRow);

        $sheet->getStyle('A' . $headerRow . ':E' . $headerRow)->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FF198754']],
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 11],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF198754']],
            ],
        ]);
        $sheet->getStyle('D' . $headerRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension($headerRow)->setRowHeight(20);

        // Freeze pane below header
        $sheet->freezePane('A' . ($headerRow + 1));

        // Data rows
        $firstDataRow = $headerRow + 1;
        $row = $firstDataRow;

        foreach ($categories as $category) {
            $recordCount = $category->getIncomes()->count();
            $status      = $category->status ? 'Active' : 'Inactive';
            $createdAt   = Yii::$app->formatter->asDate($category->created_at, 'MMM d, yyyy');

            $name        = trim(str_replace(["\r\n", "\r", "\n"], ' ', strip_tags($category->name)));
            $description = trim(strip_tags($category->description ?? ''));

            $sheet->setCellValue('A' . $row, $name);
            $sheet->setCellValue('B' . $row, $description);
            $sheet->setCellValue('C' . $row, $status);
            $sheet->setCellValue('D' . $row, $recordCount);
            $sheet->setCellValue('E' . $row, $createdAt);

            // Zebra striping on even rows
            if (($row % 2) === 0) {
                $sheet->getStyle('A' . $row . ':E' . $row)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['argb' => 'FFEAFAF1']],
                ]);
            }

            $row++;
        }

        $lastDataRow = $row - 1;

        // Apply to all data rows: thin borders, top vertical align, no wrap
        $sheet->getStyle('A' . $firstDataRow . ':E' . $lastDataRow)->applyFromArray([
            'alignment' => [
                'vertical'   => Alignment::VERTICAL_TOP,
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'wrapText'   => false,
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD5D8DC']],
            ],
        ]);

        // Wrap text in Description column
        $sheet->getStyle('B' . $firstDataRow . ':B' . $lastDataRow)
            ->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);

        // Center-align Records column
        $sheet->getStyle('D' . $firstDataRow . ':D' . $lastDataRow)
            ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Column widths
        $sheet->getColumnDimension('A')->setAutoSize(true);        // Category Name
        $sheet->getColumnDimension('B')->setAutoSize(false)->setWidth(40); // Description (capped)
        $sheet->getColumnDimension('C')->setAutoSize(true);        // Status
        $sheet->getColumnDimension('D')->setAutoSize(true);        // Records
        $sheet->getColumnDimension('E')->setAutoSize(true);        // Created At

        // Output as XLSX
        $filename = 'income-categories-' . date('Y-m-d') . '.xlsx';

        Yii::$app->response->format = Response::FORMAT_RAW;
        Yii::$app->response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        Yii::$app->response->headers->set('Content-Disposition', 'attachment;filename="' . $filename . '"');
        Yii::$app->response->headers->set('Cache-Control', 'max-age=0');

        $writer = new Xlsx($spreadsheet);
        $tmpFile = tempnam(sys_get_temp_dir(), 'income_cat_export_');
        $writer->save($tmpFile);
        $content = file_get_contents($tmpFile);
        unlink($tmpFile);

        Yii::$app->response->content = $content;

        return Yii::$app->response;
    }

    /**
     * Finds the IncomeCategory model based on its primary key value.
     *
     * Ensures the category belongs to the authenticated user.
     *
     * @param int $id The category ID
     * @return IncomeCategory The loaded model
     * @throws NotFoundHttpException if the model cannot be found or doesn't belong to user
     */
    protected function findModel(int $id): IncomeCategory
    {
        $model = IncomeCategory::find()
            ->where([
                'id' => $id,
                'workspace_id' => Yii::$app->workspace->getId(),
            ])
            ->one();

        if ($model === null) {
            throw new NotFoundHttpException(Yii::t('app', 'The requested category does not exist.'));
        }

        return $model;
    }
}
