<?php
namespace intg\Jihe\Repositories;

use intg\Jihe\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Jihe\Entities\TeamMemberEnrollmentRequest;
use Jihe\Entities\TeamMemberEnrollmentPermission;
use \PHPUnit_Framework_Assert as Assert;
use Jihe\Entities\TeamGroup;
use Jihe\Entities\Team;

class TeamMemberEnrollmentRequestRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    //==============================
    //         add
    //==============================
    public function testAdd()
    {
        // add user(#1) to team(#2)
        $request = $this->getRepository()->add(1, 2, [
            'name' => '群名字',
            'memo' => '备注信息',
            'requirements' => [
                1 => 'BMW',
                2 => '15k+',
            ]
        ]);
        Assert::assertTrue($request > 0, 'id should be given after addition');

        $this->seeInDatabase('team_member_enrollment_requests', [
            'initiator_id'  => 1,
            'team_id'       => 2,
            'name'          => '群名字',
            'memo'          => '备注信息',
            'status'        => TeamMemberEnrollmentRequest::STATUS_PENDING,
            'group_id'      => TeamGroup::UNGROUPED,
        ]);
        $this->seeInDatabase('team_member_enrollment_requirements', [
            'request_id'     => $request,
            'requirement_id' => 1,
            'value'          => 'BMW',
        ]);
        $this->seeInDatabase('team_member_enrollment_requirements', [
            'request_id'     => $request,
            'requirement_id' => 2,
            'value'          => '15k+',
        ]);
    }

    //==============================
    //        findPermission
    //==============================
    public function testFindPermission_Prohibited()
    {
        factory(\Jihe\Models\TeamMemberEnrollmentPermission::class)->create([
            'mobile'  => '13800138000',
            'team_id' => 1,
            'status'  => \Jihe\Entities\TeamMemberEnrollmentPermission::STATUS_PROHIBITED,
            'memo'    => '恶意用户',
        ]);

        $permission = $this->getRepository()->findPermission('13800138000', 1);
        self::assertNotNull($permission);
        self::assertTrue($permission->prohibited());
        self::assertEquals('恶意用户', $permission->getMemo());
    }

    public function testFindPermission_Permitted()
    {
        factory(\Jihe\Models\TeamMemberEnrollmentPermission::class)->create([
            'mobile'  => '13800138000',
            'team_id' => 1,
            'status'  => \Jihe\Entities\TeamMemberEnrollmentPermission::STATUS_PERMITTED,
        ]);

        $permission = $this->getRepository()->findPermission('13800138000', 1);
        self::assertNotNull($permission);
        self::assertTrue($permission->permitted());
    }

    public function testFindPermission_NotFound()
    {
        $permission = $this->getRepository()->findPermission('13800138000', 1);
        self::assertNull($permission);
    }
    
    //==============================
    //        findTeamsWhitelistedUser
    //==============================
    public function testFindTeamsWhitelistedUser()
    {
        factory(\Jihe\Models\TeamMemberEnrollmentPermission::class)->create([
            'mobile'  => '13800138000',
            'team_id' => 1,
            'status'  => \Jihe\Entities\TeamMemberEnrollmentPermission::STATUS_PROHIBITED,
            'memo'    => '恶意用户',
        ]);
        factory(\Jihe\Models\TeamMemberEnrollmentPermission::class)->create([
            'mobile'  => '13800138000',
            'team_id' => 2,
            'status'  => \Jihe\Entities\TeamMemberEnrollmentPermission::STATUS_PERMITTED,
        ]);
        factory(\Jihe\Models\Team::class)->create([
            'id' => 2,
            'name' => 'Team#2',
        ]);
    
        $teams = $this->getRepository()->findTeamsWhitelistedUser('13800138000');
        self::assertCount(1, $teams);
        self::assertArrayHasKey(2, $teams);
    }
    
    public function testFindTeamsWhitelistedUser_NoTeams()
    {
        factory(\Jihe\Models\TeamMemberEnrollmentPermission::class)->create([
            'mobile'  => '13800138000',
            'team_id' => 2,
            'status'  => \Jihe\Entities\TeamMemberEnrollmentPermission::STATUS_PROHIBITED,
        ]);
        
        $teams = $this->getRepository()->findTeamsWhitelistedUser('13800138000');
        self::assertEmpty($teams);
    }

    //==============================
    //        addPermission
    //==============================
    public function testAddPermission()
    {
        $permission = $this->getRepository()->addPermission([
            'mobile'  => '13800138000',
            'team'    => 2,
            'name'    => '群昵称',
            'memo'    => '备注',
            'status'  => TeamMemberEnrollmentPermission::STATUS_PERMITTED,
        ]);
        Assert::assertTrue($permission > 0, 'newly added permission should have its id greater than 0');

        $this->seeInDatabase('team_member_enrollment_permissions', [
            'mobile'   => '13800138000',
            'team_id'  => 2,
            'memo'     => '备注',
            'status'   => TeamMemberEnrollmentPermission::STATUS_PERMITTED,
        ]);
    }

    //==============================
    //        updatePermission
    //==============================
    public function testUpdatePermission_OnlyUpdateStatus()
    {
        factory(\Jihe\Models\TeamMemberEnrollmentPermission::class)->create([
            'id'      => 1,
            'memo'    => '备注',
            'mobile'  => '13800138000',
            'team_id' => 1,
            'status'  => TeamMemberEnrollmentPermission::STATUS_PROHIBITED,
        ]);
        Assert::assertTrue($this->getRepository()
            ->updatePermission(1, ['status' => TeamMemberEnrollmentPermission::STATUS_PERMITTED ]));

        $this->seeInDatabase('team_member_enrollment_permissions', [
            'id'       => 1,
            'mobile'   => '13800138000',
            'team_id'  => 1,
            'memo'     => '备注',
            'status'   => TeamMemberEnrollmentPermission::STATUS_PERMITTED,
        ]);
    }

    public function testUpdatePermission_OnlyAcceptStatusAndMemo()
    {
        factory(\Jihe\Models\TeamMemberEnrollmentPermission::class)->create([
            'id'      => 1,
            'memo'    => '备注',
            'mobile'  => '13800138000',
            'team_id' => 1,
            'status'  => TeamMemberEnrollmentPermission::STATUS_PROHIBITED,
        ]);
        Assert::assertTrue($this->getRepository()
            ->updatePermission(1, ['status' => TeamMemberEnrollmentPermission::STATUS_PERMITTED,
                                   'memo'   => '新备注',
                                   'mobile' => '1008611' // will be ignored
            ]));

        $this->seeInDatabase('team_member_enrollment_permissions', [
            'id'       => 1,
            'mobile'   => '13800138000',  // unchanged
            'team_id'  => 1,
            'memo'     => '新备注',
            'status'   => TeamMemberEnrollmentPermission::STATUS_PERMITTED,
        ]);
    }

    //===================================
    //     statPendingEnrollmentRequests
    //===================================
    public function testStatPendingEnrollmentRequests()
    {
        factory(\Jihe\Models\User::class)->create([
            'id' => 1,
            'mobile' => '13111112222',
        ]);
        factory(\Jihe\Models\User::class)->create([
            'id' => 2,
            'mobile' => '13111112221',
        ]);

        factory(\Jihe\Models\User::class)->create([
            'id' => 3,
            'mobile' => '13111112223',
        ]);
        factory(\Jihe\Models\Team::class)->create([
            'id'         => 1,
            'creator_id' => 1,
            'status'     => Team::STATUS_NORMAL,
        ]);
        factory(\Jihe\Models\Team::class)->create([
            'id'         => 2,
            'creator_id' => 2,
            'status'     => Team::STATUS_NORMAL,

        ]);
        factory(\Jihe\Models\Team::class)->create([
            'id'         => 3,
            'creator_id' => 3,
            'status'     => Team::STATUS_FORBIDDEN,
        ]);
        factory(\Jihe\Models\TeamMemberEnrollmentRequest::class)->create([
            'initiator_id' => 1,
            'team_id'      => 1,
            'status'       => \Jihe\Entities\TeamMemberEnrollmentRequest::STATUS_PENDING,
        ]);
        factory(\Jihe\Models\TeamMemberEnrollmentRequest::class)->create([
            'initiator_id' => 2,
            'team_id'      => 1,
            'status'       => \Jihe\Entities\TeamMemberEnrollmentRequest::STATUS_PENDING,
        ]);
        factory(\Jihe\Models\TeamMemberEnrollmentRequest::class)->create([
            'initiator_id' => 1,
            'team_id'      => 2,
            'status'       => \Jihe\Entities\TeamMemberEnrollmentRequest::STATUS_PENDING,
        ]);
        factory(\Jihe\Models\TeamMemberEnrollmentRequest::class)->create([
            'initiator_id' => 3,
            'team_id'      => 3,
            'status'       => \Jihe\Entities\TeamMemberEnrollmentRequest::STATUS_PENDING,
        ]);

        $stats = $this->getRepository()->statPendingEnrollmentRequests();
        Assert::assertCount(2, $stats);
        Assert::assertEquals(2, $stats[1]['pending_requests'], 'team#1\'s pending requests should be 2');
        Assert::assertEquals(1, $stats[2]['pending_requests'], 'team#2\'s pending requests should be 1');
    }

    //===================================
    //     findPendingRequestsForTeam
    //===================================
    public function testFindPendingRequestsForTeam_AllPending()
    {
        factory(\Jihe\Models\User::class)->create([
            'id' => 1
        ]);
        factory(\Jihe\Models\User::class)->create([
            'id' => 2
        ]);
        factory(\Jihe\Models\TeamMemberEnrollmentRequest::class)->create([
            'id'           => 1,
            'initiator_id' => 1,
            'team_id'      => 1,
            'memo'         => null,
            'status'       => \Jihe\Entities\TeamMemberEnrollmentRequest::STATUS_PENDING,
        ]);
        factory(\Jihe\Models\TeamMemberEnrollmentRequest::class)->create([
            'id'           => 2,
            'initiator_id' => 2,
            'team_id'      => 1,
            'memo'         => '二号成员',
            'status'       => \Jihe\Entities\TeamMemberEnrollmentRequest::STATUS_PENDING,
        ]);

        list($pages, $requests) = $this->getRepository()->findPendingRequestsForTeam(1, 1, 15);
        Assert::assertEquals(1, $pages);
        Assert::assertCount(2, $requests);

        Assert::assertEquals(1, $requests[0]->getInitiator()->getId());
        Assert::assertEquals(2, $requests[1]->getInitiator()->getId());
    }

    public function testFindPendingRequestsForTeam_PartialPending()
    {
        factory(\Jihe\Models\User::class)->create([
            'id' => 1
        ]);
        factory(\Jihe\Models\User::class)->create([
            'id' => 2
        ]);
        factory(\Jihe\Models\TeamMemberEnrollmentRequest::class)->create([ // pending
            'id'           => 1,
            'initiator_id' => 1,
            'team_id'      => 1,
            'memo'         => null,
            'status'       => \Jihe\Entities\TeamMemberEnrollmentRequest::STATUS_PENDING,
        ]);
        factory(\Jihe\Models\TeamMemberEnrollmentRequest::class)->create([  // rejected
            'id'           => 2,
            'initiator_id' => 2,
            'team_id'      => 1,
            'memo'         => '二号成员',
            'status'       => \Jihe\Entities\TeamMemberEnrollmentRequest::STATUS_REJECTED,
        ]);

        list($pages, $requests) = $this->getRepository()->findPendingRequestsForTeam(1);
        Assert::assertEquals(1, $pages);
        Assert::assertCount(1, $requests);

        Assert::assertEquals(1, $requests[0]->getInitiator()->getId());
    }

    public function testFindPendingRequestsForTeam_NoPending()
    {
        list($pages, $requests) = $this->getRepository()->findPendingRequestsForTeam(1);
        Assert::assertEquals(0, $pages);
        Assert::assertEmpty($requests);
    }

    //========================================
    //     findPendingRequestsForInitiator
    //========================================
    public function testFindPendingRequestsForInitiator_HasPending()
    {
        factory(\Jihe\Models\Team::class)->create([
            'id' => 1
        ]);
        factory(\Jihe\Models\Team::class)->create([
            'id' => 2
        ]);
        factory(\Jihe\Models\TeamMemberEnrollmentRequest::class)->create([
            'id'           => 1,
            'initiator_id' => 1,
            'team_id'      => 1,
            'memo'         => '加入team1',
            'status'       => \Jihe\Entities\TeamMemberEnrollmentRequest::STATUS_PENDING,
        ]);
        factory(\Jihe\Models\TeamMemberEnrollmentRequest::class)->create([
            'id'           => 2,
            'initiator_id' => 1,
            'team_id'      => 2,
            'memo'         => '加入team2',
            'status'       => \Jihe\Entities\TeamMemberEnrollmentRequest::STATUS_PENDING,
        ]);

        $requests = $this->getRepository()->findPendingRequestsForInitiator(1);
        Assert::assertCount(2, $requests);

        Assert::assertEquals(1, $requests[0]->getTeam()->getId());
        Assert::assertEquals(2, $requests[1]->getTeam()->getId());
    }

    public function testFindPendingRequestsForInitiator_NoPending()
    {
        $requests = $this->getRepository()->findPendingRequestsForInitiator(1);
        Assert::assertEmpty($requests);
    }
    //==============================
    //     pendingRequestExists
    //==============================
    public function testPendingRequestExists_Exists()
    {
        factory(\Jihe\Models\TeamMemberEnrollmentRequest::class)->create([ // pending
            'id'           => 1,
            'initiator_id' => 1,
            'team_id'      => 1,
            'memo'         => null,
            'status'       => \Jihe\Entities\TeamMemberEnrollmentRequest::STATUS_PENDING,
        ]);
        Assert::assertTrue($requests = $this->getRepository()->pendingRequestExists(1, 1));
    }

    public function testPendingRequestExists_NotExists()
    {
        Assert::assertFalse($requests = $this->getRepository()->pendingRequestExists(1, 1));
    }
    
    //==================================
    //     findPendingRequestedTeams
    //==================================
    public function testFindPendingRequestTeams()
    {
        for ($i = 0; $i < 4; $i++) {
            factory(\Jihe\Models\Team::class)->create([
                'id'     => $i + 1,
                'status' => Team::STATUS_NORMAL,
            ]);
        }
        
        factory(\Jihe\Models\TeamMemberEnrollmentRequest::class)->create([ // pending
            'initiator_id' => 1,
            'team_id'      => 1,
            'memo'         => null,
            'status'       => \Jihe\Entities\TeamMemberEnrollmentRequest::STATUS_PENDING,
        ]);
        
        factory(\Jihe\Models\TeamMemberEnrollmentRequest::class)->create([ // pending
            'initiator_id' => 1,
            'team_id'      => 2,
            'memo'         => null,
            'status'       => \Jihe\Entities\TeamMemberEnrollmentRequest::STATUS_PENDING,
        ]);
        
        factory(\Jihe\Models\TeamMemberEnrollmentRequest::class)->create([ // approved
            'initiator_id' => 1,
            'team_id'      => 3,
            'memo'         => null,
            'status'       => \Jihe\Entities\TeamMemberEnrollmentRequest::STATUS_APPROVED,
        ]);
        
        factory(\Jihe\Models\TeamMemberEnrollmentRequest::class)->create([ // rejected
            'initiator_id' => 1,
            'team_id'      => 4,
            'memo'         => null,
            'status'       => \Jihe\Entities\TeamMemberEnrollmentRequest::STATUS_REJECTED,
        ]);
        
        // 2 pending requested teams if initiator is 1
        $this->assertCount(2, $this->getRepository()->findPendingRequestedTeams(1));
        // 0 if initiator is 2
        $this->assertCount(0, $this->getRepository()->findPendingRequestedTeams(2));
    }

    //==================================
    //     updateStatusToApproved
    //==================================
    public function testUpdateStatusToApproved()
    {
        factory(\Jihe\Models\Team::class)->create([
            'id'     => 1,
            'status' => Team::STATUS_NORMAL,
        ]);

        factory(\Jihe\Models\TeamMemberEnrollmentRequest::class)->create([ // pending
            'id'           => 1,
            'initiator_id' => 1,
            'team_id'      => 1,
            'memo'         => null,
            'status'       => \Jihe\Entities\TeamMemberEnrollmentRequest::STATUS_PENDING,
        ]);

        factory(\Jihe\Models\TeamMemberEnrollmentRequest::class)->create([ // pending
            'id'           => 2,
            'initiator_id' => 1,
            'team_id'      => 1,
            'memo'         => null,
            'status'       => \Jihe\Entities\TeamMemberEnrollmentRequest::STATUS_PENDING,
        ]);

        factory(\Jihe\Models\TeamMemberEnrollmentRequest::class)->create([ // pending
            'id'           => 3,
            'initiator_id' => 1,
            'team_id'      => 1,
            'memo'         => null,
            'status'       => \Jihe\Entities\TeamMemberEnrollmentRequest::STATUS_PENDING,
        ]);

        factory(\Jihe\Models\TeamMemberEnrollmentRequest::class)->create([ // approved
            'id'           => 4,
            'initiator_id' => 1,
            'team_id'      => 1,
            'memo'         => null,
            'status'       => \Jihe\Entities\TeamMemberEnrollmentRequest::STATUS_APPROVED,
        ]);

        factory(\Jihe\Models\TeamMemberEnrollmentRequest::class)->create([ // rejected
            'id'           => 5,
            'initiator_id' => 1,
            'team_id'      => 1,
            'memo'         => null,
            'status'       => \Jihe\Entities\TeamMemberEnrollmentRequest::STATUS_REJECTED,
        ]);

        $this->assertTrue($this->getRepository()->updateStatusToApproved(1));
        $this->assertTrue($this->getRepository()->updateStatusToApproved([2, 3]));
        // 1 approved
        $this->assertFalse($this->getRepository()->updateStatusToApproved(4));
        // 1 approved, 1 rejected
        $this->assertFalse($this->getRepository()->updateStatusToApproved(5));
    }

    //==================================
    //     updateStatusToRejected
    //==================================
    public function testUpdateStatusToRejected()
    {
        factory(\Jihe\Models\Team::class)->create([
            'id'     => 1,
            'status' => Team::STATUS_NORMAL,
        ]);

        factory(\Jihe\Models\TeamMemberEnrollmentRequest::class)->create([ // pending
            'id'           => 1,
            'initiator_id' => 1,
            'team_id'      => 1,
            'memo'         => null,
            'status'       => \Jihe\Entities\TeamMemberEnrollmentRequest::STATUS_PENDING,
        ]);

        factory(\Jihe\Models\TeamMemberEnrollmentRequest::class)->create([ // pending
            'id'           => 2,
            'initiator_id' => 1,
            'team_id'      => 1,
            'memo'         => null,
            'status'       => \Jihe\Entities\TeamMemberEnrollmentRequest::STATUS_PENDING,
        ]);

        factory(\Jihe\Models\TeamMemberEnrollmentRequest::class)->create([ // pending
            'id'           => 3,
            'initiator_id' => 1,
            'team_id'      => 1,
            'memo'         => null,
            'status'       => \Jihe\Entities\TeamMemberEnrollmentRequest::STATUS_PENDING,
        ]);

        factory(\Jihe\Models\TeamMemberEnrollmentRequest::class)->create([ // approved
            'id'           => 4,
            'initiator_id' => 1,
            'team_id'      => 1,
            'memo'         => null,
            'status'       => \Jihe\Entities\TeamMemberEnrollmentRequest::STATUS_APPROVED,
                                                                         ]);

        factory(\Jihe\Models\TeamMemberEnrollmentRequest::class)->create([ // rejected
            'id'           => 5,
            'initiator_id' => 1,
            'team_id'      => 1,
            'memo'         => null,
            'status'       => \Jihe\Entities\TeamMemberEnrollmentRequest::STATUS_REJECTED,
        ]);

        $this->assertTrue($this->getRepository()->updateStatusToRejected(1, '不符合条件'));
        $this->assertTrue($this->getRepository()->updateStatusToRejected([2, 3], '不符合条件'));
        // 1 approved
        $this->assertFalse($this->getRepository()->updateStatusToRejected(4, '不符合条件'));
        // 1 approved, 1 rejected
        $this->assertFalse($this->getRepository()->updateStatusToRejected(5, '不符合条件'));
    }

    /**
     * @return \Jihe\Contracts\Repositories\TeamMemberEnrollmentRequestRepository
     */
    private function getRepository()
    {
        return $this->app[\Jihe\Contracts\Repositories\TeamMemberEnrollmentRequestRepository::class];
    }
}
