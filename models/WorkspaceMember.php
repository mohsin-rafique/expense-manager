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
 * WorkspaceMember model for the "{{%workspace_members}}" table.
 *
 * Joins a user to a workspace with a role. A pending row with a null user_id
 * and an invited_email represents an outstanding email invitation.
 *
 * Role capabilities (enforced via {@see can()}):
 *  - owner   : full control incl. managing members and deleting the workspace
 *  - admin   : manage members + all data
 *  - member  : create/edit/delete transactional data
 *  - viewer  : read-only
 *
 * @property int $id
 * @property int $workspace_id
 * @property int|null $user_id
 * @property string $role
 * @property string $status
 * @property string|null $invited_email
 * @property string|null $invite_token
 * @property int|null $created_at
 * @property int|null $updated_at
 *
 * @property Workspace $workspace
 * @property User $user
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */
class WorkspaceMember extends ActiveRecord
{
    public const ROLE_OWNER = 'owner';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_MEMBER = 'member';
    public const ROLE_VIEWER = 'viewer';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_PENDING = 'pending';

    /** Capability keys used with {@see can()} */
    public const CAN_MANAGE_DATA = 'manageData';     // create/edit/delete transactions, categories, budgets
    public const CAN_MANAGE_MEMBERS = 'manageMembers'; // invite/remove/change roles
    public const CAN_MANAGE_WORKSPACE = 'manageWorkspace'; // rename/delete workspace

    /**
     * {@inheritdoc}
     */
    public static function tableName(): string
    {
        return '{{%workspace_members}}';
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
            [['workspace_id', 'role'], 'required'],
            [['workspace_id', 'user_id'], 'integer'],
            [['role'], 'in', 'range' => array_keys(self::roleOptions())],
            [['status'], 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_PENDING]],
            [['status'], 'default', 'value' => self::STATUS_ACTIVE],
            [['invited_email'], 'email'],
            [['invited_email', 'invite_token'], 'string', 'max' => 191],
            [['workspace_id'], 'exist', 'skipOnError' => true, 'targetClass' => Workspace::class, 'targetAttribute' => ['workspace_id' => 'id']],
        ];
    }

    /**
     * Role => label map.
     *
     * @return array
     */
    public static function roleOptions(): array
    {
        return [
            self::ROLE_OWNER => Yii::t('app', 'Owner'),
            self::ROLE_ADMIN => Yii::t('app', 'Admin'),
            self::ROLE_MEMBER => Yii::t('app', 'Member'),
            self::ROLE_VIEWER => Yii::t('app', 'Viewer'),
        ];
    }

    /**
     * Returns the human label for this member's role.
     *
     * @return string
     */
    public function getRoleLabel(): string
    {
        return self::roleOptions()[$this->role] ?? $this->role;
    }

    /**
     * Determines whether a given role grants a capability.
     *
     * @param string $role One of the ROLE_* constants
     * @param string $capability One of the CAN_* constants
     * @return bool
     */
    public static function roleCan(string $role, string $capability): bool
    {
        $matrix = [
            self::ROLE_OWNER => [self::CAN_MANAGE_DATA, self::CAN_MANAGE_MEMBERS, self::CAN_MANAGE_WORKSPACE],
            self::ROLE_ADMIN => [self::CAN_MANAGE_DATA, self::CAN_MANAGE_MEMBERS],
            self::ROLE_MEMBER => [self::CAN_MANAGE_DATA],
            self::ROLE_VIEWER => [],
        ];

        return in_array($capability, $matrix[$role] ?? [], true);
    }

    /**
     * @return ActiveQuery
     */
    public function getWorkspace(): ActiveQuery
    {
        return $this->hasOne(Workspace::class, ['id' => 'workspace_id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getUser(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
}
