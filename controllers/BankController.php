<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 - 2026 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace app\controllers;

use Yii;
use app\components\ApiResponse;
use app\models\Bank;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * BankController manages the workspace's list of banks and backs the inline
 * "add bank" quick action on the expense form.
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.2.0
 */
class BankController extends Controller
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
                'only' => ['create', 'delete'],
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
                    'create' => ['POST'],
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lists the workspace's banks.
     *
     * @return string
     */
    public function actionIndex(): string
    {
        $dataProvider = new ActiveDataProvider([
            'query' => Bank::find()
                ->where(['workspace_id' => Yii::$app->workspace->getId()])
                ->orderBy(['name' => SORT_ASC]),
            'pagination' => ['pageSize' => 50],
        ]);

        return $this->render('index', ['dataProvider' => $dataProvider]);
    }

    /**
     * Creates a bank (AJAX/JSON). Backs the inline "add bank" on the expense
     * form and the management page.
     *
     * @return array
     */
    public function actionCreate(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $bank = new Bank();
        $bank->name = (string) Yii::$app->request->post('name', '');

        if ($bank->save()) {
            return ApiResponse::success(
                Yii::t('app', 'Bank added successfully.'),
                ['id' => $bank->id, 'name' => $bank->name]
            );
        }

        return ApiResponse::error(Yii::t('app', 'Failed to add bank.'), $bank->errors);
    }

    /**
     * Deletes a bank owned by the current workspace.
     *
     * @param int $id
     * @return array
     */
    public function actionDelete(int $id): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $bank = $this->findModel($id);
        if ($bank->delete()) {
            return ApiResponse::success(Yii::t('app', 'Bank deleted successfully.'));
        }

        return ApiResponse::error(Yii::t('app', 'Failed to delete bank.'));
    }

    /**
     * Finds a bank scoped to the active workspace.
     *
     * @param int $id
     * @return Bank
     * @throws NotFoundHttpException
     */
    protected function findModel(int $id): Bank
    {
        $bank = Bank::findOne(['id' => $id, 'workspace_id' => Yii::$app->workspace->getId()]);
        if ($bank === null) {
            throw new NotFoundHttpException(Yii::t('app', 'The requested bank does not exist.'));
        }

        return $bank;
    }
}
