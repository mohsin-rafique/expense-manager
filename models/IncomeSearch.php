<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * IncomeSearch represents the model behind the search form of `app\models\Income`.
 *
 * Provides comprehensive search and filtering capabilities for income records
 * including date range filtering, category filtering, and text search.
 *
 * @property string|null $start_date Start date for date range filter
 * @property string|null $end_date End date for date range filter
 * @property string|null $s Global search term
 * @property int|null $pageSize Custom page size
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */
class IncomeSearch extends Income
{
    /**
     * @var string|null Start date for filtering
     */
    public $start_date;

    /**
     * @var string|null End date for filtering
     */
    public $end_date;

    /**
     * @var string|null Global search term
     */
    public $s;

    /**
     * @var int|null Custom page size
     */
    public $pageSize;

    /**
     * @var string|null Amount range minimum
     */
    public $amount_min;

    /**
     * @var string|null Amount range maximum
     */
    public $amount_max;

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            // Integer fields
            [['id', 'user_id', 'income_category_id', 'created_at', 'updated_at', 'created_by', 'updated_by', 'pageSize'], 'integer'],

            // Date validation
            [['start_date', 'end_date'], 'date', 'format' => 'php:Y-m-d', 'message' => Yii::t('app', 'Invalid date format.')],

            // Safe attributes
            [['entry_date', 'reference', 'description', 'amount', 's', 'amount_min', 'amount_max'], 'safe'],

            // Numeric validation for amount range
            [['amount_min', 'amount_max'], 'number'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios(): array
    {
        return Model::scenarios();
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return array_merge(parent::attributeLabels(), [
            'start_date' => Yii::t('app', 'From Date'),
            'end_date' => Yii::t('app', 'To Date'),
            's' => Yii::t('app', 'Search'),
            'amount_min' => Yii::t('app', 'Min Amount'),
            'amount_max' => Yii::t('app', 'Max Amount'),
        ]);
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params Search parameters
     * @param bool $isExport Whether this is for export (disables pagination)
     * @return ActiveDataProvider
     */
    public function search(array $params, bool $isExport = false): ActiveDataProvider
    {
        $query = Income::find()
            ->alias('i')
            ->joinWith(['incomeCategory ic']);

        // Default pagination settings
        $defaultPageSize = Yii::$app->params['defaultPageSize'] ?? 20;

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $defaultPageSize,
                'pageSizeParam' => 'per-page',
            ],
            'sort' => [
                'defaultOrder' => ['entry_date' => SORT_DESC],
                'attributes' => [
                    'entry_date',
                    'amount',
                    'reference',
                    'created_at',
                    'income_category_id' => [
                        'asc' => ['ic.name' => SORT_ASC],
                        'desc' => ['ic.name' => SORT_DESC],
                    ],
                ],
            ],
        ]);

        // Handle export mode
        if ($isExport) {
            $dataProvider->pagination = false;
            $this->loadExportParams($params);
        } else {
            $this->load($params);
        }

        // Handle custom page size
        if (!$isExport) {
            if (isset($params['per-page'])) {
                $dataProvider->pagination->pageSize = (int) $params['per-page'];
            } elseif (!empty($this->pageSize)) {
                $dataProvider->pagination->pageSize = (int) $this->pageSize;
            }
        }

        if (!$this->validate()) {
            return $dataProvider;
        }

        // Always filter by current user
        $query->andWhere(['i.user_id' => Yii::$app->user->id]);

        // Apply filters
        $this->applyFilters($query);

