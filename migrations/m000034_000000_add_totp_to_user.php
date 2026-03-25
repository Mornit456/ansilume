<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Adds TOTP two-factor authentication fields to the user table.
 *
 * - totp_secret: AES-256-CBC encrypted TOTP shared secret (nullable)
 * - totp_enabled: whether 2FA is active for this user
 * - recovery_codes: JSON array of bcrypt-hashed one-time recovery codes
 */
class m000034_000000_add_totp_to_user extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn('{{%user}}', 'totp_secret', $this->string(512)->null()->after('password_reset_token'));
        $this->addColumn('{{%user}}', 'totp_enabled', $this->boolean()->notNull()->defaultValue(false)->after('totp_secret'));
        $this->addColumn('{{%user}}', 'recovery_codes', $this->text()->null()->after('totp_enabled'));
    }

    public function safeDown(): void
    {
        $this->dropColumn('{{%user}}', 'recovery_codes');
        $this->dropColumn('{{%user}}', 'totp_enabled');
        $this->dropColumn('{{%user}}', 'totp_secret');
    }
}
