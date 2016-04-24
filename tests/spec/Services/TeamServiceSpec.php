<?php
namespace spec\Jihe\Services;

use Bus;
use Jihe\Services\CityService;
use Jihe\Services\TeamService;
use PhpSpec\Laravel\LaravelObjectBehavior;
use Prophecy\Argument;

use Jihe\Contracts\Repositories\TeamRequestRepository;
use Jihe\Contracts\Repositories\TeamRepository;
use Jihe\Entities\TeamRequest as TeamRequestEntity;
use Jihe\Entities\Team as TeamEntity;
use Jihe\Entities\City as CityEntity;
use Jihe\Entities\User as UserEntity;
use Jihe\Entities\TeamRequirement as TeamRequirementEntity;
use Jihe\Entities\TeamCertification as TeamCertificationEntity;
use Jihe\Services\StorageService;
use Jihe\Contracts\Services\Qrcode\QrcodeService;
use Jihe\Services\TeamMemberService;

class TeamServiceSpec extends LaravelObjectBehavior
{
    function let(TeamRequestRepository $teamRequestRepository,
                 TeamRepository $teamRepository,
                 CityService $cityService,
                 StorageService $storageService,
                 QrcodeService $qrcodeService,
                 TeamMemberService $teamMemberService)
    {
        $this->beAnInstanceOf(\Jihe\Services\TeamService::class, [$teamRequestRepository,
                                                                  $teamRepository,
                                                                  $cityService,
                                                                  $storageService,
                                                                  $qrcodeService,
                                                                  $teamMemberService]);
        Bus::shouldReceive('dispatch')->with(\Mockery::on(function ($job) {
            return ($job instanceof \Jihe\Jobs\SendMessageToUserJob);
        }))->andReturn(null);
        Bus::shouldReceive('dispatch')->with(\Mockery::on(function ($job) {
            return ($job instanceof \Jihe\Jobs\TeamSearchIndexRefreshJob);
        }))->andReturn(null);
    }
    
    //======================================
    //        Request For Enrollment
    //======================================
    function it_accepts_request_for_enrollment(TeamRequestRepository $teamRequestRepository,
                                               TeamRepository $teamRepository,
                                               CityService $cityService)
    {
        $enrollmentRequest = [
            'initiator' => self::createUser(1),
            'city' => 1,
            'name' => 'team name',
            'contact_phone' => '13828134567',
        ];
        
        $cityService->getCity(1)->willReturn(self::createCity(1, '成都'));
        $teamRequestRepository->hasPendingEnrollmentRequest($enrollmentRequest['initiator']->getId())
                              ->willReturn(false);   // no pending enrollment request
        $teamRepository->getNumberOfTeamsCreatedBy($enrollmentRequest['initiator']->getId())
                       ->willReturn(0);  // no teams created before
        $teamRequestRepository->add(Argument::that(function (TeamRequestEntity $request) {
            return $this->isRequestEqual(null, 1, 1, 'team name',
                                         null, null, null, '13828134567', null,
                                         TeamRequestEntity::STATUS_PENDING,
                                         $request);
        }))->willReturn(1);
        
        $this->requestForEnrollment($enrollmentRequest)
             ->shouldBe(1);
    }
    
    function it_fails_to_accept_team_enrollment_request_if_pending_request_exists(
                        TeamRequestRepository $teamRequestRepository,
                        TeamRepository $teamRepository,
                        CityService $cityService)
    {
        $enrollmentRequest = [
                'initiator' => self::createUser(1),
                'city' => 1,
                'name' => 'team name',
        ];
        $cityService->getCity(1)->willReturn(self::createCity(1, '成都'));
        $teamRequestRepository->hasPendingEnrollmentRequest($enrollmentRequest['initiator']->getId())->willReturn(true);
        $teamRepository->getNumberOfTeamsCreatedBy($enrollmentRequest['initiator']->getId())->shouldNotBeCalled();
        $teamRequestRepository->add(Argument::cetera())->shouldNotBeCalled();
        
        $this->shouldThrow(new \Exception('社团申请正在处理中，请勿重复提交'))
             ->duringRequestForEnrollment($enrollmentRequest);
    }
    
    function it_fails_to_accept_team_enrollment_request_if_team_created_by_initiator_too_many(
                        TeamRequestRepository $teamRequestRepository,
                        TeamRepository $teamRepository,
                        CityService $cityService)
    {
        $enrollmentRequest = [
                'initiator' => self::createUser(1),
                'city' => 1,
                'name' => 'team name',
        ];
        $cityService->getCity(1)->willReturn(self::createCity(1, '成都'));
        $teamRequestRepository->hasPendingEnrollmentRequest($enrollmentRequest['initiator']->getId())
                              ->willReturn(false);
        $teamRepository->getNumberOfTeamsCreatedBy($enrollmentRequest['initiator']->getId())
                       ->willReturn(TeamService::MAX_ALLOWED_CREATED_TEAMS);
        $teamRequestRepository->add(Argument::cetera())->shouldNotBeCalled();
        
        $this->shouldThrow(new \Exception('您已创建了1个社团,不能创建更多社团'))
             ->duringRequestForEnrollment($enrollmentRequest);
    }
    
    //=====================================
    //          Request For Update
    //=====================================
    function it_accepts_update_request(TeamRequestRepository $teamRequestRepository,
                                       TeamRepository $teamRepository,
                                       CityService $cityService)
    {
        // the existing team
        $team = (new TeamEntity())
                ->setId(1)
                ->setCreator(self::createUser(1))
                ->setCity(self::createCity(1, '成都'))
                ->setName('old team name')
                ->setJoinType(TeamEntity::JOIN_TYPE_ANY)
                ->setStatus(TeamEntity::STATUS_NORMAL)
                ->setLogoUrl('http://domain/path/file.jpg')
                ->setAddress('ChengDu Hi-Tech district')
                ->setContactPhone(null)
                ->setContact(null)
                ->setIntroduction('introduction of team');
        $cityService->getCity(1)->willReturn(self::createCity(1, '成都'));
        $teamRepository->findTeam(1)->willReturn($team); // team exists
        $teamRequestRepository->hasPendingUpdateRequest(1)->willReturn(false); // no pending update request
        $teamRequestRepository->getNumberOfUpdatedTimes(1)->willReturn(0); // not updated ever before
        $teamRequestRepository->add(Argument::that(function (TeamRequestEntity $request) {
            return $this->isRequestEqual(1, 1, 1, 'team name',
                                         // the following details won't be overwritten
                                         'ChengDu Hi-Tech district', 'http://domain/path/file.jpg',
                                         null, null, 'introduction of team',
                                         // will be given a PENDING status
                                         TeamRequestEntity::STATUS_PENDING,
                                         $request);
        }))->willReturn(1);

        $this->requestForUpdate([
            'team' => 1,
            'city' => 1,
            'initiator' => self::createUser(1),
            'name' => 'team name',
        ])->shouldBe(1);
    }

