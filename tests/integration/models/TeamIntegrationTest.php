<?php

declare(strict_types=1);

namespace app\tests\integration\models;

use app\models\Team;
use app\tests\integration\DbTestCase;

class TeamIntegrationTest extends DbTestCase
{
    public function testHasMemberReturnsFalseForNonMember(): void
    {
        $user = $this->createUser();
        $team = $this->createTeam($user->id);

        $other = $this->createUser('other');

        $this->assertFalse($team->hasMember($other->id));
    }

    public function testHasMemberReturnsTrueAfterAddingMember(): void
    {
        $user   = $this->createUser();
        $team   = $this->createTeam($user->id);
        $member = $this->createUser('member');

        $this->addMember($team->id, $member->id);

        $this->assertTrue($team->hasMember($member->id));
    }

    public function testHasMemberReturnsFalseAfterMemberRemoved(): void
    {
        $user   = $this->createUser();
        $team   = $this->createTeam($user->id);
        $member = $this->createUser('ex');

        $this->addMember($team->id, $member->id);
        $this->assertTrue($team->hasMember($member->id));

        \app\models\TeamMember::deleteAll(['team_id' => $team->id, 'user_id' => $member->id]);

        $this->assertFalse($team->hasMember($member->id));
    }

    public function testHasMemberChecksCorrectTeam(): void
    {
        $user  = $this->createUser();
        $teamA = $this->createTeam($user->id);
        $teamB = $this->createTeam($user->id);

        $member = $this->createUser('member');
        $this->addMember($teamA->id, $member->id);

        $this->assertTrue($teamA->hasMember($member->id));
        $this->assertFalse($teamB->hasMember($member->id));
    }

    public function testTeamNameRequired(): void
    {
        $team = new Team();
        $team->validate();
        $this->assertArrayHasKey('name', $team->errors);
    }

    // -------------------------------------------------------------------------

    private function addMember(int $teamId, int $userId): void
    {
        $this->addTeamMember($teamId, $userId);
    }
}
