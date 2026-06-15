<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 - 2026 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace app\components;

use Yii;
use yii\base\ActionFilter;
use yii\web\ForbiddenHttpException;

/**
 * RequireWorkspaceCapability is an action filter that enforces a workspace
 * capability (e.g. CAN_MANAGE_DATA) for the guarded actions.
 *
 * Attach it to a controller and restrict it with `only`/`except` so that, for
 * example, viewers (who lack CAN_MANAGE_DATA) cannot reach create/update/delete
 * actions. AJAX requests receive a JSON error envelope; normal requests get a
 * 403.
 *
 * Usage:
 * ```php
 * 'workspaceWrite' => [
 *     'class' => RequireWorkspaceCapability::class,
 *     'capability' => WorkspaceMember::CAN_MANAGE_DATA,
 *     'only' => ['create', 'update', 'delete'],
 * ],
 * ```
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */
class RequireWorkspaceCapability extends ActionFilter
{
    /** @var string The required capability (WorkspaceMember::CAN_*) */
    public string $capability;

    /** @var string Message shown when access is denied */
    public ?string $message = null;

    /**
     * {@inheritdoc}
     *
     * @param \yii\base\Action $action
     * @return bool
     * @throws ForbiddenHttpException
     */
    public function beforeAction($action): bool
    {
        if (Yii::$app->workspace->can($this->capability)) {
            return true;
        }

        $message = $this->message ?? Yii::t('app', 'Your role in this workspace does not allow this action.');

        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            Yii::$app->response->data = \app\components\ApiResponse::error($message);
            Yii::$app->response->statusCode = 403;
            return false;
        }

        throw new ForbiddenHttpException($message);
    }
}