    function it_accepts_update_request_without_changing_city(TeamRequestRepository $teamRequestRepository,
                                                             TeamRepository $teamRepository)
    {
        // the existing team
        $team = (new TeamEntity())
            ->setId(1)
            ->setCreator(self::createUser(1))
            ->setCity(self::createCity(1, '成都'))
            ->setName('old team name')
            ->setJoinType(TeamEntity::JOIN_TYPE_ANY)
            ->setStatus(TeamEntity::STATUS_NORMAL)
            ->setLogoUrl('http://domain/path/file.jpg')
            ->setAddress('ChengDu Hi-Tech district')
            ->setContactPhone(null)
            ->setContact(null)
            ->setIntroduction('introduction of team');
        $teamRepository->findTeam(1)->willReturn($team); // team exists
        $teamRequestRepository->hasPendingUpdateRequest(1)->willReturn(false); // no pending update request
        $teamRequestRepository->getNumberOfUpdatedTimes(1)->willReturn(0); // not updated ever before
        $teamRequestRepository->add(Argument::that(function (TeamRequestEntity $request) {
            return $this->isRequestEqual(1, 1, 1, 'team name',
                // the following details won't be overwritten
                'ChengDu Hi-Tech district', 'http://domain/path/file.jpg',
                null, null, 'introduction of team',
                // will be given a PENDING status
                TeamRequestEntity::STATUS_PENDING,
                $request);
        }))->willReturn(1);

        $this->requestForUpdate([
            'team' => 1,
            'initiator' => self::createUser(1),
            'name' => 'team name',
        ])->shouldBe(1);
    }

    function it_rejects_update_request_if_pending_update_request_exists(TeamRequestRepository $teamRequestRepository,
                                                                        TeamRepository $teamRepository,
                                                                        CityService $cityService)
    {
        $cityService->getCity(1)->willReturn(self::createCity(1, '成都'));
        $teamRepository->findTeam(1)->willReturn(self::createTeam(1)); // team exists
        $teamRequestRepository->hasPendingUpdateRequest(1)->willReturn(true); // pending update request exists
        $teamRequestRepository->getNumberOfUpdatedTimes(Argument::cetera())->shouldNotBeCalled();
        $teamRequestRepository->add(Argument::cetera())->shouldNotBeCalled();
        
        $this->shouldThrow(new \Exception('社团更新申请正在处理中，请勿重复提交'))
             ->duringRequestForUpdate([
                 'team' => 1,
                 'initiator' => self::createUser(1),
                 'city' => 1,
                 'name' => 'team name',
             ]);
    }
    
    function it_rejects_update_request_if_the_team_updated_too_many_times(TeamRequestRepository $teamRequestRepository,
                                                                          TeamRepository $teamRepository,
                                                                          CityService $cityService)
    {
        $cityService->getCity(1)->willReturn(self::createCity(1, '成都'));
        $teamRepository->findTeam(1)->willReturn(self::createTeam(1));  // team exists
        $teamRequestRepository->hasPendingUpdateRequest(1)->willReturn(false); // no pending update request
        $teamRequestRepository->getNumberOfUpdatedTimes(1)->willReturn(TeamService::MAX_ALLOWED_UPDATED_TIMES);
        $teamRequestRepository->add(Argument::cetera())->shouldNotBeCalled();
        
        $this->shouldThrow(new \Exception('社团资料修改次数已达上限'))
             ->duringRequestForUpdate([
                 'team' => 1,
                 'initiator' => self::createUser(1),
                 'city' => 1,
                 'name' => 'team name',
             ]);
    }
    
    function it_rejects_update_request_if_team_not_exists(TeamRequestRepository $teamRequestRepository,
                                                          TeamRepository $teamRepository,
                                                          CityService $cityService)
    {
        $cityService->getCity(1)->willReturn(self::createCity(1, '成都'));
        $teamRepository->findTeam(1)->willReturn(null);
        $teamRequestRepository->hasPendingUpdateRequest(Argument::cetera())->shouldNotBeCalled();
        $teamRequestRepository->getNumberOfUpdatedTimes(Argument::cetera())->shouldNotBeCalled();
        $teamRequestRepository->add(Argument::cetera())->shouldNotBeCalled();
    
        $this->shouldThrow(new \Exception('非法社团'))->duringRequestForUpdate([
            'team' => 1,
            'initiator' => self::createUser(1),
            'city' => 1,
            'name' => 'team name',
        ]);
    }

    private function isRequestEqual($expectedTeamId, $expectedInitiatorId, $expectedCityId,
                                    $expectedName, $expectedAddress, $expectedLogoUrl,
                                    $expectedContact, $expectedContactPhone,
                                    $expectedIntroduction, $expectedStatus,
                                    TeamRequestEntity $request)
    {
        return ((null == $expectedTeamId && empty($request->getTeam())) ||
               $request->getTeam()->getId() == $expectedTeamId) &&
               $request->getInitiator()->getId() == $expectedInitiatorId &&
               $request->getCity()->getId() == $expectedCityId &&
               $request->getName() == $expectedName &&
               $request->getAddress() == $expectedAddress &&
               $request->getLogoUrl() == $expectedLogoUrl &&
               $request->getContact() == $expectedContact &&
               $request->getContactPhone() == $expectedContactPhone &&
               $request->getIntroduction() == $expectedIntroduction &&
               $request->getStatus() == $expectedStatus;
    }
    
