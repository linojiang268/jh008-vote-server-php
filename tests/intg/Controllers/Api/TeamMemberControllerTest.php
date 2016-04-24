<?php
namespace intg\Jihe\Controllers\Api;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use intg\Jihe\RequestSignCheck;
use intg\Jihe\AuthDeviceCheck;
use intg\Jihe\TestCase;
use Jihe\Entities\Team as TeamEntity;
use Jihe\Entities\TeamMember;
use Jihe\Entities\TeamMemberEnrollmentPermission;
use Jihe\Entities\TeamMemberEnrollmentRequest;

class TeamMemberControllerTest extends TestCase
{
    use DatabaseTransactions;
    use RequestSignCheck;
    use AuthDeviceCheck;

    //=========================================
    //          requestForEnrollment
    //=========================================
    public function testSuccessfulRequestForEnrollment()
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
        factory(\Jihe\Models\User::class)->create([
            'id'     => 2,  // Team1的团长
            'mobile' => '13800138001',
        ]);

        $this->actingAs(factory(\Jihe\Models\User::class)->create([
            'id'     => 1,
            'mobile' => '13800138000'
        ]))->post('/api/team/member/enroll', [
            'team'  => 1,
            'memo' => '备注信息'
        ])->seeJsonContains([ 'code' => 0 ]);
    }

    public function testSuccessfulRequestForEnrollmentWithRequirements()
    {
        factory(\Jihe\Models\Team::class)->create([
            'id'         => 1,
            'city_id'    => 1,
            'creator_id' => 2,
        ]);
        factory(\Jihe\Models\TeamRequirement::class)->create([
            'id'             => 1,
            'team_id'        => 1,
            'requirement'    => 'Your car?'
        ]);
        factory(\Jihe\Models\TeamRequirement::class)->create([
            'id'             => 2,
            'team_id'        => 1,
            'requirement'    => 'Your salary?'
        ]);
        factory(\Jihe\Models\City::class)->create([
            'id'   => 1,
            'name' => '成都',
        ]);
        factory(\Jihe\Models\User::class)->create([
            'id'     => 2,  // Team1的团长
            'mobile' => '13800138001',
        ]);

        $this->actingAs(factory(\Jihe\Models\User::class)->create([
            'id'     => 1,
            'mobile' => '13800138000'
        ]))->post('/api/team/member/enroll', [
            'team'  => 1,
            'memo' => '备注信息',
            'requirements' => '{"1":"BMW","2":"15k+"}',
        ])->seeJsonContains([ 'code' => 0 ]);
    }

    public function testRequestForEnrollment_Rejected()
    {
        factory(\Jihe\Models\Team::class)->create([
            'id'         => 1,
            'city_id'    => 1,
            'creator_id' => 2,
            'join_type'  => TeamEntity::JOIN_TYPE_VERIFY,
        ]);
        factory(\Jihe\Models\TeamMemberEnrollmentPermission::class)->create([
            'team_id' => 1,
            'mobile'  => '13800138000',
            'status'  => TeamMemberEnrollmentPermission::STATUS_PROHIBITED
        ]);
        factory(\Jihe\Models\City::class)->create([
            'id'   => 1,
            'name' => '成都',
        ]);
        factory(\Jihe\Models\User::class)->create([
            'id'     => 2,  // Team1的团长
            'mobile' => '13800138001',
        ]);

        $this->actingAs(factory(\Jihe\Models\User::class)->create([
            'id'     => 1,
            'mobile' => '13800138000'
        ]))->post('/api/team/member/enroll', [
            'team'  => 1
        ]);

        $response = json_decode($this->response->getContent());
        self::assertContains('您的申请未通过', $response->message);
        self::assertEquals(10000, $response->code);
    }

    public function testRequestForEnrollment_InsufficientRequirements()
    {
        factory(\Jihe\Models\Team::class)->create([
            'id'         => 1,
            'city_id'    => 1,
            'creator_id' => 2,
            'join_type'  => TeamEntity::JOIN_TYPE_VERIFY,
        ]);
        factory(\Jihe\Models\TeamRequirement::class)->create([
            'id'             => 1,
            'team_id'        => 1,
            'requirement'    => 'Your car?'
        ]);
        factory(\Jihe\Models\TeamRequirement::class)->create([
            'id'             => 2,
            'team_id'        => 1,
            'requirement'    => 'Your salary?'
        ]);
        factory(\Jihe\Models\City::class)->create([
            'id'   => 1,
            'name' => '成都',
        ]);
        factory(\Jihe\Models\User::class)->create([
            'id'     => 2,  // Team1的团长
            'mobile' => '13800138001',
        ]);

        $this->actingAs(factory(\Jihe\Models\User::class)->create([
            'id'     => 1,
            'mobile' => '13800138000'
        ]))->post('/api/team/member/enroll', [
            'team'  => 1,
            'memo' => '备注信息',
            'requirements' => '{"1":"BMW"}',  // no answer to the second requirement
        ]);

        $response = json_decode($this->response->getContent());
        self::assertContains('加入条件未填写完整', $response->message);
        self::assertEquals(10000, $response->code);
    }

    public function testRequestForEnrollment_WithFinishedRequest()
    {
        factory(\Jihe\Models\Team::class)->create([
            'id'         => 1,
            'city_id'    => 1,
            'creator_id' => 2,
            'join_type'  => TeamEntity::JOIN_TYPE_VERIFY,
        ]);
        factory(\Jihe\Models\City::class)->create([
            'id'   => 1,
            'name' => '成都',
        ]);
        factory(\Jihe\Models\User::class)->create([
            'id'     => 2,  // Team1的团长
            'mobile' => '13800138001',
        ]);
        factory(\Jihe\Models\TeamMemberEnrollmentRequest::class)->create([
            'id'           => 1,
            'initiator_id' => 1,
            'team_id'      => 1,
            'status'       => TeamMemberEnrollmentRequest::STATUS_REJECTED,
        ]);

        $this->actingAs(factory(\Jihe\Models\User::class)->create([
            'id'     => 1,
            'mobile' => '13800138000'
        ]))->post('/api/team/member/enroll', [
            'team'  => 1,
            'memo' => '备注信息'
        ])->seeJsonContains([ 'code' => 0 ]);

        $this->notSeeInDatabase('team_member_enrollment_requests', [
            'id' => 1,
        ]);
        $this->seeInDatabase('team_member_enrollment_requests', [
            'initiator_id'  => 1,
            'team_id'       => 1,
            'status'        => TeamMemberEnrollmentRequest::STATUS_PENDING,
        ]);
    }

    public function testRequestForEnrollment_WithFinishedRequestAndRequirement()
    {
        factory(\Jihe\Models\Team::class)->create([
            'id'         => 1,
            'city_id'    => 1,
            'creator_id' => 2,
            'join_type'  => TeamEntity::JOIN_TYPE_VERIFY,
        ]);
        factory(\Jihe\Models\City::class)->create([
            'id'   => 1,
            'name' => '成都',
        ]);
        factory(\Jihe\Models\User::class)->create([
            'id'     => 2,  // Team1的团长
            'mobile' => '13800138001',
        ]);
        factory(\Jihe\Models\TeamMemberEnrollmentRequest::class)->create([
            'id'           => 1,
            'initiator_id' => 1,
            'team_id'      => 1,
            'status'       => TeamMemberEnrollmentRequest::STATUS_REJECTED,
        ]);
        factory(\Jihe\Models\TeamMemberEnrollmentRequirement::class)->create([
            'request_id'     => 1,
            'requirement_id' => 1,
            'value'          => 'test',
        ]);
        factory(\Jihe\Models\TeamMemberEnrollmentRequirement::class)->create([
            'request_id'     => 1,
            'requirement_id' => 2,
            'value'          => 'test2',
        ]);


        $this->actingAs(factory(\Jihe\Models\User::class)->create([
            'id'     => 1,
            'mobile' => '13800138000'
        ]))->post('/api/team/member/enroll', [
            'team'  => 1,
            'memo' => '备注信息'
        ])->seeJsonContains([ 'code' => 0 ]);

        $this->notSeeInDatabase('team_member_enrollment_requests', [
            'id' => 1,
        ]);
        $this->notSeeInDatabase('team_member_enrollment_requirements', [
            'request_id' => 1,
        ]);
        $this->seeInDatabase('team_member_enrollment_requests', [
            'initiator_id'  => 1,
            'team_id'       => 1,
            'status'        => TeamMemberEnrollmentRequest::STATUS_PENDING,
        ]);
    }

    //=========================================
    //      listPendingEnrollmentRequests
    //=========================================
    public function testListPendingEnrollmentRequests()
    {
        factory(\Jihe\Models\Team::class)->create([
            'id'      => 1,
            'city_id' => 1,
        ]);
        factory(\Jihe\Models\Team::class)->create([
            'id'      => 2,
            'city_id' => 1,
        ]);
        factory(\Jihe\Models\City::class)->create([
            'id'   => 1,
            'name' => '成都',
        ]);
        factory(\Jihe\Models\TeamMemberEnrollmentRequest::class)->create([
            'team_id'      => 1,
            'initiator_id' => 1,
            'status'       => TeamMemberEnrollmentRequest::STATUS_PENDING,
        ]);
        factory(\Jihe\Models\TeamMemberEnrollmentRequest::class)->create([
            'team_id'      => 2,
            'initiator_id' => 1,
            'status'       => TeamMemberEnrollmentRequest::STATUS_PENDING,
        ]);
        $this->startSession();

        $this->actingAs(factory(\Jihe\Models\User::class)->create([
            'id'     => 1,
            'mobile' => '13800138000'
        ]))->get('/api/team/enrollment/pending');

        $response = json_decode($this->response->getContent());
        self::assertCount(2, $response->requests);
        self::assertEquals(0, $response->code);
    }

    //=========================================
    //            quitTeam
    //=========================================
    public function testQuitTeam()
    {
        factory(\Jihe\Models\TeamMember::class)->create([
            'team_id' => 1,
            'user_id' => 1,
        ]);
        $this->startSession();

        $this->actingAs(factory(\Jihe\Models\User::class)->create([
            'id'     => 1,
            'mobile' => '13800138000'
        ]))->post('/api/team/member/quit', [
            'team'     => 1,
            '_token'   => csrf_token(),
        ])->seeJsonContains(['code' => 0]);

        $this->notSeeInDatabase('team_members', [
            'team_id'  => 1,
            'user_id'  => 1,
        ]);
    }

    public function testQuitTeam_NotMember()
    {
        factory(\Jihe\Models\TeamMember::class)->create([
            'team_id' => 1,
            'user_id' => 1,
        ]);
        $this->startSession();

        $this->actingAs(factory(\Jihe\Models\User::class)->create([
            'id'     => 2,
            'mobile' => '13800138000'
        ]))->post('/api/team/member/quit', [
            'team'     => 1,
            '_token'   => csrf_token(),
        ])->seeJsonContains(['code' => 10000]);
    }

    //=========================================
    //           update
    //=========================================
    public function testUpdate()
    {
        factory(\Jihe\Models\TeamMember::class)->create([
            'team_id'    => 1,
            'user_id'    => 1,
            'visibility' => TeamMember::VISIBILITY_ALL,
        ]);
        $this->startSession();

        $this->actingAs(factory(\Jihe\Models\User::class)->create([
            'id'     => 1,
            'mobile' => '13800138000'
        ]))->post('/api/team/member/update', [
            'team'       => 1,
            'visibility' => 1,   // 1 for VISIBILITY_TEAM
            '_token'     => csrf_token(),
        ])->seeJsonContains(['code' => 0]);

        $this->seeInDatabase('team_members', [
            'team_id'    => 1,
            'user_id'    => 1,
            'visibility' => TeamMember::VISIBILITY_TEAM,
        ]);
    }

    //=========================================
    //            listMembers
    //=========================================
    public function testListMembers()
    {
        factory(\Jihe\Models\Team::class)->create([
            'id'         => 1,
            'city_id'    => 1,
            'creator_id' => 1,
        ]);
        factory(\Jihe\Models\City::class)->create([
            'id'   => 1,
            'name' => '成都',
        ]);
        factory(\Jihe\Models\User::class)->create([
            'id'         => 1,
            'nick_name'  => 'First',
        ]);
        factory(\Jihe\Models\User::class)->create([
            'id'         => 2,
            'nick_name'  => 'Second',
        ]);
        factory(\Jihe\Models\TeamMember::class)->create([
            'team_id'    => 1,
            'user_id'    => 1,
            'visibility' => TeamMember::VISIBILITY_ALL,
            'status'     => TeamMember::STATUS_NORMAL,
        ]);
        factory(\Jihe\Models\TeamMember::class)->create([
            'team_id'    => 1,
            'user_id'    => 2,
            'visibility' => TeamMember::VISIBILITY_TEAM,
            'status'     => TeamMember::STATUS_NORMAL,
        ]);

        $this->actingAs(factory(\Jihe\Models\User::class)->make())
             ->ajaxGet('/api/team/member/list?team=1');

        $response = json_decode($this->response->getContent());
        self::assertEquals(0, $response->code);
        self::assertCount(1, $response->members);
        self::assertEquals(1, $response->pages);
        self::assertEquals('First', $response->members[0]->name);
        self::assertEquals(1, $response->members[0]->id);
    }

    public function testListMembers_AsTeamManager()
    {
        factory(\Jihe\Models\Team::class)->create([
            'id'         => 1,
            'city_id'    => 1,
            'creator_id' => 1,
        ]);
        factory(\Jihe\Models\City::class)->create([
            'id'   => 1,
            'name' => '成都',
        ]);
        factory(\Jihe\Models\User::class)->create([
            'id'         => 1,
            'nick_name'  => 'First',
        ]);
        factory(\Jihe\Models\User::class)->create([
            'id'         => 2,
            'nick_name'  => 'Second',
        ]);
        factory(\Jihe\Models\TeamMember::class)->create([
            'team_id'    => 1,
            'user_id'    => 1,
            'visibility' => TeamMember::VISIBILITY_ALL,
            'status'     => TeamMember::STATUS_NORMAL,
        ]);
        factory(\Jihe\Models\TeamMember::class)->create([
            'team_id'    => 1,
            'user_id'    => 2,
            'visibility' => TeamMember::VISIBILITY_TEAM,
            'status'     => TeamMember::STATUS_NORMAL,
        ]);

        $this->actingAs(factory(\Jihe\Models\User::class)->make([
            'id' => 1,
        ]))->ajaxGet('/api/team/member/list?team=1');

        $response = json_decode($this->response->getContent());
        self::assertEquals(0, $response->code);
        self::assertCount(2, $response->members);
    }

    public function testListMembers_All_NonMember()
    {
        factory(\Jihe\Models\Team::class)->create([
            'id'         => 1,
            'city_id'    => 1,
            'creator_id' => 1,
        ]);
        factory(\Jihe\Models\City::class)->create([
            'id'   => 1,
            'name' => '成都',
        ]);
        factory(\Jihe\Models\User::class)->create([
            'id'        => 1,
            'nick_name' => 'First',
        ]);
        factory(\Jihe\Models\User::class)->create([
            'id'        => 2,
            'nick_name' => 'Second',
        ]);
        factory(\Jihe\Models\TeamMember::class)->create([
            'team_id'    => 1,
            'user_id'    => 1,
            'visibility' => TeamMember::VISIBILITY_ALL,
            'status'     => TeamMember::STATUS_NORMAL,
        ]);
        factory(\Jihe\Models\TeamMember::class)->create([
            'team_id'    => 1,
            'user_id'    => 2,
            'visibility' => TeamMember::VISIBILITY_TEAM,
            'status'     => TeamMember::STATUS_NORMAL,
        ]);
        $this->startSession();

        $this->actingAs(factory(\Jihe\Models\User::class)->make())
             ->get('/api/team/member/list?team=1');

        $response = json_decode($this->response->getContent());
        self::assertEquals(0, $response->code);
    }

    public function testListMembers_Paginated()
    {
        factory(\Jihe\Models\Team::class)->create([
            'id' => 1,
            'city_id' => 1,
            'creator_id' => 1,
        ]);
        factory(\Jihe\Models\City::class)->create([
            'id'   => 1,
            'name' => '成都',
        ]);
        factory(\Jihe\Models\User::class)->create([
            'id'         => 1,
            'nick_name'  => 'First',
        ]);
        factory(\Jihe\Models\User::class)->create([
            'id'         => 2,
            'nick_name'  => 'Second',
        ]);
        factory(\Jihe\Models\User::class)->create([
            'id'         => 3,
            'nick_name'  => 'Third',
        ]);
        factory(\Jihe\Models\TeamMember::class)->create([
            'team_id' => 1,
            'user_id' => 1,
            'visibility' => TeamMember::VISIBILITY_ALL,
            'status'  => TeamMember::STATUS_NORMAL,
        ]);
        factory(\Jihe\Models\TeamMember::class)->create([
            'team_id' => 1,
            'user_id' => 2,
            'visibility' => TeamMember::VISIBILITY_ALL,
            'status'  => TeamMember::STATUS_NORMAL,
        ]);
        factory(\Jihe\Models\TeamMember::class)->create([
            'team_id' => 1,
            'user_id' => 3,
            'visibility' => TeamMember::VISIBILITY_ALL,
            'status'  => TeamMember::STATUS_NORMAL,
        ]);
        $this->startSession();

        // the first page
        $this->actingAs(factory(\Jihe\Models\User::class)->make())
            ->get('/api/team/member/list?team=1&page=1&size=2');

        $response = json_decode($this->response->getContent());
        self::assertEquals(0, $response->code);
        self::assertCount(2, $response->members);
        self::assertEquals(2, $response->pages);

        // the second page
        $this->actingAs(factory(\Jihe\Models\User::class)->make())
            ->get('/api/team/member/list?team=1&page=2&size=2');

        $response = json_decode($this->response->getContent());
        self::assertEquals(0, $response->code);
        self::assertCount(1, $response->members);
        self::assertEquals(2, $response->pages);

        // the third page -- same as the second one
        $this->actingAs(factory(\Jihe\Models\User::class)->make())
            ->get('/api/team/member/list?team=1&page=3&size=2');

        $response = json_decode($this->response->getContent());
        self::assertEquals(0, $response->code);
        self::assertCount(1, $response->members);
        self::assertEquals(2, $response->pages);
    }

}
