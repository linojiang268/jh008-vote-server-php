<?php
namespace spec\Jihe\Services;

use Bus;
use Jihe\Contracts\Repositories\TeamMemberEnrollmentRequestRepository;
use Jihe\Contracts\Repositories\TeamMemberRepository;
use Jihe\Contracts\Repositories\TeamRepository;
use Jihe\Contracts\Repositories\UserRepository;
use Jihe\Entities\Team;
use Jihe\Entities\TeamGroup;
use Jihe\Entities\TeamMemberEnrollmentPermission;
use Jihe\Entities\TeamMemberEnrollmentRequest;
use Jihe\Entities\TeamRequirement;
use Jihe\Entities\User;
use PhpSpec\Laravel\LaravelObjectBehavior;
use PHPUnit_Framework_Assert as Assert;
use Prophecy\Argument;

class TeamMemberServiceSpec extends LaravelObjectBehavior
{
    function let(TeamMemberEnrollmentRequestRepository $requests,
                 TeamMemberRepository $members,
                 UserRepository $users,
                 TeamRepository $teams)
    {
        $this->beAnInstanceOf(\Jihe\Services\TeamMemberService::class, [ $requests, $members, $users, $teams ]);
    }

    //=============================================
    //           requestForEnrollment
    //=============================================
    function it_accepts_enrollment_request_if_no_permission_set_and_team_accepts_all_request(
        TeamMemberEnrollmentRequestRepository $requests, TeamMemberRepository $members)
    {
        // the team accepts all enrollment request without auditing
        $team = (new Team)->setId(1)->setJoinType(Team::JOIN_TYPE_ANY);
        $user = (new User)->setId(1)->setMobile('13800138000');

        // no permission set, neither in whitelist nor blacklist
        $requests->findPermission('13800138000', 1)->shouldBeCalled()->willReturn(null);
        // not enrolled in the team
        $members->exists(1, [1])->shouldBeCalled()->willReturn([1 => false]);
        $members->add(1, 1, Argument::that(function (array $requests) {
            return $requests['name'] == '群名片' &&
                   $requests['memo'] == '备注' &&
                   empty($requests['requirements']);
        }))->shouldBeCalled()->willReturn(true); // request should be accepted and team member added
        $requests->add(Argument::cetera())->shouldNotBeCalled();
        Bus::shouldReceive('dispatch')->with(\Mockery::on(function ($job) {
            return ($job instanceof \Jihe\Jobs\SendMessageToTeamMemberJob);
        }))->andReturn(null);

        $this->requestForEnrollment($user, $team, [
            'name' => '群名片',
            'memo' => '备注',
        ])->shouldHaveKeyWithValue(0, 2);
    }

    function it_pends_enrollment_request_if_no_permission_set_and_team_requires_enrollment_auditing(
        TeamMemberEnrollmentRequestRepository $requests, TeamMemberRepository $members)
    {
        // the team requires auditing for enrollment
        $team = (new Team)->setId(1)->setJoinType(Team::JOIN_TYPE_VERIFY);
        $user = (new User)->setId(1)->setMobile('13800138000');

        // no permission set, neither in whitelist nor blacklist
        $requests->findPermission('13800138000', 1)->shouldBeCalled()->willReturn(null);
        // no pending enrollment request before
        $requests->pendingRequestExists(1, 1)->shouldBeCalled()->willReturn(false);
        // not enrolled in the team
        $members->exists(1, [1])->shouldBeCalled()->willReturn([1 => false]);
        $members->add(Argument::cetera())->shouldNotBeCalled(); // no member added now
        $requests->removeFinishedRequestsOf(1)->shouldBeCalled();
        $requests->add(1, 1, Argument::that(function (array $requests) {
            return $requests['name'] == '群名片' &&
                   $requests['memo'] == '备注' &&
                   empty($requests['requirements']);
        }))->shouldBeCalled()->willReturn(true); // request should be added

        $this->requestForEnrollment($user, $team, [
            'name' => '群名片',
            'memo' => '备注',
        ])->shouldHaveKeyWithValue(0, 1);
    }

