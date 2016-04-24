<?php
namespace intg\Jihe\Repositories;

use intg\Jihe\TestCase;
use Jihe\Entities\TeamMember as TeamMemberEntity;
use Jihe\Entities\TeamGroup as TeamGroupEntity;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Jihe\Entities\User as UserEntity;
use Jihe\Entities\Team as TeamEntity;
use \PHPUnit_Framework_Assert as Assert;

class TeamMemberRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    //=======================================
    //              Add
    //=======================================
    public function testAdd()
    {
        // add user(#1) to team(#2)
        $member = $this->getRepository()->add(1, 2, [
            'name' => '名字',
            'memo' => '备注信息',
            'requirements' => [
                1 => 'BMW',
                2 => '15k+',
            ]
        ]);
        Assert::assertTrue($member > 0);

        $this->seeInDatabase('team_members', [
            'user_id'  => 1,
            'team_id'  => 2,
            'memo'     => '备注信息',
            'name'     => '名字',
            'status'   => TeamMemberEntity::STATUS_NORMAL,
            'group_id' => TeamGroupEntity::UNGROUPED,
        ]);
        $this->seeInDatabase('team_member_requirements', [
            'member_id'      => $member,
            'requirement_id' => 1,
            'value'          => 'BMW'
        ]);
        $this->seeInDatabase('team_member_requirements', [
            'member_id'      => $member,
            'requirement_id' => 2,
            'value'          => '15k+'
        ]);
    }

    public function testAddWithOptionalRequirementsAndName()
    {
        // add user(#1) to team(#2) with no requirements and name
        Assert::assertTrue($this->getRepository()->add(1, 2, [
            'memo' => '备注信息'
        ]) > 0);

        $this->seeInDatabase('team_members', [
            'user_id'  => 1,
            'team_id'  => 2,
            'memo'     => '备注信息',
            'name'     => null,
            'status'   => TeamMemberEntity::STATUS_NORMAL,
            'group_id' => TeamGroupEntity::UNGROUPED,
        ]);
    }

    //=======================================
    //           Change Group
    //=======================================
    public function testChangeGroup_SingleMember()
    {
        factory(\Jihe\Models\TeamMember::class)->create([
            'user_id'  => 1,
            'team_id'  => 1,
            'group_id' => 1,
            'role'     => \Jihe\Entities\TeamMember::ROLE_ORDINARY,
            'memo'     => null,
            'status'   => \Jihe\Entities\TeamMember::STATUS_NORMAL,
        ]);

        Assert::assertTrue($this->getRepository()->updateGroup(1, 1, 2));

        $this->seeInDatabase('team_members', [
            'user_id'  => 1,
            'team_id'  => 1,
            'group_id' => 2, // group changed
        ]);
    }

    public function testChangeGroup_WholeGroup()
    {
        factory(\Jihe\Models\TeamMember::class)->create([
            'user_id'  => 1,
            'team_id'  => 1,
            'group_id' => 1,
        ]);
        factory(\Jihe\Models\TeamMember::class)->create([
            'user_id'  => 2,
            'team_id'  => 1,
            'group_id' => 1,
        ]);

        // move all members in team#1 to team#2
        Assert::assertTrue($this->getRepository()->updateGroupOfGroupedMembers(1, 1, 2));

        $this->seeInDatabase('team_members', [
            'user_id'  => 1,
            'team_id'  => 1,
            'group_id' => 2, // group changed
        ]);
        $this->seeInDatabase('team_members', [
            'user_id'  => 2,
            'team_id'  => 1,
            'group_id' => 2, // group changed
        ]);
    }

    //=======================================
    //           List Members
    //=======================================
    public function testListMembers_Paginated()
    {
        factory(\Jihe\Models\User::class)->create([
            'id'         => 1,
            'gender'     => UserEntity::GENDER_MALE,
            'avatar_url' => 'http://jhla.com/avatar1.jpg',
        ]);
        factory(\Jihe\Models\User::class)->create([
            'id'         => 2,
            'gender'     => UserEntity::GENDER_FEMALE,
            'avatar_url' => 'http://jhla.com/avatar2.jpg',
        ]);
        factory(\Jihe\Models\User::class)->create([
            'id'         => 3,
            'gender'     => UserEntity::GENDER_MALE,
            'avatar_url' => 'http://jhla.com/avatar3.jpg',
        ]);
        factory(\Jihe\Models\TeamGroup::class)->create([
            'id'   => 1,
            'name' => 'FirstGroup',
        ]);
        factory(\Jihe\Models\TeamGroup::class)->create([
            'id'   => 2,
            'name' => 'SecondGroup',
        ]);
        factory(\Jihe\Models\TeamMember::class)->create([
            'user_id'  => 1,
            'team_id'  => 1,
            'group_id' => 1,
            'visibility' => TeamMemberEntity::VISIBILITY_TEAM,
        ]);
        factory(\Jihe\Models\TeamMember::class)->create([
            'user_id'  => 2,
            'team_id'  => 1,
            'group_id' => 2,
            'visibility' => TeamMemberEntity::VISIBILITY_ALL,
        ]);
        factory(\Jihe\Models\TeamMember::class)->create([
            'user_id'  => 3,
            'team_id'  => 1,
            'group_id' => 1,
            'visibility' => TeamMemberEntity::VISIBILITY_ALL,
        ]);

        // 2 members meet the specification
        list($pages, $members) = $this->getRepository()->listMembers(1, 1, 2, [
            'visibility' => TeamMemberEntity::VISIBILITY_ALL
        ]);
        Assert::assertEquals(1, $pages);  // since page size is 2,
        Assert::assertCount(2, $members); // there is only 1 page

        // first page of page-sized 1 request
        list($pages, $members) = $this->getRepository()->listMembers(1, 1, 1, [
            'visibility' => TeamMemberEntity::VISIBILITY_ALL
        ]);
        Assert::assertEquals(2, $pages);   // since page size is 1,
        Assert::assertCount(1, $members);  // there are 2 pages
        Assert::assertEquals(2, $members[0]->getUser()->getId());

        // second page of page-sized 1 request
        list($pages, $members) = $this->getRepository()->listMembers(1, 2, 1, [
            'visibility' => TeamMemberEntity::VISIBILITY_ALL
        ]);
        Assert::assertEquals(2, $pages);   // since page size is 1,
        Assert::assertCount(1, $members);  // there are 2 pages
        Assert::assertEquals(3, $members[0]->getUser()->getId());
    }

    public function testListMembers_FilteredByMobile()
    {
        factory(\Jihe\Models\User::class)->create([
            'id'         => 1,
            'gender'     => UserEntity::GENDER_MALE,
            'avatar_url' => 'http://jhla.com/avatar1.jpg',
            'mobile'     => '13800138000'
        ]);
        factory(\Jihe\Models\User::class)->create([
            'id'         => 2,
            'gender'     => UserEntity::GENDER_FEMALE,
            'avatar_url' => 'http://jhla.com/avatar2.jpg',
            'mobile'     => '13800138001',
        ]);
        factory(\Jihe\Models\User::class)->create([
            'id'         => 3,
            'gender'     => UserEntity::GENDER_MALE,
            'avatar_url' => 'http://jhla.com/avatar3.jpg',
        ]);
        factory(\Jihe\Models\TeamGroup::class)->create([
            'id'   => 1,
            'name' => 'FirstGroup',
        ]);
        factory(\Jihe\Models\TeamMember::class)->create([
            'user_id'  => 1,
            'team_id'  => 1,
            'group_id' => 1,
            'visibility' => TeamMemberEntity::VISIBILITY_TEAM,
        ]);
        factory(\Jihe\Models\TeamMember::class)->create([
            'user_id'    => 2,
            'team_id'    => 1,
            'group_id'   => 1,
            'visibility' => TeamMemberEntity::VISIBILITY_ALL,
        ]);
        factory(\Jihe\Models\TeamMember::class)->create([
            'user_id'  => 3,
            'team_id'  => 1,
            'group_id' => 1,
            'visibility' => TeamMemberEntity::VISIBILITY_ALL,
        ]);

        // 1 member meet the specification
        list($pages, $members) = $this->getRepository()->listMembers(1, 1, 5, [
            'mobile'     => '13800138000'
        ]);
        Assert::assertEquals(1, $pages);  // since page size is 5,
        Assert::assertCount(1, $members); // there is only 1 page
        Assert::assertEquals('13800138000', $members[0]->getUser()->getMobile());
    }

    public function testListMembers_FilteredByName()
    {
        factory(\Jihe\Models\User::class)->create([
            'id'         => 1,
            'gender'     => UserEntity::GENDER_MALE,
            'avatar_url' => 'http://jhla.com/avatar1.jpg',
            'mobile'     => '13800138000',
            'nick_name'  => 'Steven',
        ]);
        factory(\Jihe\Models\User::class)->create([
            'id'         => 2,
            'gender'     => UserEntity::GENDER_FEMALE,
            'avatar_url' => 'http://jhla.com/avatar2.jpg',
            'mobile'     => '13800138001',
            'nick_name'  => 'Smith',
        ]);
        factory(\Jihe\Models\User::class)->create([
            'id'         => 3,
            'gender'     => UserEntity::GENDER_MALE,
            'avatar_url' => 'http://jhla.com/avatar3.jpg',
        ]);
        factory(\Jihe\Models\TeamGroup::class)->create([
            'id'   => 1,
            'name' => 'FirstGroup',
        ]);
        factory(\Jihe\Models\TeamMember::class)->create([
            'user_id'  => 1,
            'team_id'  => 1,
            'group_id' => 1,
            'name'     => 'Steven',
            'visibility' => TeamMemberEntity::VISIBILITY_TEAM,
        ]);
        factory(\Jihe\Models\TeamMember::class)->create([
            'user_id'    => 2,
            'team_id'    => 1,
            'group_id'   => 1,
            'name'     => 'Smith',
            'visibility' => TeamMemberEntity::VISIBILITY_ALL,
        ]);

        // 1 member meet the specification
        list($pages, $members) = $this->getRepository()->listMembers(1, 1, 5, [
            'name'     => 'S'
        ]);
        Assert::assertEquals(1, $pages);  // since page size is 5,
        Assert::assertCount(2, $members); // there is only 1 page
    }

    //=======================================
    //         List Enrolled Teams
    //=======================================
    public function testListEnrolledTeams()
    {
        factory(\Jihe\Models\Team::class)->create([
            'id'   => 1,
            'status' => TeamEntity::STATUS_NORMAL,
        ]);
        factory(\Jihe\Models\Team::class)->create([
            'id'   => 2,
            'status' => TeamEntity::STATUS_FORBIDDEN,
        ]);
        factory(\Jihe\Models\Team::class)->create([
            'id'   => 3,
            'status' => TeamEntity::STATUS_FREEZE,
        ]);
        factory(\Jihe\Models\Team::class)->create([
            'id'   => 4,
            'status' => TeamEntity::STATUS_NORMAL,
        ]);
        factory(\Jihe\Models\TeamMember::class)->create([
            'user_id'  => 1,
            'team_id'  => 1,
        ]);
        factory(\Jihe\Models\TeamMember::class)->create([
            'user_id'  => 1,
            'team_id'  => 2,
        ]);
        factory(\Jihe\Models\TeamMember::class)->create([
            'user_id'  => 1,
            'team_id'  => 3,
        ]);
        factory(\Jihe\Models\TeamMember::class)->create([
            'user_id'  => 1,
            'team_id'  => 4,
        ]);
    
        // 2 team ids meet the specification
        $teams = $this->getRepository()->listEnrolledTeams(1);
        Assert::assertEquals([1, 4], $teams);
    }

    //=======================================
    //                Exists
    //=======================================
    public function testExists()
    {
        factory(\Jihe\Models\User::class)->create([
            'id' => 1,
            'gender' => UserEntity::GENDER_MALE,
            'avatar_url' => 'http://jhla.com/avatar1.jpg',
        ]);
        factory(\Jihe\Models\User::class)->create([
            'id' => 2,
            'gender' => UserEntity::GENDER_FEMALE,
            'avatar_url' => 'http://jhla.com/avatar2.jpg',
        ]);
        factory(\Jihe\Models\TeamMember::class)->create([
            'user_id'  => 1,
            'team_id'  => 1,
            'group_id' => 1,
            'visibility' => TeamMemberEntity::VISIBILITY_TEAM,
        ]);
        factory(\Jihe\Models\TeamMember::class)->create([
            'user_id'  => 1,
            'team_id'  => 2,
            'group_id' => 2,
            'visibility' => TeamMemberEntity::VISIBILITY_TEAM,
        ]);
        factory(\Jihe\Models\TeamMember::class)->create([
            'user_id'  => 2,
            'team_id'  => 1,
            'group_id' => 2,
            'visibility' => TeamMemberEntity::VISIBILITY_ALL,
        ]);
    
        $teams = $this->getRepository()->exists(1, [1, 2, 3]);
        Assert::assertCount(3, $teams);
        Assert::assertEquals(true, $teams[1]);
        Assert::assertEquals(true, $teams[2]);
        Assert::assertEquals(false, $teams[3]);
    }
    
    //=======================================
    //            Count Members
    //=======================================
    public function testCountMembers_OneTeam()
    {
        factory(\Jihe\Models\User::class)->create([
            'id' => 1,
            'gender' => UserEntity::GENDER_MALE,
            'avatar_url' => 'http://jhla.com/avatar1.jpg',
        ]);
        factory(\Jihe\Models\User::class)->create([
            'id' => 2,
            'gender' => UserEntity::GENDER_FEMALE,
            'avatar_url' => 'http://jhla.com/avatar2.jpg',
        ]);
        factory(\Jihe\Models\TeamMember::class)->create([
            'user_id'  => 1,
            'team_id'  => 1,
            'group_id' => 1,
            'visibility' => TeamMemberEntity::VISIBILITY_TEAM,
        ]);
        factory(\Jihe\Models\TeamMember::class)->create([
            'user_id'  => 1,
            'team_id'  => 2,
            'group_id' => 2,
            'visibility' => TeamMemberEntity::VISIBILITY_TEAM,
        ]);
        factory(\Jihe\Models\TeamMember::class)->create([
            'user_id'  => 2,
            'team_id'  => 1,
            'group_id' => 2,
            'visibility' => TeamMemberEntity::VISIBILITY_ALL,
        ]);
    
        Assert::assertEquals(2, $this->getRepository()->countMembers(1));
        Assert::assertEquals(1, $this->getRepository()->countMembers(1, TeamMemberEntity::VISIBILITY_TEAM));
        Assert::assertEquals([1 => 2, 2 => 1], $this->getRepository()->countMembers([1, 2]));
    }

    /**
     * @return \Jihe\Contracts\Repositories\TeamMemberRepository
     */
    private function getRepository()
    {
        return $this->app[\Jihe\Contracts\Repositories\TeamMemberRepository::class];
    }
}
