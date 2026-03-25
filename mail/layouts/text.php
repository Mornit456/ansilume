<?php

declare(strict_types=1);

/**
 * Plain-text email layout for Ansilume.
 *
 * @var yii\web\View $this
 * @var string $content
 */

$appName = Yii::$app->name ?? 'Ansilume';
?>
<?= $appName ?>

<?= str_repeat('─', 50) ?>

<?= $content ?>

<?= str_repeat('─', 50) ?>
This is an automated message from <?= $appName ?>.
Please do not reply to this email.
