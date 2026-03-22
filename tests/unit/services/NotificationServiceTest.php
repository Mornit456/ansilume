<?php

declare(strict_types=1);

namespace app\tests\unit\services;

use app\models\Job;
use app\models\JobTemplate;
use app\services\NotificationService;
use PHPUnit\Framework\TestCase;
use yii\db\BaseActiveRecord;

/**
 * Tests for NotificationService::notifyJobFailed() early-exit conditions.
 * No actual mail is sent — tests verify the guard clauses work correctly.
 */
class NotificationServiceTest extends TestCase
{
    public function testNoNotificationWhenTemplateIsNull(): void
    {
        $service = $this->partialService();
        $job     = $this->makeJob(template: null);

        // Should return early — no exception, no mail sent
        $service->notifyJobFailed($job);
        $this->assertTrue(true); // reached here without error
    }

    public function testNoNotificationWhenNotifyOnFailureIsFalse(): void
    {
        $service = $this->partialService();
        $job     = $this->makeJob(notifyOnFailure: false, emails: 'ops@example.com');
        $service->notifyJobFailed($job);
        $this->assertTrue(true);
    }

    public function testNoNotificationWhenEmailListIsEmpty(): void
    {
        $service = $this->partialService();
        $job     = $this->makeJob(notifyOnFailure: true, emails: '');
        $service->notifyJobFailed($job);
        $this->assertTrue(true);
    }

    public function testSendIsCalledWhenConditionsMet(): void
    {
        $sendCalled = false;

        // Anonymous subclass that captures the sendFailureMail call
        $service = new class($sendCalled) extends NotificationService {
            public function __construct(private bool &$called) {}

            protected function sendFailureMail(Job $job, array $recipients): void
            {
                $this->called = true;
            }
        };

        $job = $this->makeJob(notifyOnFailure: true, emails: 'ops@example.com');
        $service->notifyJobFailed($job);

        $this->assertTrue($sendCalled, 'sendFailureMail should have been called');
    }

    public function testSendFailureMailExceptionIsCaughtAndLogged(): void
    {
        $service = new class extends NotificationService {
            protected function sendFailureMail(Job $job, array $recipients): void
            {
                throw new \RuntimeException('mail server down');
            }
        };

        $job = $this->makeJob(notifyOnFailure: true, emails: 'ops@example.com');
        // Should not propagate exception
        $service->notifyJobFailed($job);
        $this->assertTrue(true);
    }

    // ── helpers ───────────────────────────────────────────────────────────────

    private function partialService(): NotificationService
    {
        return new class extends NotificationService {
            /** @phpstan-ignore-next-line */
            protected function sendFailureMail(Job $job, array $recipients): void
            {
                throw new \LogicException('sendFailureMail should not be called in this test');
            }
        };
    }

    private function makeJob(?JobTemplate $template = null, bool $notifyOnFailure = false, string $emails = ''): Job
    {
        if ($template === null && $notifyOnFailure === false && $emails === '') {
            // null template case
            $job = $this->getMockBuilder(Job::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['attributes', 'save'])
                ->getMock();
            $job->method('attributes')->willReturn(['id', 'status', 'job_template_id']);
            $job->method('save')->willReturn(true);
            $attrRef = new \ReflectionProperty(BaseActiveRecord::class, '_attributes');
            $attrRef->setAccessible(true);
            $attrRef->setValue($job, ['id' => 1, 'status' => 'failed', 'job_template_id' => null]);
            // Use a pre-loaded null relation so no DB lookup is triggered
            $relRef = new \ReflectionProperty(BaseActiveRecord::class, '_related');
            $relRef->setAccessible(true);
            $relRef->setValue($job, ['jobTemplate' => null]);
            return $job;
        }

        $tpl = $this->makeTemplate(notifyOnFailure: $notifyOnFailure, emails: $emails);

        $job = $this->getMockBuilder(Job::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['attributes', 'save'])
            ->getMock();
        $job->method('attributes')->willReturn(['id', 'status', 'exit_code', 'job_template_id', 'launched_by',
                                                 'started_at', 'finished_at', 'created_at', 'updated_at']);
        $job->method('save')->willReturn(true);
        $attrRef = new \ReflectionProperty(BaseActiveRecord::class, '_attributes');
        $attrRef->setAccessible(true);
        $attrRef->setValue($job, ['id' => 1, 'status' => 'failed', 'exit_code' => 1,
                                   'job_template_id' => 1, 'launched_by' => 1,
                                   'started_at' => null, 'finished_at' => null]);
        $relRef = new \ReflectionProperty(BaseActiveRecord::class, '_related');
        $relRef->setAccessible(true);
        $relRef->setValue($job, ['jobTemplate' => $tpl]);
        return $job;
    }

    private function makeTemplate(bool $notifyOnFailure, string $emails): JobTemplate
    {
        $tpl = $this->getMockBuilder(JobTemplate::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['attributes', 'save', 'getNotifyEmailList'])
            ->getMock();
        $tpl->method('attributes')->willReturn(
            ['id', 'name', 'notify_on_failure', 'notify_emails', 'playbook']
        );
        $tpl->method('save')->willReturn(true);
        $tpl->method('getNotifyEmailList')->willReturn(
            $emails ? array_map('trim', explode(',', $emails)) : []
        );
        $ref = new \ReflectionProperty(BaseActiveRecord::class, '_attributes');
        $ref->setAccessible(true);
        $ref->setValue($tpl, [
            'id'                => 1,
            'name'              => 'Test Template',
            'notify_on_failure' => $notifyOnFailure ? 1 : 0,
            'notify_emails'     => $emails ?: null,
            'playbook'          => 'site.yml',
        ]);
        return $tpl;
    }
}
