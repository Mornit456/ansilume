<?php

declare(strict_types=1);

use yii\db\Migration;

class m000029_000000_add_lint_to_job_template extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn('{{%job_template}}', 'lint_output',    $this->text()->null()->after('trigger_token'));
        $this->addColumn('{{%job_template}}', 'lint_at',        $this->integer()->unsigned()->null()->after('lint_output'));
        $this->addColumn('{{%job_template}}', 'lint_exit_code', $this->smallInteger()->null()->after('lint_at'));
    }

    public function safeDown(): void
    {
        $this->dropColumn('{{%job_template}}', 'lint_exit_code');
        $this->dropColumn('{{%job_template}}', 'lint_at');
        $this->dropColumn('{{%job_template}}', 'lint_output');
    }
}
