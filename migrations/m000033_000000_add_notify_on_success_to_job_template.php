<?php

declare(strict_types=1);

use yii\db\Migration;

class m000033_000000_add_notify_on_success_to_job_template extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn('{{%job_template}}', 'notify_on_success', $this->boolean()->notNull()->defaultValue(false)->after('notify_on_failure'));
    }

    public function safeDown(): void
    {
        $this->dropColumn('{{%job_template}}', 'notify_on_success');
    }
}
