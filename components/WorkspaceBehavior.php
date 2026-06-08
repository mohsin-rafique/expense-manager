<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

namespace app\components;

use Yii;
use yii\base\Behavior;
use yii\db\ActiveRecord;

/**
 * WorkspaceBehavior stamps the active workspace id on a record before insert.
 *
 * Attach to any workspace-owned ActiveRecord (Expense, Income, categories,
 * Budget). If the model already has a workspace_id set (e.g. by an import or a
 * console command), it is left untouched. In contexts without a resolvable
 * workspace (console/guest), nothing is set and the caller is responsible.
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.0.0
 */
class WorkspaceBehavior extends Behavior
{
    /** @var string The workspace foreign-key attribute */
    public string $attribute = 'workspace_id';

    /**
     * {@inheritdoc}
     */
    public function events(): array
    {
        // BEFORE_VALIDATE so workspace-scoped unique rules and validators see
        // the value; BEFORE_INSERT as a safety net for save(false) paths.
        return [
            ActiveRecord::EVENT_BEFORE_VALIDATE => 'onBeforeInsert',
            ActiveRecord::EVENT_BEFORE_INSERT => 'onBeforeInsert',
        ];
    }

    /**
     * Sets the workspace id from the active workspace when not already present.
     *
     * @param \yii\base\Event $event
     */
    public function onBeforeInsert($event): void
    {
        $owner = $this->owner;

        if (!empty($owner->{$this->attribute})) {
            return;
        }

        if (Yii::$app instanceof \yii\web\Application && Yii::$app->has('workspace')) {
            $workspaceId = Yii::$app->workspace->getId();
            if ($workspaceId !== null) {
                $owner->{$this->attribute} = $workspaceId;
            }
        }
    }
}
