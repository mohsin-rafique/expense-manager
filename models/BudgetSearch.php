<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 - 2026 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * BudgetSearch represents the model behind the search form of `app\models\Budget`.
 *
 * Provides filtering and sorting for the budgets list, scoped to the current
 * user, including filters by category type, period type, and status.
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */
class BudgetSearch extends Budget
{
    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['id', 'user_id', 'category_id', 'alert_threshold'], 'integer'],
            [['category_type', 'period_type', 'status'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios(): array
    {
        // Bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied.
     *
     * @param array $params Search parameters from request
     * @return ActiveDataProvider
     */
    public function search(array $params): ActiveDataProvider
    {
        $query = Budget::find()->where(['workspace_id' => Yii::$app->workspace->getId()]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => [
                    'category_type' => SORT_ASC,
                    'period_type' => SORT_ASC,
                ],
                'attributes' => [
                    'category_type',
                    'period_type',
                    'amount',
                    'status',
                    'created_at',
                ],
            ],
            'pagination' => [
                'pageSize' => Yii::$app->request->get('per-page', 50),
                'pageSizeParam' => 'per-page',
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'category_type' => $this->category_type,
            'period_type' => $this->period_type,
            'status' => $this->status === '' ? null : $this->status,
        ]);

        return $dataProvider;
    }

    /**
     * Category type options for the filter dropdown (with "All" prompt).
     *
     * @return array
     */
    public static function getCategoryTypeFilterOptions(): array
    {
        return ['' => Yii::t('app', 'All Types')] + Budget::getCategoryTypeOptions();
    }

    /**
     * Period type options for the filter dropdown (with "All" prompt).
     *
     * @return array
     */
    public static function getPeriodTypeFilterOptions(): array
    {
        return ['' => Yii::t('app', 'All Periods')] + Budget::getPeriodTypeOptions();
    }

    /**
     * Status options for the filter dropdown (with "All" prompt).
     *
     * @return array
     */
    public static function getStatusFilterOptions(): array
    {
        return [
            '' => Yii::t('app', 'All Status'),
            self::STATUS_ACTIVE => Yii::t('app', 'Active'),
            self::STATUS_INACTIVE => Yii::t('app', 'Inactive'),
        ];
    }
}
