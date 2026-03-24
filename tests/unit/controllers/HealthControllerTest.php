<?php

declare(strict_types=1);

namespace app\tests\unit\controllers;

use app\controllers\HealthController;
use PHPUnit\Framework\TestCase;

/**
 * Tests for HealthController runner check logic.
 *
 * The health endpoint checks database, Redis, and runner availability.
 * Runner counts come from the database (Runner model with last_seen_at).
 * These tests stub getRunnerCounts() to control the runner state.
 */
class HealthControllerTest extends TestCase
{
    /**
     * Build a HealthController with stubbed runner counts and optional DB/Redis stubs.
     */
    private function makeController(array $runnerCounts, bool $dbOk = true, bool $redisOk = true): HealthController
    {
        return new class('health', \Yii::$app, $runnerCounts, $dbOk, $redisOk) extends HealthController {
            public int    $capturedStatus = 0;
            private array $fakeRunnerCounts;
            private bool  $fakeDbOk;
            private bool  $fakeRedisOk;

            public function __construct($id, $module, array $rc, bool $dbOk, bool $redisOk) {
                parent::__construct($id, $module);
                $this->fakeRunnerCounts = $rc;
                $this->fakeDbOk         = $dbOk;
                $this->fakeRedisOk      = $redisOk;
            }

            protected function getRunnerCounts(): array { return $this->fakeRunnerCounts; }
            protected function setHttpStatus(int $code): void { $this->capturedStatus = $code; }

            // Expose private check methods for testing
            public function testCheckRunners(): array {
                return $this->checkRunners();
            }
        };
    }

    // ── checkRunners() ─────────────────────────────────────────────────────

    public function testCheckRunnersReturnsFalseWhenNoRunners(): void
    {
        $ctrl = $this->makeController(['total' => 0, 'online' => 0, 'offline' => 0]);
        $result = $ctrl->testCheckRunners();

        $this->assertFalse($result['ok']);
        $this->assertArrayHasKey('error', $result);
    }

    public function testCheckRunnersReturnsFalseWhenAllOffline(): void
    {
        $ctrl = $this->makeController(['total' => 4, 'online' => 0, 'offline' => 4]);
        $result = $ctrl->testCheckRunners();

        $this->assertFalse($result['ok']);
    }

    public function testCheckRunnersReturnsTrueWhenRunnersOnline(): void
    {
        $ctrl = $this->makeController(['total' => 4, 'online' => 2, 'offline' => 2]);
        $result = $ctrl->testCheckRunners();

        $this->assertTrue($result['ok']);
        $this->assertSame(2, $result['online']);
        $this->assertSame(4, $result['total']);
    }

    // ── runChecks() structure ──────────────────────────────────────────────

    public function testRunChecksIncludesRunnersKey(): void
    {
        $ctrl = $this->makeController(['total' => 1, 'online' => 1, 'offline' => 0]);
        $ref  = new \ReflectionMethod($ctrl, 'runChecks');
        $ref->setAccessible(true);

        $checks = $ref->invoke($ctrl);

        $this->assertArrayHasKey('runners', $checks,
            'runChecks() must include a runners check so missing runners affect the health status.'
        );
        $this->assertArrayHasKey('database', $checks);
        $this->assertArrayHasKey('redis', $checks);
    }

    // ── Full action — HTTP status ──────────────────────────────────────────

    public function testActionIndexReturnsOkWhenRunnersOnline(): void
    {
        $ctrl = $this->makeController(['total' => 2, 'online' => 2, 'offline' => 0]);
        $response = $ctrl->actionIndex();

        $this->assertSame('ok', $response['status']);
        $this->assertSame(200, $ctrl->capturedStatus);
    }

    public function testActionIndexReturnsDegradedWhenNoRunnersOnline(): void
    {
        $ctrl = $this->makeController(['total' => 2, 'online' => 0, 'offline' => 2]);
        $response = $ctrl->actionIndex();

        $this->assertSame('degraded', $response['status']);
        $this->assertSame(503, $ctrl->capturedStatus);
        $this->assertFalse($response['checks']['runners']['ok']);
    }

    public function testActionIndexReturnsDegradedWhenNoRunnersRegistered(): void
    {
        $ctrl = $this->makeController(['total' => 0, 'online' => 0, 'offline' => 0]);
        $response = $ctrl->actionIndex();

        $this->assertSame('degraded', $response['status']);
        $this->assertSame(503, $ctrl->capturedStatus);
    }

    // ── Response structure ─────────────────────────────────────────────────

    public function testResponseIncludesRunnersSection(): void
    {
        $ctrl = $this->makeController(['total' => 4, 'online' => 2, 'offline' => 2]);
        $response = $ctrl->actionIndex();

        $this->assertArrayHasKey('runners', $response);
        $this->assertSame(4, $response['runners']['total']);
        $this->assertSame(2, $response['runners']['online']);
        $this->assertSame(2, $response['runners']['offline']);
    }

    public function testResponseIncludesQueueSection(): void
    {
        $ctrl = $this->makeController(['total' => 1, 'online' => 1, 'offline' => 0]);
        $response = $ctrl->actionIndex();

        $this->assertArrayHasKey('queue', $response);
        $this->assertArrayHasKey('pending', $response['queue']);
        $this->assertArrayHasKey('running', $response['queue']);
    }
}