    function it_rejects_enrollment_request_if_permission_says_no(
        TeamMemberEnrollmentRequestRepository $requests, TeamMemberRepository $members)
    {
        // the team requires auditing for enrollment
        $team = (new Team)->setId(1)->setJoinType(Team::JOIN_TYPE_VERIFY);
        $user = (new User)->setId(1)->setMobile('13800138000');

        // user#1 is banned in team #1
        $requests->findPermission('13800138000', 1)->shouldBeCalled()->willReturn(
            (new TeamMemberEnrollmentPermission())
                ->setStatus(TeamMemberEnrollmentPermission::STATUS_PROHIBITED)
        );
        // user#1 not enrolled in team#1
        $members->exists(1, [1])->shouldBeCalled()->willReturn([1 => false]);
        $members->add(Argument::cetera())->shouldNotBeCalled();
        $requests->add(Argument::cetera())->shouldNotBeCalled();

        $this->shouldThrow(new \Exception('您的申请未通过'))
            ->duringRequestForEnrollment($user, $team);
    }

    function it_accepts_enrollment_request_if_permission_says_yes(
        TeamMemberEnrollmentRequestRepository $requests, TeamMemberRepository $members)
    {
        // the team requires auditing for enrollment
        $team = (new Team)->setId(1)->setJoinType(Team::JOIN_TYPE_VERIFY);
        $user = (new User)->setId(1)->setMobile('13800138000');

        // user#1 is the whitelist of team #1
        $requests->findPermission('13800138000', 1)->shouldBeCalled()->willReturn(
            (new TeamMemberEnrollmentPermission())
                ->setStatus(TeamMemberEnrollmentPermission::STATUS_PERMITTED)
        );
        // user#1 not enrolled in team#1
        $members->exists(1, [1])->shouldBeCalled()->willReturn([1 => false]);
        $members->add(1, 1, Argument::that(function (array $requests) {
            return $requests['name'] == '群名片' &&
                   $requests['memo'] == '备注' &&
                   empty($requests['requirements']);
        }))->shouldBeCalled()->willReturn(true); // should be enrolled into team#1
        $requests->add(Argument::cetera())->shouldNotBeCalled(); // no request should be queued

        $this->requestForEnrollment($user, $team, [
            'name' => '群名片',
            'memo' => '备注',
        ])->shouldHaveKeyWithValue(0, 2);
    }

    function it_copies_memo_after_accepting_if_permission_provides_memo_and_no_such_in_request(
        TeamMemberEnrollmentRequestRepository $requests, TeamMemberRepository $members)
    {
        // the team requires auditing for enrollment
        $team = (new Team)->setId(1)->setJoinType(Team::JOIN_TYPE_VERIFY);
        $user = (new User)->setId(1)->setMobile('13800138000');

        // user#1 is the whitelist of team #1
        $requests->findPermission('13800138000', 1)->shouldBeCalled()->willReturn(
            (new TeamMemberEnrollmentPermission())
                ->setStatus(TeamMemberEnrollmentPermission::STATUS_PERMITTED)
                ->setMemo('备注来自这里')
        );
        $members->exists(1, [1])->shouldBeCalled()->willReturn([1 => false]);
        $members->add(1, 1, Argument::that(function (array $requests) {
            return $requests['name'] == '群名片' &&
                   $requests['memo'] == '备注来自这里' &&
                   empty($requests['requirements']);
        }))->shouldBeCalled()->willReturn(true);
        $requests->add(Argument::cetera())->shouldNotBeCalled();

        $this->requestForEnrollment($user, $team, [
            'name' => '群名片',
            'memo' => null/* no memo */
        ])->shouldHaveKeyWithValue(0, 2);
    }

    function it_accepts_enrollment_request_if_requirements_not_met_with_no_requirements(
        TeamMemberEnrollmentRequestRepository $requests, TeamMemberRepository $members)
    {
        $requests->findPermission(Argument::cetera())->shouldNotBeCalled();
        // the team requires auditing for enrollment
        $team = (new Team)->setId(1)->setJoinType(Team::JOIN_TYPE_ANY)
                  ->setRequirements([
                      (new TeamRequirement)->setId(1)->setRequirement('Your hobby?'),
                      (new TeamRequirement)->setId(2)->setRequirement('Your favorite food?'),
                  ]);
        $user = (new User)->setId(1)->setMobile('13800138000');
        $members->add(Argument::cetera())->shouldNotBeCalled();

        $this->shouldThrow(new \Exception('加入条件未填写完整'))
            ->duringRequestForEnrollment($user, $team);
    }

