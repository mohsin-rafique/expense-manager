<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 - 2026 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * Team & Workspaces management page.
 *
 * Manage the active workspace: members, roles, invitations, rename/delete,
 * plus switching between and creating workspaces.
 *
 * @var yii\web\View $this
 * @var app\models\Workspace|null $workspace Active workspace
 * @var app\models\WorkspaceMember[] $members Members of the active workspace
 * @var string|null $role Current user's role
 * @var bool $canManageMembers
 * @var bool $canManageWorkspace
 * @var app\models\Workspace[] $workspaces All workspaces the user belongs to
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */

use yii\helpers\Html;
use yii\helpers\Url;
use app\models\WorkspaceMember;

$this->title = Yii::t('app', 'Team & Workspaces');

$roleBadge = [
    WorkspaceMember::ROLE_OWNER => 'bg-primary',
    WorkspaceMember::ROLE_ADMIN => 'bg-info',
    WorkspaceMember::ROLE_MEMBER => 'bg-secondary',
    WorkspaceMember::ROLE_VIEWER => 'bg-light text-dark border',
];
$assignableRoles = [
    WorkspaceMember::ROLE_ADMIN => Yii::t('app', 'Admin'),
    WorkspaceMember::ROLE_MEMBER => Yii::t('app', 'Member'),
    WorkspaceMember::ROLE_VIEWER => Yii::t('app', 'Viewer'),
];
?>