    //====================================================
    //           Approve enrollment request
    //====================================================
    function it_approves_enrollment_request(TeamRequestRepository $teamRequestRepository,
                                            TeamRepository $teamRepository, 
                                            StorageService $storageService,
                                            QrcodeService $qrcodeService,
                                            TeamMemberService $teamMemberService)
    {
        $enrollmentRequest = (new TeamRequestEntity())
                             ->setId(1)
                             ->setInitiator(self::createUser(1))
                             ->setCity(self::createCity(1, '成都'))
                             ->setName('team name')
                             ->setLogoUrl('http://domain/logo.jpg');
    
        $teamRequestRepository->findRequest(1)->willReturn($enrollmentRequest);
        $teamRepository->getNumberOfTeamsCreatedBy(1)->willReturn(0);
        $teamRequestRepository->updatePendingRequestToApproved(1, null)->willReturn(true);
        // save as tmp file, so use cetera argument
        $qrcodeService->generate(Argument::cetera())
                      ->shouldBeCalled()
                      ->willReturn(true);
        $storageService->storeAsImage(Argument::cetera())
                       ->shouldBeCalled()
                       ->willReturn('http://domain/qrcode.jpg');
        $teamRepository->add(Argument::that(function (TeamEntity $team) {
            return null == $team->getQrCodeUrl() && 
                   $this->isTeamEqual(1, 1, 'team name', null, 'http://domain/logo.jpg',
                             null, null, null,
                             TeamEntity::JOIN_TYPE_ANY, //when creating a new team, it's public for all to join
                             TeamEntity::STATUS_NORMAL, // and in NORMAL state
                             $team);
        }))->willReturn(1);
        $teamRepository->update(Argument::cetera())
                       ->shouldBeCalled()
                       ->willReturn(true);
        $teamMemberService->acceptAsTeamMember(
                            Argument::that(function (UserEntity $user) {
                                return 1 == $user->getId();
                            }), 
                            Argument::that(function (TeamEntity $team) {
                                return 1 == $team->getId();
                            }), 
                            [])
                          ->shouldBeCalled()
                          ->willReturn(1);
        
        $this->approveEnrollmentRequest(1)->shouldBe(true);
    }
    
    function it_fails_to_approve_request_if_it_not_exists(TeamRequestRepository $teamRequestRepository,
                                                          TeamRepository $teamRepository)
    {
        $teamRequestRepository->findRequest(1)->willReturn(null);
        $teamRepository->getNumberOfTeamsCreatedBy(Argument::cetera())->shouldNotBeCalled();
        $teamRepository->add(Argument::cetera())->shouldNotBeCalled();
        $teamRepository->update(Argument::cetera())->shouldNotBeCalled();
        $teamRequestRepository->updatePendingRequestToApproved(Argument::cetera())->shouldNotBeCalled();
    
        $this->shouldThrow(new \Exception('申请不存在'))->duringApproveEnrollmentRequest(1);
    }
    
    function it_fails_to_approve_request_if_it_was_approved(TeamRequestRepository $teamRequestRepository,
                                                            TeamRepository $teamRepository)
    {
        $request = (new TeamRequestEntity())
                    ->setId(1)
                    ->setInitiator(self::createUser(1))
                    ->setCity(self::createCity(1, '成都'))
                    ->setName('team name')
                    ->setStatus(TeamRequestEntity::STATUS_APPROVED);
        
        $teamRequestRepository->findRequest(1)->willReturn($request);
        $teamRepository->getNumberOfTeamsCreatedBy(Argument::cetera())->shouldNotBeCalled();
        $teamRepository->add(Argument::cetera())->shouldNotBeCalled();
        $teamRepository->update(Argument::cetera())->shouldNotBeCalled();
        $teamRequestRepository->updatePendingRequestToApproved(Argument::cetera())->shouldNotBeCalled();
    
        $this->shouldThrow(new \Exception('申请已处理'))->duringApproveEnrollmentRequest(1);
    }
    
    function it_fails_to_approve_request_if_it_was_rejected(TeamRequestRepository $teamRequestRepository,
                                                            TeamRepository $teamRepository)
    {
        $request = (new TeamRequestEntity())
                    ->setId(1)
                    ->setInitiator(self::createUser(1))
                    ->setCity(self::createCity(1, '成都'))
                    ->setName('team name')
                    ->setStatus(TeamRequestEntity::STATUS_REJECTED);

        $teamRequestRepository->findRequest(1)->willReturn($request);
        $teamRepository->getNumberOfTeamsCreatedBy(Argument::cetera())->shouldNotBeCalled();
        $teamRepository->add(Argument::cetera())->shouldNotBeCalled();
        $teamRepository->update(Argument::cetera())->shouldNotBeCalled();
        $teamRequestRepository->updatePendingRequestToApproved(Argument::cetera())->shouldNotBeCalled();

        $this->shouldThrow(new \Exception('申请已处理'))->duringApproveEnrollmentRequest(1);
    }

    private function isTeamEqual($creatorId, $cityId,
                                 $name, $address, $logoUrl,
                                 $contactPhone, $contact,
                                 $introduction, $joinType, $status,
                                 TeamEntity $team)
    {
        return $team->getCreator()->getId() == $creatorId &&
               $team->getCity()->getId() == $cityId &&
               $team->getName() == $name &&
               $team->getAddress() == $address &&
               $team->getLogoUrl() == $logoUrl &&
               $team->getContactPhone() == $contactPhone &&
               $team->getContact() == $contact &&
               $team->getIntroduction() == $introduction &&
               $team->getJoinType() == $joinType &&
               $team->getStatus() == $status;
    }
    
    //====================================================
    //           Reject Enrollment Request
    //====================================================
    function it_rejects_enrollment_request(TeamRequestRepository $teamRequestRepository)
    {
        $request = (new TeamRequestEntity())
                   ->setId(1)
                   ->setInitiator(self::createUser(1))
                   ->setCity(self::createCity(1, '成都'))
                   ->setName('team name');
    
        $teamRequestRepository->findRequest(1)->willReturn($request);
        $teamRequestRepository->updatePendingRequestToRejected($request->getId(), null)->willReturn(true);
    
        $this->rejectEnrollmentRequest(1)
             ->shouldBe(true);
    }
    