        return $dataProvider;
    }

    /**
     * Load export-specific parameters
     *
     * @param array $params Parameters from request
     */
    protected function loadExportParams(array $params): void
    {
        $this->income_category_id = $params['income_category_id'] ?? null;
        $this->start_date = $params['start_date'] ?? null;
        $this->end_date = $params['end_date'] ?? null;
        $this->reference = $params['reference'] ?? null;
        $this->description = $params['description'] ?? null;
        $this->amount_min = $params['amount_min'] ?? null;
        $this->amount_max = $params['amount_max'] ?? null;
        $this->s = $params['s'] ?? null;
    }

    /**
     * Apply all search filters to query
     *
     * @param \yii\db\ActiveQuery $query Query to modify
     */
    protected function applyFilters($query): void
    {
        // Exact match filters
        $query->andFilterWhere([
            'i.id' => $this->id,
            'i.income_category_id' => $this->income_category_id,
            'i.created_by' => $this->created_by,
            'i.updated_by' => $this->updated_by,
        ]);

        // Specific date filter
        if (!empty($this->entry_date)) {
            $query->andWhere(['i.entry_date' => $this->entry_date]);
        }

        // Date range filter
        if (!empty($this->start_date) && !empty($this->end_date)) {
            $query->andWhere(['between', 'i.entry_date', $this->start_date, $this->end_date]);
        } elseif (!empty($this->start_date)) {
            $query->andWhere(['>=', 'i.entry_date', $this->start_date]);
        } elseif (!empty($this->end_date)) {
            $query->andWhere(['<=', 'i.entry_date', $this->end_date]);
        }

        // Amount range filter
        if (!empty($this->amount_min)) {
            $query->andWhere(['>=', 'i.amount', $this->amount_min]);
        }
        if (!empty($this->amount_max)) {
            $query->andWhere(['<=', 'i.amount', $this->amount_max]);
        }

        // Text filters (LIKE)
        $query->andFilterWhere(['like', 'i.reference', $this->reference]);
        $query->andFilterWhere(['like', 'i.description', $this->description]);

        // Exact amount filter
        if (!empty($this->amount) && empty($this->amount_min) && empty($this->amount_max)) {
            $query->andFilterWhere(['like', 'i.amount', $this->amount]);
        }

        // Global search
        if (!empty($this->s)) {
            $query->andWhere([
                'or',
                ['like', 'i.reference', $this->s],
                ['like', 'i.description', $this->s],
                ['like', 'ic.name', $this->s],
            ]);
        }
    }

    /**
     * Get summary statistics for the current search
     *
     * @param ActiveDataProvider $dataProvider Data provider with applied filters
     * @return array Statistics [total_amount, count, average]
     */
    public function getStatistics(ActiveDataProvider $dataProvider): array
    {
        $query = clone $dataProvider->query;

        // Remove pagination and sorting for aggregate query
        $query->limit(null)->offset(null)->orderBy([]);

        $total = (float) ($query->sum('i.amount') ?? 0);
        $count = (int) $query->count();
        $average = $count > 0 ? $total / $count : 0;

        return [
            'total_amount' => $total,
            'count' => $count,
            'average' => $average,
        ];
    }

    /**
     * Get monthly income data for charts
     *
     * @param int $months Number of months to retrieve
     * @return array [month => amount]
     */
    public function getMonthlyData(int $months = 12): array
    {
        $endDate = date('Y-m-t');
        $startDate = date('Y-m-01', strtotime("-{$months} months"));

        $query = Income::find()
            ->select([
                'DATE_FORMAT(entry_date, "%Y-%m") as month',
                'SUM(amount) as total',
            ])
            ->where(['user_id' => Yii::$app->user->id])
            ->andWhere(['between', 'entry_date', $startDate, $endDate])
            ->groupBy(['month'])
            ->orderBy(['month' => SORT_ASC])
            ->asArray()
            ->all();

        return array_column($query, 'total', 'month');
    }

    /**
     * Get income by category for the current filter
     *
     * @return array [category_name => total]
     */
    public function getCategoryBreakdown(): array
    {
        $query = Income::find()
            ->alias('i')
            ->select(['ic.name', 'SUM(i.amount) as total'])
            ->joinWith(['incomeCategory ic'])
            ->where(['i.user_id' => Yii::$app->user->id])
            ->groupBy(['i.income_category_id', 'ic.name'])
            ->orderBy(['total' => SORT_DESC]);

        // Apply date filters if set
        if (!empty($this->start_date)) {
            $query->andWhere(['>=', 'i.entry_date', $this->start_date]);
        }
        if (!empty($this->end_date)) {
            $query->andWhere(['<=', 'i.entry_date', $this->end_date]);
        }

        $results = $query->asArray()->all();

        return array_column($results, 'total', 'name');
    }

    /**
     * Quick filter presets
     *
     * @param string $preset Preset name (today, this_week, this_month, this_year)
     * @return self
     */
    public function applyPreset(string $preset): self
    {
        switch ($preset) {
            case 'today':
                $this->start_date = date('Y-m-d');
                $this->end_date = date('Y-m-d');
                break;

            case 'this_week':
                $this->start_date = date('Y-m-d', strtotime('monday this week'));
                $this->end_date = date('Y-m-d', strtotime('sunday this week'));
                break;

            case 'this_month':
                $this->start_date = date('Y-m-01');
                $this->end_date = date('Y-m-t');
                break;

            case 'last_month':
                $this->start_date = date('Y-m-01', strtotime('first day of last month'));
                $this->end_date = date('Y-m-t', strtotime('last day of last month'));
                break;

            case 'this_year':
                $this->start_date = date('Y-01-01');
                $this->end_date = date('Y-12-31');
                break;

            case 'last_30_days':
                $this->start_date = date('Y-m-d', strtotime('-30 days'));
                $this->end_date = date('Y-m-d');
                break;
        }

        return $this;
    }
}