<div class="workspace-index">
    <div class="mb-4">
        <h1 class="h3 mb-1"><?= Html::encode($this->title) ?></h1>
        <p class="text-muted mb-0"><?= Yii::t('app', 'Share a workspace with your team and control who can do what') ?></p>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <!-- Active workspace -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="bi <?= $workspace && $workspace->is_personal ? 'bi-person' : 'bi-people' ?> me-2 text-primary"></i>
                        <?= $workspace ? Html::encode($workspace->name) : Yii::t('app', 'No workspace') ?>
                    </h5>
                    <?php if ($role !== null): ?>
                        <span class="badge <?= $roleBadge[$role] ?? 'bg-secondary' ?>"><?= Html::encode(WorkspaceMember::roleOptions()[$role] ?? $role) ?></span>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if ($canManageWorkspace && $workspace && !$workspace->is_personal): ?>
                        <?= Html::beginForm(['rename'], 'post', ['class' => 'row g-2 align-items-end mb-3']) ?>
                            <div class="col-sm-8">
                                <label class="form-label small text-muted mb-1"><?= Yii::t('app', 'Workspace Name') ?></label>
                                <?= Html::textInput('name', $workspace->name, ['class' => 'form-control', 'maxlength' => 191]) ?>
                            </div>
                            <div class="col-sm-4">
                                <?= Html::submitButton('<i class="bi bi-pencil me-1"></i>' . Yii::t('app', 'Rename'), ['class' => 'btn btn-outline-primary w-100']) ?>
                            </div>
                        <?= Html::endForm() ?>
                    <?php endif; ?>

                    <!-- Members -->
                    <h6 class="text-muted mb-2"><?= Yii::t('app', 'Members') ?></h6>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th><?= Yii::t('app', 'Member') ?></th>
                                    <th><?= Yii::t('app', 'Role') ?></th>
                                    <th><?= Yii::t('app', 'Status') ?></th>
                                    <?php if ($canManageMembers): ?><th class="text-end"><?= Yii::t('app', 'Actions') ?></th><?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($members as $member): ?>
                                    <?php
                                    $displayName = $member->user->username ?? $member->invited_email;
                                    $email = $member->user->email ?? $member->invited_email;
                                    $isSelf = $member->user_id !== null && (int) $member->user_id === (int) Yii::$app->user->id;
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="fw-medium"><?= Html::encode($displayName) ?>
                                                <?php if ($isSelf): ?><span class="text-muted small">(<?= Yii::t('app', 'you') ?>)</span><?php endif; ?>
                                            </div>
                                            <div class="small text-muted"><?= Html::encode($email) ?></div>
                                        </td>
                                        <td>
                                            <?php if ($canManageMembers && !$isSelf && $member->status === WorkspaceMember::STATUS_ACTIVE): ?>
                                                <?= Html::beginForm(['update-role'], 'post', ['class' => 'd-flex gap-1']) ?>
                                                    <?= Html::hiddenInput('member_id', $member->id) ?>
                                                    <?= Html::dropDownList('role', $member->role, WorkspaceMember::roleOptions(), ['class' => 'form-select form-select-sm', 'style' => 'width:auto', 'onchange' => 'this.form.submit()']) ?>
                                                <?= Html::endForm() ?>
                                            <?php else: ?>
                                                <span class="badge <?= $roleBadge[$member->role] ?? 'bg-secondary' ?>"><?= Html::encode($member->getRoleLabel()) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($member->status === WorkspaceMember::STATUS_PENDING): ?>
                                                <span class="badge bg-warning-subtle text-warning"><?= Yii::t('app', 'Pending') ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-success-subtle text-success"><?= Yii::t('app', 'Active') ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <?php if ($canManageMembers): ?>
                                            <td class="text-end">
                                                <?php if (!$isSelf): ?>
                                                    <?= Html::beginForm(['remove-member'], 'post', ['class' => 'd-inline', 'data-confirm' => Yii::t('app', 'Remove this member?')]) ?>
                                                        <?= Html::hiddenInput('member_id', $member->id) ?>
                                                        <?= Html::submitButton('<i class="bi bi-x-lg"></i>', ['class' => 'btn btn-sm btn-outline-danger', 'title' => Yii::t('app', 'Remove')]) ?>
                                                    <?= Html::endForm() ?>
                                                <?php endif; ?>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Invite -->
                    <?php if ($canManageMembers): ?>
                        <hr>
                        <h6 class="text-muted mb-2"><?= Yii::t('app', 'Invite a member') ?></h6>
                        <?= Html::beginForm(['invite'], 'post', ['class' => 'row g-2 align-items-end']) ?>
                            <div class="col-sm-6">
                                <label class="form-label small text-muted mb-1"><?= Yii::t('app', 'Email Address') ?></label>
                                <?= Html::input('email', 'email', '', ['class' => 'form-control', 'placeholder' => 'name@example.com', 'required' => true]) ?>
                            </div>
                            <div class="col-sm-3">
                                <label class="form-label small text-muted mb-1"><?= Yii::t('app', 'Role') ?></label>
                                <?= Html::dropDownList('role', WorkspaceMember::ROLE_MEMBER, $assignableRoles, ['class' => 'form-select']) ?>
                            </div>
                            <div class="col-sm-3">
                                <?= Html::submitButton('<i class="bi bi-send me-1"></i>' . Yii::t('app', 'Invite'), ['class' => 'btn btn-primary w-100']) ?>
                            </div>
                        <?= Html::endForm() ?>
                    <?php endif; ?>
                </div>

                <?php if ($workspace && !$workspace->is_personal): ?>
                    <div class="card-footer bg-transparent d-flex justify-content-between">
                        <?= Html::beginForm(['leave'], 'post', ['data-confirm' => Yii::t('app', 'Leave this workspace?')]) ?>
                            <?= Html::submitButton('<i class="bi bi-box-arrow-right me-1"></i>' . Yii::t('app', 'Leave Workspace'), ['class' => 'btn btn-sm btn-outline-secondary']) ?>
                        <?= Html::endForm() ?>
                        <?php if ($canManageWorkspace): ?>
                            <?= Html::beginForm(['delete'], 'post', ['data-confirm' => Yii::t('app', 'Delete this workspace and all its data? This cannot be undone.')]) ?>
                                <?= Html::submitButton('<i class="bi bi-trash me-1"></i>' . Yii::t('app', 'Delete Workspace'), ['class' => 'btn btn-sm btn-outline-danger']) ?>
                            <?= Html::endForm() ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Your workspaces -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-transparent border-0">
                    <h5 class="card-title mb-0"><i class="bi bi-collection me-2 text-primary"></i><?= Yii::t('app', 'Your Workspaces') ?></h5>
                </div>
                <div class="list-group list-group-flush">
                    <?php foreach ($workspaces as $ws): ?>
                        <?php $isActive = $workspace && $ws->id === $workspace->id; ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <span>
                                <i class="bi <?= $ws->is_personal ? 'bi-person' : 'bi-people' ?> me-2"></i>
                                <?= Html::encode($ws->name) ?>
                            </span>
                            <?php if ($isActive): ?>
                                <span class="badge bg-success-subtle text-success"><?= Yii::t('app', 'Active') ?></span>
                            <?php else: ?>
                                <?= Html::a(Yii::t('app', 'Switch'), ['switch', 'id' => $ws->id], ['class' => 'btn btn-sm btn-outline-primary', 'data-pjax' => '0']) ?>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Create workspace -->
            <div class="card shadow-sm">
                <div class="card-header bg-transparent border-0">
                    <h5 class="card-title mb-0"><i class="bi bi-plus-circle me-2 text-primary"></i><?= Yii::t('app', 'Create Workspace') ?></h5>
                </div>
                <div class="card-body">
                    <?= Html::beginForm(['create'], 'post') ?>
                        <div class="mb-2">
                            <?= Html::textInput('name', '', ['class' => 'form-control', 'placeholder' => Yii::t('app', 'e.g. Marketing Team'), 'maxlength' => 191, 'required' => true]) ?>
                        </div>
                        <?= Html::submitButton('<i class="bi bi-plus-lg me-1"></i>' . Yii::t('app', 'Create Workspace'), ['class' => 'btn btn-primary w-100']) ?>
                    <?= Html::endForm() ?>
                    <p class="text-muted small mt-2 mb-0"><?= Yii::t('app', 'You will be the owner and can invite others.') ?></p>
                </div>
            </div>
        </div>
    </div>
</div>
