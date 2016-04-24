<?php
namespace intg\Jihe\Repositories;

use intg\Jihe\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class TeamGroupRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    //==============================
    //            add
    //==============================
    public function testAddGroup()
    {
        $id = $this->getRepository()->add('VIP', 1);
        self::assertTrue($id > 0, 'newly added group should have its valid identifier');

        $this->seeInDatabase('team_groups', [
            'team_id' => 1,
            'name'    => 'VIP',
        ]);
    }

    //==============================
    //            exists
    //==============================
    public function testExists_GroupExists()
    {
        factory(\Jihe\Models\TeamGroup::class)->create([
            'team_id' => 1,
            'name'    => 'VIP',
        ]);

        self::assertTrue($this->getRepository()->exists('VIP', 1));
    }

    public function testExists_GroupNotExists()
    {
        factory(\Jihe\Models\TeamGroup::class)->create([
            'team_id' => 1,
            'name'    => 'VIP',
        ]);

        self::assertFalse($this->getRepository()->exists('VVIP', 1));
    }

    //==============================
    //          all
    //==============================
    public function testAll_NoGroups()
    {
        self::assertEmpty($this->getRepository()->all(1));
    }

    public function testAll_MultiGroups()
    {
        factory(\Jihe\Models\TeamGroup::class)->create([
            'team_id' => 1,
            'name'    => 'VIP',
        ]);
        factory(\Jihe\Models\TeamGroup::class)->create([
            'team_id' => 1,
            'name'    => 'VVIP',
        ]);

        $groups = $this->getRepository()->all(1);
        self::assertNotEmpty($groups);
        self::assertCount(2, $groups);
        self::assertEquals('VIP',  $groups[0]->getName());
        self::assertEquals('VVIP', $groups[1]->getName());
    }

    //==============================
    //          find group
    //==============================
    public function testFindGroup_Found()
    {
        factory(\Jihe\Models\TeamGroup::class)->create([
            'id'      => 1,   // group id
            'team_id' => 1,
            'name'    => 'VIP',
        ]);

        $group = $this->getRepository()->findGroup(1, 1);
        self::assertNotNull($group);
        self::assertEquals('VIP', $group->getName());

        $team = $group->getTeam();
        self::assertNotNull($team);
        // the $team got only id
        self::assertEquals(1, $team->getId());
        self::assertEmpty($team->getName());
    }

    public function testFindGroup_NotFound_WithNoGroupsAtAll()
    {
        self::assertNull($this->getRepository()->findGroup(1, 1));
    }

    public function testFindGroup_NotFound_WithSomeGroups()
    {
        factory(\Jihe\Models\TeamGroup::class)->create([
            'id'      => 1,   // group id
            'team_id' => 1,
            'name'    => 'VIP',
        ]);

        self::assertNull($this->getRepository()->findGroup(2, 1));
    }

    //==============================
    //          update
    //==============================
    public function testUpdate()
    {
        factory(\Jihe\Models\TeamGroup::class)->create([
            'id'      => 1,   // group id
            'team_id' => 1,
            'name'    => 'VIP',
        ]);

        self::assertTrue($this->getRepository()->update(1, 1, 'VVIP'));

        $this->seeInDatabase('team_groups', [
            'id'    => 1,
            'name'  => 'VVIP',
        ]);
    }

    public function testUpdate_ForgedGroup()
    {
        factory(\Jihe\Models\TeamGroup::class)->create([
            'id'      => 1,   // group id
            'team_id' => 1,
            'name'    => 'VIP',
        ]);

        self::assertFalse($this->getRepository()->update(2, 1, 'VVIP'));

        $this->seeInDatabase('team_groups', [
            'id'    => 1,
            'name'  => 'VIP',   // intact
        ]);
    }

    //==============================
    //          delete
    //==============================
    public function testDelete()
    {
        factory(\Jihe\Models\TeamGroup::class)->create([
            'id'      => 1,   // group id
            'team_id' => 1,
            'name'    => 'VIP',
        ]);

        self::assertTrue($this->getRepository()->delete(1, 1));

        $this->notSeeInDatabase('team_groups', [
            'id'    => 1,
        ]);
    }

    public function testDelete_InvalidGroup()
    {
        factory(\Jihe\Models\TeamGroup::class)->create([
            'id'      => 1,   // group id
            'team_id' => 1,
            'name'    => 'VIP',
        ]);

        self::assertFalse($this->getRepository()->delete(1, 2));

        $this->seeInDatabase('team_groups', [
            'id'    => 1,
        ]);
    }

    /**
     * @return \Jihe\Contracts\Repositories\TeamGroupRepository
     */
    private function getRepository()
    {
        return $this->app[\Jihe\Contracts\Repositories\TeamGroupRepository::class];
    }
}
