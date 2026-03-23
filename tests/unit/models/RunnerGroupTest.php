<?php

declare(strict_types=1);

namespace app\tests\unit\models;

use app\models\RunnerGroup;
use PHPUnit\Framework\TestCase;

class RunnerGroupTest extends TestCase
{
    public function testTableName(): void
    {
        $this->assertSame('{{%runner_group}}', RunnerGroup::tableName());
    }

    public function testStaleAfterConstantIs120(): void
    {
        $this->assertSame(120, RunnerGroup::STALE_AFTER);
    }

    public function testRulesRequireName(): void
    {
        $group = new RunnerGroup();
        $group->validate();
        $this->assertArrayHasKey('name', $group->errors);
    }

    public function testNameMaxLength128(): void
    {
        $group = new RunnerGroup();
        $group->name = str_repeat('x', 129);
        $group->validate(['name']);
        $this->assertArrayHasKey('name', $group->errors);
    }
}
