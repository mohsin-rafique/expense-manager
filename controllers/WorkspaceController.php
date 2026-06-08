<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace app\controllers;

use Yii;
use app\models\User;
use app\models\Workspace;
use app\models\WorkspaceMember;
use yii\web\Controller;
use yii\web\Response;
use yii\web\ForbiddenHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;

/**
 * WorkspaceController manages workspaces and their members.
 *
 * Covers switching the active workspace, creating team workspaces, inviting
 * members by email (existing or new users), accepting invitations, changing
 * roles, removing members, leaving, renaming, and deleting workspaces. Role
 * capabilities are enforced per action.
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */
class WorkspaceController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    ['allow' => true, 'roles' => ['@']],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'create' => ['POST'],
                    'invite' => ['POST'],
                    'update-role' => ['POST'],
                    'remove-member' => ['POST'],
                    'leave' => ['POST'],
                    'rename' => ['POST'],
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Team management page for the active workspace.
     *
     * @return string
     */
    public function actionIndex(): string
    {
        $manager = Yii::$app->workspace;
        $workspace = $manager->getWorkspace();

        $members = $workspace
            ? WorkspaceMember::find()
                ->with('user')
                ->where(['workspace_id' => $workspace->id])
                ->orderBy(['role' => SORT_ASC, 'status' => SORT_ASC])
                ->all()
            : [];

        return $this->render('index', [
            'workspace' => $workspace,
            'members' => $members,
            'role' => $manager->getRole(),
            'canManageMembers' => $manager->can(WorkspaceMember::CAN_MANAGE_MEMBERS),
            'canManageWorkspace' => $manager->can(WorkspaceMember::CAN_MANAGE_WORKSPACE),
            'workspaces' => $manager->getAll(),
        ]);
    }

    /**
     * Switches the active workspace and returns to the previous page.
     *
     * @param int $id
     * @return Response
     */
    public function actionSwitch(int $id): Response
    {
        if (Yii::$app->workspace->setActive($id)) {
            $name = Yii::$app->workspace->getWorkspace()->name ?? '';
            Yii::$app->session->setFlash('success', Yii::t('app', 'Switched to {workspace}.', ['workspace' => $name]));
        } else {
            Yii::$app->session->setFlash('error', Yii::t('app', 'You do not have access to that workspace.'));
        }

        return $this->redirect(Yii::$app->request->referrer ?: Yii::$app->homeUrl);
    }

    /**
     * Creates a new team workspace owned by the current user and switches to it.
     *
     * @return Response
     */
    public function actionCreate(): Response
    {
        $name = trim((string) Yii::$app->request->post('name', ''));
        if ($name === '') {
            Yii::$app->session->setFlash('error', Yii::t('app', 'Please enter a workspace name.'));
            return $this->redirect(['index']);
        }

        $workspace = new Workspace([
            'name' => $name,
            'owner_id' => Yii::$app->user->id,
            'is_personal' => false,
        ]);

        if ($workspace->save()) {
            (new WorkspaceMember([
                'workspace_id' => $workspace->id,
                'user_id' => Yii::$app->user->id,
                'role' => WorkspaceMember::ROLE_OWNER,
                'status' => WorkspaceMember::STATUS_ACTIVE,
            ]))->save();

            Yii::$app->workspace->setActive($workspace->id);
            Yii::$app->session->setFlash('success', Yii::t('app', 'Workspace "{name}" created.', ['name' => $name]));
        } else {
            Yii::$app->session->setFlash('error', Yii::t('app', 'Failed to create workspace.'));
        }

        return $this->redirect(['index']);
    }

    /**
     * Invites a member to the active workspace by email.
     *
     * @return Response
     * @throws ForbiddenHttpException
     */
    public function actionInvite(): Response
    {
        $this->requireCapability(WorkspaceMember::CAN_MANAGE_MEMBERS);

        $workspace = Yii::$app->workspace->getWorkspace();
        $request = Yii::$app->request;
        $email = trim(mb_strtolower((string) $request->post('email', '')));
        $role = (string) $request->post('role', WorkspaceMember::ROLE_MEMBER);

        $invitableRoles = [WorkspaceMember::ROLE_ADMIN, WorkspaceMember::ROLE_MEMBER, WorkspaceMember::ROLE_VIEWER];
        if (!in_array($role, $invitableRoles, true)) {
            $role = WorkspaceMember::ROLE_MEMBER;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Yii::$app->session->setFlash('error', Yii::t('app', 'Please enter a valid email address.'));
            return $this->redirect(['index']);
        }

        $invitee = User::findOne(['email' => $email]);

        // Already a member?
        $existing = WorkspaceMember::find()->where(['workspace_id' => $workspace->id])
            ->andWhere(['or', ['user_id' => $invitee->id ?? -1], ['invited_email' => $email]])
            ->one();
        if ($existing !== null) {
            Yii::$app->session->setFlash('warning', Yii::t('app', '{email} is already a member or has a pending invite.', ['email' => $email]));
            return $this->redirect(['index']);
        }

        $member = new WorkspaceMember([
            'workspace_id' => $workspace->id,
            'user_id' => $invitee->id ?? null,
            'role' => $role,
            'status' => WorkspaceMember::STATUS_PENDING,
            'invited_email' => $email,
            'invite_token' => Yii::$app->security->generateRandomString(32),
        ]);

        if (!$member->save()) {
            Yii::$app->session->setFlash('error', Yii::t('app', 'Failed to send the invitation.'));
            return $this->redirect(['index']);
        }

        $this->sendInviteEmail($member, $workspace, $invitee);
        Yii::$app->session->setFlash('success', Yii::t('app', 'Invitation sent to {email}.', ['email' => $email]));

        return $this->redirect(['index']);
    }

    /**
     * Accepts a workspace invitation by token (current user must be logged in
     * and match the invite).
     *
     * @param string $token
     * @return Response
     */
    public function actionAccept(string $token): Response
    {
        $member = WorkspaceMember::findOne(['invite_token' => $token, 'status' => WorkspaceMember::STATUS_PENDING]);

        if ($member === null) {
            Yii::$app->session->setFlash('error', Yii::t('app', 'This invitation is invalid or has already been used.'));
            return $this->redirect(['index']);
        }

        $identity = Yii::$app->user->identity;
        $emailMatches = $member->invited_email !== null
            && mb_strtolower($member->invited_email) === mb_strtolower((string) $identity->email);
        $userMatches = $member->user_id !== null && (int) $member->user_id === (int) $identity->id;

        if (!$userMatches && !$emailMatches) {
            Yii::$app->session->setFlash('error', Yii::t('app', 'This invitation was sent to a different account.'));
            return $this->redirect(['index']);
        }

        $member->user_id = $identity->id;
        $member->status = WorkspaceMember::STATUS_ACTIVE;
        $member->invite_token = null;
        $member->save(false);

        Yii::$app->workspace->setActive((int) $member->workspace_id);
        $name = $member->workspace->name ?? '';
        Yii::$app->session->setFlash('success', Yii::t('app', 'You have joined {workspace}.', ['workspace' => $name]));

        return $this->redirect(['index']);
    }

    /**
     * Changes a member's role in the active workspace.
     *
     * @return Response
     * @throws ForbiddenHttpException
     */
    public function actionUpdateRole(): Response
    {
        $this->requireCapability(WorkspaceMember::CAN_MANAGE_MEMBERS);

        $member = $this->findMember((int) Yii::$app->request->post('member_id'));
        $role = (string) Yii::$app->request->post('role');

        if (!array_key_exists($role, WorkspaceMember::roleOptions())) {
            Yii::$app->session->setFlash('error', Yii::t('app', 'Invalid role.'));
            return $this->redirect(['index']);
        }

        // Don't allow removing the last owner via a demotion
        if ($member->role === WorkspaceMember::ROLE_OWNER && $role !== WorkspaceMember::ROLE_OWNER && $this->ownerCount() <= 1) {
            Yii::$app->session->setFlash('error', Yii::t('app', 'A workspace must have at least one owner.'));
            return $this->redirect(['index']);
        }

        $member->role = $role;
        $member->save(false, ['role', 'updated_at']);
        Yii::$app->session->setFlash('success', Yii::t('app', 'Role updated.'));

        return $this->redirect(['index']);
    }

    /**
     * Removes a member from the active workspace.
     *
     * @return Response
     * @throws ForbiddenHttpException
     */
    public function actionRemoveMember(): Response
    {
        $this->requireCapability(WorkspaceMember::CAN_MANAGE_MEMBERS);

        $member = $this->findMember((int) Yii::$app->request->post('member_id'));

        if ($member->role === WorkspaceMember::ROLE_OWNER && $this->ownerCount() <= 1) {
            Yii::$app->session->setFlash('error', Yii::t('app', 'You cannot remove the last owner.'));
            return $this->redirect(['index']);
        }

        $member->delete();
        Yii::$app->session->setFlash('success', Yii::t('app', 'Member removed.'));

        return $this->redirect(['index']);
    }

    /**
     * Current user leaves the active workspace.
     *
     * @return Response
     */
    public function actionLeave(): Response
    {
        $workspace = Yii::$app->workspace->getWorkspace();

        if ($workspace === null || $workspace->is_personal) {
            Yii::$app->session->setFlash('error', Yii::t('app', 'You cannot leave your personal workspace.'));
            return $this->redirect(['index']);
        }

        $membership = Yii::$app->workspace->getMembership();
        if ($membership !== null && $membership->role === WorkspaceMember::ROLE_OWNER && $this->ownerCount() <= 1) {
            Yii::$app->session->setFlash('error', Yii::t('app', 'As the only owner you must transfer ownership or delete the workspace before leaving.'));
            return $this->redirect(['index']);
        }

        if ($membership !== null) {
            $membership->delete();
        }

        // Fall back to the personal workspace
        Yii::$app->workspace->reset();
        Yii::$app->session->remove(Yii::$app->workspace->sessionKey);
        Yii::$app->session->setFlash('success', Yii::t('app', 'You have left the workspace.'));

        return $this->redirect(['index']);
    }

    /**
     * Renames the active workspace (owner only).
     *
     * @return Response
     * @throws ForbiddenHttpException
     */
    public function actionRename(): Response
    {
        $this->requireCapability(WorkspaceMember::CAN_MANAGE_WORKSPACE);

        $workspace = Yii::$app->workspace->getWorkspace();
        $name = trim((string) Yii::$app->request->post('name', ''));

        if ($name !== '') {
            $workspace->name = $name;
            $workspace->save(false, ['name', 'updated_at']);
            Yii::$app->session->setFlash('success', Yii::t('app', 'Workspace renamed.'));
        }

        return $this->redirect(['index']);
    }

    /**
     * Deletes the active (non-personal) workspace and all its data (owner only).
     *
     * @return Response
     * @throws ForbiddenHttpException
     */
    public function actionDelete(): Response
    {
        $this->requireCapability(WorkspaceMember::CAN_MANAGE_WORKSPACE);

        $workspace = Yii::$app->workspace->getWorkspace();
        if ($workspace->is_personal) {
            Yii::$app->session->setFlash('error', Yii::t('app', 'You cannot delete your personal workspace.'));
            return $this->redirect(['index']);
        }

        $workspace->delete(); // FK cascade removes members and all scoped data
        Yii::$app->workspace->reset();
        Yii::$app->session->remove(Yii::$app->workspace->sessionKey);
        Yii::$app->session->setFlash('success', Yii::t('app', 'Workspace deleted.'));

        return $this->redirect(['index']);
    }

    // ─── Helpers ─────────────────────────────────────────────────────

    /**
     * Throws if the current user lacks a capability in the active workspace.
     *
     * @param string $capability
     * @throws ForbiddenHttpException
     */
    private function requireCapability(string $capability): void
    {
        if (!Yii::$app->workspace->can($capability)) {
            throw new ForbiddenHttpException(Yii::t('app', 'Your role in this workspace does not allow this action.'));
        }
    }

    /**
     * Finds a member by id within the active workspace.
     *
     * @param int $memberId
     * @return WorkspaceMember
     * @throws \yii\web\NotFoundHttpException
     */
    private function findMember(int $memberId): WorkspaceMember
    {
        $member = WorkspaceMember::findOne([
            'id' => $memberId,
            'workspace_id' => Yii::$app->workspace->getId(),
        ]);

        if ($member === null) {
            throw new \yii\web\NotFoundHttpException(Yii::t('app', 'Member not found.'));
        }

        return $member;
    }

    /**
     * Counts active owners in the active workspace.
     *
     * @return int
     */
    private function ownerCount(): int
    {
        return (int) WorkspaceMember::find()
            ->where([
                'workspace_id' => Yii::$app->workspace->getId(),
                'role' => WorkspaceMember::ROLE_OWNER,
                'status' => WorkspaceMember::STATUS_ACTIVE,
            ])
            ->count();
    }

    /**
     * Sends an invitation email with an accept link.
     *
     * @param WorkspaceMember $member
     * @param Workspace $workspace
     * @param User|null $invitee Existing user, or null for a brand-new email
     */
    private function sendInviteEmail(WorkspaceMember $member, Workspace $workspace, ?User $invitee): void
    {
        try {
            $acceptUrl = Yii::$app->urlManager->createAbsoluteUrl(['workspace/accept', 'token' => $member->invite_token]);

            Yii::$app->mailer
                ->compose(
                    ['html' => 'workspaceInvite-html', 'text' => 'workspaceInvite-text'],
                    [
                        'workspace' => $workspace,
                        'inviter' => Yii::$app->user->identity,
                        'acceptUrl' => $acceptUrl,
                        'isExistingUser' => $invitee !== null,
                        'role' => $member->getRoleLabel(),
                    ]
                )
                ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->name])
                ->setTo($member->invited_email)
                ->setSubject(Yii::t('app', 'You are invited to join {workspace}', ['workspace' => $workspace->name]))
                ->send();
        } catch (\Throwable $e) {
            Yii::error('Failed to send workspace invite: ' . $e->getMessage(), __METHOD__);
        }
    }
}
