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
 * ExpenseCategorySearch represents the model behind the search form of `app\models\ExpenseCategory`.
 *
 * This search model provides filtering and sorting capabilities for the expense categories
 * grid view, including search by name, description, parent, and status filtering.
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */
class ExpenseCategorySearch extends ExpenseCategory
{
    /**
     * @var string Global search term (searches name and description)
     */
    public $globalSearch;

    /**
     * @var string View type: 'tree' or 'list'
     */
    public $viewType = 'tree';

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['id', 'parent_id', 'user_id', 'status', 'created_by', 'updated_by'], 'integer'],
            [['name', 'description', 'icon', 'color', 'globalSearch', 'viewType'], 'safe'],
            [['created_at', 'updated_at'], 'safe'],
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
     * Creates data provider instance with search query applied
     *
     * @param array $params Search parameters from request
     * @return ActiveDataProvider
     */
    public function search(array $params): ActiveDataProvider
    {
        $query = ExpenseCategory::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => [
                    'parent_id' => SORT_ASC,
                    'name' => SORT_ASC,
                ],
                'attributes' => [
                    'name',
                    'parent_id',
                    'status',
                    'created_at',
                    'updated_at',
                ],
            ],
            'pagination' => [
                'pageSize' => Yii::$app->request->get('per-page', 25),
                'pageSizeParam' => 'per-page',
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        // Grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'user_id' => $this->user_id,
            'status' => $this->status,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
        ]);

        // Text search on name and description
        $query->andFilterWhere(['like', 'name', $this->name]);
        $query->andFilterWhere(['like', 'description', $this->description]);

        // Global search (if provided)
        if (!empty($this->globalSearch)) {
            $query->andFilterWhere([
                'or',
                ['like', 'name', $this->globalSearch],
                ['like', 'description', $this->globalSearch],
            ]);
        }

        return $dataProvider;
    }

    /**
     * Returns the flat set of categories the tree view should render.
     *
     * Filtering a hierarchy by row would orphan a matching child whose parent
     * was filtered out, so every match drags its ancestors along. Those
     * ancestors are context: they are shown so the match can be reached, even
     * when they do not match themselves.
     *
     * @param array $params Search parameters from request
     * @param int $workspaceId Owning workspace ID
     * @return ExpenseCategory[] Matches plus their ancestors, parent-first
     */
    public function searchTreeNodes(array $params, int $workspaceId): array
    {
        $this->load($params);

        $all = ExpenseCategory::find()
            ->where(['workspace_id' => $workspaceId])
            ->orderBy(['parent_id' => SORT_ASC, 'name' => SORT_ASC])
            ->all();

        if (!$this->validate() || !$this->hasTreeFilters()) {
            return $all;
        }

        $byId = [];
        foreach ($all as $category) {
            $byId[$category->id] = $category;
        }

        $keep = [];
        foreach ($all as $category) {
            if (!$this->matchesNode($category)) {
                continue;
            }
            // Walk to the root. Hitting a node that is already kept means its
            // own ancestors were kept on an earlier walk, so stop there.
            for ($node = $category; $node !== null; $node = $byId[$node->parent_id] ?? null) {
                if (isset($keep[$node->id])) {
                    break;
                }
                $keep[$node->id] = true;
            }
        }

        return array_values(array_filter($all, fn ($category) => isset($keep[$category->id])));
    }

    /**
     * Whether any filter that narrows the tree is active.
     *
     * @return bool
     */
    public function hasTreeFilters(): bool
    {
        return trim((string) $this->globalSearch) !== ''
            || ($this->status !== null && $this->status !== '');
    }

    /**
     * Whether a single category satisfies the active filters, ignoring its
     * place in the hierarchy.
     *
     * @param ExpenseCategory $category
     * @return bool
     */
    private function matchesNode(ExpenseCategory $category): bool
    {
        if ($this->status !== null && $this->status !== '' && (int) $category->status !== (int) $this->status) {
            return false;
        }

        $term = trim((string) $this->globalSearch);
        if ($term === '') {
            return true;
        }

        return stripos((string) $category->name, $term) !== false
            || stripos((string) $category->description, $term) !== false;
    }

    /**
     * Search only root categories
     *
     * @param array $params Search parameters
     * @return ActiveDataProvider
     */
    public function searchRoots(array $params): ActiveDataProvider
    {
        $dataProvider = $this->search($params);
        $dataProvider->query->andWhere(['parent_id' => null]);

        return $dataProvider;
    }

    /**
     * Search children of a specific parent
     *
     * @param int $parentId Parent category ID
     * @param array $params Search parameters
     * @return ActiveDataProvider
     */
    public function searchChildren(int $parentId, array $params): ActiveDataProvider
    {
        $dataProvider = $this->search($params);
        $dataProvider->query->andWhere(['parent_id' => $parentId]);

        return $dataProvider;
    }

    /**
     * Returns list of status options for dropdown
     *
     * @return array Status options [value => label]
     */
    public static function getStatusOptions(): array
    {
        return [
            '' => Yii::t('app', 'All Status'),
            self::STATUS_ACTIVE => Yii::t('app', 'Active'),
            self::STATUS_INACTIVE => Yii::t('app', 'Inactive'),
        ];
    }

    /**
     * Returns list of view type options
     *
     * @return array View type options
     */
    public static function getViewTypeOptions(): array
    {
        return [
            'tree' => Yii::t('app', 'Tree View'),
            'list' => Yii::t('app', 'List View'),
        ];
    }
}
