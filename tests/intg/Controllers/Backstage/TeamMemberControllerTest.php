<?php
namespace intg\Jihe\Controllers\Backstage;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use intg\Jihe\MappedMimeTypeGuesser;
use intg\Jihe\RequestSignCheck;
use intg\Jihe\AuthDeviceCheck;
use intg\Jihe\TestCase;
use Jihe\Entities\TeamGroup;
use Jihe\Entities\TeamMember;
use Jihe\Entities\TeamMemberEnrollmentPermission;
use Jihe\Entities\TeamMemberEnrollmentRequest;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesser;
use \PHPUnit_Framework_Assert as Assert;


class TeamMemberControllerTest extends TestCase
{
    use DatabaseTransactions;
    use RequestSignCheck;
    use AuthDeviceCheck;

    //=========================================
    //   listPendingEnrollmentRequestForTeam
    //=========================================
    public function testListPendingEnrollmentRequestForTeam_Empty()
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

        $this->actingAs($user)->ajaxGet('/community/team/member/enrollment/pending?team=1');
        $response = json_decode($this->response->getContent());
        self::assertEquals(0, $response->code);
        self::assertEmpty($response->requests);
    }

    public function testListPendingEnrollmentRequestForTeam_NotEmpty()
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
        factory(\Jihe\Models\User::class)->create([
            'id'     => 1,  //
            'mobile' => '13800138000',
        ]);
        factory(\Jihe\Models\TeamMemberEnrollmentRequest::class)->create([
            'team_id'      => 1,
            'initiator_id' => 1,
        ]);
        $this->startSession();

        $this->actingAs($user)->ajaxGet('/community/team/member/enrollment/pending?team=1');

        $response = json_decode($this->response->getContent());
        self::assertEquals(0, $response->code);
        self::assertEquals(1, $response->pages);
        self::assertCount(1, $response->requests);

        $request = $response->requests[0];
        self::assertObjectHasAttribute('id', $request);
        self::assertObjectHasAttribute('initiator', $request);
        self::assertEquals(1, $request->initiator->id);
        self::assertEquals('13800138000',  $request->initiator->mobile);
    }

    //=========================================
    //   listRejectedEnrollmentRequestForTeam
    //=========================================
    public function testListRejectedEnrollmentRequestForTeam_Empty()
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

        $this->actingAs($user)->ajaxGet('/community/team/member/enrollment/rejected?team=1');
        $response = json_decode($this->response->getContent());
        self::assertEquals(0, $response->code);
        self::assertEquals(0, $response->pages);
        self::assertEmpty($response->requests);
    }

    public function testListRejectedEnrollmentRequestForTeam_NotEmpty()
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
        factory(\Jihe\Models\User::class)->create([
            'id'     => 1,  //
            'mobile' => '13800138000',
        ]);
        factory(\Jihe\Models\TeamMemberEnrollmentRequest::class)->create([
            'team_id'      => 1,
            'initiator_id' => 1,
            'status'       => TeamMemberEnrollmentRequest::STATUS_REJECTED,
        ]);
        $this->startSession();

        $this->actingAs($user)->ajaxGet('/community/team/member/enrollment/rejected?team=1');
        $response = json_decode($this->response->getContent());
        self::assertEquals(0, $response->code);
        self::assertCount(1, $response->requests);

        $request = $response->requests[0];
        self::assertObjectHasAttribute('id', $request);
        self::assertObjectHasAttribute('initiator', $request);
        self::assertEquals(1,  $request->initiator->id);
        self::assertEquals('13800138000',  $request->initiator->mobile);
    }

    //=========================================
    //            listMembers
    //=========================================
    public function testListMembers_AsTeamManager()
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
        $user = factory(\Jihe\Models\User::class)->create([
            'id'         => 1,
            'nick_name'  => 'First',
        ]);
        factory(\Jihe\Models\User::class)->create([
            'id'         => 2,
            'nick_name'  => 'Second',
        ]);
        factory(\Jihe\Models\TeamMember::class)->create([
            'team_id' => 1,
            'user_id' => 1,
            'visibility' => TeamMember::VISIBILITY_ALL,
            'status'  => TeamMember::STATUS_NORMAL,
            'created_at' => date('Y-m-d H:i:s', time() + 2),
        ]);
        factory(\Jihe\Models\TeamMember::class)->create([
            'team_id' => 1,
            'user_id' => 2,
            'visibility' => TeamMember::VISIBILITY_TEAM,
            'status'  => TeamMember::STATUS_NORMAL,
            'created_at' => date('Y-m-d H:i:s', time() + 4),
        ]);

        $this->actingAs($user)->ajaxGet('/community/team/member/list?team=1');

        $response = json_decode($this->response->getContent());
        self::assertEquals(0, $response->code);
        self::assertCount(2, $response->members);
        self::assertEquals(1, $response->pages);
        self::assertEquals('First', $response->members[0]->name);
        self::assertEquals(1, $response->members[0]->id);
        self::assertEquals('Second', $response->members[1]->name);
        self::assertEquals(2, $response->members[1]->id);
    }

    public function testListMembers_WithRequirements()
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
        $user = factory(\Jihe\Models\User::class)->create([
            'id'         => 1,
            'nick_name'  => 'First',
        ]);
        factory(\Jihe\Models\User::class)->create([
            'id'         => 2,
            'nick_name'  => 'Second',
        ]);
        factory(\Jihe\Models\TeamMember::class)->create([
            'id'      => 1,
            'team_id' => 1,
            'user_id' => 1,
            'visibility' => TeamMember::VISIBILITY_ALL,
            'status'  => TeamMember::STATUS_NORMAL,
            'created_at' => '2015-08-06 10:12:13',
        ]);
        factory(\Jihe\Models\TeamMember::class)->create([
            'id'      => 2,
            'team_id' => 1,
            'user_id' => 2,
            'visibility' => TeamMember::VISIBILITY_TEAM,
            'status'  => TeamMember::STATUS_NORMAL,
            'created_at' => '2015-08-06 11:12:13',
        ]);
        factory(\Jihe\Models\TeamMemberRequirement::class)->create([
            'member_id'      => 2,
            'requirement_id' => 1,
            'value'          => 'test'
        ]);

        $this->actingAs($user)->ajaxGet('/community/team/member/list?team=1');

        $response = json_decode($this->response->getContent());
        self::assertEquals(0, $response->code);
        self::assertCount(2, $response->members);
        self::assertEquals(1, $response->pages);
        self::assertEquals('First', $response->members[0]->name);
        self::assertEquals(1, $response->members[0]->id);
        self::assertEquals('Second', $response->members[1]->name);
        self::assertEquals(2, $response->members[1]->id);
        self::assertNotEmpty($response->members[1]->requirements);
    }

    //=========================================
    //            exportMembers
    //=========================================
    public function testExportMembers()
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
        $user = factory(\Jihe\Models\User::class)->create([
            'id'         => 1,
            'nick_name'  => 'First',
        ]);
        factory(\Jihe\Models\User::class)->create([
            'id'         => 2,
            'nick_name'  => 'Second',
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
            'visibility' => TeamMember::VISIBILITY_TEAM,
            'status'  => TeamMember::STATUS_NORMAL,
        ]);

        $this->actingAs($user)->get('/community/team/member/export?team=1');
        self::assertStringStartsWith('attachment', $this->response->headers->get('Content-Disposition'));
    }

    //=========================================
    //         rejectEnrollmentRequest
    //=========================================
    public function testRejectEnrollmentRequest()
    {
        $this->expectsJobs(\Jihe\Jobs\SendMessageToUserJob::class);
        factory(\Jihe\Models\Team::class)->create([
            'id'         => 1,
            'city_id'    => 1,
            'creator_id' => 2
        ]);
        factory(\Jihe\Models\TeamMemberEnrollmentRequest::class)->create([
            'id'           => 1,
            'initiator_id' => 1,
            'team_id'      => 1,
        ]);
        factory(\Jihe\Models\City::class)->create([
            'id'   => 1,
            'name' => '成都',
        ]);
        factory(\Jihe\Models\User::class)->create([
            'id'     => 1,
            'mobile' => '13800138000',
        ]);
        $user = factory(\Jihe\Models\User::class)->create([
            'id'     => 2,  // Team1的团长
            'mobile' => '13800138001',
        ]);
        $this->startSession();

        $this->actingAs($user)->ajaxPost('/community/team/member/enrollment/reject', [
            'request' => 1,
            'reason'  => '非法',
            '_token'  => csrf_token(),
        ]);

        $response = json_decode($this->response->getContent());
        self::assertEquals(0, $response->code);
        self::assertEquals('拒绝入团申请成功', $response->message);

        $this->notSeeInDatabase('team_members', [
            'user_id'  => 1,
            'team_id'  => 1,
        ]);
        $this->seeInDatabase('team_member_enrollment_requests', [
            'initiator_id'  => 1,
            'team_id'       => 1,
            'status'        => TeamMemberEnrollmentRequest::STATUS_REJECTED,
        ]);
    }

    public function testRejectEnrollmentRequest_BadRequest()
    {
        factory(\Jihe\Models\Team::class)->create([
            'id'         => 1,
            'city_id'    => 1,
            'creator_id' => 2
        ]);
        factory(\Jihe\Models\TeamMemberEnrollmentRequest::class)->create([
            'id'           => 1,
            'initiator_id' => 1,
            'team_id'      => 1,
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

        $this->actingAs($user)->ajaxPost('/community/team/member/enrollment/reject', [
            'request' => 2,     // no such request
            '_token'  => csrf_token(),
        ]);

        $response = json_decode($this->response->getContent());
        self::assertEquals(10000, $response->code);
    }

    //=========================================
    //         rejectEnrollmentRequests
    //=========================================
    public function testRejectEnrollmentRequests()
    {
        $this->expectsJobs(\Jihe\Jobs\SendMessageToUserJob::class);
        factory(\Jihe\Models\Team::class)->create([
            'id'         => 1,
            'city_id'    => 1,
            'creator_id' => 1,
        ]);
        factory(\Jihe\Models\TeamMemberEnrollmentRequest::class)->create([
            'id'           => 1,
            'initiator_id' => 1,
            'team_id'      => 1,
            'status'       => TeamMemberEnrollmentRequest::STATUS_PENDING,
        ]);
        factory(\Jihe\Models\TeamMemberEnrollmentRequest::class)->create([
            'id'           => 2,
            'initiator_id' => 2,
            'team_id'      => 1,
            'status'       => TeamMemberEnrollmentRequest::STATUS_PENDING,
        ]);
        factory(\Jihe\Models\City::class)->create([
            'id'   => 1,
            'name' => '成都',
        ]);
        $user = factory(\Jihe\Models\User::class)->create([
            'id'     => 1,  // Team1的团长
            'mobile' => '13800138001',
        ]);
        factory(\Jihe\Models\User::class)->create([
            'id'     => 2,
            'mobile' => '13800138000',
        ]);
        factory(\Jihe\Models\User::class)->create([
            'id'     => 3,
            'mobile' => '13800138003',
        ]);
        $this->startSession();

        $this->actingAs($user)->ajaxPost('/community/team/member/enrollments/reject', [
            'requests' => [1, 2],
            'reason'   => '非法',
            '_token'   => csrf_token(),
        ]);

        $response = json_decode($this->response->getContent());
        self::assertEquals(0, $response->code);
        self::assertEquals('拒绝入团申请成功', $response->message);

        $this->notSeeInDatabase('team_members', [
            'user_id'  => 1,
            'team_id'  => 1,
        ]);
        $this->notSeeInDatabase('team_members', [
            'user_id'  => 2,
            'team_id'  => 1,
        ]);
        $this->seeInDatabase('team_member_enrollment_requests', [
            'initiator_id'  => 1,
            'team_id'       => 1,
            'status'        => TeamMemberEnrollmentRequest::STATUS_REJECTED,
        ]);
        $this->seeInDatabase('team_member_enrollment_requests', [
            'initiator_id'  => 2,
            'team_id'       => 1,
            'status'        => TeamMemberEnrollmentRequest::STATUS_REJECTED,
        ]);
    }

    //=========================================
    //        updateEnrollmentRequest
    //=========================================
    public function testUpdateEnrollmentRequest()
    {
        factory(\Jihe\Models\Team::class)->create([
            'id'         => 1,
            'city_id'    => 1,
            'creator_id' => 2
        ]);
        factory(\Jihe\Models\TeamMemberEnrollmentRequest::class)->create([
            'id'           => 1,
            'initiator_id' => 1,
            'team_id'      => 1,
            'memo'         => 'old memo',
        ]);
        factory(\Jihe\Models\City::class)->create([
            'id'   => 1,
            'name' => '成都',
        ]);
        factory(\Jihe\Models\User::class)->create([
            'id'     => 1,
            'mobile' => '13800138000',
        ]);
        $user = factory(\Jihe\Models\User::class)->create([
            'id'     => 2,  // Team1的团长
            'mobile' => '13800138001',
        ]);
        $this->startSession();

        $this->actingAs($user)->ajaxPost('/community/team/member/enrollment/update', [
            'request' => 1,
            'memo'    => 'new memo',
            '_token'  => csrf_token(),
        ]);

        $response = json_decode($this->response->getContent());
        self::assertEquals(0, $response->code);

        $this->seeInDatabase('team_member_enrollment_requests', [
            'initiator_id'  => 1,
            'team_id'       => 1,
            'memo'          => 'new memo',
        ]);
    }

    //=========================================
    //        approveEnrollmentRequest
    //=========================================
    public function testApproveEnrollmentRequest()
    {
        $this->expectsJobs(\Jihe\Jobs\SendMessageToTeamMemberJob::class);
        factory(\Jihe\Models\Team::class)->create([
            'id'         => 1,
            'city_id'    => 1,
            'creator_id' => 2
        ]);
        factory(\Jihe\Models\TeamMemberEnrollmentRequest::class)->create([
            'id'           => 1,
            'initiator_id' => 1,
            'team_id'      => 1,
        ]);
        factory(\Jihe\Models\City::class)->create([
            'id'   => 1,
            'name' => '成都',
        ]);
        factory(\Jihe\Models\User::class)->create([
            'id'     => 1,
            'mobile' => '13800138000',
        ]);
        $user = factory(\Jihe\Models\User::class)->create([
            'id'     => 2,  // Team1的团长
            'mobile' => '13800138001',
        ]);
        $this->startSession();

        $this->actingAs($user)->ajaxPost('/community/team/member/enrollment/approve', [
            'request' => 1,
            '_token'  => csrf_token(),
        ]);

        $response = json_decode($this->response->getContent());
        self::assertEquals(0, $response->code);
        self::assertEquals('批准入团申请成功', $response->message);

        $this->seeInDatabase('team_members', [
            'group_id' => TeamGroup::UNGROUPED,
            'user_id'  => 1,
            'team_id'  => 1,
        ]);
        $this->seeInDatabase('team_member_enrollment_requests', [
            'initiator_id'  => 1,
            'team_id'       => 1,
            'status'        => TeamMemberEnrollmentRequest::STATUS_APPROVED,
        ]);
    }

    public function testApproveEnrollmentRequest_WithMemo()
    {
        $this->expectsJobs(\Jihe\Jobs\SendMessageToTeamMemberJob::class);
        factory(\Jihe\Models\Team::class)->create([
            'id'         => 1,
            'city_id'    => 1,
            'creator_id' => 2
        ]);
        factory(\Jihe\Models\TeamMemberEnrollmentRequest::class)->create([
            'id'           => 1,
            'initiator_id' => 1,
            'team_id'      => 1,
        ]);
        factory(\Jihe\Models\City::class)->create([
            'id'   => 1,
            'name' => '成都',
        ]);
        factory(\Jihe\Models\User::class)->create([
            'id'     => 1,
            'mobile' => '13800138000',
        ]);
        $user = factory(\Jihe\Models\User::class)->create([
            'id'     => 2,  // Team1的团长
            'mobile' => '13800138001',
        ]);
        $this->startSession();

        $this->actingAs($user)->ajaxPost('/community/team/member/enrollment/approve', [
            'request' => 1,
            'memo'    => 'hello',
            '_token'  => csrf_token(),
        ]);

        $response = json_decode($this->response->getContent());
        self::assertEquals(0, $response->code);
        self::assertEquals('批准入团申请成功', $response->message);

        $this->seeInDatabase('team_members', [
            'group_id' => TeamGroup::UNGROUPED,
            'user_id'  => 1,
            'team_id'  => 1,
            'memo'     => 'hello',
        ]);
        $this->seeInDatabase('team_member_enrollment_requests', [
            'initiator_id'  => 1,
            'team_id'       => 1,
            'status'        => TeamMemberEnrollmentRequest::STATUS_APPROVED,
        ]);
    }

    //=========================================
    //         changeMemberGroup
    //=========================================
    public function testChangeMemberGroup_Single()
    {
        factory(\Jihe\Models\Team::class)->create([
            'id'         => 1,
            'city_id'    => 1,
            'creator_id' => 2
        ]);
        factory(\Jihe\Models\TeamGroup::class)->create([
            'id'         => 1,
            'team_id'    => 1,
            'name'       => '第一小组'
        ]);
        factory(\Jihe\Models\TeamGroup::class)->create([
            'id'         => 2,
            'team_id'    => 1,
            'name'       => '第二小组'
        ]);
        factory(\Jihe\Models\TeamMember::class)->create([
            'team_id'  => 1,
            'user_id'  => 1,
            'group_id' => 1,
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

        $this->actingAs($user)->ajaxPost('/community/team/member/group/update', [
            'team'   => 1,
            'to'     => 2,
            'member' => [1, 2],
            '_token' => csrf_token(),
        ])->seeJsonContains([ 'code' => 0 ]);
    }

    //=========================================
    //           update
    //=========================================
    public function testUpdate()
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
        factory(\Jihe\Models\TeamMember::class)->create([
            'team_id' => 1,
            'user_id' => 1,
            'memo'    => 'old memo',
        ]);
        $this->startSession();

        $this->actingAs($user)->ajaxPost('/community/team/member/update', [
            'team'   => 1,
            'member' => 1,
            'memo'   => 'new memo',
            '_token' => csrf_token(),
        ])->seeJsonContains(['code' => 0]);

        $this->seeInDatabase('team_members', [
            'team_id' => 1,
            'user_id' => 1,
            'memo'    => 'new memo',
        ]);
    }


    //=========================================
    //         importEnrollmentWhitelist
    //=========================================
    public function testImportEnrollmentWhitelist_AllFailed()
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
        $this->mockExcelForUploading(__DIR__ . '/test-data/Team/Member/Whitelist/all_failed.xls');
        $this->startSession();

        $this->actingAs($user)->ajaxCall('POST', '/community/team/member/enrollment/whitelist/import', [
            'team'   => 1,
            '_token' => csrf_token(),
        ], [], [
            'whitelist' => $this->makeUploadFile($this->getMockedExcelPath('all_failed.xls')),
        ]);

        $response = json_decode($this->response->getContent());
        self::assertEquals(0, $response->code);
        self::assertNotEmpty($response->failed);
    }

    public function testImportEnrollmentWhitelist_SuccessWithFailedItems()
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
        $this->mockExcelForUploading(__DIR__ . '/test-data/Team/Member/Whitelist/success_with_failed.xls');
        $this->startSession();
        $this->expectsJobs(\Jihe\Jobs\SendMessageToUserJob::class);

        $this->actingAs($user)->ajaxCall('POST', '/community/team/member/enrollment/whitelist/import', [
            'team'   => 1,
            '_token' => csrf_token(),
        ], [], [
            'whitelist' => $this->makeUploadFile($this->getMockedExcelPath('success_with_failed.xls')),
        ]);

        $response = json_decode($this->response->getContent());
        self::assertEquals(0, $response->code);
        self::assertCount(1, $response->failed);

        $this->seeInDatabase('team_member_enrollment_permissions', [
            'mobile' => '18612345678',
            'name'   => '谁谁谁',
            'status' => TeamMemberEnrollmentPermission::STATUS_PERMITTED
        ]);
    }

    //=========================================
    //        addEnrollmentWhitelist
    //=========================================
    public function testAddEnrollmentWhitelist()
    {
        $this->expectsJobs(\Jihe\Jobs\SendMessageToUserJob::class);

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

        $this->actingAs($user)->ajaxCall('POST', '/community/team/member/enrollment/whitelist', [
            'team'   => 1,
            'mobile' => '13800138000',
            '_token' => csrf_token(),
        ])->seeJsonContains([ 'code' => 0 ]);
    }

    public function testAddEnrollmentWhitelist_InBlacklist()
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
        factory(\Jihe\Models\TeamMemberEnrollmentPermission::class)->create([
            'team_id' => 1,
            'mobile'  => '13800138000',
            'status'  => TeamMemberEnrollmentPermission::STATUS_PROHIBITED,
        ]);
        $user = factory(\Jihe\Models\User::class)->create([
            'id'     => 2,  // Team1的团长
            'mobile' => '13800138001',
        ]);
        $this->startSession();

        $this->actingAs($user)->ajaxCall('POST', '/community/team/member/enrollment/whitelist', [
            'team'   => 1,
            'mobile' => '13800138000',
            '_token' => csrf_token(),
        ]);
        $response = json_decode($this->response->getContent());
        Assert::assertEquals(10000, $response->code);
        Assert::assertEquals('用户已经在黑名单中', $response->message);
    }

    public function testAddEnrollmentWhitelist_InWhitelist()
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
        factory(\Jihe\Models\TeamMemberEnrollmentPermission::class)->create([
            'team_id' => 1,
            'mobile'  => '13800138000',
            'status'  => TeamMemberEnrollmentPermission::STATUS_PERMITTED,
        ]);
        $user = factory(\Jihe\Models\User::class)->create([
            'id'     => 2,  // Team1的团长
            'mobile' => '13800138001',
        ]);
        $this->startSession();

        $this->actingAs($user)->ajaxCall('POST', '/community/team/member/enrollment/whitelist', [
            'team'   => 1,
            'mobile' => '13800138000',
            '_token' => csrf_token(),
        ]);
        $response = json_decode($this->response->getContent());
        Assert::assertEquals(10000, $response->code);
        Assert::assertEquals('用户已经在白名单中', $response->message);
    }

    //=========================================
    //    whiteBlacklistedEnrollmentRequest
    //=========================================
    public function testWhiteBlacklistedEnrollmentRequest()
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
        factory(\Jihe\Models\TeamMemberEnrollmentPermission::class)->create([
            'team_id' => 1,
            'mobile'  => '13800138000',
        ]);
        factory(\Jihe\Models\User::class)->create([
            'id'     => 1,
            'mobile' => '13800138000',
        ]);
        $this->startSession();

        $this->actingAs(factory(\Jihe\Models\User::class)->create([
            'id'     => 2,  // Team1的团长
            'mobile' => '13800138001',
        ]))->ajaxDelete('/community/team/member/enrollment/blacklist', [
            'mobile' => '13800138000',
            '_token' => csrf_token(),
        ])->seeJsonContains([ 'code' => 0 ]);

        $this->notSeeInDatabase('team_member_enrollment_permissions', [
            'team_id' => 1,
            'mobile'  => '13800138000',
        ]);
    }

    public function testWhiteBlacklistedEnrollmentRequest_NoSuchPermission()
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
            'id'     => 1,
            'mobile' => '13800138000',
        ]);
        $this->startSession();

        $this->actingAs(factory(\Jihe\Models\User::class)->create([
            'id'     => 2,  // Team1的团长
            'mobile' => '13800138001',
        ]))->ajaxDelete('/community/team/member/enrollment/blacklist', [
            'mobile' => '13800138000',
            '_token' => csrf_token(),
        ])->seeJsonContains([ 'code' => 0 ]);
    }

    //=========================================
    //        updateEnrollmentWhitelist
    //=========================================
    public function testUpdateEnrollmentWhitelist()
    {
        factory(\Jihe\Models\Team::class)->create([
            'id'         => 1,
            'city_id'    => 1,
            'creator_id' => 2
        ]);
        factory(\Jihe\Models\TeamMemberEnrollmentPermission::class)->create([
            'team_id' => 1,
            'mobile'  => '13800138000',
            'memo'    => 'old memo'
        ]);
        factory(\Jihe\Models\City::class)->create([
            'id'   => 1,
            'name' => '成都',
        ]);
        factory(\Jihe\Models\User::class)->create([
            'id'     => 1,
            'mobile' => '13800138000',
        ]);
        $user = factory(\Jihe\Models\User::class)->create([
            'id'     => 2,  // Team1的团长
            'mobile' => '13800138001',
        ]);
        $this->startSession();

        $this->actingAs($user)->ajaxPost('/community/team/member/enrollment/whitelist/update', [
            'mobile'  => '13800138000',
            'memo'    => 'new memo',
            '_token'  => csrf_token(),
        ]);

        $response = json_decode($this->response->getContent());
        self::assertEquals(0, $response->code);

        $this->seeInDatabase('team_member_enrollment_permissions', [
            'team_id' => 1,
            'mobile'  => '13800138000',
            'memo'    => 'new memo'
        ]);
    }

    //=========================================
    //        showEnrollmentWhitelist
    //=========================================
    public function testShowEnrollmentWhitelist()
    {
        factory(\Jihe\Models\Team::class)->create([
            'id'         => 1,
            'creator_id' => 2,
            'city_id'    => 1,
        ]);
        factory(\Jihe\Models\City::class)->create([
            'id'   => 1,
            'name' => '成都',
        ]);
        factory(\Jihe\Models\TeamMemberEnrollmentPermission::class)->create([
            'team_id' => 1,
            'mobile'  => '13800138001',
            'name'    => 'First',
            'status'  => TeamMemberEnrollmentPermission::STATUS_PERMITTED,
        ]);
        factory(\Jihe\Models\TeamMemberEnrollmentPermission::class)->create([
            'team_id' => 1,
            'mobile'  => '13800138002',
            'name'    => 'Second',
            'status'  => TeamMemberEnrollmentPermission::STATUS_PROHIBITED,
        ]);

        $this->actingAs(factory(\Jihe\Models\User::class)->create([
           'id' => 2,
        ]))->ajaxGet('/community/team/member/enrollment/whitelist?team=1');

        $response = json_decode($this->response->getContent());
        self::assertEquals(0, $response->code);
        self::assertCount(1, $response->whitelist);

        $permission = $response->whitelist[0];
        self::assertEquals('First', $permission->name);
        self::assertEquals('13800138001', $permission->mobile);
    }

    //=========================================
    //        showEnrollmentBlacklist
    //=========================================
    public function testShowEnrollmentBlacklist()
    {
        factory(\Jihe\Models\Team::class)->create([
            'id'         => 1,
            'creator_id' => 2,
            'city_id'    => 1,
        ]);
        factory(\Jihe\Models\City::class)->create([
            'id'   => 1,
            'name' => '成都',
        ]);
        factory(\Jihe\Models\TeamMemberEnrollmentPermission::class)->create([
            'team_id' => 1,
            'mobile'  => '13800138001',
            'name'    => 'First',
            'status'  => TeamMemberEnrollmentPermission::STATUS_PERMITTED,
        ]);
        factory(\Jihe\Models\TeamMemberEnrollmentPermission::class)->create([
            'team_id' => 1,
            'mobile'  => '13800138002',
            'name'    => 'Second',
            'status'  => TeamMemberEnrollmentPermission::STATUS_PROHIBITED,
        ]);

        $this->actingAs(factory(\Jihe\Models\User::class)->create([
            'id' => 2,
        ]))->ajaxGet('/community/team/member/enrollment/blacklist?team=1');

        $response = json_decode($this->response->getContent());
        self::assertEquals(0, $response->code);
        self::assertCount(1, $response->blacklist);

        $permission = $response->blacklist[0];
        self::assertEquals('Second', $permission->name);
        self::assertEquals('13800138002', $permission->mobile);
    }

    //=========================================
    //      blacklistEnrollmentRequest
    //=========================================
    public function testBlacklistEnrollmentRequest()
    {
        $this->expectsJobs(\Jihe\Jobs\SendMessageToUserJob::class);
        factory(\Jihe\Models\Team::class)->create([
            'id'         => 1,
            'city_id'    => 1,
            'creator_id' => 2
        ]);
        factory(\Jihe\Models\City::class)->create([
            'id'   => 1,
            'name' => '成都',
        ]);
        factory(\Jihe\Models\TeamMemberEnrollmentRequest::class)->create([
            'id'           => 1,
            'team_id'      => 1,
            'initiator_id' => 1,
            'status'       => TeamMemberEnrollmentRequest::STATUS_PENDING,
        ]);
        factory(\Jihe\Models\User::class)->create([
            'id'     => 1,
            'mobile' => '13800138000',
        ]);
        $user = factory(\Jihe\Models\User::class)->create([
            'id'     => 2,
            'mobile' => '13800138001',
        ]);
        $this->startSession();

        $this->actingAs($user)->ajaxCall('POST', '/community/team/member/enrollment/blacklist', [
            'team'    => 1,
            'request' => 1,
            '_token'  => csrf_token(),
        ]);

        $response = json_decode($this->response->getContent());
        self::assertEquals(0, $response->code);
        $this->seeInDatabase('team_member_enrollment_permissions', [
            'mobile'  => '13800138000',
            'team_id' => 1,
            'status'  => TeamMemberEnrollmentPermission::STATUS_PROHIBITED,
        ]);
    }

    /**
     * mock excel file for uploading (as whitelist)
     * @param string $path    file path to existing excel file
     * @param string $name    name in the mocked file system (virtual file system, exactly)
     */
    private function mockExcelForUploading($path, $name = null)
    {
        // populate name if not given
        !$name && $name = pathinfo($path, PATHINFO_BASENAME);

        // put excel files in 'teammember' directory - the root directory
        // NOTE: 'teammember' is also used by getMockedExcelUrl() below
        $this->mockFiles('teammember', [
            $name => file_get_contents($path),
        ]);

        // mime_content_type() does not produce precise mime type sometimes,
        // for example, mime_content_type('/path/to/excel/file') may produce
        // 'application/vnd.ms-office', but we want is 'application/vnd.ms-excel'
        // so that ExtensionGuessers can give desired file extension and validation
        // against mime types (the mimes validator) can pass.
        // On the other hand, FileinfoMimeTypeGuesser cannot produce desired
        // mime type as well. For mocked excel files, it simply produces
        // 'application/octet-stream', which is definitely improper.
        MimeTypeGuesser::getInstance()->register(new MappedMimeTypeGuesser([
            $this->getMockedExcelPath($name) => 'application/vnd.ms-excel'
        ]));
    }

    /**
     * get mocked excel file's full path (including scheme and root directory)
     *
     * @param $path      path to mocked excel file
     * @return string    full path of the mocked excel file
     */
    private function getMockedExcelPath($path)
    {
        return $this->getMockedFilePath(sprintf('teammember/%s', ltrim($path, '/')));
    }
}
