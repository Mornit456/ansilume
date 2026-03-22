<?php

declare(strict_types=1);

use yii\db\Migration;

class m000023_000000_add_runner_group_to_job_template extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn('{{%job_template}}', 'runner_group_id',
            $this->integer()->unsigned()->null()->after('credential_id'));

        $this->createIndex('idx_jt_runner_group_id', '{{%job_template}}', 'runner_group_id');
        $this->addForeignKey(
            'fk_jt_runner_group_id',
            '{{%job_template}}', 'runner_group_id',
            '{{%runner_group}}', 'id',
            'SET NULL', 'CASCADE'
        );
    }

    public function safeDown(): void
    {
        $this->dropForeignKey('fk_jt_runner_group_id', '{{%job_template}}');
        $this->dropIndex('idx_jt_runner_group_id', '{{%job_template}}');
        $this->dropColumn('{{%job_template}}', 'runner_group_id');
    }
}
