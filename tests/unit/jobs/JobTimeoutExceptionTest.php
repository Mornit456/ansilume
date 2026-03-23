<?php

declare(strict_types=1);

namespace app\tests\unit\jobs;

use app\jobs\JobTimeoutException;
use PHPUnit\Framework\TestCase;

class JobTimeoutExceptionTest extends TestCase
{
    public function testGetTimeoutMinutesReturnsConstructorValue(): void
    {
        $e = new JobTimeoutException(45);
        $this->assertSame(45, $e->getTimeoutMinutes());
    }

    public function testMessageContainsTimeoutMinutes(): void
    {
        $e = new JobTimeoutException(120);
        $this->assertStringContainsString('120', $e->getMessage());
    }

    public function testIsRuntimeException(): void
    {
        $e = new JobTimeoutException(60);
        $this->assertInstanceOf(\RuntimeException::class, $e);
    }
}
