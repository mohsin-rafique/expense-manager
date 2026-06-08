<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace app\controllers;

use Yii;
use app\components\ApiResponse;
use app\models\Budget;
use app\models\BudgetSearch;
use app\models\ExpenseCategory;
use app\models\IncomeCategory;
use app\services\BudgetService;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;

/**
 * BudgetController implements the CRUD actions for the Budget model.
 *
 * Handles per-category budget management with monthly/yearly/fiscal periods,
 * progress tracking, and alert thresholds. All create/update/view operations
 * are AJAX-modal friendly and return standardized {@see ApiResponse} envelopes.
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */
class BudgetController extends Controller
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
                    'toggle-status' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lists all budgets for the current user with progress and summary stats.
     *
     * @return string
     */
    public function actionIndex(): string
    {
        $searchModel = new BudgetSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        $stats = (new BudgetService())->getSummary(Yii::$app->workspace->getId());

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'stats' => $stats,
        ]);
    }

    /**
     * Displays a single Budget in a modal.
     *
     * @param int $id
     * @return string|Response
     * @throws NotFoundHttpException
     */
    public function actionView(int $id)
    {
        $model = $this->findModel($id);

        if (Yii::$app->request->isAjax) {
            return $this->renderAjax('view', ['model' => $model]);
        }

        return $this->render('view', ['model' => $model]);
    }

    /**
     * Creates a new Budget.
     *
     * @return string|Response
     */
    public function actionCreate()
    {
        $model = new Budget();
        $model->user_id = Yii::$app->user->id;
        $model->category_type = Budget::TYPE_EXPENSE;
        $model->period_type = Budget::PERIOD_MONTHLY;
        $model->alert_threshold = 80;
        $model->status = Budget::STATUS_ACTIVE;

        if (Yii::$app->request->isAjax && $model->load(Yii::$app->request->post())) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $model->user_id = Yii::$app->user->id;

            if ($model->save()) {
                return ApiResponse::success(Yii::t('app', 'Budget created successfully.'), ['id' => $model->id]);
            }

            return ApiResponse::error(Yii::t('app', 'Failed to save budget.'), $model->errors);
        }

        return $this->renderAjax('_form', [
            'model' => $model,
            'categoryOptions' => $this->categoryOptions($model->category_type),
        ]);
    }

    /**
     * Updates an existing Budget.
     *
     * @param int $id
     * @return string|Response
     * @throws NotFoundHttpException
     */
    public function actionUpdate(int $id)
    {
        $model = $this->findModel($id);

        if (Yii::$app->request->isAjax && $model->load(Yii::$app->request->post())) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $model->user_id = Yii::$app->user->id;

            if ($model->save()) {
                return ApiResponse::success(Yii::t('app', 'Budget updated successfully.'), ['id' => $model->id]);
            }

            return ApiResponse::error(Yii::t('app', 'Failed to save budget.'), $model->errors);
        }

        return $this->renderAjax('_form', [
            'model' => $model,
            'categoryOptions' => $this->categoryOptions($model->category_type),
        ]);
    }

    /**
     * Deletes a Budget.
     *
     * @param int $id
     * @return Response
     * @throws NotFoundHttpException
     */
    public function actionDelete(int $id): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $model = $this->findModel($id);

        if ($model->delete()) {
            return $this->asJson(ApiResponse::success(Yii::t('app', 'Budget deleted successfully.')));
        }

        return $this->asJson(ApiResponse::error(Yii::t('app', 'Failed to delete budget.')));
    }

    /**
     * Toggles a budget's active/inactive status.
     *
     * @param int $id
     * @return Response
     * @throws NotFoundHttpException
     */
    public function actionToggleStatus(int $id): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $model = $this->findModel($id);
        $model->status = $model->status ? Budget::STATUS_INACTIVE : Budget::STATUS_ACTIVE;

        if ($model->save(false, ['status', 'updated_at', 'updated_by'])) {
            return $this->asJson(ApiResponse::success(Yii::t('app', 'Status updated successfully.'), [
                'status' => (int) $model->status,
            ]));
        }

        return $this->asJson(ApiResponse::error(Yii::t('app', 'Failed to update status.')));
    }

    /**
     * Returns the category dropdown options for a given category type as JSON.
     *
     * Used by the budget form to refresh the category list when the user
     * switches between expense and income.
     *
     * @param string $type 'expense' | 'income'
     * @return Response
     */
    public function actionCategories(string $type): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        return $this->asJson($this->categoryOptions($type));
    }

    /**
     * Builds the category dropdown options for the given type, scoped to the
     * current user (active categories only).
     *
     * @param string $type
     * @return array [id => name]
     */
    protected function categoryOptions(string $type): array
    {
        $userId = Yii::$app->workspace->getId();

        if ($type === Budget::TYPE_INCOME) {
            return IncomeCategory::getIncomeCategory(true, $userId);
        }

        // Hierarchical, indented list for expense categories
        return ExpenseCategory::getDropdownList($userId);
    }

    /**
     * Finds the Budget model scoped to the current user.
     *
     * @param int $id
     * @return Budget
     * @throws NotFoundHttpException
     */
    protected function findModel(int $id): Budget
    {
        $model = Budget::find()
            ->where(['id' => $id, 'workspace_id' => Yii::$app->workspace->getId()])
            ->one();

        if ($model === null) {
            throw new NotFoundHttpException(Yii::t('app', 'The requested budget does not exist.'));
        }

        return $model;
    }
}
