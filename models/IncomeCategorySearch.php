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
 * IncomeCategorySearch represents the model behind the search form of `app\models\IncomeCategory`.
 *
 * This search model provides filtering and sorting capabilities for the income categories
 * grid view, including search by name, description, and status filtering.
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */
class IncomeCategorySearch extends IncomeCategory
{
    /**
     * @var string Global search term (searches name and description)
     */
    public $globalSearch;

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['id', 'user_id', 'status'], 'integer'],
            [['name', 'description', 'icon', 'color', 'globalSearch', 'created_at', 'updated_at'], 'safe'],
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
     * @param bool $isExport Whether this is for export (disables pagination)
     * @return ActiveDataProvider
     */
    public function search(array $params, bool $isExport = false): ActiveDataProvider
    {
        $query = IncomeCategory::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => [
                    'name' => SORT_ASC,
                ],
                'attributes' => [
                    'name',
                    'status',
                    'created_at',
                    'updated_at',
                ],
            ],
            'pagination' => $isExport ? false : [
                'pageSize' => Yii::$app->request->get('per-page', 10),
                'pageSizeParam' => 'per-page',
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // Return all records if validation fails
            return $dataProvider;
        }

        // Grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'user_id' => $this->user_id,
            'status' => $this->status,
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

        // Date range filtering
        if (!empty($this->created_at)) {
            $query->andFilterWhere(['>=', 'created_at', $this->created_at . ' 00:00:00']);
        }

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
}