    function it_fails_to_reject_a_non_exist_enrollment_request(TeamRequestRepository $teamRequestRepository)
    {
        $teamRequestRepository->findRequest(1)->willReturn(null);
        $teamRequestRepository->updatePendingRequestToRejected(Argument::cetera())->shouldNotBeCalled();
    
        $this->shouldThrow(new \Exception('申请不存在'))->duringRejectEnrollmentRequest(1);
    }
    
    function it_fails_to_reject_an_approved_enrollment_request(TeamRequestRepository $teamRequestRepository)
    {
        $request = (new TeamRequestEntity())
                   ->setId(1)
                   ->setInitiator(self::createUser(1))
                   ->setCity(self::createCity(1, '成都'))
                   ->setName('team name')
                   ->setStatus(TeamRequestEntity::STATUS_APPROVED);
    
        $teamRequestRepository->findRequest(1)->willReturn($request);
        $teamRequestRepository->updatePendingRequestToRejected(Argument::cetera())->shouldNotBeCalled();
    
        $this->shouldThrow(new \Exception('申请已处理'))->duringRejectEnrollmentRequest(1);
    }

    function it_fails_to_reject_an_rejected_enrollment_request(TeamRequestRepository $teamRequestRepository)
    {
        $request = (new TeamRequestEntity())
            ->setId(1)
            ->setInitiator(self::createUser(1))
            ->setCity(self::createCity(1, '成都'))
            ->setName('team name')
            ->setStatus(TeamRequestEntity::STATUS_REJECTED);

        $teamRequestRepository->findRequest(1)->willReturn($request);
        $teamRequestRepository->updatePendingRequestToRejected(Argument::cetera())->shouldNotBeCalled();

        $this->shouldThrow(new \Exception('申请已处理'))->duringRejectEnrollmentRequest(1);
    }
    
    //====================================================
    //           Approve Update Request
    //====================================================
    function it_approves_update_request(TeamRequestRepository $teamRequestRepository,
                                        TeamRepository $teamRepository,
                                        StorageService $storageService,
                                        QrcodeService $qrcodeService)
    {
        $team = (new TeamEntity())
                ->setId(1)
                ->setCreator(self::createUser(1))
                ->setCity(self::createCity(1, '成都'))
                ->setLogoUrl('http://domain/team_logo.jpg')
                ->setQrCodeUrl('http://domain/team_qrcode.jpg')
                ->setName('team name');
        
        $request = (new TeamRequestEntity())
                    ->setId(1)
                    ->setInitiator(self::createUser(1))
                    ->setCity(self::createCity(1, '成都'))
                    ->setTeam($team)
                    ->setLogoUrl('http://domain/logo.jpg')
                    ->setName('new team name');
        
        $teamRequestRepository->findRequest($request->getId())->willReturn($request);
        $teamRequestRepository->getNumberOfUpdatedTimes($request->getTeam()->getId())->willReturn(0);
        $teamRequestRepository->updatePendingRequestToApproved($request->getId(), null)->willReturn(true);
        $teamRepository->findTeam($request->getTeam()->getId())->willReturn($team);
        $storageService->remove('http://domain/team_logo.jpg')
                       ->shouldBeCalled();
        // save as tmp file, so use cetera argument
        $qrcodeService->generate(Argument::cetera())
                      ->shouldBeCalled()
                      ->willReturn(true);
        $storageService->remove('http://domain/team_qrcode.jpg')
                       ->shouldBeCalled()
                       ->willReturn(null);
        $storageService->storeAsImage(Argument::cetera())
                       ->shouldBeCalled()
                       ->willReturn('http://domain/qrcode.jpg');
        $teamRepository->update(Argument::that(function (TeamEntity $team) {
            return null != $team->getQrCodeUrl() && 
                   $this->isTeamEqual(1, 1, 'new team name',
                            null, 'http://domain/logo.jpg', null, null, null,
                            TeamEntity::JOIN_TYPE_ANY, TeamEntity::STATUS_NORMAL,
                            $team);
        }))->willReturn(true);

        $this->approveUpdateRequest(1)
            ->shouldBe(true);
    }

    function it_fails_to_approve_update_request_if_it_does_not_exist(TeamRequestRepository $teamRequestRepository,
                                                                     TeamRepository $teamRepository)
    {
        $teamRequestRepository->findRequest(1)->willReturn(null);
        $teamRequestRepository->getNumberOfUpdatedTimes(Argument::cetera())->shouldNotBeCalled();
        $teamRequestRepository->updatePendingRequestToApproved(Argument::cetera())->shouldNotBeCalled();
        $teamRepository->findTeam(Argument::cetera())->shouldNotBeCalled();
        $teamRepository->update(Argument::cetera())->shouldNotBeCalled();
    
        $this->shouldThrow(new \Exception('申请不存在'))->duringApproveUpdateRequest(1);
    }
    
    function it_fails_to_approve_an_approved_update_request(TeamRequestRepository $teamRequestRepository,
                                                            TeamRepository $teamRepository)
    {
        $request = (new TeamRequestEntity())
                   ->setId(1)
                   ->setInitiator(self::createUser(1))
                   ->setCity(self::createCity(1, '成都'))
                   ->setName('new team name')
                   ->setStatus(TeamRequestEntity::STATUS_APPROVED);
    
        $teamRequestRepository->findRequest(1)->willReturn($request);
        $teamRequestRepository->getNumberOfUpdatedTimes(Argument::cetera())->shouldNotBeCalled();
        $teamRequestRepository->updatePendingRequestToApproved(Argument::cetera())->shouldNotBeCalled();
        $teamRepository->findTeam(Argument::cetera())->shouldNotBeCalled();
        $teamRepository->update(Argument::cetera())->shouldNotBeCalled();
    
        $this->shouldThrow(new \Exception('申请已处理'))->duringApproveUpdateRequest(1);
    }