    function it_accepts_enrollment_request_if_requirements_not_met_with_partial_requirements(
        TeamMemberEnrollmentRequestRepository $requests, TeamMemberRepository $members)
    {
        $requests->findPermission(Argument::cetera())->shouldNotBeCalled();
        // the team requires auditing for enrollment
        $team = (new Team)->setId(1)->setJoinType(Team::JOIN_TYPE_ANY)
            ->setRequirements([
                (new TeamRequirement)->setId(1)->setRequirement('Your hobby?'),
                (new TeamRequirement)->setId(2)->setRequirement('Your favorite food?'),
            ]);
        $user = (new User)->setId(1)->setMobile('13800138000');
        $members->add(Argument::cetera())->shouldNotBeCalled();

        $this->shouldThrow(new \Exception('加入条件未填写完整'))
            ->duringRequestForEnrollment($user, $team, [
                'requirements' => [
                    1 => 'grass skating',
                ]
            ]);
    }

    function it_accepts_enrollment_request_if_no_permission_set_and_team_accepts_all_request_and_requirement_met(
        TeamMemberEnrollmentRequestRepository $requests, TeamMemberRepository $members)
    {
        // the team accepts all enrollment request without auditing
        $team = (new Team)->setId(1)->setJoinType(Team::JOIN_TYPE_ANY)
            ->setRequirements([
                (new TeamRequirement)->setId(1)->setRequirement('Your hobby?'),
                (new TeamRequirement)->setId(2)->setRequirement('Your favorite food?'),
            ]);
        $user = (new User)->setId(1)->setMobile('13800138000');

        // no permission set, neither in black or white list
        $requests->findPermission('13800138000', 1)->shouldBeCalled()->willReturn(null);
        $members->exists(1, [1])->shouldBeCalled()->willReturn([1 => false]);
        $members->add(1, 1, Argument::that(function (array $requests) {
            // check requirements
            $requirements = $requests['requirements'];
            if ($requirements[1] != 'grass skating' ||
                $requirements[2] != 'Braised pork ball in brown sauce') {
                return false;
            }

            return $requests['name'] == '群名片' &&
                   $requests['memo'] == '备注';
        }))->shouldBeCalled()->willReturn(true);
        $requests->add(Argument::cetera())->shouldNotBeCalled();

        $this->requestForEnrollment($user, $team,  [
            'name'     => '群名片',
            'memo'     => '备注',
            'requirements' => [
                1 => 'grass skating',
                2 => 'Braised pork ball in brown sauce',
            ]
        ])->shouldHaveKeyWithValue(0, 2);
    }

    function it_rejects_enrollement_request_if_user_is_enrolled_in_the_team(
        TeamMemberEnrollmentRequestRepository $requests, TeamMemberRepository $members)
    {
        // the team accepts all enrollment request without auditing
        $team = (new Team)->setId(1)->setJoinType(Team::JOIN_TYPE_ANY);
        $user = (new User)->setId(1)->setMobile('13800138000');

        // enrolled
        $members->exists(1, [1])->shouldBeCalled()->willReturn([1 => true]);
        // no permission check, no member added, no request queued for auditing
        $requests->findPermission(Argument::cetera())->shouldNotBeCalled();
        $members->add(Argument::cetera())->shouldNotBeCalled();
        $requests->add(Argument::cetera())->shouldNotBeCalled();

        $this->shouldThrow(new \Exception('已是社团成员,请勿重复申请'))
            ->duringRequestForEnrollment($user, $team);
    }

    function it_wont_pend_enrollment_request_if_pended_before(
        TeamMemberEnrollmentRequestRepository $requests, TeamMemberRepository $members)
    {
        // the team requires auditing for enrollment
        $team = (new Team)->setId(1)->setJoinType(Team::JOIN_TYPE_VERIFY);
        $user = (new User)->setId(1)->setMobile('13800138000');

        // no permission set, neither in black nor white list
        $requests->findPermission('13800138000', 1)->shouldBeCalled()->willReturn(null);
        // not enrolled
        $members->exists(1, [1])->shouldBeCalled()->willReturn([1 => false]);
        // pending enrollment request exists
        $requests->pendingRequestExists(1, 1)->shouldBeCalled()->willReturn(true);
        // no member added, no enrollment request queued
        $members->add(Argument::cetera())->shouldNotBeCalled();
        $requests->add(Argument::cetera())->shouldNotBeCalled();

        $this->requestForEnrollment($user, $team)
            ->shouldHaveKeyWithValue(0, 1);
    }

