<?php

declare(strict_types=1);

namespace app\tests\unit\services\audit;

use app\services\AuditService;
use app\services\audit\AuditTargetInterface;
use PHPUnit\Framework\TestCase;

class AuditServiceDispatchTest extends TestCase
{
    public function testLogDispatchesToAllTargets(): void
    {
        $targetA = $this->createMock(AuditTargetInterface::class);
        $targetB = $this->createMock(AuditTargetInterface::class);

        $targetA->expects($this->once())->method('send');
        $targetB->expects($this->once())->method('send');

        $service = new AuditService();
        $service->targets = [$targetA, $targetB];

        $service->log('test.dispatch', 'test', 1);
    }

    public function testFailingTargetDoesNotBlockOthers(): void
    {
        $badTarget  = $this->createMock(AuditTargetInterface::class);
        $goodTarget = $this->createMock(AuditTargetInterface::class);

        $badTarget->method('send')->willThrowException(new \RuntimeException('fail'));
        $goodTarget->expects($this->once())->method('send');

        $service = new AuditService();
        $service->targets = [$badTarget, $goodTarget];

        // Should not throw — bad target failure is logged, good target still runs
        $service->log('test.resilience', 'test', 1);
    }

    public function testLogBuildsCorrectEntryShape(): void
    {
        $captured = null;
        $target   = $this->createMock(AuditTargetInterface::class);
        $target->expects($this->once())
            ->method('send')
            ->willReturnCallback(function (array $entry) use (&$captured) {
                $captured = $entry;
            });

        $service = new AuditService();
        $service->targets = [$target];

        $service->log('user.created', 'user', 5, 99, ['username' => 'alice']);

        $this->assertSame('user.created', $captured['action']);
        $this->assertSame('user', $captured['object_type']);
        $this->assertSame(5, $captured['object_id']);
        $this->assertSame(99, $captured['user_id']);
        $this->assertIsInt($captured['created_at']);

        $meta = json_decode($captured['metadata'], true);
        $this->assertSame('alice', $meta['username']);
    }

    public function testEmptyTargetsDoesNotThrow(): void
    {
        $service = new AuditService();
        $service->targets = [];

        $service->log('test.no_targets');
        $this->assertTrue(true); // no exception = pass
    }
}
