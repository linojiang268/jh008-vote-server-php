<?php
namespace intg\Jihe\Repositories;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use intg\Jihe\TestCase;
use Jihe\Models\TeamRequest;
use Jihe\Models\City;
use Jihe\Models\User;
use Jihe\Models\Team;
use Jihe\Entities\TeamRequest as TeamRequestEntity;

class TeamRequestRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    //=================================================
    //         hasPendingEnrollmentRequest
    //=================================================
    public function testHasPendingEnrollmentRequest_HasAPendingOne()
    {
        factory(TeamRequest::class)->create([
            'initiator_id' => 1,
            'team_id'      => null, // for enrollment request, team_id MUST be null, and which is default
            'status'       => TeamRequestEntity::STATUS_PENDING
        ]);
        
        self::assertTrue($this->getRepository()->hasPendingEnrollmentRequest(1));
    }
    
    public function testHasPendingEnrollmentRequest_NoPendingRequests()
    {
        self::assertFalse($this->getRepository()->hasPendingEnrollmentRequest(1));
    }

    public function testHasPendingEnrollmentRequest_NoPendingOnesButHandledOnesExist()
    {
        factory(TeamRequest::class)->create([
                'initiator_id' => 1,
                'status'       => TeamRequestEntity::STATUS_APPROVED
        ]);

        factory(TeamRequest::class)->create([
                'initiator_id' => 1,
                'status'       => TeamRequestEntity::STATUS_REJECTED
        ]);

        self::assertFalse($this->getRepository()->hasPendingEnrollmentRequest(1));
    }
    
    //=================================================
    //            hasPendingUpdateRequest
    //=================================================
    public function testHasPendingUpdateRequest_HasOnePendingUpdateRequest()
    {
        factory(TeamRequest::class)->create([
                'id'             => 1,
                'initiator_id'   => 1,
                'team_id'        => 1,
                'status'         => TeamRequestEntity::STATUS_PENDING
        ]);
    
        self::assertTrue($this->getRepository()->hasPendingUpdateRequest(1));
    }
    
    public function testHasPendingUpdateRequest_NoPendingUpdateRequests()
    {
        self::assertFalse($this->getRepository()->hasPendingUpdateRequest(1));
    }
    
    public function testHasPendingUpdateRequest_ApplicationRequestedHasProcess()
    {
        factory(TeamRequest::class)->create([
                'team_id'      => 1,
                'status'       => TeamRequestEntity::STATUS_APPROVED
        ]);
    
        factory(TeamRequest::class)->create([
                'team_id'      => 1,
                'status'       => TeamRequestEntity::STATUS_REJECTED
        ]);
    
        self::assertFalse($this->getRepository()->hasPendingUpdateRequest(1));
    }
    
    //=================================================
    //            findPendingEnrollmentRequest
    //=================================================
    public function testFindPendingEnrollmentRequest_HasOne()
    {
        factory(City::class)->create([
                'id' => 1,
        ]);
        
        factory(User::class)->create([
                'id' => 1,
        ]);
        
        factory(TeamRequest::class)->create([
                'id'           => 1,
                'city_id'      => 1,
                'initiator_id' => 1,
                'status'       => TeamRequestEntity::STATUS_PENDING
        ]);
    
        self::assertRequestEquals(1, 1, null, TeamRequestEntity::STATUS_PENDING, 0, $this->getRepository()->findPendingEnrollmentRequest(1));
    }
    
    public function testFindPendingEnrollmentRequest_NoRequest()
    {
        self::assertNull($this->getRepository()->findPendingEnrollmentRequest(1));
    }
    
    public function testFindPendingEnrollmentRequest_WithApprovedAndRejectedButNoPendingOne()
    {
        factory(TeamRequest::class)->create([
                'city_id'      => 1,
                'initiator_id' => 1,
                'team_id'      => null,
                'status'       => TeamRequestEntity::STATUS_APPROVED
        ]);
    
        factory(TeamRequest::class)->create([
                'city_id'      => 1,
                'initiator_id' => 1,
                'team_id'      => null,
                'status'       => TeamRequestEntity::STATUS_REJECTED
        ]);
    
        self::assertNull($this->getRepository()->findPendingEnrollmentRequest(1));
    }
    
    private static function assertRequestEquals($expectedId, $expectedInitiatorId,
                                                $expectedTeamId, $expectedStatus,
                                                $expectedRead,
                                                TeamRequestEntity $request)
    {
        self::assertNotNull($request);
        self::assertEquals($expectedId, $request->getId());
        self::assertEquals($expectedInitiatorId, $request->getInitiator()->getId());
        if (isset($expectedTeamId)) {
            self::assertEquals($expectedTeamId, $request->getTeam()->getId());
        } else {
            self::assertEquals(null, $request->getTeam());
        }
        self::assertEquals($expectedStatus, $request->getStatus());
        self::assertEquals($expectedRead, $request->isRead());
    }
    
    //=================================================
    //              findPendingRequests
    //=================================================
    public function testFindPendingRequests()
    {
        $user = self::createUser();
        $city = self::createCity();
        $team = self::createTeam($city, $user);

        factory(TeamRequest::class)->create([
            'city_id'      => $city['id'],
            'initiator_id' => $user['id'],
            'team_id'      => null,
            'status'       => TeamRequestEntity::STATUS_PENDING
        ]);

        factory(TeamRequest::class)->create([
            'city_id'      => $city['id'],
            'initiator_id' => $user['id'],
            'team_id'      => $team['id'],
            'status'       => TeamRequestEntity::STATUS_PENDING
        ]);

        factory(TeamRequest::class)->create([
            'city_id'      => $city['id'],
            'initiator_id' => $user['id'],
            'team_id'      => null,
            'status'       => TeamRequestEntity::STATUS_REJECTED
        ]);

        factory(TeamRequest::class)->create([
            'city_id'      => $city['id'],
            'initiator_id' => $user['id'],
            'team_id'      => $team['id'],
            'status'       => TeamRequestEntity::STATUS_APPROVED
        ]);

        list($pages, $requests) = $this->getRepository()->findPendingRequests(1, 2, ['city']);
        self::assertEquals(1, $pages);
        self::assertCount(2, $requests);
    }

    //=================================================
    //            findPendingUpdateRequest
    //=================================================
    public function testFindPendingUpdateRequest_HasOne()
    {
        factory(City::class)->create([
                'id' => 1,
        ]);

        factory(User::class)->create([
                'id' => 1,
        ]);

        factory(Team::class)->create([
                'id' => 1,
        ]);

        factory(TeamRequest::class)->create([
                'id'           => 1,
                'city_id'      => 1,
                'initiator_id' => 1,
                'team_id'      => 1,
                'status'       => TeamRequestEntity::STATUS_PENDING
        ]);

        self::assertRequestEquals(1, 1, 1, TeamRequestEntity::STATUS_PENDING, 0, $this->getRepository()->findPendingUpdateRequest(1));
    }

    public function testFindPendingUpdateRequest_NoRequest()
    {
        self::assertNull($this->getRepository()->findPendingUpdateRequest(1));
    }

    public function testFindPendingUpdateRequest_WithApprovedAndRejectedButNoPendingOne()
    {
        $user = self::createUser();
        $city = self::createCity();
        $team = self::createTeam($city, $user);

        factory(TeamRequest::class)->create([
                'team_id'      => $team['id'],
                'status'       => TeamRequestEntity::STATUS_APPROVED
        ]);

        factory(TeamRequest::class)->create([
                'team_id'      => $team['id'],
                'status'       => TeamRequestEntity::STATUS_REJECTED
        ]);

        self::assertNull($this->getRepository()->findPendingUpdateRequest(1));
    }

    //=================================================
    //            findUninspectedRequests
    //=================================================
    public function testUninspectedRequests_HasOneUninspected()
    {
        factory(City::class)->create([
                'id' => 1,
        ]);

        factory(User::class)->create([
                'id' => 1,
        ]);

        factory(Team::class)->create([
                'id' => 1,
        ]);

        factory(TeamRequest::class)->create([
                'id'           => 1,
                'city_id'      => 1,
                'initiator_id' => 1,
                'team_id'      => 1,
                'status'       => TeamRequestEntity::STATUS_APPROVED,
                'read'         => TeamRequest::UN_READ,
        ]);

        $requests = $this->getRepository()->findUninspectedRequests(1);

        self::assertCount(1, $requests);
        self::assertRequestEquals(1, 1, 1, TeamRequestEntity::STATUS_APPROVED, 0, $requests[0]);
    }

    public function testFindUninspectedRequest_HasMultiUninspected()
    {
        factory(City::class)->create([
                'id' => 1,
        ]);

        factory(User::class)->create([
                'id' => 1,
        ]);

        factory(Team::class)->create([
                'id' => 1,
        ]);

        factory(TeamRequest::class)->create([
                'id'           => 1,
                'city_id'      => 1,
                'initiator_id' => 1,
                'team_id'      => 1,
                'status'       => TeamRequestEntity::STATUS_APPROVED,
                'read'         => TeamRequest::UN_READ,
        ]);

        factory(TeamRequest::class)->create([
                'id'           => 2,
                'city_id'      => 1,
                'initiator_id' => 1,
                'team_id'      => 1,
                'status'       => TeamRequestEntity::STATUS_REJECTED,
                'read'         => TeamRequest::UN_READ,
        ]);

        factory(TeamRequest::class)->create([
                'id'           => 3,
                'city_id'      => 1,
                'initiator_id' => 1,
                'team_id'      => 1,
                'status'       => TeamRequestEntity::STATUS_PENDING,
                'read'         => TeamRequest::UN_READ,
        ]);

        $requests = $this->getRepository()->findUninspectedRequests(1);

        self::assertCount(3, $requests);
        self::assertRequestEquals(1, 1, 1, TeamRequestEntity::STATUS_APPROVED, 0, $requests[0]);
        self::assertRequestEquals(2, 1, 1, TeamRequestEntity::STATUS_REJECTED, 0, $requests[1]);
        self::assertRequestEquals(3, 1, 1, TeamRequestEntity::STATUS_PENDING, 0, $requests[2]);
    }

    public function testFindUninspectedRequest_NoRequest()
    {
        self::assertEmpty($this->getRepository()->findUninspectedRequests(1));
    }

    public function testUninspectedRequests_HasOneInspected()
    {
        $user = self::createUser();
        $city = self::createCity();
        $team = self::createTeam($city, $user);

        factory(TeamRequest::class)->create([
                'id'           => 1,
                'city_id'      => $city['id'],
                'initiator_id' => $user['id'],
                'team_id'      => $team['id'],
                'status'       => TeamRequestEntity::STATUS_APPROVED,
                'read'         => TeamRequest::READ,
        ]);

        self::assertEmpty($this->getRepository()->findUninspectedRequests(1));
    }

    //=================================================
    //            getNumberOfUpdatedTimes
    //=================================================
    public function testGetNumberOfUpdatedTimes_OnlyOneApplicationOfUpdated()
    {
        factory(TeamRequest::class)->create([
                'team_id'   => 1,
                'status'    => TeamRequestEntity::STATUS_APPROVED,
        ]);

        self::assertEquals(1, $this->getRepository()->getNumberOfUpdatedTimes(1));
    }

    public function testGetNumberOfUpdatedTimes_NoApplicationOfUpdated()
    {
        self::assertEquals(0, $this->getRepository()->getNumberOfUpdatedTimes(1));
    }

    //===========================================
    //              findRequest
    //===========================================
    public function testFindRequest_HasOne()
    {
        $user = self::createUser();
        $city = self::createCity();
        $team = self::createTeam($city, $user);

        factory(TeamRequest::class)->create([
            'id'           => 1,
            'city_id'      => $city['id'],
            'initiator_id' => $user['id'],
            'team_id'      => $team['id'],
        ]);

        self::assertNotNull($this->getRepository()->findRequest(1));
    }

    public function testFindRequest_NoRequest()
    {
        self::assertNull($this->getRepository()->findRequest(1));
    }

    //=================================================
    //           updatePendingRequestToApproved
    //=================================================
    public function testUpdatePendingRequestToApproved_WithOnePendingRequest()
    {
        factory(TeamRequest::class)->create([
                'id'     => 1,
                'status' => TeamRequestEntity::STATUS_PENDING,
        ]);

        self::assertTrue($this->getRepository()->updatePendingRequestToApproved(1));
    }

    public function testUpdatePendingRequestToApproved_NoRequstAtAll()
    {
        self::assertFalse($this->getRepository()->updatePendingRequestToApproved(1));
    }

    public function testUpdatePendingRequestToApproved_NoPendingRequest()
    {
        factory(TeamRequest::class)->create([
                'id'     => 1,
                'status' => TeamRequestEntity::STATUS_APPROVED,
        ]);

        self::assertFalse($this->getRepository()->updatePendingRequestToApproved(1));
    }

    public function testUpdatePendingRequestToApproved_WithRejectedRequest()
    {
        factory(TeamRequest::class)->create([
                'id'     => 1,
                'status' => TeamRequestEntity::STATUS_REJECTED,
        ]);

        self::assertFalse($this->getRepository()->updatePendingRequestToApproved(1));
    }

    //=================================================
    //           updatePendingRequestToRejected
    //=================================================
    public function testUpdatePendingRequestToRejected_WithOnePendingRequest()
    {
        factory(TeamRequest::class)->create([
                'id'     => 1,
                'status' => TeamRequestEntity::STATUS_PENDING,
        ]);

        self::assertTrue($this->getRepository()->updatePendingRequestToRejected(1));
    }

    public function testUpdatePendingRequestToRejected_NoRequestAtAll()
    {
        self::assertFalse($this->getRepository()->updatePendingRequestToRejected(1));
    }

    public function testUpdatePendingRequestToRejected_ReqeustIsApproved()
    {
        factory(TeamRequest::class)->create([
                'id'     => 1,
                'status' => TeamRequestEntity::STATUS_APPROVED,
        ]);

        self::assertFalse($this->getRepository()->updatePendingRequestToRejected(1));
    }

    public function testUpdatePendingRequestToRejected__ReqeustIsRejected()
    {
        factory(TeamRequest::class)->create([
                'id'     => 1,
                'status' => TeamRequestEntity::STATUS_REJECTED,
        ]);

        self::assertFalse($this->getRepository()->updatePendingRequestToRejected(1));
    }

    //=================================================
    //            updateRequestToInspected
    //=================================================
    public function testUpdateRequestToInspected_WithOneHandledAndUnInspectedRequest()
    {
        factory(TeamRequest::class)->create([
                'id'     => 1,
                'status' => TeamRequestEntity::STATUS_APPROVED,
                'read'   => TeamRequest::UN_READ,
        ]);

        self::assertTrue($this->getRepository()->updateRequestToInspected(1));
    }

    public function testUpdateRequestToInspected_NoRequstAtAll()
    {
        self::assertFalse($this->getRepository()->updateRequestToInspected(1));
    }

    public function testUpdateRequestToInspected_OneUnHandledRequest()
    {
        factory(TeamRequest::class)->create([
                'id'     => 1,
                'status' => TeamRequestEntity::STATUS_PENDING,
                'read'   => TeamRequest::UN_READ,
        ]);

        self::assertFalse($this->getRepository()->updateRequestToInspected(1));
    }

    public function testUpdateRequestToInspected_OneHandledWithInspectedRequest()
    {
        factory(TeamRequest::class)->create([
                'id'     => 1,
                'status' => TeamRequestEntity::STATUS_REJECTED,
                'read'   => TeamRequest::READ,
        ]);

        self::assertFalse($this->getRepository()->updateRequestToInspected(1));
    }

    /**
     *
     * @return \Jihe\Models\User
     */
    private function createUser()
    {
        return factory(\Jihe\Models\User::class)->create();
    }

    /**
     *
     * @return \Jihe\Models\City
     */
    private function createCity()
    {
        return factory(\Jihe\Models\City::class)->create();
    }

    /**
     *
     * @return \Jihe\Models\Team
     */
    private function createTeam($city = null, $creator = null)
    {
        $team = [ ];

        if(null != $city) {
            $team ['city_id'] = $city ['id'];
        }

        if(null != $creator) {
            $team ['creator_id'] = $creator ['id'];
        }

        return factory(\Jihe\Models\Team::class)->create($team);
    }

    /**
     * @return \Jihe\Contracts\Repositories\TeamRequestRepository
     */
    private function getRepository()
    {
        return $this->app[\Jihe\Contracts\Repositories\TeamRequestRepository::class];
    }
}
