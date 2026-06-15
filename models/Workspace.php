<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 - 2026 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace app\models;

use Yii;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * Workspace model for the "{{%workspaces}}" table.
 *
 * A workspace is a shared container that owns transactional data (incomes,
 * expenses, categories, budgets). Every user has a personal workspace; teams
 * are workspaces with additional members.
 *
 * @property int $id
 * @property string $name
 * @property int $owner_id
 * @property bool $is_personal
 * @property int|null $created_at
 * @property int|null $updated_at
 *
 * @property User $owner
 * @property WorkspaceMember[] $members
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */
class Workspace extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName(): string
    {
        return '{{%workspaces}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors(): array
    {
        return [TimestampBehavior::class];
    }

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['name'], 'required'],
            [['name'], 'string', 'max' => 191],
            [['name'], 'trim'],
            [['owner_id'], 'integer'],
            [['is_personal'], 'boolean'],
            [['owner_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['owner_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'name' => Yii::t('app', 'Workspace Name'),
            'owner_id' => Yii::t('app', 'Owner'),
            'is_personal' => Yii::t('app', 'Personal'),
        ];
    }

    /**
     * @return ActiveQuery
     */
    public function getOwner(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'owner_id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getMembers(): ActiveQuery
    {
        return $this->hasMany(WorkspaceMember::class, ['workspace_id' => 'id']);
    }

    /**
     * Returns the active member count.
     *
     * @return int
     */
    public function getMemberCount(): int
    {
        return (int) $this->getMembers()->andWhere(['status' => WorkspaceMember::STATUS_ACTIVE])->count();
    }

    /**
     * Creates a personal workspace (with an owner membership) for a user.
     *
     * @param User $user
     * @return self|null
     */
    public static function createPersonalFor(User $user): ?self
    {
        $workspace = new self([
            'name' => Yii::t('app', 'Personal'),
            'owner_id' => $user->id,
            'is_personal' => true,
        ]);

        if (!$workspace->save()) {
            return null;
        }

        $member = new WorkspaceMember([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => WorkspaceMember::ROLE_OWNER,
            'status' => WorkspaceMember::STATUS_ACTIVE,
        ]);
        $member->save();

        return $workspace;
    }
}
