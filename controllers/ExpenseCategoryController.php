<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace app\controllers;

use Yii;
use app\components\ApiResponse;
use app\models\ExpenseCategory;
use app\models\ExpenseCategorySearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;

/**
 * ExpenseCategoryController implements the CRUD actions for ExpenseCategory model.
 *
 * This controller handles atatusll expense category management operations including:
 * - Hierarchical tree view of categories
 * - Creating new categories (root or child)
 * - Viewing category details with children
 * - Updating existing categories
 * - Moving categories to different parents
 * - Deleting categories with children handling
 * - Bulk operations
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */
class ExpenseCategoryController extends Controller
{
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
                    'delete' => ['POST'],
                    'bulk-delete' => ['POST'],
                    'move' => ['POST'],
                    'toggle-status' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lists all ExpenseCategory models with tree view support.
     *
     * @return string
     */
    public function actionIndex(): string
    {
        $searchModel = new ExpenseCategorySearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        // Ensure user can only see their own categories
        $dataProvider->query->andWhere(['user_id' => Yii::$app->user->id]);

        // Get tree data for tree view
        $treeData = ExpenseCategory::getJsTreeData(Yii::$app->user->id, false);

        // Get statistics
        $stats = $this->getCategoryStats();

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'treeData' => $treeData,
            'stats' => $stats,
        ]);
    }

    /**
     * Displays a single ExpenseCategory model.
     *
     * @param int $id
     * @return string|Response
     * @throws NotFoundHttpException
     */
    public function actionView(int $id)
    {
        $model = $this->findModel($id);

        // Get children
        $children = $model->getChildren()->all();

        // Get expense statistics
        $expenseCount = $model->getExpenses()->count();
        $totalExpense = $model->getTotalExpense();

        if (Yii::$app->request->isAjax) {
            return $this->renderAjax('view', [
                'model' => $model,
                'children' => $children,
                'expenseCount' => $expenseCount,
                'totalExpense' => $totalExpense,
            ]);
        }

        return $this->render('view', [
            'model' => $model,
            'children' => $children,
            'expenseCount' => $expenseCount,
            'totalExpense' => $totalExpense,
        ]);
    }

    /**
     * Creates a new ExpenseCategory model.
     *
     * @param int|null $parent Parent category ID for creating subcategory
     * @return string|Response
     */
    public function actionCreate(?int $parent = null)
    {
        $model = new ExpenseCategory();
        $model->user_id = Yii::$app->user->id;
        $model->status = ExpenseCategory::STATUS_ACTIVE;

        // Set parent if provided
        if ($parent !== null) {
            $parentModel = $this->findModel($parent);
            $model->parent_id = $parent;
            // Inherit color from parent by default
            $model->color = $parentModel->color;
        }

        if ($model->load(Yii::$app->request->post())) {
            if ($model->save()) {
                if (Yii::$app->request->isAjax) {
                    Yii::$app->response->format = Response::FORMAT_JSON;
                    return ApiResponse::success(Yii::t('app', 'Category created successfully.'), [
                        'id' => $model->id,
                        'category' => [
                            'id' => $model->id,
                            'name' => $model->name,
                            'parent_id' => $model->parent_id,
                            'icon' => $model->icon,
                            'color' => $model->color,
                        ],
                    ]);
                }

                Yii::$app->session->setFlash('success', Yii::t('app', 'Category created successfully.'));
                return $this->redirect(['index']);
            }

            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ApiResponse::error(Yii::t('app', 'Failed to create category.'), $model->errors);
            }
        }

        // Get parent options for dropdown
        $parentOptions = ExpenseCategory::getDropdownList(Yii::$app->user->id);

        if (Yii::$app->request->isAjax) {
            return $this->renderAjax('_form', [
                'model' => $model,
                'parentOptions' => $parentOptions,
            ]);
        }

        return $this->render('create', [
            'model' => $model,
            'parentOptions' => $parentOptions,
        ]);
    }

    /**
     * Updates an existing ExpenseCategory model.
     *
     * @param int $id
     * @return string|Response
     * @throws NotFoundHttpException
     */
    public function actionUpdate(int $id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post())) {
            if ($model->save()) {
                if (Yii::$app->request->isAjax) {
                    Yii::$app->response->format = Response::FORMAT_JSON;
                    return ApiResponse::success(Yii::t('app', 'Category updated successfully.'), [
                        'category' => [
                            'id' => $model->id,
                            'name' => $model->name,
                            'parent_id' => $model->parent_id,
                            'icon' => $model->icon,
                            'color' => $model->color,
                        ],
                    ]);
                }

                Yii::$app->session->setFlash('success', Yii::t('app', 'Category updated successfully.'));
                return $this->redirect(['index']);
            }

            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ApiResponse::error(Yii::t('app', 'Failed to update category.'), $model->errors);
            }
        }

        // Get parent options, excluding this category and its descendants
        $parentOptions = ExpenseCategory::getDropdownList(Yii::$app->user->id, $model->id);

        if (Yii::$app->request->isAjax) {
            return $this->renderAjax('_form', [
                'model' => $model,
                'parentOptions' => $parentOptions,
            ]);
        }

        return $this->render('update', [
            'model' => $model,
            'parentOptions' => $parentOptions,
        ]);
    }

    /**
     * Deletes an existing ExpenseCategory model.
     *
     * @param int $id
     * @return Response
     * @throws NotFoundHttpException
     */
    public function actionDelete(int $id): Response
    {
        $model = $this->findModel($id);
        $deleteChildren = Yii::$app->request->post('deleteChildren', false);

        try {
            // Check if category has expenses
            $hasExpenses = $model->getExpenses()->exists();
            $hasChildrenWithExpenses = false;

            foreach ($model->children as $child) {
                if (!$child->canDelete()) {
                    $hasChildrenWithExpenses = true;
                    break;
                }
            }

            if ($hasExpenses || $hasChildrenWithExpenses) {
                // Soft delete - deactivate instead
                $model->status = ExpenseCategory::STATUS_INACTIVE;
                $model->save(false, ['status', 'updated_at', 'updated_by']);

                // Deactivate children if requested
                if ($deleteChildren) {
                    $this->deactivateChildren($model);
                }

                $message = Yii::t('app', 'Category deactivated (has associated expenses).');
            } else {
                // Hard delete
                $model->deleteWithChildren($deleteChildren);
                $message = Yii::t('app', 'Category deleted successfully.');
            }

            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return $this->asJson(ApiResponse::success($message));
            }

            Yii::$app->session->setFlash('success', $message);
        } catch (\Exception $e) {
            Yii::error('Failed to delete expense category: ' . $e->getMessage(), __METHOD__);

            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return $this->asJson(ApiResponse::error(Yii::t('app', 'Failed to delete category.')));
            }

            Yii::$app->session->setFlash('error', Yii::t('app', 'Failed to delete category.'));
        }

        return $this->redirect(['index']);
    }

    /**
     * Moves a category to a new parent.
     *
     * @return Response
     */
    public function actionMove(): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $id = Yii::$app->request->post('id');
        $newParentId = Yii::$app->request->post('parent_id');

        if (empty($id)) {
            return $this->asJson(ApiResponse::error(Yii::t('app', 'Invalid category ID.')));
        }

        try {
            $model = $this->findModel((int) $id);

            // Convert empty string to null for root level
            $newParentId = $newParentId === '' || $newParentId === '#' ? null : (int) $newParentId;

            // Validate new parent belongs to user
            if ($newParentId !== null) {
                $this->findModel($newParentId);
            }

            if ($model->moveTo($newParentId)) {
                return $this->asJson(ApiResponse::success(Yii::t('app', 'Category moved successfully.')));
            }

            return $this->asJson(ApiResponse::error(Yii::t('app', 'Failed to move category.'), $model->errors));
        } catch (\Exception $e) {
            return $this->asJson(ApiResponse::error($e->getMessage()));
        }
    }

    /**
     * Toggle category status (active/inactive).
     *
     * @param int $id
     * @return Response
     * @throws NotFoundHttpException
     */
    public function actionToggleStatus(int $id): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $model = $this->findModel($id);
        $model->status = $model->status === ExpenseCategory::STATUS_ACTIVE
            ? ExpenseCategory::STATUS_INACTIVE
            : ExpenseCategory::STATUS_ACTIVE;

        if ($model->save(false, ['status', 'updated_at', 'updated_by'])) {
            return $this->asJson(ApiResponse::success(Yii::t('app', 'Status updated successfully.'), [
                'status' => $model->status,
                'statusLabel' => $model->getStatusLabel(),
            ]));
        }

        return $this->asJson(ApiResponse::error(Yii::t('app', 'Failed to update status.')));
    }

    /**
     * Bulk delete multiple categories.
     *
     * @return Response
     */
    public function actionBulkDelete(): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $ids = Yii::$app->request->post('ids', []);
        $deleteChildren = Yii::$app->request->post('deleteChildren', false);

        if (empty($ids)) {
            return $this->asJson(ApiResponse::error(Yii::t('app', 'No categories selected.')));
        }

        $deleted = 0;
        $deactivated = 0;
        $failed = 0;

        foreach ($ids as $id) {
            try {
                $model = $this->findModel((int) $id);

                if (!$model->canDelete()) {
                    $model->status = ExpenseCategory::STATUS_INACTIVE;
                    $model->save(false, ['status', 'updated_at', 'updated_by']);
                    $deactivated++;
                } else {
                    $model->deleteWithChildren($deleteChildren);
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
            $messages[] = Yii::t('app', '{count} category(s) failed.', ['count' => $failed]);
        }

        $summary = implode(' ', $messages);
        $response = $failed === 0 ? ApiResponse::success($summary) : ApiResponse::error($summary);
        return $this->asJson($response);
    }

    /**
     * Returns tree data as JSON for AJAX requests.
     *
     * @return Response
     */
    public function actionTreeData(): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $treeData = ExpenseCategory::getJsTreeData(Yii::$app->user->id, false);

        return $this->asJson($treeData);
    }

    /**
     * Returns children of a category as JSON.
     *
     * @param int $id Parent category ID
     * @return Response
     * @throws NotFoundHttpException
     */
    public function actionChildren(int $id): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $model = $this->findModel($id);
        $children = [];

        foreach ($model->children as $child) {
            $children[] = [
                'id' => $child->id,
                'name' => $child->name,
                'icon' => $child->icon,
                'color' => $child->color,
                'status' => $child->status,
                'hasChildren' => $child->hasChildren(),
            ];
        }

        return $this->asJson($children);
    }

    /**
     * Exports expense categories to CSV.
     *
     * @return Response
     */
    public function actionExport(): Response
    {
        $categories = ExpenseCategory::find()
            ->where(['user_id' => Yii::$app->user->id])
            ->orderBy(['parent_id' => SORT_ASC, 'name' => SORT_ASC])
            ->all();

        $csv = "ID,Parent ID,Name,Full Path,Description,Icon,Color,Status,Created At\n";

        foreach ($categories as $category) {
            $csv .= sprintf(
                "%d,%s,\"%s\",\"%s\",\"%s\",%s,%s,%s,%s\n",
                $category->id,
                $category->parent_id ?? '',
                str_replace('"', '""', $category->name),
                str_replace('"', '""', $category->getFullPath()),
                str_replace('"', '""', $category->description ?? ''),
                $category->icon ?? '',
                $category->color ?? '',
                $category->status ? 'Active' : 'Inactive',
                Yii::$app->formatter->asDatetime($category->created_at)
            );
        }

        $filename = 'expense-categories-' . date('Y-m-d') . '.csv';

        return Yii::$app->response->sendContentAsFile(
            $csv,
            $filename,
            ['mimeType' => 'text/csv']
        );
    }

    /**
     * Get category statistics for dashboard.
     *
     * @return array
     */
    protected function getCategoryStats(): array
    {
        $userId = Yii::$app->user->id;

        $total = ExpenseCategory::find()
            ->where(['user_id' => $userId])
            ->count();

        $active = ExpenseCategory::find()
            ->where(['user_id' => $userId, 'status' => ExpenseCategory::STATUS_ACTIVE])
            ->count();

        $rootCount = ExpenseCategory::find()
            ->where(['user_id' => $userId, 'parent_id' => null])
            ->count();

        $maxDepth = 0;
        $roots = ExpenseCategory::getRootCategories($userId, false);
        foreach ($roots as $root) {
            $depth = $this->calculateMaxDepth($root);
            $maxDepth = max($maxDepth, $depth);
        }

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $total - $active,
            'rootCount' => $rootCount,
            'maxDepth' => $maxDepth,
        ];
    }

    /**
     * Calculate maximum depth from a category.
     *
     * @param ExpenseCategory $category
     * @param int $currentDepth
     * @return int
     */
    protected function calculateMaxDepth(ExpenseCategory $category, int $currentDepth = 0): int
    {
        $maxDepth = $currentDepth;

        foreach ($category->children as $child) {
            $childDepth = $this->calculateMaxDepth($child, $currentDepth + 1);
            $maxDepth = max($maxDepth, $childDepth);
        }

        return $maxDepth;
    }

    /**
     * Recursively deactivate children.
     *
     * @param ExpenseCategory $parent
     */
    protected function deactivateChildren(ExpenseCategory $parent): void
    {
        foreach ($parent->children as $child) {
            $child->status = ExpenseCategory::STATUS_INACTIVE;
            $child->save(false, ['status', 'updated_at', 'updated_by']);
            $this->deactivateChildren($child);
        }
    }

    /**
     * Finds the ExpenseCategory model based on its primary key value.
     *
     * @param int $id
     * @return ExpenseCategory
     * @throws NotFoundHttpException
     */
    protected function findModel(int $id): ExpenseCategory
    {
        $model = ExpenseCategory::find()
            ->where([
                'id' => $id,
                'user_id' => Yii::$app->user->id,
            ])
            ->one();

        if ($model === null) {
            throw new NotFoundHttpException(Yii::t('app', 'The requested category does not exist.'));
        }

        return $model;
    }
}