    function it_fails_to_approve_an_rejected_update_request(TeamRequestRepository $teamRequestRepository,
                                                            TeamRepository $teamRepository)
    {
        $request = (new TeamRequestEntity())
            ->setId(1)
            ->setInitiator(self::createUser(1))
            ->setCity(self::createCity(1))
            ->setName('new team name')
            ->setStatus(TeamRequestEntity::STATUS_REJECTED);

        $teamRequestRepository->findRequest(1)->willReturn($request);
        $teamRequestRepository->getNumberOfUpdatedTimes(Argument::cetera())->shouldNotBeCalled();
        $teamRequestRepository->updatePendingRequestToApproved(Argument::cetera())->shouldNotBeCalled();
        $teamRepository->findTeam(Argument::cetera())->shouldNotBeCalled();
        $teamRepository->update(Argument::cetera())->shouldNotBeCalled();

        $this->shouldThrow(new \Exception('申请已处理'))->duringApproveUpdateRequest(1);
    }
    
    //====================================================
    //           Reject Update Request
    //====================================================
    function it_rejects_update_request(TeamRequestRepository $teamRequestRepository)
    {
        $request = (new TeamRequestEntity())
                   ->setId(1)
                   ->setInitiator(self::createUser(1))
                   ->setCity(self::createCity(1, '成都'))
                   ->setName('team name')
                   ->setTeam((new TeamEntity())->setId(1)->setName('old team name'));
    
        $teamRequestRepository->findRequest(1)->willReturn($request);
        $teamRequestRepository->updatePendingRequestToRejected(1, null)->willReturn(true);
    
        $this->rejectUpdateRequest(1)
             ->shouldBe(true);
    }
    
    function it_fails_to_reject_an_non_exist_update_request(TeamRequestRepository $teamRequestRepository)
    {
        $teamRequestRepository->findRequest(1)->willReturn(null);
        $teamRequestRepository->updatePendingRequestToRejected(Argument::cetera())->shouldNotBeCalled();
    
        $this->shouldThrow(new \Exception('申请不存在'))->duringRejectUpdateRequest(1);
    }
    
    function it_fails_to_reject_an_approved_update_request(TeamRequestRepository $teamRequestRepository)
    {
        $request = (new TeamRequestEntity())
                    ->setId(1)
                    ->setInitiator(self::createUser(1))
                    ->setCity(self::createCity(1, '成都'))
                    ->setName('team name')
                    ->setStatus(TeamRequestEntity::STATUS_APPROVED);
    
        $teamRequestRepository->findRequest(1)->willReturn($request);
        $teamRequestRepository->updatePendingRequestToRejected(Argument::cetera())->shouldNotBeCalled();
    
        $this->shouldThrow(new \Exception('申请已处理'))->duringRejectUpdateRequest(1);
    }

    function it_fails_to_reject_an_rejected_update_request(TeamRequestRepository $teamRequestRepository)
    {
        $request = (new TeamRequestEntity())
            ->setId(1)
            ->setInitiator(self::createUser(1))
            ->setCity(self::createCity(1, '成都'))
            ->setName('team name')
            ->setStatus(TeamRequestEntity::STATUS_REJECTED);

        $teamRequestRepository->findRequest(1)->willReturn($request);
        $teamRequestRepository->updatePendingRequestToRejected(Argument::cetera())->shouldNotBeCalled();

        $this->shouldThrow(new \Exception('申请已处理'))->duringRejectUpdateRequest(1);
    }
    
    //====================================================
    //                Inspecte Request
    //====================================================
    function it_inspects_request(TeamRequestRepository $teamRequestRepository)
    {
        $teamRequestRepository->updateRequestToInspected(1)
                              ->willReturn(true);
    
        $this->inspectRequest(1)
             ->shouldBe(true);
    }
    
    function it_fails_to_inspect_request(TeamRequestRepository $teamRequestRepository)
    {
        $teamRequestRepository->updateRequestToInspected(1)
                              ->willReturn(false);
    
        $this->inspectRequest(1)
             ->shouldBe(false);
    }
    
    //====================================================
    //                     Get Team
    //====================================================
    function it_get_team_successfully(TeamRepository $teamRepository)
    {
        $team = new TeamEntity();
        $team->setId(1)
             ->setCreator(self::createUser(1))
             ->setCity(self::createCity(1, '成都'))
             ->setName('team name');
        
        $creator = new UserEntity();
        $creator->setId($team->getCreator()->getId());
        
        $city = new CityEntity();
        $city->setId($team->getCity()->getId());
        
        $team->setCity($city)
             ->setCreator($creator);
        
        $teamRepository->findTeam($team->getId(), ['city', 'creator', 'requirements'])
                       ->willReturn($team);
        
        $this->getTeam(1)
             ->shouldBe($team);
    }
    
    function it_get_team_if_team_not_exists(TeamRepository $teamRepository)
    {
        $teamRepository->findTeam(1, ['city', 'creator', 'requirements'])
                       ->willReturn(null);
    
        $this->getTeam(1)->shouldBeNull();
    }
    
    //====================================================
    //                 Get Teams By Creator
    //====================================================
    function it_get_teams_by_creator_successfully(TeamRepository $teamRepository)
    {
        $team = new TeamEntity();
        $team->setId(1)
             ->setCreator(self::createUser(1))
             ->setCity(self::createCity(1, '成都'))
             ->setName('team name');
    
        $creator = new UserEntity();
        $creator->setId($team->getCreator()->getId());
    
        $city = new CityEntity();
        $city->setId($team->getCity()->getId());
    
        $team->setCity($city)
             ->setCreator($creator);
    
        $teamRepository->findTeamsCreatedBy($team->getCreator()->getId(), ['city'])
                       ->willReturn([$team]);
    
        $result = $this->getTeamsByCreator(1);
        $result->shouldBe([$team]);
    }
    
    function it_get_teams_by_creator_if_team_not_exists(TeamRepository $teamRepository)
    {
        $teamRepository->findTeamsCreatedBy(1, ['city'])
                       ->willReturn([]);
    
        $this->getTeamsByCreator(1)->shouldBe([]);
    }
    
