<?php

declare(strict_types=1);

/**
 * Password reset email — plain text version.
 *
 * @var yii\web\View $this
 * @var app\models\User $user
 * @var string $resetUrl
 * @var int $expireMinutes
 */
?>
Password Reset

Hi <?= $user->username ?>,

We received a request to reset your Ansilume password.

Click the link below to set a new password:

    <?= $resetUrl ?>

This link expires in <?= $expireMinutes ?> minutes.

If you did not request a password reset, you can safely
ignore this email. Your password will not be changed.
