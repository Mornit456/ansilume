<?php

declare(strict_types=1);

namespace app\tests\unit\models;

use app\models\PasswordResetForm;
use app\models\PasswordResetRequestForm;
use app\models\User;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for password reset token logic and form validation.
 */
class PasswordResetTest extends TestCase
{
    // ── Token validation logic (static, no DB) ──────────────────────────────

    public function testTokenIsValidWhenFresh(): void
    {
        $user = $this->createUserStub();
        $user->password_reset_token = 'abc123_' . time();
        $this->assertTrue($user->isPasswordResetTokenValid());
    }

    public function testTokenIsInvalidWhenExpired(): void
    {
        $user = $this->createUserStub();
        $expiredTimestamp = time() - User::PASSWORD_RESET_TOKEN_EXPIRE - 1;
        $user->password_reset_token = 'abc123_' . $expiredTimestamp;
        $this->assertFalse($user->isPasswordResetTokenValid());
    }

    public function testTokenIsInvalidWhenNull(): void
    {
        $user = $this->createUserStub();
        $user->password_reset_token = null;
        $this->assertFalse($user->isPasswordResetTokenValid());
    }

    public function testTokenIsInvalidWhenEmpty(): void
    {
        $user = $this->createUserStub();
        $user->password_reset_token = '';
        $this->assertFalse($user->isPasswordResetTokenValid());
    }

    public function testTokenIsValidAtExactExpiry(): void
    {
        $user = $this->createUserStub();
        $user->password_reset_token = 'abc123_' . (time() - User::PASSWORD_RESET_TOKEN_EXPIRE);
        $this->assertTrue($user->isPasswordResetTokenValid());
    }

    public function testFindByPasswordResetTokenReturnsNullForEmpty(): void
    {
        $this->assertNull(User::findByPasswordResetToken(''));
    }

    public function testFindByPasswordResetTokenReturnsNullForExpired(): void
    {
        $expiredTimestamp = time() - User::PASSWORD_RESET_TOKEN_EXPIRE - 1;
        $this->assertNull(User::findByPasswordResetToken('abc123_' . $expiredTimestamp));
    }

    public function testPasswordResetTokenExpireIsReasonable(): void
    {
        // Should be at least 15 minutes and at most 24 hours
        $this->assertGreaterThanOrEqual(900, User::PASSWORD_RESET_TOKEN_EXPIRE);
        $this->assertLessThanOrEqual(86400, User::PASSWORD_RESET_TOKEN_EXPIRE);
    }

    // ── PasswordResetRequestForm validation ─────────────────────────────────

    public function testRequestFormRequiresEmail(): void
    {
        $form = new PasswordResetRequestForm();
        $form->email = '';
        $this->assertFalse($form->validate());
        $this->assertArrayHasKey('email', $form->errors);
    }

    public function testRequestFormRejectsInvalidEmail(): void
    {
        $form = new PasswordResetRequestForm();
        $form->email = 'not-an-email';
        $this->assertFalse($form->validate());
        $this->assertArrayHasKey('email', $form->errors);
    }

    public function testRequestFormAcceptsValidEmail(): void
    {
        $form = new PasswordResetRequestForm();
        $form->email = 'user@example.com';
        $this->assertTrue($form->validate());
    }

    // ── PasswordResetForm validation ────────────────────────────────────────

    public function testResetFormRejectsInvalidToken(): void
    {
        $this->expectException(\yii\base\InvalidArgumentException::class);
        new PasswordResetForm('invalid_0');
    }

    public function testResetFormRejectsExpiredToken(): void
    {
        $expired = time() - User::PASSWORD_RESET_TOKEN_EXPIRE - 100;
        $this->expectException(\yii\base\InvalidArgumentException::class);
        new PasswordResetForm("abc_{$expired}");
    }

    // ── helpers ──────────────────────────────────────────────────────────────

    private function createUserStub(): User
    {
        $user = new class extends User {
            private array $_data = [];

            public function init(): void
            {
            }

            public static function getTableSchema(): ?\yii\db\TableSchema
            {
                return null;
            }

            public function __set($name, $value)
            {
                $this->_data[$name] = $value;
            }

            public function __get($name)
            {
                return $this->_data[$name] ?? null;
            }

            public function __isset($name)
            {
                return isset($this->_data[$name]);
            }
        };

        return $user;
    }
}
