<?php

declare(strict_types=1);

use yii\db\Migration;

class m000026_000000_add_job_host_summary extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%job_host_summary}}', [
            'id'          => $this->primaryKey(),
            'job_id'      => $this->integer()->unsigned()->notNull(),
            'host'        => $this->string(255)->notNull(),
            'ok'          => $this->integer()->notNull()->defaultValue(0),
            'changed'     => $this->integer()->notNull()->defaultValue(0),
            'failed'      => $this->integer()->notNull()->defaultValue(0),
            'skipped'     => $this->integer()->notNull()->defaultValue(0),
            'unreachable' => $this->integer()->notNull()->defaultValue(0),
            'rescued'     => $this->integer()->notNull()->defaultValue(0),
            'created_at'  => $this->integer()->notNull(),
        ]);

        $this->addForeignKey(
            'fk_job_host_summary_job',
            '{{%job_host_summary}}', 'job_id',
            '{{%job}}', 'id',
            'CASCADE'
        );

        $this->createIndex('uq_job_host_summary', '{{%job_host_summary}}', ['job_id', 'host'], true);
        $this->createIndex('idx_job_host_summary_job',  '{{%job_host_summary}}', 'job_id');
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%job_host_summary}}');
    }
}
