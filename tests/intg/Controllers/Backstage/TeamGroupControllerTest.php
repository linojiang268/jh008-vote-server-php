<?php
namespace intg\Jihe\Controllers\Backstage;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use intg\Jihe\RequestSignCheck;
use intg\Jihe\AuthDeviceCheck;
use intg\Jihe\TestCase;
use Jihe\Entities\TeamGroup;


class TeamGroupControllerTest extends TestCase
{
    use DatabaseTransactions;
    use RequestSignCheck;
    use AuthDeviceCheck;

    //=========================================
    //               createGroup
    //=========================================
    public function testCreateGroup_Success()
    {
        factory(\Jihe\Models\Team::class)->create([
            'id'         => 1,
            'city_id'    => 1,
            'creator_id' => 2
        ]);
        factory(\Jihe\Models\City::class)->create([
            'id'   => 1,
            'name' => '成都',
        ]);
        $user = factory(\Jihe\Models\User::class)->create([
            'id'     => 2,  // Team1的团长
            'mobile' => '13800138001',
        ]);
        $this->startSession();

        $this->actingAs($user)->post('/community/team/group/create', [
            'name'   => '新分组',
            '_token' => csrf_token(),
        ])->seeJsonContains(['code' => 0]);
    }

    public function testCreateGroup_BadName()
    {
        factory(\Jihe\Models\Team::class)->create([
            'id'         => 1,
            'city_id'    => 1,
            'creator_id' => 2
        ]);
        factory(\Jihe\Models\City::class)->create([
            'id'   => 1,
            'name' => '成都',
        ]);
        $user = factory(\Jihe\Models\User::class)->create([
            'id'     => 2,
            'mobile' => '13800138001',
        ]);
        $this->startSession();

        $this->actingAs($user)->post('/community/team/group/create', [
            'name'   => '未分组',   // '未分组' is reserved
            '_token' => csrf_token(),
        ])->seeJsonContains(['code' => 10000]);
    }

    public function testCreateGroup_CreatingAnExistingGroup()
    {
        factory(\Jihe\Models\Team::class)->create([
            'id'         => 1,
            'city_id'    => 1,
            'creator_id' => 2
        ]);
        factory(\Jihe\Models\City::class)->create([
            'id'   => 1,
            'name' => '成都',
        ]);
        factory(\Jihe\Models\TeamGroup::class)->create([
            'team_id' => 1,
            'name'    => '已存在'
        ]);
        $user = factory(\Jihe\Models\User::class)->create([
            'id'     => 2,
            'mobile' => '13800138001',
        ]);
        $this->startSession();

        $this->actingAs($user)->post('/community/team/group/create', [
            'name'   => '已存在',   // '已存在' exists
            '_token' => csrf_token(),
        ]);

        $response = json_decode($this->response->getContent());
        self::assertEquals(10000, $response->code);
        self::assertContains('社团分组已存在', $response->message);
    }

    //=========================================
    //               updateGroup
    //=========================================
    public function testUpdateGroup_Success()
    {
        factory(\Jihe\Models\Team::class)->create([
            'id'         => 1,
            'city_id'    => 1,
            'creator_id' => 2
        ]);
        factory(\Jihe\Models\City::class)->create([
            'id'   => 1,
            'name' => '成都',
        ]);
        factory(\Jihe\Models\TeamGroup::class)->create([
            'id'      => 1,
            'team_id' => 1,
            'name'    => '已存在'
        ]);
        $user = factory(\Jihe\Models\User::class)->create([
            'id'     => 2,
            'mobile' => '13800138001',
        ]);
        $this->startSession();

        $this->actingAs($user)->post('/community/team/group/update', [
            'name'   => '新名字',
            'group'  => 1,
            '_token' => csrf_token(),
        ])->seeJsonContains(['code' => 0]);

        $this->seeInDatabase('team_groups', [
            'id'      => 1,
            'team_id' => 1,
            'name'    => '新名字'
        ]);
    }

    public function testUpdateGroup_NonExisting()
    {
        factory(\Jihe\Models\Team::class)->create([
            'id'         => 1,
            'city_id'    => 1,
            'creator_id' => 2
        ]);
        factory(\Jihe\Models\City::class)->create([
            'id'   => 1,
            'name' => '成都',
        ]);
        $user = factory(\Jihe\Models\User::class)->create([
            'id'     => 2,
            'mobile' => '13800138001',
        ]);
        $this->startSession();

        $this->actingAs($user)->post('/community/team/group/update', [
            'name'   => '新名字',
            'group'  => 1,
            '_token' => csrf_token(),
        ])->seeJsonContains(['code' => 10000]); // 社团分组非法
    }

    //=========================================
    //               deleteGroup
    //=========================================
    public function testDeleteGroup_Success()
    {
        factory(\Jihe\Models\Team::class)->create([
            'id'         => 1,
            'city_id'    => 1,
            'creator_id' => 2
        ]);
        factory(\Jihe\Models\City::class)->create([
            'id'   => 1,
            'name' => '成都',
        ]);
        $user = factory(\Jihe\Models\User::class)->create([
            'id'     => 2,
            'mobile' => '13800138001',
        ]);
        factory(\Jihe\Models\TeamGroup::class)->create([
            'id'      => 1,
            'team_id' => 1,
            'name'    => '分组1'
        ]);
        $this->startSession();

        $this->actingAs($user)->post('/community/team/group/delete', [
            'group'  => 1,
            '_token' => csrf_token(),
        ])->seeJsonContains(['code' => 0]);
    }