    //=============================================
    //         addEnrollmentPermission
    //=============================================
    function it_adds_enrollment_permission(TeamMemberEnrollmentRequestRepository $requests)
    {
        $team = (new Team())->setId(1);

        $requests->addPermission(Argument::that(function ($permission) {
            return $permission['mobile'] == '13800138000' &&
                   $permission['team'] == 1 &&
                   $permission['name'] == '群名字' &&
                   $permission['memo'] == '团长提供的备注';
        }))->shouldBeCalledTimes(1)->willReturn(1);
        Bus::shouldReceive('dispatch')->with(\Mockery::on(function ($job) {
            return ($job instanceof \Jihe\Jobs\SendMessageToUserJob);
        }))->andReturn(null);

        $this->addEnrollmentPermission([
            'mobile' => '13800138000',
            'team'   => 1,
            'name'   => '群名字',
            'memo'   => '团长提供的备注'
        ], $team)->shouldBe(1);
    }

    function it_rejects_adding_enrollment_permission_on_invalid_status(TeamMemberEnrollmentRequestRepository $requests)
    {
        $requests->addPermission(Argument::cetera())->shouldNotBeCalled();

        $this->shouldThrow(new \InvalidArgumentException('invalid permission status'))
            ->duringAddEnrollmentPermission([
                'mobile' => '13800138000',
                'team'   => 1,
                'name'   => '群名字',
                'memo'   => '团长提供的备注',
                'status' => -999 /* invalid status */
            ], (new Team())->setId(1));
    }

    //=============================================
    //         importEnrollmentPermissions
    //=============================================
    function it_imports_enrollment_permissions_all_new(TeamMemberEnrollmentRequestRepository $requests,
                                                       UserRepository $users)
    {
        $team = (new Team)->setId(1)->setName('my team');

        // no existing permissions for all
        $requests->findPermission(Argument::cetera())->willReturn(null);
        $requests->addPermission([
            'mobile' => '13800138000',
            'team'   => 1,
            'name'   => '钟移动',
            'memo'   => null,
            'status' => TeamMemberEnrollmentPermission::STATUS_PERMITTED
        ])->shouldBeCalledTimes(1)->willReturn(1);
        $requests->addPermission([
            'mobile' => '18612345678',
            'team'   => 1,
            'name'   => '谁谁谁',
            'memo'   => null,
            'status' => TeamMemberEnrollmentPermission::STATUS_PERMITTED
        ])->shouldBeCalledTimes(1)->willReturn(2);
        $users->findIdsByMobiles(['13800138000', '18612345678'])->shouldBeCalledTimes(1)
              ->willReturn([
                  '13800138000' => 1,
                  '18612345678' => 2,
              ]);
        Bus::shouldReceive('dispatch')->with(\Mockery::on(function ($job) {
            return ($job instanceof \Jihe\Jobs\SendMessageToUserJob);
        }))->andReturn(null);

        $excel = __DIR__ . '/test-data/team/member/enrollment_permissions_all_fine.xls';
        $failedRows = $this->importEnrollmentPermissions($excel, $team)
                           ->getWrappedObject();
        Assert::assertEmpty($failedRows); // no failed rows at all
    }

    function it_checks_when_importing_enrollment_permissions(TeamMemberEnrollmentRequestRepository $requests,
                                                             UserRepository $users)
    {
        // find permission will call exactly once since we err at first line
        $requests->findPermission(Argument::cetera())->shouldBeCalledTimes(1)->willReturn(null);
        $requests->addPermission([
            'mobile' => '10086',
            'team'   => 1,
            'name'   => '钟移动',
            'memo'   => null,
            'status' => TeamMemberEnrollmentPermission::STATUS_PERMITTED
        ])->shouldNotBeCalled();
        $requests->addPermission([
            'mobile' => '18612345678',
            'team'   => 1,
            'name'   => '谁谁谁',
            'memo'   => null,
            'status' => TeamMemberEnrollmentPermission::STATUS_PERMITTED
        ])->shouldBeCalledTimes(1)->willReturn(1);
        $users->findIdsByMobiles(['18612345678'])->shouldBeCalledTimes(1)
            ->willReturn([
                '18612345678' => 2,
            ]);
        Bus::shouldReceive('dispatch')->with(\Mockery::on(function ($job) {
            return ($job instanceof \Jihe\Jobs\SendMessageToUserJob);
        }))->andReturn(null);

        $excel = __DIR__ . '/test-data/team/member/enrollment_permissions_bad_mobile.xls';
        $failedRows = $this->importEnrollmentPermissions($excel, (new Team)->setId(1)->setName('my team'))
                           ->getWrappedObject();
        Assert::assertCount(1, $failedRows);

        // the first row fails
        list($row, $reason) = $failedRows[0];
        Assert::assertContains('10086', $row);
        Assert::assertContains('手机号错误', $reason);
    }

