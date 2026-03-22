<?php

declare(strict_types=1);

use yii\db\Migration;

class m000021_000000_create_runner_groups_table extends Migration
{
    public function safeUp(): void
    {
        $opts = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';

        $this->createTable('{{%runner_group}}', [
            'id'          => $this->primaryKey()->unsigned(),
            'name'        => $this->string(128)->notNull(),
            'description' => $this->text()->null(),
            'created_by'  => $this->integer()->unsigned()->notNull(),
            'created_at'  => $this->integer()->unsigned()->notNull(),
            'updated_at'  => $this->integer()->unsigned()->notNull(),
        ], $opts);

        $this->createIndex('idx_runner_group_created_by', '{{%runner_group}}', 'created_by');
        $this->addForeignKey(
            'fk_runner_group_created_by',
            '{{%runner_group}}', 'created_by',
            '{{%user}}', 'id',
            'RESTRICT', 'CASCADE'
        );
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%runner_group}}');
    }
}
