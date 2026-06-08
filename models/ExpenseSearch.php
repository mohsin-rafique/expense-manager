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
 * ExpenseSearch - Search model for filtering and sorting expenses
 *
 * This model provides comprehensive search functionality including:
 * - Date range filtering
 * - Category filtering (including child categories)
 * - Payment method filtering
 * - Full-text search across multiple fields
 * - Export mode support
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */
class ExpenseSearch extends Expense
{
    /**
     * @var int Page size for pagination
     */
    public $pageSize = 20;

    /**
     * @var string Global search term
     */
    public $s;

    /**
     * @var string Start date for date range filter
     */
    public $start_date;

    /**
     * @var string End date for date range filter
     */
    public $end_date;

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            // Integer fields
            [['id', 'user_id', 'expense_category_id', 'created_at', 'updated_at', 'created_by', 'updated_by'], 'integer'],

            // Date range validation
            [['start_date', 'end_date'], 'date', 'format' => 'php:Y-m-d', 'message' => Yii::t('app', 'Invalid date format!')],

            // Safe attributes for filtering
            [['expense_date', 'payment_method', 'reference', 'description', 'amount', 'filename', 'filepath', 'start_date', 'end_date', 's', 'pageSize'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params Search parameters
     * @param bool $isExport Whether this is for export (disables pagination)
     * @return ActiveDataProvider
     */
    public function search($params, $isExport = false)
    {
        $query = Expense::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => Yii::$app->params['defaultPageSize'] ?? 20,
            ],
            'sort' => [
                'defaultOrder' => ['expense_date' => SORT_DESC],
                'attributes' => [
                    'id',
                    'expense_date',
                    'amount',
                    'payment_method',
                    'reference',
                    'created_at',
                    'updated_at',
                    'description' => [
                        'asc'  => ['{{%expenses}}.description' => SORT_ASC],
                        'desc' => ['{{%expenses}}.description' => SORT_DESC],
                    ],
                    'expense_category_id' => [
                        'asc'  => ['{{%expense_categories}}.name' => SORT_ASC],
                        'desc' => ['{{%expense_categories}}.name' => SORT_DESC],
                    ],
                ],
            ],
        ]);

        // Load attributes based on mode
        if ($isExport) {
            $this->loadExportParams($params);
        } else {
            $this->load($params);
        }

        // Apply custom page size if provided
        if (isset($params['ExpenseSearch']['pageSize']) && !$isExport) {
            $dataProvider->pagination->pageSize = (int)$params['ExpenseSearch']['pageSize'];
        }

        // Disable pagination for exports
        if ($isExport) {
            $dataProvider->pagination = false;
        }

        // Skip validation for export — required fields from parent Expense model
        // would cause validate() to fail, returning all records unfiltered.
        if (!$isExport && !$this->validate()) {
            return $dataProvider;
        }

        // Join with category table for sorting
        $query->joinWith('expenseCategory');

        // Apply filters
        $this->applyFilters($query);

        return $dataProvider;
    }

    /**
     * Load parameters for export mode
     *
     * @param array $params
     */
    protected function loadExportParams($params)
    {
        $data = $params['ExpenseSearch'] ?? [];

        // Only overwrite a property if the key is explicitly present in params.
        // This preserves defaults set by the controller (e.g. current-month dates).
        if (array_key_exists('expense_category_id', $data)) {
            $this->expense_category_id = $data['expense_category_id'];
        }
        if (array_key_exists('start_date', $data)) {
            $this->start_date = $data['start_date'];
        }
        if (array_key_exists('end_date', $data)) {
            $this->end_date = $data['end_date'];
        }
        if (array_key_exists('reference', $data)) {
            $this->reference = $data['reference'];
        }
        if (array_key_exists('description', $data)) {
            $this->description = $data['description'];
        }
        if (array_key_exists('payment_method', $data)) {
            $this->payment_method = $data['payment_method'];
        }
        if (array_key_exists('amount', $data)) {
            $this->amount = $data['amount'];
        }
        if (array_key_exists('s', $data)) {
            $this->s = $data['s'];
        }
    }

    /**
     * Apply all filter conditions to the query
     *
     * @param \yii\db\ActiveQuery $query
     */
    protected function applyFilters($query)
    {
        // Always filter by the active workspace
        $query->andFilterWhere([
            '{{%expenses}}.workspace_id' => Yii::$app->workspace->getId(),
        ]);

        // ID filter
        $query->andFilterWhere(['{{%expenses}}.id' => $this->id]);

        // Date range filter
        if (!empty($this->start_date) && !empty($this->end_date)) {
            $query->andFilterWhere(['between', 'expense_date', $this->start_date, $this->end_date]);
        } elseif (!empty($this->start_date)) {
            $query->andFilterWhere(['>=', 'expense_date', $this->start_date]);
        } elseif (!empty($this->end_date)) {
            $query->andFilterWhere(['<=', 'expense_date', $this->end_date]);
        }

        // Category filter (including child categories)
        if (!empty($this->expense_category_id)) {
            $categoryIds = $this->getCategoryIds($this->expense_category_id);
            $query->andFilterWhere(['in', 'expense_category_id', $categoryIds]);
        }

        // Payment method filter
        $query->andFilterWhere(['like', 'payment_method', $this->payment_method]);

        // Reference filter
        $query->andFilterWhere(['like', 'reference', $this->reference]);

        // Description filter
        $query->andFilterWhere(['like', '{{%expenses}}.description', $this->description]);

        // Amount filter (exact match)
        if (!empty($this->amount)) {
            $cleanAmount = str_replace(',', '', $this->amount);
            $query->andFilterWhere(['amount' => $cleanAmount]);
        }

        // Global search
        if (!empty($this->s)) {
            $searchTerm = trim($this->s);
            $query->andWhere([
                'or',
                ['like', '{{%expenses}}.description', $searchTerm],
                ['like', '{{%expenses}}.reference', $searchTerm],
                ['like', '{{%expense_categories}}.name', $searchTerm],
            ]);
        }

        // Audit fields
        $query->andFilterWhere(['{{%expenses}}.created_by' => $this->created_by]);
        $query->andFilterWhere(['{{%expenses}}.updated_by' => $this->updated_by]);
    }

    /**
     * Get all category IDs for a given parent category ID, including child categories
     *
     * @param int $parentId
     * @return array
     */
    protected function getCategoryIds($parentId)
    {
        $categoryIds = [$parentId];

        $childCategories = ExpenseCategory::find()
            ->where(['parent_id' => $parentId])
            ->all();

        foreach ($childCategories as $category) {
            $categoryIds[] = $category->id;
            $categoryIds = array_merge($categoryIds, $this->getCategoryIds($category->id));
        }

        return $categoryIds;
    }

    /**
     * Get total amount for current filtered results
     *
     * @param array $params
     * @return float
     */
    public function getTotalAmount($params)
    {
        $dataProvider = $this->search($params, true);
        return array_sum(array_column($dataProvider->getModels(), 'amount'));
    }

    /**
     * Get expense statistics for the filtered results
     *
     * @param array $params
     * @return array
     */
    public function getStatistics($params)
    {
        $dataProvider = $this->search($params, true);
        $models = $dataProvider->getModels();

        if (empty($models)) {
            return [
                'total' => 0,
                'count' => 0,
                'average' => 0,
                'max' => 0,
                'min' => 0,
            ];
        }

        $amounts = array_column($models, 'amount');

        return [
            'total' => array_sum($amounts),
            'count' => count($models),
            'average' => array_sum($amounts) / count($models),
            'max' => max($amounts),
            'min' => min($amounts),
        ];
    }
}