    function it_can_stop_on_error_when_importing_enrollment_permissions(TeamMemberEnrollmentRequestRepository $requests,
                                                                        UserRepository $users)
    {
        // no existing permissions for all
        $requests->findPermission(Argument::cetera())->shouldNotBeCalled();
        $requests->addPermission(Argument::cetera())->shouldNotBeCalled();

        $excel = __DIR__ . '/test-data/team/member/enrollment_permissions_bad_mobile.xls';
        $failedRows = $this->importEnrollmentPermissions($excel, (new Team)->setId(1)->setName('my team'), [
            'on_error_stop' => true,
        ])->getWrappedObject();
        Assert::assertCount(1, $failedRows);
        $users->findIdsByMobiles(Argument::cetera())->shouldNotBeCalled();
        Bus::shouldNotReceive('dispatch');

        // the first row fails
        list($row, $reason) = $failedRows[0];
        Assert::assertContains('10086', $row);
        Assert::assertContains('手机号错误', $reason);
    }

    function it_updates_existing_permissions_when_importing_enrollment_permissions(TeamMemberEnrollmentRequestRepository $requests,
                                                                                   UserRepository $users)
    {
        $requests->findPermission(['13800138000', '18612345678'], 1)->shouldBeCalledTimes(1)
            ->willReturn([
                // 13800138000 will be updated
                '13800138000' => (new TeamMemberEnrollmentPermission)->setId(1)->setMobile('1380013800')->setMemo('旧memo'),
                // 18612345678 will be added
            ]);
        $requests->addPermission(Argument::withEntry('mobile', '18612345678'))->shouldBeCalledTimes(1)->willReturn(1);
        $requests->addPermission(Argument::withEntry('mobile', '13800138000'))->shouldNotBeCalled();
        $requests->updatePermission(1, Argument::withEntry('name', '钟移动'))->shouldBeCalled()->willReturn(true);
        // '13800138000' already exists in permissions, so SMS will not be sent
        $users->findIdsByMobiles(['18612345678'])->shouldBeCalledTimes(1)
            ->willReturn([
                '18612345678' => 2,     // to push
            ]);
        Bus::shouldReceive('dispatch')->with(\Mockery::on(function ($job) {
            return ($job instanceof \Jihe\Jobs\PushToAliasMessageJob);
        }))->andReturn(null);

        $excel = __DIR__ . '/test-data/team/member/enrollment_permissions_all_fine.xls';
        $failedRows = $this->importEnrollmentPermissions($excel, (new Team)->setId(1)->setName('my team'))
                           ->getWrappedObject();
        Assert::assertEmpty($failedRows);
    }

    function it_updates_existing_permissions_when_importing_enrollment_permissions_in_batch(
        TeamMemberEnrollmentRequestRepository $requests, UserRepository $users)
    {
        // first batch
        $requests->findPermission(['13800138000', '18612345678'], 1)->shouldBeCalledTimes(1)
            ->willReturn([
                // 13800138000 will be updated
                '13800138000' => (new TeamMemberEnrollmentPermission)->setId(1)->setMobile('1380013800')->setMemo('旧memo'),
                // 18612345678 will be added
            ]);
        $requests->addPermission(Argument::withEntry('mobile', '18612345678'))->shouldBeCalledTimes(1)->willReturn(1);
        $requests->addPermission(Argument::withEntry('mobile', '13800138000'))->shouldNotBeCalled();
        $requests->updatePermission(1, Argument::withEntry('name', '钟移动'))->shouldBeCalled()->willReturn(true);
        // '13800138000' already exists in permissions, so SMS will not be sent
        $users->findIdsByMobiles(['18612345678'])->shouldBeCalledTimes(1)
            ->willReturn([
                '18612345678' => 3,     // to push
            ]);

        // second batch
        $requests->findPermission(['18612345679'], 1)->shouldBeCalledTimes(1)
            ->willReturn([
                // 18612345679 will be added
            ]);
        $requests->addPermission(Argument::withEntry('mobile', '18612345679'))->shouldBeCalledTimes(1)->willReturn(1);
        $users->findIdsByMobiles(['18612345679'])->shouldBeCalledTimes(1)
            ->willReturn([
                '18612345679' => 2,     // to push
            ]);

        Bus::shouldReceive('dispatch')->with(\Mockery::on(function ($job) {
            return ($job instanceof \Jihe\Jobs\PushToAliasMessageJob);
        }))->andReturn(null);

        $excel = __DIR__ . '/test-data/team/member/enrollment_permissions_batch_test.xls';
        $failedRows = $this->importEnrollmentPermissions($excel, (new Team)->setId(1)->setName('my team'), [
            'batch_size' => 2,
        ])->getWrappedObject();
        Assert::assertEmpty($failedRows);
    }

