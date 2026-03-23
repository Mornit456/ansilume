<?php

declare(strict_types=1);

namespace app\tests\unit\controllers;

use app\components\WorkerHeartbeat;
use app\controllers\HealthController;
use PHPUnit\Framework\TestCase;

/**
 * Tests for HealthController worker check logic.
 *
 * Critical regression covered: before the fix, the worker liveness check
 * was informational only and did not affect the HTTP status or "status" field.
 * A system with no running workers would still return {"status":"ok"} / HTTP 200.
 */
class HealthControllerTest extends TestCase
{
    /**
     * Build a HealthController with a stubbed worker heartbeat list.
     *
     * @param array[] $workers  Raw worker records as returned by WorkerHeartbeat::all().
     */
    private function makeController(array $workers): HealthController
    {
        return new class('health', \Yii::$app, $workers) extends HealthController {
            private array $fakeWorkers;

            public function __construct($id, $module, array $workers) {
                parent::__construct($id, $module);
                $this->fakeWorkers = $workers;
            }

            protected function getWorkerHeartbeats(): array {
                return $this->fakeWorkers;
            }
        };
    }

    private function workerRecord(int $seenAgo = 10): array
    {
        return [
            'worker_id'  => 'host:1234',
            'hostname'   => 'host',
            'started_at' => time() - 60,
            'seen_at'    => time() - $seenAgo,
        ];
    }

    // -------------------------------------------------------------------------
    // checkWorker() — via reflection
    // -------------------------------------------------------------------------

    public function testCheckWorkerReturnsFalseWhenNoWorkers(): void
    {
        $ctrl = $this->makeController([]);
        $ref  = new \ReflectionMethod($ctrl, 'checkWorker');
        $ref->setAccessible(true);

        $result = $ref->invoke($ctrl);

        $this->assertFalse($result['ok']);
        $this->assertArrayHasKey('error', $result);
    }

    public function testCheckWorkerReturnsTrueWhenActiveWorkerPresent(): void
    {
        $ctrl = $this->makeController([$this->workerRecord(seenAgo: 10)]);
        $ref  = new \ReflectionMethod($ctrl, 'checkWorker');
        $ref->setAccessible(true);

        $result = $ref->invoke($ctrl);

        $this->assertTrue($result['ok']);
        $this->assertSame(1, $result['count']);
    }

    public function testCheckWorkerIgnoresStaleWorkers(): void
    {
        // Worker last seen way beyond STALE_AFTER threshold
        $ctrl = $this->makeController([$this->workerRecord(seenAgo: WorkerHeartbeat::STALE_AFTER + 1)]);
        $ref  = new \ReflectionMethod($ctrl, 'checkWorker');
        $ref->setAccessible(true);

        $result = $ref->invoke($ctrl);

        $this->assertFalse($result['ok']);
    }

    public function testCheckWorkerCountsOnlyAliveWorkers(): void
    {
        $workers = [
            $this->workerRecord(seenAgo: 10),                              // alive
            $this->workerRecord(seenAgo: WorkerHeartbeat::STALE_AFTER + 1), // stale
        ];

        $ctrl = $this->makeController($workers);
        $ref  = new \ReflectionMethod($ctrl, 'checkWorker');
        $ref->setAccessible(true);

        $result = $ref->invoke($ctrl);

        $this->assertTrue($result['ok']);
        $this->assertSame(1, $result['count']);
    }

    // -------------------------------------------------------------------------
    // runChecks() — worker key must be present
    // -------------------------------------------------------------------------

    public function testRunChecksIncludesWorkerKey(): void
    {
        $ctrl = $this->makeController([]);
        $ref  = new \ReflectionMethod($ctrl, 'runChecks');
        $ref->setAccessible(true);

        $checks = $ref->invoke($ctrl);

        $this->assertArrayHasKey('worker', $checks,
            'runChecks() must include a worker check so missing workers affect the health status.'
        );
    }

    // -------------------------------------------------------------------------
    // Full action — HTTP status reflects worker state
    // -------------------------------------------------------------------------

    public function testActionIndexReturnsOkStatusWhenWorkerAlive(): void
    {
        $ctrl = new class('health', \Yii::$app, [$this->workerRecord()]) extends HealthController {
            public int    $capturedStatus = 0;
            private array $fakeWorkers;
            public function __construct($id, $module, array $w) {
                parent::__construct($id, $module);
                $this->fakeWorkers = $w;
            }
            protected function getWorkerHeartbeats(): array { return $this->fakeWorkers; }
            protected function checkDatabase(): array { return ['ok' => true]; }
            protected function checkRedis(): array    { return ['ok' => true]; }
            protected function setHttpStatus(int $code): void { $this->capturedStatus = $code; }
        };

        $response = $ctrl->actionIndex();

        $this->assertSame('ok', $response['status']);
        $this->assertSame(200, $ctrl->capturedStatus);
    }

    public function testActionIndexReturnsDegradedWhenNoWorkers(): void
    {
        $ctrl = new class('health', \Yii::$app) extends HealthController {
            public int $capturedStatus = 0;
            protected function getWorkerHeartbeats(): array { return []; }
            protected function checkDatabase(): array { return ['ok' => true]; }
            protected function checkRedis(): array    { return ['ok' => true]; }
            protected function setHttpStatus(int $code): void { $this->capturedStatus = $code; }
        };

        $response = $ctrl->actionIndex();

        $this->assertSame('degraded', $response['status'],
            'Health status must be degraded when no workers are alive.'
        );
        $this->assertSame(503, $ctrl->capturedStatus,
            'HTTP status must be 503 when no workers are alive.'
        );
        $this->assertFalse($response['checks']['worker']['ok']);
    }
}
