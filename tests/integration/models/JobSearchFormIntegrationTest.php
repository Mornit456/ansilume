<?php

declare(strict_types=1);

namespace app\tests\integration\models;

use app\models\Job;
use app\models\JobSearchForm;
use app\tests\integration\DbTestCase;
use yii\data\ActiveDataProvider;

/**
 * Integration tests for JobSearchForm::search() — verifies filtering logic
 * against real database rows.
 */
class JobSearchFormIntegrationTest extends DbTestCase
{
    public function testSearchReturnsActiveDataProvider(): void
    {
        $form   = new JobSearchForm();
        $result = $form->search([]);

        $this->assertInstanceOf(ActiveDataProvider::class, $result);
    }

    public function testSearchWithNoFiltersReturnsAllJobs(): void
    {
        [$template, $user] = $this->makeFixtures();
        $this->createJob($template->id, $user->id, Job::STATUS_QUEUED);
        $this->createJob($template->id, $user->id, Job::STATUS_RUNNING);

        $form     = new JobSearchForm();
        $provider = $form->search([]);

        $this->assertGreaterThanOrEqual(2, $provider->getTotalCount());
    }

    public function testSearchFiltersbyStatus(): void
    {
        [$template, $user] = $this->makeFixtures();
        $this->createJob($template->id, $user->id, Job::STATUS_QUEUED);
        $this->createJob($template->id, $user->id, Job::STATUS_SUCCEEDED);

        $form     = new JobSearchForm();
        $provider = $form->search(['status' => Job::STATUS_QUEUED]);

        foreach ($provider->getModels() as $job) {
            $this->assertSame(Job::STATUS_QUEUED, $job->status);
        }
    }

    public function testSearchFiltersByTemplateId(): void
    {
        [$templateA, $user] = $this->makeFixtures();
        [$templateB]        = $this->makeFixtures();
        $this->createJob($templateA->id, $user->id);
        $this->createJob($templateB->id, $user->id);

        $form     = new JobSearchForm();
        $provider = $form->search(['template_id' => $templateA->id]);

        foreach ($provider->getModels() as $job) {
            $this->assertSame($templateA->id, $job->job_template_id);
        }
    }

    public function testSearchFiltersByLaunchedBy(): void
    {
        [$template, $userA] = $this->makeFixtures();
        $userB = $this->createUser('other');
        $this->createJob($template->id, $userA->id);
        $this->createJob($template->id, $userB->id);

        $form     = new JobSearchForm();
        $provider = $form->search(['launched_by' => $userA->id]);

        foreach ($provider->getModels() as $job) {
            $this->assertSame($userA->id, $job->launched_by);
        }
    }

    public function testSearchWithDateFromFiltersOldJobs(): void
    {
        [$template, $user] = $this->makeFixtures();
        $this->createJob($template->id, $user->id);

        // Tomorrow's date — no jobs created today should appear
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        $form     = new JobSearchForm();
        $provider = $form->search(['date_from' => $tomorrow]);

        $ids = array_column($provider->getModels(), 'id');
        $this->assertEmpty($ids, 'No jobs should match a future date_from');
    }

    public function testSearchWithDateToFiltersNewJobs(): void
    {
        [$template, $user] = $this->makeFixtures();
        $this->createJob($template->id, $user->id);

        // Yesterday's date — jobs created today should not appear
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $form      = new JobSearchForm();
        $provider  = $form->search(['date_to' => $yesterday]);

        $ids = array_column($provider->getModels(), 'id');
        $this->assertEmpty($ids, 'No jobs should match a past date_to');
    }

    public function testSearchIgnoresInvalidStatusFilter(): void
    {
        $form   = new JobSearchForm();
        // Invalid status should not throw, just ignore
        $result = $form->search(['status' => 'nonexistent_status']);
        $this->assertInstanceOf(ActiveDataProvider::class, $result);
    }

    // -------------------------------------------------------------------------

    private function makeFixtures(): array
    {
        $user     = $this->createUser();
        $group    = $this->createRunnerGroup($user->id);
        $project  = $this->createProject($user->id);
        $inv      = $this->createInventory($user->id);
        $template = $this->createJobTemplate($project->id, $inv->id, $group->id, $user->id);
        return [$template, $user];
    }
}