    //=============================================
    //           approveEnrollmentRequest
    //=============================================
    public function it_approves_enrollment_request(TeamMemberEnrollmentRequestRepository $requests,
                                                   TeamMemberRepository $members)
    {
        $request = (new TeamMemberEnrollmentRequest)
            ->setId(100)
            ->setStatus(TeamMemberEnrollmentRequest::STATUS_PENDING)
            ->setInitiator((new User)->setId(1))
            ->setName('群名片')
            ->setMemo('备注')
            ->setTeam((new Team)->setId(2));
        $requests->updateStatusToApproved(100)->shouldBeCalledTimes(1)->willReturn(true);
        $members->add(1, 2, Argument::that(function (array $request) {
            return $request['name'] == '群名片' &&
            $request['group'] == TeamGroup::UNGROUPED &&
            $request['memo'] == '备注';
        }))->willReturn(true);

        $this->approveEnrollmentRequest($request)
             ->shouldBe(true);
    }

    public function it_fails_to_approve_non_pending_enrollment_request(
        TeamMemberEnrollmentRequestRepository $requests, TeamMemberRepository $members)
    {
        $request = (new TeamMemberEnrollmentRequest)
            ->setId(100)
            ->setStatus(TeamMemberEnrollmentRequest::STATUS_APPROVED)
            ->setInitiator((new User)->setId(1))
            ->setName('群名片')
            ->setTeam((new Team)->setId(2));
        $requests->updateStatusToApproved(Argument::cetera())->shouldNotBeCalled();
        $members->add(Argument::cetera())->shouldNotBeCalled();

        $this->approveEnrollmentRequest($request)
             ->shouldBe(false);
    }

    public function it_approves_enrollment_request_and_overrides_memo_if_not_provided(
        TeamMemberEnrollmentRequestRepository $requests, TeamMemberRepository $members)
    {
        $request = (new TeamMemberEnrollmentRequest)
            ->setId(100)
            ->setStatus(TeamMemberEnrollmentRequest::STATUS_PENDING)
            ->setInitiator((new User)->setId(1))
            ->setTeam((new Team)->setId(2))
            ->setName('群名片')
            ->setMemo('备注来自请求');
        $requests->updateStatusToApproved(100)->shouldBeCalledTimes(1)->willReturn(true);
        $members->add(1, 2, Argument::that(function (array $requests) {
            return $requests['name'] == '群名片' &&
                   $requests['memo'] == '备注来自请求' &&
                   empty($requests['requirements']);
        }))->willReturn(true);

        $this->approveEnrollmentRequest($request)
            ->shouldBe(true);
    }

    //=============================================
    //           rejectEnrollmentRequest
    //=============================================
    public function it_rejects_enrollment_request(TeamMemberEnrollmentRequestRepository $requests)
    {
        $request = (new TeamMemberEnrollmentRequest)
            ->setId(100)
            ->setStatus(TeamMemberEnrollmentRequest::STATUS_PENDING)
            ->setInitiator((new User)->setId(1))
            ->setName('群名片')
            ->setMemo('备注')
            ->setTeam((new Team)->setId(2)->setName('某跑团')->setContactPhone('13800138000'));
        $requests->updateStatusToRejected(100, '拒绝理由')->shouldBeCalledTimes(1)->willReturn(true);
        Bus::shouldReceive('dispatch')->with(\Mockery::on(function ($job) {
            return ($job instanceof \Jihe\Jobs\SendMessageToUserJob);
        }))->andReturn(null);

        $this->rejectEnrollmentRequest($request, '拒绝理由')
            ->shouldBe(true);
    }

    public function it_does_not_reject_non_pending_enrollment_request(TeamMemberEnrollmentRequestRepository $requests)
    {
        $request = (new TeamMemberEnrollmentRequest)
            ->setId(100)
            ->setStatus(TeamMemberEnrollmentRequest::STATUS_REJECTED)
            ->setInitiator((new User)->setId(1))
            ->setName('群名片')
            ->setMemo('备注')
            ->setTeam((new Team)->setId(2)->setName('某跑团')->setContactPhone('13800138000'));
        $requests->updateStatusToRejected(Argument::cetera())->shouldNotBeCalled();
        Bus::shouldNotReceive('dispatch');

        $this->rejectEnrollmentRequest($request, '拒绝理由')
            ->shouldBe(false);
    }
    
