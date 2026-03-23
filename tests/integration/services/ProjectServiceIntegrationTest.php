<?php

declare(strict_types=1);

namespace app\tests\integration\services;

use app\models\Project;
use app\services\ProjectService;
use app\tests\integration\DbTestCase;

/**
 * Integration tests for ProjectService::sync() with manual projects,
 * which requires no real git operations.
 */
class ProjectServiceIntegrationTest extends DbTestCase
{
    private ProjectService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = \Yii::$app->get('projectService');
    }

    public function testSyncManualProjectSetsStatusSynced(): void
    {
        $user    = $this->createUser();
        $project = $this->createProject($user->id);
        // createProject sets scm_type = SCM_TYPE_MANUAL

        $this->service->sync($project);

        $project->refresh();
        $this->assertSame(Project::STATUS_SYNCED, $project->status);
    }

    public function testSyncManualProjectSetsLastSyncedAt(): void
    {
        $before  = time();
        $user    = $this->createUser();
        $project = $this->createProject($user->id);

        $this->service->sync($project);

        $project->refresh();
        $this->assertGreaterThanOrEqual($before, (int)$project->last_synced_at);
    }

    public function testSyncManualProjectDoesNotRequireScmUrl(): void
    {
        $user    = $this->createUser();
        $project = $this->createProject($user->id);
        $project->scm_url = null;
        $project->save(false);

        // Should not throw
        $this->service->sync($project);

        $project->refresh();
        $this->assertSame(Project::STATUS_SYNCED, $project->status);
    }

    public function testLocalPathCombinesWorkspaceAndProjectId(): void
    {
        $user    = $this->createUser();
        $project = $this->createProject($user->id);

        $path = $this->service->localPath($project);

        $this->assertStringEndsWith('/' . $project->id, $path);
    }

    public function testSyncGitProjectWithoutUrlThrowsRuntimeException(): void
    {
        $user    = $this->createUser();
        $project = $this->createProject($user->id);
        $project->scm_type = Project::SCM_TYPE_GIT;
        $project->scm_url  = '';
        $project->save(false);

        $this->expectException(\RuntimeException::class);
        $this->service->sync($project);
    }
}
