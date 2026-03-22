<?php

declare(strict_types=1);

use yii\db\Migration;

class m000022_000000_create_runners_table extends Migration
{
    public function safeUp(): void
    {
        $opts = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';

        $this->createTable('{{%runner}}', [
            'id'              => $this->primaryKey()->unsigned(),
            'runner_group_id' => $this->integer()->unsigned()->notNull(),
            'name'            => $this->string(128)->notNull(),
            'token_hash'      => $this->string(64)->notNull()->comment('SHA-256 of the raw token'),
            'description'     => $this->text()->null(),
            'last_seen_at'    => $this->integer()->unsigned()->null(),
            'created_by'      => $this->integer()->unsigned()->notNull(),
            'created_at'      => $this->integer()->unsigned()->notNull(),
            'updated_at'      => $this->integer()->unsigned()->notNull(),
        ], $opts);

        $this->createIndex('idx_runner_group_id',   '{{%runner}}', 'runner_group_id');
        $this->createIndex('idx_runner_last_seen',  '{{%runner}}', 'last_seen_at');
        $this->createIndex('uq_runner_token_hash',  '{{%runner}}', 'token_hash', true);
        $this->createIndex('idx_runner_created_by', '{{%runner}}', 'created_by');

        $this->addForeignKey(
            'fk_runner_group_id',
            '{{%runner}}', 'runner_group_id',
            '{{%runner_group}}', 'id',
            'CASCADE', 'CASCADE'
        );
        $this->addForeignKey(
            'fk_runner_created_by',
            '{{%runner}}', 'created_by',
            '{{%user}}', 'id',
            'RESTRICT', 'CASCADE'
        );
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%runner}}');
    }
}