    //=============================================
    //       findTeamsWhitelistedUser
    //=============================================
    public function it_finds_teams_that_whitelisted_user(TeamMemberEnrollmentRequestRepository $requests, TeamMemberRepository $members)
    {
        $requests->findTeamsWhitelistedUser('13800138000')->shouldBeCalledTimes(1)->willReturn([
                1 => (new Team)->setId(1)->setName('Team#1'),
        ]);
        $members->listEnrolledTeams(1)->shouldBeCalledTimes(1)->willReturn([ 2 ]);
    
        $teams = $this->findTeamsWhitelistedUser(1, '13800138000')->getWrappedObject();
        Assert::assertCount(1, $teams);
        Assert::assertEquals(1, current($teams)->getId());
    }
    
    public function it_finds_no_teams_that_whitelisted_user_if_all_whitelsited_teams_are_added(TeamMemberEnrollmentRequestRepository $requests, TeamMemberRepository $members)
    {
        $requests->findTeamsWhitelistedUser('13800138000')->shouldBeCalledTimes(1)->willReturn([
            1 => (new Team)->setId(1)->setName('Team#1'),
        ]);
        $members->listEnrolledTeams(1)->shouldBeCalledTimes(1)->willReturn([ 1, 2 ]);
    
        $teams = $this->findTeamsWhitelistedUser(1, '13800138000')->getWrappedObject();
        Assert::assertEmpty($teams);
    }
    
    public function it_finds_teams_that_whitelisted_user_if_partial_whitelsited_teams_are_added(TeamMemberEnrollmentRequestRepository $requests, TeamMemberRepository $members)
    {
        $requests->findTeamsWhitelistedUser('13800138000')->shouldBeCalledTimes(1)->willReturn([
            1 => (new Team)->setId(1)->setName('Team#1'),
            2 => (new Team)->setId(2)->setName('Team#2'),
        ]);
        $members->listEnrolledTeams(1)->shouldBeCalledTimes(1)->willReturn([ 2, 3 ]);
    
        $teams = $this->findTeamsWhitelistedUser(1, '13800138000')->getWrappedObject();
        Assert::assertCount(1, $teams);
        Assert::assertEquals(1, current($teams)->getId());
    }
    
    public function it_finds_no_teams_that_whitelisted_user_if_no_whitelsited_teams(TeamMemberEnrollmentRequestRepository $requests, TeamMemberRepository $members)
    {
        $requests->findTeamsWhitelistedUser('13800138000')->shouldBeCalledTimes(1)->willReturn([]);
        $members->listEnrolledTeams(Argument::cetera())->shouldNotBeCalled();
    
        $teams = $this->findTeamsWhitelistedUser(1, '13800138000')->getWrappedObject();
        Assert::assertEmpty($teams);
    }
    

    //=============================================
    //       blacklistEnrollmentRequest
    //=============================================
    function it_blacklists_enrollment_request_based_on_a_permitted_permission(
        TeamMemberEnrollmentRequestRepository $requests)
    {
        $requests->updateStatusToRejected(1, Argument::cetera())->shouldBeCalledTimes(1)->willReturn(true);
        Bus::shouldReceive('dispatch')->with(\Mockery::on(function ($job) {
            return ($job instanceof \Jihe\Jobs\SendMessageToUserJob);
        }))->andReturn(null);

        // a permitted permission is returned
        $requests->findPermission('13800138000', 1)->shouldBeCalledTimes(1)->willReturn(
            (new TeamMemberEnrollmentPermission)->setId(1)
            ->setMobile('13800138000')
            ->setTeam((new Team)->setId(1))
            ->setStatus(TeamMemberEnrollmentPermission::STATUS_PERMITTED)
        );
        $requests->updatePermission(1, Argument::withEntry('status', TeamMemberEnrollmentPermission::STATUS_PROHIBITED))
            ->shouldBeCalledTimes(1)->willReturn(true);

        $this->blacklistEnrollmentRequest((new TeamMemberEnrollmentRequest)->setId(1)
                                               ->setInitiator((new User)->setMobile('13800138000')),
                                          (new Team)->setId(1))
             ->shouldBe(true);

    }

