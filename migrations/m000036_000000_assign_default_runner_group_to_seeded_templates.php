<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Assigns the "default" runner group to seeded job templates that have no
 * runner group set (Selftest and Demo templates created before this fix).
 *
 * Safe to run multiple times — only updates rows where runner_group_id IS NULL
 * and a runner group named "default" exists.
 */
class m000036_000000_assign_default_runner_group_to_seeded_templates extends Migration
{
    /** Names of job templates that were seeded without a runner group. */
    private const SEEDED_TEMPLATE_PREFIXES = ['Selftest', 'Demo —'];

    public function safeUp(): void
    {
        $groupId = $this->db->createCommand(
            "SELECT id FROM {{%runner_group}} WHERE name = 'default' LIMIT 1"
        )->queryScalar();

        if ($groupId === false) {
            echo "    > No 'default' runner group found — skipping (runners not registered yet).\n";
            return;
        }

        $groupId = (int) $groupId;

        // Build WHERE clause matching seeded template name prefixes.
        $conditions = array_map(
            fn($prefix) => "name LIKE " . $this->db->quoteValue($prefix . '%'),
            self::SEEDED_TEMPLATE_PREFIXES
        );
        $whereNames = '(' . implode(' OR ', $conditions) . ')';

        $updated = $this->db->createCommand(
            "UPDATE {{%job_template}}
             SET runner_group_id = :gid
             WHERE runner_group_id IS NULL AND {$whereNames}",
            [':gid' => $groupId]
        )->execute();

        echo "    > Assigned default runner group (#{$groupId}) to {$updated} seeded template(s).\n";
    }

    public function safeDown(): void
    {
        // Intentionally not reverting — runner group assignments are user-editable.
    }
}
