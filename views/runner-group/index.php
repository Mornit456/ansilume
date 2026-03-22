<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\models\RunnerGroup[] $groups */
/** @var array $total   group_id → total runner count */
/** @var array $online  group_id → online runner count */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Runner Groups';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">Runner Groups</h2>
    <?php if (\Yii::$app->user->can('runner-group.create')): ?>
        <?= Html::a('New Group', ['create'], ['class' => 'btn btn-success']) ?>
    <?php endif; ?>
</div>

<?php if (empty($groups)): ?>
    <div class="card">
        <div class="card-body text-muted">
            No runner groups yet. <a href="<?= Url::to(['create']) ?>">Create one</a> to get started.
        </div>
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Name</th>
                    <th>Description</th>
                    <th class="text-center">Runners</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($groups as $group): ?>
                <?php
                $cnt    = $total[$group->id]  ?? 0;
                $on     = $online[$group->id] ?? 0;
                $allOff = $cnt > 0 && $on === 0;
                $badge  = $allOff ? 'danger' : ($on > 0 ? 'success' : 'secondary');
                ?>
                <tr>
                    <td><?= Html::a(Html::encode($group->name), ['view', 'id' => $group->id]) ?></td>
                    <td class="text-muted small"><?= Html::encode($group->description ?? '') ?></td>
                    <td class="text-center">
                        <span class="badge text-bg-<?= $badge ?>"><?= $on ?>/<?= $cnt ?> online</span>
                    </td>
                    <td class="text-end">
                        <?php if (\Yii::$app->user->can('runner-group.update')): ?>
                            <?= Html::a('Edit', ['update', 'id' => $group->id], ['class' => 'btn btn-sm btn-outline-secondary me-1']) ?>
                        <?php endif; ?>
                        <?php if (\Yii::$app->user->can('runner-group.delete')): ?>
                            <?= Html::a('Delete', ['delete', 'id' => $group->id], [
                                'class' => 'btn btn-sm btn-outline-danger',
                                'data'  => ['method' => 'post', 'confirm' => 'Delete this runner group and all its runners?'],
                            ]) ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