    //====================================================
    //                     Get Teams
    //====================================================
    function it_get_teams_successfully(TeamRepository $teamRepository)
    {
        $team = new TeamEntity();
        $team->setId(1)
             ->setCreator(self::createUser(1))
             ->setCity(self::createCity(1, '成都'))
             ->setName('team name');
    
        $teamRepository->findTeams(1, 10, [], ['city' => 1])
                       ->willReturn([1, [$team]]);
    
        $result = $this->getTeams(1, 10, ['city' => 1]);
        $result->shouldBe([1, [$team]]);
    }
    
    function it_get_teams_if_team_not_exists(TeamRepository $teamRepository)
    {
        $teamRepository->findTeams(1, 10, [], ['city' => 1])
                       ->willReturn([0, []]);
    
        $this->getTeams(1, 10, ['city' => 1])->shouldBe([0, []]);
    }
    
    //====================================================
    //          Get Pending Enrollment Request
    //====================================================
    function it_gets_pending_enrollment_request(TeamRequestRepository $teamRequestRepository)
    {
        $request = (new TeamRequestEntity())
                    ->setId(1)
                    ->setInitiator(self::createUser(1));
        $teamRequestRepository->findPendingEnrollmentRequest($request->getInitiator()->getId(), ['city'])
                              ->willReturn($request);
    
        $this->getPendingEnrollmentRequest($request->getInitiator()->getId())
             ->shouldBe($request);
    }
    
    function it_gets_no_pending_enrollment_request(TeamRequestRepository $teamRequestRepository)
    {
        $teamRequestRepository->findPendingEnrollmentRequest(1, ['city'])
                              ->willReturn(null);
    
        $this->getPendingEnrollmentRequest(1)->shouldBe(null);
    }
    
    //====================================================
    //          Get Pending Update Request
    //====================================================
    function it_gets_pending_update_request(TeamRequestRepository $teamRequestRepository)
    {
        $request = (new TeamRequestEntity())
                    ->setId(1)
                    ->setInitiator(self::createUser(1))
                    ->setTeam(self::createTeam(1));
        $teamRequestRepository->findPendingUpdateRequest($request->getTeam()->getId(), ['city', 'team'])
                              ->willReturn($request);
    
        $this->getPendingUpdateRequest($request->getTeam()->getId())
             ->shouldBe($request);
    }
    
    function it_gets_no_pending_update_request(TeamRequestRepository $teamRequestRepository)
    {
        $teamRequestRepository->findPendingUpdateRequest(1, ['city', 'team'])
                              ->willReturn(null);
    
        $this->getPendingUpdateRequest(1)->shouldBe(null);
    }
    
    //====================================================
    //                 Get Uninspected Requests
    //====================================================
    function it_gets_uninspected_requests(TeamRequestRepository $teamRequestRepository)
    {
        $request = (new TeamRequestEntity())
                    ->setId(1)
                    ->setInitiator(self::createUser(1))
                    ->setStatus(TeamRequestEntity::STATUS_APPROVED)
                    ->setRead(false);
        
        $teamRequestRepository->findUninspectedRequests($request->getInitiator()->getId(), ['city'])
                              ->willReturn([$request]);
    
        $this->getUninspectedRequests($request->getInitiator()->getId())
             ->shouldBe([$request]);
    }
    
    function it_gets_no_uninspected_request(TeamRequestRepository $teamRequestRepository)
    {
        $teamRequestRepository->findUninspectedRequests(1, ['city'])
                              ->willReturn(null);
    
        $this->getUninspectedRequests(1)->shouldBe(null);
    }
    
    //====================================================
    //               Update Team Requirements
    //====================================================
    function it_updates_team_requirements_successfully(TeamRepository $teamRepository)
    {
        $team = new TeamEntity();
        $team->setId(1)
             ->setCreator(self::createUser(1))
             ->setCity(self::createCity(1, '成都'))
             ->setName('team name')
             ->setJoinType(TeamEntity::JOIN_TYPE_VERIFY);
        
        $creator = new UserEntity();
        $creator->setId($team->getCreator()->getId());
        
        $city = new CityEntity();
        $city->setId($team->getCity()->getId());
        
        $team->setCity($city)
             ->setCreator($creator);
        
        $requirement1 = new TeamRequirementEntity();
        $requirement1->setId(1);
        $requirement1->setTeam((new TeamEntity())->setId(1));
        $requirement1->setRequirement('车型');

        $requirement2 = new TeamRequirementEntity();
        $requirement2->setId(2);
        $requirement2->setTeam((new TeamEntity())->setId(1));
        $requirement2->setRequirement('排量');
        
        $requirementEntities = [$requirement1, $requirement2];
        
        $requirements = [
            [
                'id'          => 1,
                'requirement' => '车型',
            ],
            [
                'requirement' => '出厂日期',
            ],
        ];
        
        $teamRepository->findTeam($team->getId(), ['city', 'creator', 'requirements'])
                       ->willReturn($team);
        $teamRepository->update($team->setJoinType(TeamEntity::JOIN_TYPE_VERIFY))
                       ->willReturn(true);
        $teamRepository->findRequirements($team->getId())
                       ->willReturn($requirementEntities);
        $teamRepository->addRequirements(1, 
                                         Argument::that(function ($requirementArray) {
                                                            return 1 == count($requirementArray) && 
                                                                   '出厂日期' == $requirementArray[0]->getRequirement();
                                                       }))
                       ->willReturn(true);
        $teamRepository->deleteRequirements([2])
                               ->willReturn(true);
        
        $this->updateTeamRequirements($team->getId(), TeamEntity::JOIN_TYPE_VERIFY, $requirements)
             ->shouldBe(true);
    }
    
