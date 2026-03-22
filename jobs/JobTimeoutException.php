<?php

declare(strict_types=1);

namespace app\jobs;

class JobTimeoutException extends \RuntimeException
{
    public function __construct(private readonly int $timeoutMinutes)
    {
        parent::__construct("Job exceeded timeout of {$timeoutMinutes} minutes.");
    }

    public function getTimeoutMinutes(): int
    {
        return $this->timeoutMinutes;
    }
}
