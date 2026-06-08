<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace app\components;

use Yii;
use yii\base\Component;
use app\models\User;
use app\models\Workspace;
use app\models\WorkspaceMember;

/**
 * WorkspaceManager resolves and exposes the authenticated user's active
 * workspace, accessible application-wide as `Yii::$app->workspace`.
 *
 * The active workspace id is the scope used by every data query. It is stored
 * in the session and validated against the user's memberships on each request;
 * if missing or invalid it falls back to the user's personal workspace (or the
 * first workspace they belong to). A user who somehow has no membership gets a
 * personal workspace created on demand so the app never breaks.
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */
class WorkspaceManager extends Component
{
    /** @var string Session key holding the active workspace id */
    public string $sessionKey = 'activeWorkspaceId';

    /** @var WorkspaceMember|null Resolved active membership (false = not yet resolved) */
    private $_membership = false;

    /**
     * Returns the active workspace id, or null for guests.
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        $membership = $this->getMembership();
        return $membership ? (int) $membership->workspace_id : null;
    }

    /**
     * Returns the active Workspace model, or null for guests.
     *
     * @return Workspace|null
     */
    public function getWorkspace(): ?Workspace
    {
        $membership = $this->getMembership();
        return $membership ? $membership->workspace : null;
    }

    /**
     * Returns the current user's role in the active workspace, or null.
     *
     * @return string|null
     */
    public function getRole(): ?string
    {
        $membership = $this->getMembership();
        return $membership ? $membership->role : null;
    }

    /**
     * Whether the current user may perform a capability in the active workspace.
     *
     * @param string $capability One of WorkspaceMember::CAN_*
     * @return bool
     */
    public function can(string $capability): bool
    {
        $role = $this->getRole();
        return $role !== null && WorkspaceMember::roleCan($role, $capability);
    }

    /**
     * Returns all workspaces the current user actively belongs to.
     *
     * @return Workspace[]
     */
    public function getAll(): array
    {
        if (Yii::$app->user->isGuest) {
            return [];
        }

        return Workspace::find()
            ->innerJoin('{{%workspace_members}} m', 'm.workspace_id = {{%workspaces}}.id')
            ->where(['m.user_id' => Yii::$app->user->id, 'm.status' => WorkspaceMember::STATUS_ACTIVE])
            ->orderBy(['{{%workspaces}}.is_personal' => SORT_DESC, '{{%workspaces}}.name' => SORT_ASC])
            ->all();
    }

    /**
     * Switches the active workspace, validating membership first.
     *
     * @param int $workspaceId
     * @return bool Whether the switch succeeded
     */
    public function setActive(int $workspaceId): bool
    {
        if (Yii::$app->user->isGuest) {
            return false;
        }

        $membership = WorkspaceMember::findOne([
            'workspace_id' => $workspaceId,
            'user_id' => Yii::$app->user->id,
            'status' => WorkspaceMember::STATUS_ACTIVE,
        ]);

        if ($membership === null) {
            return false;
        }

        Yii::$app->session->set($this->sessionKey, $workspaceId);
        $this->_membership = $membership;

        return true;
    }

    /**
     * Resolves (and caches) the active membership for this request.
     *
     * @return WorkspaceMember|null
     */
    public function getMembership(): ?WorkspaceMember
    {
        if ($this->_membership !== false) {
            return $this->_membership;
        }

        $this->_membership = $this->resolve();
        return $this->_membership;
    }

    /**
     * Resets the cached membership (e.g. after switching).
     */
    public function reset(): void
    {
        $this->_membership = false;
    }

    /**
     * Resolves the active membership from session/default, with fallbacks.
     *
     * @return WorkspaceMember|null
     */
    private function resolve(): ?WorkspaceMember
    {
        if (!Yii::$app->has('user') || Yii::$app->user->isGuest) {
            return null;
        }

        $userId = Yii::$app->user->id;
        $stored = Yii::$app->session->get($this->sessionKey);

        // Honour the stored workspace if the user still belongs to it
        if ($stored !== null) {
            $membership = WorkspaceMember::findOne([
                'workspace_id' => (int) $stored,
                'user_id' => $userId,
                'status' => WorkspaceMember::STATUS_ACTIVE,
            ]);
            if ($membership !== null) {
                return $membership;
            }
        }

        // Fall back to the user's personal workspace, else their first membership
        $membership = WorkspaceMember::find()
            ->alias('m')
            ->innerJoin('{{%workspaces}} w', 'w.id = m.workspace_id')
            ->where(['m.user_id' => $userId, 'm.status' => WorkspaceMember::STATUS_ACTIVE])
            ->orderBy(['w.is_personal' => SORT_DESC, 'w.id' => SORT_ASC])
            ->one();

        // Safety net: a user with no workspace gets a personal one
        if ($membership === null) {
            $user = User::findOne($userId);
            if ($user !== null) {
                $workspace = Workspace::createPersonalFor($user);
                if ($workspace !== null) {
                    $membership = WorkspaceMember::findOne([
                        'workspace_id' => $workspace->id,
                        'user_id' => $userId,
                    ]);
                }
            }
        }

        if ($membership !== null) {
            Yii::$app->session->set($this->sessionKey, (int) $membership->workspace_id);
        }

        return $membership;
    }
}