    function it_updates_team_requirements_successfully_if_request_join_type_is_any(TeamRepository $teamRepository)
    {
        $team = new TeamEntity();
        $team->setId(1)
             ->setCreator(self::createUser(1))
             ->setCity(self::createCity(1, '成都'))
             ->setName('team name')
             ->setJoinType(TeamEntity::JOIN_TYPE_VERIFY);
    
        $creator = new UserEntity();
        $creator->setId($team->getCreator()->getId());
    
        $city = new CityEntity();
        $city->setId($team->getCity()->getId());
    
        $team->setCity($city)
             ->setCreator($creator);
    
        $requirement1 = new TeamRequirementEntity();
        $requirement1->setId(1);
        $requirement1->setTeam((new TeamEntity())->setId(1));
        $requirement1->setRequirement('车型');
    
        $requirement2 = new TeamRequirementEntity();
        $requirement2->setId(2);
        $requirement2->setTeam((new TeamEntity())->setId(1));
        $requirement2->setRequirement('排量');
    
        $requirementEntities = [$requirement1, $requirement2];
    
        $requirements = [
            [
                'id'          => 1,
                'requirement' => '车型',
            ],
        ];
    
        $teamRepository->findTeam($team->getId(), ['city', 'creator', 'requirements'])
                       ->willReturn($team);
        $teamRepository->findRequirements($team->getId())
                       ->willReturn($requirementEntities);
        $teamRepository->update($team->setJoinType(TeamEntity::JOIN_TYPE_ANY))
                       ->willReturn(true);
        $teamRepository->addRequirements(1, [])
                       ->willReturn(true);
        $teamRepository->deleteRequirements([1, 2])
                       ->willReturn(true);
    
        $this->updateTeamRequirements($team->getId(), TeamEntity::JOIN_TYPE_ANY, $requirements)
            ->shouldBe(true);
    }
    
    function it_updates_team_requirements_throw_exception_if_team_not_exists(TeamRepository $teamRepository)
    {
        $requirements = [
            [
                'id'          => 1,
                'requirement' => '车型',
            ],
            [
                'requirement' => '出厂日期',
            ],
        ];
        
        $teamRepository->findTeam(1, ['city', 'creator', 'requirements'])
                       ->willReturn(null);
    
        $this->shouldThrow(new \Exception('社团不存在'))
             ->duringUpdateTeamRequirements(1, TeamEntity::JOIN_TYPE_VERIFY, $requirements);
    }
    
    //====================================================
    //               get Team Requirements
    //====================================================
    function it_gets_team_requirements_successfully(TeamRepository $teamRepository)
    {
        $team = new TeamEntity();
        $team->setId(1)
             ->setCreator(self::createUser(1))
             ->setCity(self::createCity(1, '成都'))
             ->setName('team name')
             ->setJoinType(TeamEntity::JOIN_TYPE_VERIFY);
    
        $creator = new UserEntity();
        $creator->setId($team->getCreator()->getId());
    
        $city = new CityEntity();
        $city->setId($team->getCity()->getId());
    
        $requirement = new TeamRequirementEntity();
        $requirement->setId(1);
        $requirement->setTeam((new TeamEntity())->setId(1));
        $requirement->setRequirement('车型');
    
        $team->setCity($city)
             ->setCreator($creator)
             ->setRequirements([$requirement]);
    
        $teamRepository->findTeam($team->getId(), ['city', 'creator', 'requirements'])
                       ->willReturn($team);
        $teamRepository->findRequirements($team->getId())
                       ->willReturn([$requirement]);
    
        $this->getTeamRequirements($team->getId())
             ->shouldBe([
                            'joinType' => TeamEntity::JOIN_TYPE_VERIFY, 
                            'requirements' => [$requirement]
                       ]);
    }
    
    function it_gets_team_requirements_successfully_join_type_is_any(TeamRepository $teamRepository)
    {
        $team = new TeamEntity();
        $team->setId(1)
             ->setCreator(self::createUser(1))
             ->setCity(self::createCity(1, '成都'))
             ->setName('team name')
             ->setJoinType(TeamEntity::JOIN_TYPE_ANY);
    
        $creator = new UserEntity();
        $creator->setId($team->getCreator()->getId());
    
        $city = new CityEntity();
        $city->setId($team->getCity()->getId());
    
        $team->setCity($city)
             ->setCreator($creator);
    
        $teamRepository->findTeam($team->getId(), ['city', 'creator', 'requirements'])
                       ->willReturn($team);
        $teamRepository->findRequirements($team->getId())->shouldNotBeCalled();
    
        $this->getTeamRequirements($team->getId())
             ->shouldBe([
                           'joinType' => TeamEntity::JOIN_TYPE_ANY,
                           'requirements' => [],
                       ]);
    }
    
    function it_gets_team_requirements_throw_exception_if_team_not_exists(TeamRepository $teamRepository)
    {
    
        $teamRepository->findTeam(1, ['city', 'creator', 'requirements'])
                       ->willReturn(null);
    
        $this->shouldThrow(new \Exception('社团不存在'))
             ->duringGetTeamRequirements(1);
    }
    
    //====================================================
    //            Request Team Certifications
    //====================================================
    function it_requests_team_certifications_successfully(TeamRepository $teamRepository)
    {
        $team = new TeamEntity();
        $team->setId(1)
             ->setCreator(self::createUser(1))
             ->setCity(self::createCity(1, '成都'))
             ->setName('team name');
    
        $creator = new UserEntity();
        $creator->setId($team->getCreator()->getId());
    
        $city = new CityEntity();
        $city->setId($team->getCity()->getId());
    
        $team->setCity($city)
             ->setCreator($creator);
    
        $certification1 = new TeamCertificationEntity();
        $certification1->setId(1);
        $certification1->setTeam((new TeamEntity())->setId(1));
        $certification1->setCertificationUrl('http://domain/certification.jpg');
        
        $certification2 = new TeamCertificationEntity();
        $certification2->setId(2);
        $certification2->setTeam((new TeamEntity())->setId(1));
        $certification2->setCertificationUrl('http://domain/certification.jpg');
        
        $certificationEntities = [$certification1, $certification2];
        
        $certifications = [
            [
                'id'               => 1,
                'certification_id' => 'http://domain/certification1.jpg',
                'type'             => TeamCertificationEntity::TYPE_BUSSINESS_CERTIFICATES,
            ],
            [
                'certification_id' => 'http://domain/certification2.jpg',
                'type'             => TeamCertificationEntity::TYPE_BUSSINESS_CERTIFICATES,
            ],
        ];
    
        $teamRepository->findTeam($team->getId(), ['city', 'creator', 'requirements'])
                       ->willReturn($team);
        $teamRepository->findCertifications($team->getId())
                       ->willReturn($certificationEntities);
        $teamRepository->addCertifications(1,
                                           Argument::that(function ($certificationArray) {
                                               return 1 == count($certificationArray) &&
                                               'http://domain/certification2.jpg' == $certificationArray[0]->getCertificationUrl();
                                           }))
                       ->willReturn(true);
        $teamRepository->deleteCertifications([2])
                       ->willReturn(true);
        $teamRepository->updateTeamToPendingCertification(1)
                       ->willReturn(true);
    
        $this->requestTeamCertifications($team->getId(), $certifications)
             ->shouldBe(true);
    }
    
