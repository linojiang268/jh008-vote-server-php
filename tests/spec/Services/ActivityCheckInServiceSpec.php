<?php
namespace spec\Jihe\Services;

use Jihe\Services\ActivityService;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Jihe\Contracts\Repositories\ActivityCheckInRepository;
use Jihe\Contracts\Repositories\ActivityMemberRepository;
use Jihe\Contracts\Repositories\ActivityCheckInQRCodeRepository;
use Jihe\Contracts\Repositories\UserRepository;

class ActivityCheckInServiceSpec extends ObjectBehavior
{
    function let(ActivityCheckInRepository $activityCheckInRepository,
                 ActivityMemberRepository $activityMemberRepository,
                 ActivityCheckInQRCodeRepository $activityCheckInQRCodeRepository,
                 UserRepository $userRepository,
                 ActivityService $activityService
    ) {
        // by default there is no configuration
        $this->beAnInstanceOf(\Jihe\Services\ActivityCheckInService::class, [
            $activityCheckInRepository,
            $activityMemberRepository,
            $activityCheckInQRCodeRepository,
            $userRepository,
            $activityService,
        ]);
    }

    //==========================================
    //      FirstStepQuickCheckIn
    //==========================================
    function it_first_step_quick_check_in_success(
        ActivityCheckInRepository $activityCheckInRepository,
        ActivityMemberRepository $activityMemberRepository,
        ActivityCheckInQRCodeRepository $activityCheckInQRCodeRepository,
        UserRepository $userRepository
    ) {
        $mobile = '13800138000';
        $userId = 1;
        $activityId = 2;
        $userRepository->findId($mobile)->shouldBeCalledTimes(1)->willReturn($userId);
        $activityMemberRepository->exists($activityId, $userId)
                                 ->shouldBeCalledTimes(1)
                                 ->willReturn(true);
        $activityCheckInQRCodeRepository->all($activityId)
                                        ->shouldBeCalledTimes(1)
                                        ->willReturn([
                                            [
                                                'id'            => 1,
                                                'activity_id'   => $activityId,
                                                'step'          => 1,
                                                'url'           => 'http://domain/qrcode',
                                            ],
                                        ]);
        $activityCheckInRepository->all($activityId, $userId)
                                  ->shouldBeCalledTimes(1)
                                  ->willReturn([]);
        $activityCheckInRepository->add($userId, $activityId, 1, 0)
                                  ->shouldBeCalledTimes(1)
                                  ->willReturn(1);
        $activityMemberRepository->markAsCheckin($userId, $activityId)
                                 ->shouldBeCalledTimes(1)
                                 ->willReturn(1);

        $this->firstStepQuickCheckIn($mobile, $activityId)
             ->shouldReturn(null);
    }

    function it_first_step_quick_check_in_success_more_than_once(
        ActivityCheckInRepository $activityCheckInRepository,
        ActivityMemberRepository $activityMemberRepository,
        ActivityCheckInQRCodeRepository $activityCheckInQRCodeRepository,
        UserRepository $userRepository
    ) {
        $mobile = '13800138000';
        $userId = 1;
        $activityId = 2;
        $userRepository->findId($mobile)->shouldBeCalledTimes(1)->willReturn($userId);
        $activityMemberRepository->exists($activityId, $userId)
                                 ->shouldBeCalledTimes(1)
                                 ->willReturn(true);
        $activityCheckInQRCodeRepository->all($activityId)
                                        ->shouldBeCalledTimes(1)
                                        ->willReturn([
                                            [
                                                'id'            => 1,
                                                'activity_id'   => $activityId,
                                                'step'          => 1,
                                                'url'           => 'http://domain/qrcode',
                                            ],
                                        ]);
        $activityCheckInRepository->all($activityId, $userId)
                                  ->shouldBeCalledTimes(1)
                                  ->willReturn([
                                    [
                                        'id'            => 1,
                                        'user_id'       => $userId,
                                        'activity_id'   => $activityId,
                                        'step'          => 1,
                                        'process_id'    => 0,
                                    ],
                                  ]);
        $activityCheckInRepository->add($userId, $activityId, 1, 0)
                                  ->shouldNotBeCalled();
        $activityMemberRepository->markAsCheckin(Argument::any())
                                 ->shouldNotBeCalled();

        $this->firstStepQuickCheckIn($mobile, $activityId)
             ->shouldReturn(null);
    }

