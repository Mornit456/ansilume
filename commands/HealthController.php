<?php

declare(strict_types=1);

namespace app\commands;

use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Application health check for Docker healthchecks and monitoring.
 *
 * Usage:
 *   php yii health/check   — exits 0 (healthy) or 1 (degraded)
 */
class HealthController extends Controller
{
    public function actionCheck(): int
    {
        $healthy = true;

        // Database
        try {
            \Yii::$app->db->createCommand('SELECT 1')->queryScalar();
            $this->stdout("[health] db: ok\n");
        } catch (\Throwable $e) {
            $this->stderr("[health] db: error — " . $e->getMessage() . "\n");
            $healthy = false;
        }

        // Redis
        try {
            \Yii::$app->cache->set('health_probe', 1, 5);
            $this->stdout("[health] redis: ok\n");
        } catch (\Throwable $e) {
            $this->stderr("[health] redis: error — " . $e->getMessage() . "\n");
            $healthy = false;
        }

        if (!$healthy) {
            $this->stderr("[health] status: degraded\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->stdout("[health] status: ok\n");
        return ExitCode::OK;
    }
}