    function it_requests_team_certifications_throw_exception_if_team_not_exists(TeamRepository $teamRepository)
    {
        $certifications = [
            [
                'id'          => 1,
                'certification_url' => 'http://domain/certification1.jpg',
                'type'              => TeamCertificationEntity::TYPE_BUSSINESS_CERTIFICATES,
            ],
            [
                'certification_url' => 'http://domain/certification2.jpg',
                'type'              => TeamCertificationEntity::TYPE_BUSSINESS_CERTIFICATES,
            ],
        ];
    
        $teamRepository->findTeam(1, ['city', 'creator', 'requirements'])
                       ->willReturn(null);
    
        $this->shouldThrow(new \Exception('社团不存在'))
             ->duringRequestTeamCertifications(1, $certifications);
    }
    
    //====================================================
    //               get Team Certifications
    //====================================================
    function it_gets_team_certifications_successfully(TeamRepository $teamRepository)
    {
        $team = new TeamEntity();
        $team->setId(1)
             ->setCreator(self::createUser(1))
             ->setCity(self::createCity(1, '成都'))
             ->setName('team name');
    
        $creator = new UserEntity();
        $creator->setId($team->getCreator()->getId());
    
        $city = new CityEntity();
        $city->setId($team->getCity()->getId());

        $team->setCity($city)
             ->setCreator($creator);
        
        $certification = new TeamCertificationEntity();
        $certification->setId(1);
        $certification->setTeam((new TeamEntity())->setId(1));
        $certification->setCertificationUrl('http://domain/certification.jpg');
    
        $teamRepository->findTeam($team->getId(), ['city', 'creator', 'requirements'])
                       ->willReturn($team);
        $teamRepository->findCertifications($team->getId())
                       ->willReturn([$certification]);
    
        $this->getTeamCertifications($team->getId())
             ->shouldBe([$certification]);
    }
    
    function it_gets_team_certifications_throw_exception_if_team_not_exists(TeamRepository $teamRepository)
    {
    
        $teamRepository->findTeam(1, ['city', 'creator', 'requirements'])
                       ->willReturn(null);
    
        $this->shouldThrow(new \Exception('社团不存在'))
             ->duringGetTeamCertifications(1);
    }
    
    //====================================================
    //            Approve team Certification
    //====================================================
    function it_approves_team_certification(TeamRepository $teamRepository)
    {
        $team = new TeamEntity();
        $team->setId(1)
             ->setCreator(self::createUser(1))
             ->setCity(self::createCity(1, '成都'))
             ->setName('team name')
             ->setCertification(TeamEntity::CERTIFICATION_PENDING);
        
        $creator = new UserEntity();
        $creator->setId($team->getCreator()->getId());
        
        $city = new CityEntity();
        $city->setId($team->getCity()->getId());
        
        $team->setCity($city)
             ->setCreator($creator);
    
        $teamRepository->findTeam($team->getId(), ['city', 'creator', 'requirements'])
                       ->willReturn($team);
        $teamRepository->updateTeamToCertification($team->getId())
                       ->willReturn(true);
    
        $this->approveTeamCertification(1)
             ->shouldBe(true);
    }
    
    function it_approves_team_certification_throw_exception_if_team_not_exists(TeamRepository $teamRepository)
    {
        $teamRepository->findTeam(1, ['city', 'creator', 'requirements'])
                       ->willReturn(null);
        $teamRepository->updateTeamToCertification(1)->shouldNotBeCalled();
    
        $this->shouldThrow(new \Exception('社团不存在'))
             ->duringApproveTeamCertification(1);
    }
    
    //====================================================
    //             Reject team Certification
    //====================================================
    function it_rejects_team_certification(TeamRepository $teamRepository)
    {
        $team = new TeamEntity();
        $team->setId(1)
             ->setCreator(self::createUser(1))
             ->setCity(self::createCity(1, '成都'))
             ->setName('team name')
             ->setCertification(TeamEntity::CERTIFICATION_PENDING);
    
        $creator = new UserEntity();
        $creator->setId($team->getCreator()->getId());
    
        $city = new CityEntity();
        $city->setId($team->getCity()->getId());
    
        $team->setCity($city)
             ->setCreator($creator);
    
        $teamRepository->findTeam($team->getId(), ['city', 'creator', 'requirements'])
                       ->willReturn($team);
        $teamRepository->updateTeamToUnCertification($team->getId())
                       ->willReturn(true);
    
        $this->rejectTeamCertification(1)
             ->shouldBe(true);
    }
    
    function it_rejects_team_certification_throw_exception_if_team_not_exists(TeamRepository $teamRepository)
    {
        $teamRepository->findTeam(1, ['city', 'creator', 'requirements'])
                       ->willReturn(null);
        $teamRepository->updateTeamToUnCertification(1)->shouldNotBeCalled();
    
        $this->shouldThrow(new \Exception('社团不存在'))
             ->duringRejectTeamCertification(1);
    }

    private static function createCity($id, $name = null)
    {
        return (new CityEntity())
                ->setId($id)
                ->setName($name);
    }
    
    private static function createUser($id, $mobile = null)
    {
        return (new UserEntity())
                ->setId($id)
                ->setMobile($mobile);
    }
    
    private static function createTeam($id, $name = null)
    {
        return (new TeamEntity())
                ->setId($id)
                ->setName($name);
    }
}