    function it_throw_exceptions_if_user_not_activity_member(
        ActivityMemberRepository $activityMemberRepository,
        UserRepository $userRepository
    ) {
        $mobile = '13800138000';
        $userId = 1;
        $activityId = 2;
        $userRepository->findId($mobile)->shouldBeCalledTimes(1)->willReturn($userId);
        $activityMemberRepository->exists($activityId, $userId)
                                 ->shouldBeCalledTimes(1)
                                 ->willReturn(false);

        $this->shouldThrow(new \Exception('非活动成员'))
             ->duringFirstStepQuickCheckIn($mobile, $activityId);
    }

    function it_throw_exceptions_if_activity_no_need_check_in(
        ActivityMemberRepository $activityMemberRepository,
        ActivityCheckInQRCodeRepository $activityCheckInQRCodeRepository,
        UserRepository $userRepository
    ) {
        $mobile = '13800138000';
        $userId = 1;
        $activityId = 2;
        $userRepository->findId($mobile)->shouldBeCalledTimes(1)->willReturn($userId);
        $activityMemberRepository->exists($activityId, $userId)
                                 ->shouldBeCalledTimes(1)
                                 ->willReturn(true);
        $activityCheckInQRCodeRepository->all($activityId)
                                        ->shouldBeCalledTimes(1)
                                        ->willReturn([]);

        $this->shouldThrow(new \Exception('该活动不需要签到'))
             ->duringFirstStepQuickCheckIn($mobile, $activityId);
    }

    function it_throw_exceptions_if_activity_qr_not_exists(
        ActivityMemberRepository $activityMemberRepository,
        ActivityCheckInQRCodeRepository $activityCheckInQRCodeRepository,
        UserRepository $userRepository
    ) {
        $mobile = '13800138000';
        $userId = 1;
        $activityId = 2;
        $userRepository->findId($mobile)->shouldBeCalledTimes(1)->willReturn($userId);
        $activityMemberRepository->exists($activityId, $userId)
                                 ->shouldBeCalledTimes(1)
                                 ->willReturn(true);
        $activityCheckInQRCodeRepository->all($activityId)
                                        ->shouldBeCalledTimes(1)
                                        ->willReturn([
                                            [
                                                'id'            => 1,
                                                'activity_id'   => $activityId,
                                                'url'           => 'http://domain/qrcode',
                                            ],
                                        ]);

        $this->shouldThrow(new \Exception('未查询到活动二维码'))
             ->duringFirstStepQuickCheckIn($mobile, $activityId);
    }

    function it_throw_exceptions_if_check_in_failed(
        ActivityCheckInRepository $activityCheckInRepository,
        ActivityMemberRepository $activityMemberRepository,
        ActivityCheckInQRCodeRepository $activityCheckInQRCodeRepository,
        UserRepository $userRepository
    ) {
        $mobile = '13800138000';
        $userId = 1;
        $activityId = 2;
        $userRepository->findId($mobile)->shouldBeCalledTimes(1)->willReturn($userId);
        $activityMemberRepository->exists($activityId, $userId)
                                 ->shouldBeCalledTimes(1)
                                 ->willReturn(true);
        $activityCheckInQRCodeRepository->all($activityId)
                                        ->shouldBeCalledTimes(1)
                                        ->willReturn([
                                            [
                                                'id'            => 1,
                                                'activity_id'   => $activityId,
                                                'step'          => 1,
                                                'url'           => 'http://domain/qrcode',
                                            ],
                                        ]);
        $activityCheckInRepository->all($activityId, $userId)
                                  ->shouldBeCalledTimes(1)
                                  ->willReturn([]);
        $activityCheckInRepository->add($userId, $activityId, 1, 0)
                                  ->shouldBeCalledTimes(1)
                                  ->willReturn(null);

        $this->shouldThrow(new \Exception('签到失败，请联系现场工作人员'))
             ->duringFirstStepQuickCheckIn($mobile, $activityId);
    }
}
