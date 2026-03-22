<?php

declare(strict_types=1);

use yii\db\Migration;

class m000030_000000_add_timeout_minutes extends Migration
{
    public function safeUp(): void
    {
        // Job template: required field, default 120 minutes
        $this->addColumn(
            '{{%job_template}}',
            'timeout_minutes',
            $this->integer()->unsigned()->notNull()->defaultValue(120)->after('skip_tags')
        );
        $this->update('{{%job_template}}', ['timeout_minutes' => 120]);

        // Job: snapshot at launch time; backfill existing records
        $this->addColumn(
            '{{%job}}',
            'timeout_minutes',
            $this->integer()->unsigned()->null()->after('limit')
        );
        $this->update('{{%job}}', ['timeout_minutes' => 120]);

        // Add timed_out to the status enum
        $this->alterColumn(
            '{{%job}}',
            'status',
            "ENUM('pending','queued','running','succeeded','failed','canceled','timed_out') NOT NULL DEFAULT 'pending'"
        );
    }

    public function safeDown(): void
    {
        $this->dropColumn('{{%job_template}}', 'timeout_minutes');
        $this->dropColumn('{{%job}}', 'timeout_minutes');
        $this->alterColumn(
            '{{%job}}',
            'status',
            "ENUM('pending','queued','running','succeeded','failed','canceled') NOT NULL DEFAULT 'pending'"
        );
    }
}