    public function testDeleteGroup_SuccessWithMembersInThatGroup()
    {
        factory(\Jihe\Models\Team::class)->create([
            'id'         => 1,
            'city_id'    => 1,
            'creator_id' => 2
        ]);
        factory(\Jihe\Models\City::class)->create([
            'id'   => 1,
            'name' => '成都',
        ]);
        $user = factory(\Jihe\Models\User::class)->create([
            'id'     => 2,
            'mobile' => '13800138001',
        ]);
        factory(\Jihe\Models\TeamGroup::class)->create([
            'id'      => 1,
            'team_id' => 1,
            'name'    => '分组1'
        ]);
        factory(\Jihe\Models\TeamMember::class)->create([
            'id'       => 1,
            'user_id'  => 1,
            'team_id'  => 1,
            'group_id' => 1,
        ]);
        factory(\Jihe\Models\TeamMember::class)->create([
            'id'       => 2,
            'user_id'  => 2,
            'team_id'  => 1,
            'group_id' => 1,
        ]);
        $this->startSession();

        $this->actingAs($user)->post('/community/team/group/delete', [
            'group'  => 1,
            '_token' => csrf_token(),
        ])->seeJsonContains(['code' => 0]);

        $this->seeInDatabase('team_members', [
            'id'       => 1,
            'group_id' => TeamGroup::UNGROUPED,
        ]);
        $this->seeInDatabase('team_members', [
            'id'       => 2,
            'group_id' => TeamGroup::UNGROUPED,
        ]);
    }

    public function testDeleteGroup_SuccessWithMembersInThatGroupAndNewGroup()
    {
        factory(\Jihe\Models\Team::class)->create([
            'id'         => 1,
            'city_id'    => 1,
            'creator_id' => 2
        ]);
        factory(\Jihe\Models\City::class)->create([
            'id'   => 1,
            'name' => '成都',
        ]);
        $user = factory(\Jihe\Models\User::class)->create([
            'id'     => 2,
            'mobile' => '13800138001',
        ]);
        factory(\Jihe\Models\TeamGroup::class)->create([
            'id'      => 1,
            'team_id' => 1,
            'name'    => '分组1'
        ]);
        factory(\Jihe\Models\TeamGroup::class)->create([
            'id'      => 2,
            'team_id' => 1,
            'name'    => '分组2'
        ]);
        factory(\Jihe\Models\TeamMember::class)->create([
            'id'       => 1,
            'user_id'  => 1,
            'team_id'  => 1,
            'group_id' => 1,
        ]);
        factory(\Jihe\Models\TeamMember::class)->create([
            'id'       => 2,
            'user_id'  => 2,
            'team_id'  => 1,
            'group_id' => 1,
        ]);
        $this->startSession();

        $this->actingAs($user)->post('/community/team/group/delete', [
            'group'  => 1,
            'to'     => 2,
            '_token' => csrf_token(),
        ])->seeJsonContains(['code' => 0]);

        $this->seeInDatabase('team_members', [
            'id'       => 1,
            'group_id' => 2,
        ]);
        $this->seeInDatabase('team_members', [
            'id'       => 2,
            'group_id' => 2,
        ]);
    }

    //=========================================
    //             listGroups
    //=========================================
    public function testListGroups_Success()
    {
        factory(\Jihe\Models\Team::class)->create([
            'id'         => 1,
            'city_id'    => 1,
            'creator_id' => 2
        ]);
        factory(\Jihe\Models\City::class)->create([
            'id'   => 1,
            'name' => '成都',
        ]);
        $user = factory(\Jihe\Models\User::class)->create([
            'id'     => 2,
            'mobile' => '13800138001',
        ]);
        factory(\Jihe\Models\TeamGroup::class)->create([
            'id'      => 1,
            'team_id' => 1,
            'name'    => '分组1'
        ]);
        factory(\Jihe\Models\TeamGroup::class)->create([
            'id'      => 2,
            'team_id' => 1,
            'name'    => '分组2'
        ]);
        $this->startSession();

        $this->actingAs($user)->get('/community/team/group/list');

        $response = json_decode($this->response->getContent());
        self::assertEquals(0, $response->code);
        self::assertCount(2, $response->groups);
    }

    public function testListGroups_Empty()
    {
        factory(\Jihe\Models\Team::class)->create([
            'id'         => 1,
            'city_id'    => 1,
            'creator_id' => 2
        ]);
        factory(\Jihe\Models\City::class)->create([
            'id'   => 1,
            'name' => '成都',
        ]);
        $user = factory(\Jihe\Models\User::class)->create([
            'id'     => 2,
            'mobile' => '13800138001',
        ]);
        $this->startSession();

        $this->actingAs($user)->get('/community/team/group/list');

        $response = json_decode($this->response->getContent());
        self::assertEquals(0, $response->code);
        self::assertEmpty($response->groups);
    }
}
