<?php

/**
 * @link https://github.com/mohsin-rafique/expense-manager
 * @copyright Copyright (c) 2025 - 2026 Mohsin Rafique
 * @license https://opensource.org/licenses/MIT MIT License
 */

/**
 * Banks management view.
 *
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 *
 * @author Mohsin Rafique <mohsin.rafique@gmail.com>
 * @since 1.2.0
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = Yii::t('app', 'Banks');
$this->params['breadcrumbs'][] = $this->title;

$canManage = Yii::$app->workspace->can(\app\models\WorkspaceMember::CAN_MANAGE_DATA);
$banks = $dataProvider->getModels();
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1"><i class="bi bi-bank2 me-2 text-danger"></i><?= Html::encode($this->title) ?></h1>
        <p class="text-muted mb-0"><?= Yii::t('app', 'Banks you can tag on Card and Bank Transfer expenses.') ?></p>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <?php if ($canManage): ?>
            <div class="input-group mb-3" style="max-width: 460px;">
                <span class="input-group-text"><i class="bi bi-plus-lg"></i></span>
                <input type="text" class="form-control" id="bank-name" maxlength="191" autocomplete="off"
                    placeholder="<?= Yii::t('app', 'Add a bank (e.g. Standard Chartered)') ?>">
                <button class="btn btn-danger" type="button" id="bank-save"><?= Yii::t('app', 'Add') ?></button>
            </div>
            <div class="text-danger small mb-3 d-none" id="bank-error"></div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th><?= Yii::t('app', 'Bank') ?></th>
                        <?php if ($canManage): ?>
                            <th class="text-end" style="width: 100px;"><?= Yii::t('app', 'Action') ?></th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($banks)): ?>
                        <tr>
                            <td colspan="2" class="text-center text-muted py-4">
                                <?= Yii::t('app', 'No banks yet. Add one above, or from the expense form.') ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($banks as $bank): ?>
                            <tr>
                                <td><i class="bi bi-bank2 me-2 text-info"></i><?= Html::encode($bank->name) ?></td>
                                <?php if ($canManage): ?>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-danger bank-delete"
                                            data-id="<?= $bank->id ?>" data-name="<?= Html::encode($bank->name) ?>"
                                            title="<?= Yii::t('app', 'Delete') ?>">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
if ($canManage) {
    $cfg = json_encode([
        'createUrl' => Url::to(['/bank/create']),
        'deleteBase' => Url::to(['/bank/delete']),
        'csrfParam' => Yii::$app->request->csrfParam,
        'csrfToken' => Yii::$app->request->csrfToken,
        'confirmDelete' => Yii::t('app', 'Delete this bank? Expenses tagged with it will keep their other details but lose the bank.'),
        'failMsg' => Yii::t('app', 'Something went wrong. Please try again.'),
    ]);
    $js = <<<JS
(function () {
    var CFG = {$cfg};
    function post(url, extra) {
        var body = encodeURIComponent(CFG.csrfParam) + '=' + encodeURIComponent(CFG.csrfToken);
        for (var k in extra) { body += '&' + encodeURIComponent(k) + '=' + encodeURIComponent(extra[k]); }
        return fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: body
        }).then(function (r) { return r.json(); });
    }

    var nameEl = document.getElementById('bank-name');
    var saveEl = document.getElementById('bank-save');
    var errEl = document.getElementById('bank-error');
    function showErr(m) { errEl.textContent = m; errEl.classList.remove('d-none'); }

    function add() {
        var name = (nameEl.value || '').trim();
        if (!name) { nameEl.focus(); return; }
        saveEl.disabled = true;
        post(CFG.createUrl, { name: name }).then(function (d) {
            saveEl.disabled = false;
            if (d && d.status === 'success') { window.location.reload(); }
            else { showErr((d && d.errors && d.errors.name) ? d.errors.name[0] : ((d && d.message) || CFG.failMsg)); }
        }).catch(function () { saveEl.disabled = false; showErr(CFG.failMsg); });
    }
    saveEl.addEventListener('click', add);
    nameEl.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); add(); } });

    document.querySelectorAll('.bank-delete').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (!window.confirm(CFG.confirmDelete)) { return; }
            btn.disabled = true;
            post(CFG.deleteBase + '/' + btn.getAttribute('data-id'), {}).then(function (d) {
                if (d && d.status === 'success') { window.location.reload(); }
                else { btn.disabled = false; showErr((d && d.message) || CFG.failMsg); }
            }).catch(function () { btn.disabled = false; showErr(CFG.failMsg); });
        });
    });
})();
JS;
    $this->registerJs($js);
}
?>
