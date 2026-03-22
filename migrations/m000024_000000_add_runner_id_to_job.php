<?php

declare(strict_types=1);

use yii\db\Migration;

class m000024_000000_add_runner_id_to_job extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn('{{%job}}', 'runner_id',
            $this->integer()->unsigned()->null()->after('worker_id'));

        $this->createIndex('idx_job_runner_id', '{{%job}}', 'runner_id');
        $this->addForeignKey(
            'fk_job_runner_id',
            '{{%job}}', 'runner_id',
            '{{%runner}}', 'id',
            'SET NULL', 'CASCADE'
        );
    }

    public function safeDown(): void
    {
        $this->dropForeignKey('fk_job_runner_id', '{{%job}}');
        $this->dropIndex('idx_job_runner_id', '{{%job}}');
        $this->dropColumn('{{%job}}', 'runner_id');
    }
}