    function it_blacklists_enrollment_request_based_on_a_prohibited_permission(
        TeamMemberEnrollmentRequestRepository $requests)
    {
        $requests->updateStatusToRejected(1, Argument::cetera())->shouldBeCalledTimes(1)->willReturn(true);
        Bus::shouldNotReceive('dispatch');

        // a permitted permission is returned
        $requests->findPermission('13800138000', 1)->shouldBeCalledTimes(1)->willReturn(
            (new TeamMemberEnrollmentPermission)->setId(1)
                ->setMobile('13800138000')
                ->setTeam((new Team)->setId(1))
                ->setStatus(TeamMemberEnrollmentPermission::STATUS_PROHIBITED)
        );
        $requests->updatePermission(1, Argument::withEntry('status', TeamMemberEnrollmentPermission::STATUS_PROHIBITED))
            ->shouldBeCalledTimes(1)->willReturn(true);

        $this->blacklistEnrollmentRequest((new TeamMemberEnrollmentRequest)->setId(1)
                                                  ->setInitiator((new User)->setMobile('13800138000')),
                                          (new Team)->setId(1))
            ->shouldBe(true);

    }

    function it_blacklists_enrollment_request_based_on_non_existing_permission(
        TeamMemberEnrollmentRequestRepository $requests)
    {
        $requests->updateStatusToRejected(1, Argument::cetera())->shouldBeCalledTimes(1)->willReturn(true);
        Bus::shouldNotReceive('dispatch');
        // no permission set before
        $requests->findPermission('13800138000', 1)->shouldBeCalledTimes(1)->willReturn(null);
        $requests->addPermission([
            'mobile' => '13800138000',
            'name'   => '名字',
            'memo'   => null,
            'team'   => 1,
            'status' => TeamMemberEnrollmentPermission::STATUS_PROHIBITED,
        ])->shouldBeCalledTimes(1)->willReturn(1);
        $requests->updatePermission(Argument::cetera())->shouldNotBeCalled();

        $this->blacklistEnrollmentRequest((new TeamMemberEnrollmentRequest)->setId(1)
                                              ->setInitiator((new User)->setMobile('13800138000')),
                                          (new Team)->setId(1), '名字')
            ->shouldBe(true);
    }

    //=============================================
    //       whiteBlacklistedEnrollmentRequest
    //=============================================
    function it_whites_blacklisted_enrollment_request_based_on_non_existing_permission(
        TeamMemberEnrollmentRequestRepository $requests)
    {
        // no permission set before
        $requests->findPermission('13800138000', 1)->shouldBeCalledTimes(1)->willReturn(null);
        $requests->updatePermission(Argument::cetera())->shouldNotBeCalled();

        $this->whiteBlacklistedEnrollmentRequest('13800138000', (new Team)->setId(1), '备注')
            ->shouldBe(true);
    }

    function it_whites_blacklisted_enrollment_request_based_on_permitted_permission(
        TeamMemberEnrollmentRequestRepository $requests)
    {
        // was permitted
        $requests->findPermission('13800138000', 1)->shouldBeCalledTimes(1)->willReturn(
            (new TeamMemberEnrollmentPermission)->setId(1)
                ->setMobile('13800138000')
                ->setTeam((new Team)->setId(1))
                ->setStatus(TeamMemberEnrollmentPermission::STATUS_PERMITTED)
        );
        $requests->updatePermission(Argument::cetera())->shouldNotBeCalled();

        $this->whiteBlacklistedEnrollmentRequest('13800138000', (new Team)->setId(1), '备注')
            ->shouldBe(true);
    }

    function it_whites_blacklisted_enrollment_request_based_on_prohibited_permission(
        TeamMemberEnrollmentRequestRepository $requests)
    {
        // was permitted
        $requests->findPermission('13800138000', 1)->shouldBeCalledTimes(1)->willReturn(
            (new TeamMemberEnrollmentPermission)->setId(1)
                ->setMobile('13800138000')
                ->setTeam((new Team)->setId(1))
                ->setStatus(TeamMemberEnrollmentPermission::STATUS_PROHIBITED)
        );
//        $requests->updatePermission(1, Argument::withEntry('status', TeamMemberEnrollmentPermission::STATUS_PERMITTED))
//            ->shouldBeCalledTimes(1)->willReturn(true);
        $requests->deletePermission(1)->shouldBeCalledTimes(1)->willReturn(true);

        $this->whiteBlacklistedEnrollmentRequest('13800138000', (new Team)->setId(1), '备注')
            ->shouldBe(true);
    }
}