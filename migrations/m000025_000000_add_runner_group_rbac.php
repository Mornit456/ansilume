<?php

declare(strict_types=1);

use yii\db\Migration;

class m000025_000000_add_runner_group_rbac extends Migration
{
    public function safeUp(): void
    {
        $auth = Yii::$app->authManager;

        $perms = [
            'runner-group.view'   => 'View runner groups and runners',
            'runner-group.create' => 'Create runner groups',
            'runner-group.update' => 'Manage runners within a group',
            'runner-group.delete' => 'Delete runner groups',
        ];

        $created = [];
        foreach ($perms as $name => $desc) {
            $p = $auth->createPermission($name);
            $p->description = $desc;
            $auth->add($p);
            $created[$name] = $p;
        }

        $viewer   = $auth->getRole('viewer');
        $operator = $auth->getRole('operator');
        $admin    = $auth->getRole('admin');

        if ($viewer)   $auth->addChild($viewer,   $created['runner-group.view']);
        if ($operator) {
            $auth->addChild($operator, $created['runner-group.view']);
            $auth->addChild($operator, $created['runner-group.create']);
            $auth->addChild($operator, $created['runner-group.update']);
        }
        if ($admin) {
            $auth->addChild($admin, $created['runner-group.create']);
            $auth->addChild($admin, $created['runner-group.update']);
            $auth->addChild($admin, $created['runner-group.delete']);
        }
    }

    public function safeDown(): void
    {
        $auth = Yii::$app->authManager;
        foreach (['runner-group.view', 'runner-group.create', 'runner-group.update', 'runner-group.delete'] as $name) {
            $p = $auth->getPermission($name);
            if ($p) $auth->remove($p);
        }
    }
}
