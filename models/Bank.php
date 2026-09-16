<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 - 2026 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace app\models;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "{{%banks}}".
 *
 * A workspace-scoped list of banks used to tag Card / Bank Transfer expenses
 * with the bank they went through.
 *
 * @property int $id Bank ID
 * @property int $workspace_id Owning workspace ID
 * @property string $name Bank name
 * @property int $status Active/Inactive status
 * @property int|null $created_at Creation timestamp
 * @property int|null $updated_at Last update timestamp
 * @property int|null $created_by Creator user ID
 * @property int|null $updated_by Last updater user ID
 *
 * @property Expense[] $expenses Related expense records
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.2.0
 */
class Bank extends ActiveRecord
{
    /**
     * Status constants
     */
    public const STATUS_INACTIVE = 0;
    public const STATUS_ACTIVE = 1;

    /**
     * {@inheritdoc}
     */
    public static function tableName(): string
    {
        return '{{%banks}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors(): array
    {
        return [
            TimestampBehavior::class,
            BlameableBehavior::class,
            \app\components\WorkspaceBehavior::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['name'], 'required'],
            [['name'], 'trim'],
            [['name'], 'string', 'max' => 191],

            [['workspace_id', 'status', 'created_at', 'updated_at', 'created_by', 'updated_by'], 'integer'],
            [['status'], 'default', 'value' => self::STATUS_ACTIVE],

            // One bank name per workspace.
            [
                ['name'],
                'unique',
                'targetAttribute' => ['name', 'workspace_id'],
                'message' => Yii::t('app', 'You already have a bank with this name.'),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'name' => Yii::t('app', 'Bank Name'),
            'status' => Yii::t('app', 'Status'),
        ];
    }

    /**
     * Gets query for related expenses.
     *
     * @return ActiveQuery
     */
    public function getExpenses(): ActiveQuery
    {
        return $this->hasMany(Expense::class, ['bank_id' => 'id']);
    }

    /**
     * Returns active banks for the current workspace as an `id => name` map,
     * suitable for a dropdown.
     *
     * @param int|null $workspaceId Defaults to the active workspace
     * @return array<int, string>
     */
    public static function getList(?int $workspaceId = null): array
    {
        $workspaceId = $workspaceId ?? Yii::$app->workspace->getId();

        $rows = self::find()
            ->select(['id', 'name'])
            ->where(['workspace_id' => $workspaceId, 'status' => self::STATUS_ACTIVE])
            ->orderBy(['name' => SORT_ASC])
            ->asArray()
            ->all();

        return ArrayHelper::map($rows, 'id', 'name');
    }
}
