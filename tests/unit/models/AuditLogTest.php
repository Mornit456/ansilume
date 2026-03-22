<?php

declare(strict_types=1);

namespace app\tests\unit\models;

use app\models\AuditLog;
use PHPUnit\Framework\TestCase;

/**
 * Tests for AuditLog model — immutability guard and action constants.
 * No database required.
 */
class AuditLogTest extends TestCase
{
    public function testUpdateThrowsLogicException(): void
    {
        $log = new AuditLog();
        $this->expectException(\LogicException::class);
        $log->update();
    }

    public function testUpdateExceptionMessageMentionsImmutable(): void
    {
        $log = new AuditLog();
        try {
            $log->update();
            $this->fail('LogicException not thrown');
        } catch (\LogicException $e) {
            $this->assertStringContainsStringIgnoringCase('immutable', $e->getMessage());
        }
    }

    public function testTableName(): void
    {
        $this->assertSame('{{%audit_log}}', AuditLog::tableName());
    }

    public function testActionUserLoginConstant(): void
    {
        $this->assertSame('user.login', AuditLog::ACTION_USER_LOGIN);
    }

    public function testActionUserLogoutConstant(): void
    {
        $this->assertSame('user.logout', AuditLog::ACTION_USER_LOGOUT);
    }

    public function testActionUserLoginFailedConstant(): void
    {
        $this->assertSame('user.login.failed', AuditLog::ACTION_USER_LOGIN_FAILED);
    }

    public function testActionJobLaunchedConstant(): void
    {
        $this->assertSame('job.launched', AuditLog::ACTION_JOB_LAUNCHED);
    }

    public function testActionJobCanceledConstant(): void
    {
        $this->assertSame('job.canceled', AuditLog::ACTION_JOB_CANCELED);
    }

    public function testActionJobStartedConstant(): void
    {
        $this->assertSame('job.started', AuditLog::ACTION_JOB_STARTED);
    }

    public function testActionJobFinishedConstant(): void
    {
        $this->assertSame('job.finished', AuditLog::ACTION_JOB_FINISHED);
    }

    public function testActionCredentialCreatedConstant(): void
    {
        $this->assertSame('credential.created', AuditLog::ACTION_CREDENTIAL_CREATED);
    }

    public function testActionCredentialUpdatedConstant(): void
    {
        $this->assertSame('credential.updated', AuditLog::ACTION_CREDENTIAL_UPDATED);
    }

    public function testActionCredentialDeletedConstant(): void
    {
        $this->assertSame('credential.deleted', AuditLog::ACTION_CREDENTIAL_DELETED);
    }

    public function testAllActionConstantsAreUnique(): void
    {
        $constants = [
            AuditLog::ACTION_USER_LOGIN,
            AuditLog::ACTION_USER_LOGOUT,
            AuditLog::ACTION_USER_LOGIN_FAILED,
            AuditLog::ACTION_JOB_LAUNCHED,
            AuditLog::ACTION_JOB_CANCELED,
            AuditLog::ACTION_JOB_STARTED,
            AuditLog::ACTION_JOB_FINISHED,
            AuditLog::ACTION_CREDENTIAL_CREATED,
            AuditLog::ACTION_CREDENTIAL_UPDATED,
            AuditLog::ACTION_CREDENTIAL_DELETED,
        ];
        $this->assertSame(count($constants), count(array_unique($constants)));
    }
}
