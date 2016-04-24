<?php
namespace spec\Jihe\Services;

use PhpSpec\Laravel\LaravelObjectBehavior;
use Prophecy\Argument;
use Mockery;
use Event;
use Bus;
use PHPUnit_Framework_Assert as Assert;
use DB;

use Jihe\Contracts\Repositories\UserRepository;
use Jihe\Contracts\Repositories\ActivityApplicantRepository;
use Jihe\Contracts\Repositories\ActivityMemberRepository;
use Jihe\Services\ActivityService;
use Jihe\Services\TeamMemberService;
use Jihe\Services\UserAttributeService;
use Jihe\Services\UserService;
use Jihe\Services\VerificationService;
use Jihe\Entities\Activity as ActivityEntity;;
use Jihe\Entities\ActivityApplicant as ActivityApplicantEntity;
use Jihe\Entities\User as UserEntity;
use Jihe\Models\ActivityMember;

class ActivityApplicantServiceSpec extends LaravelObjectBehavior
{
    function let(ActivityApplicantRepository $activityApplicantRepository,
                 ActivityMemberRepository $activityMemberRepository,
                 UserRepository $userRepository,
                 ActivityService $activityService,
                 TeamMemberService $teamMemberService,
                 UserAttributeService $userAttributeService,
                 UserService $userService,
                 VerificationService $verificationService
    ) {
        $this->beAnInstanceOf(\Jihe\Services\ActivityApplicantService::class, [
            $activityApplicantRepository,
            $activityMemberRepository,
            $userRepository,
            $activityService,
            $teamMemberService,
            $userAttributeService,
            $userService,
            $verificationService,
        ]);
    }

    //=============================================
    //           onActivityApplicantPaymentSuccess
    //=============================================
    function it_on_activity_applicant_payment_success(
        ActivityApplicantRepository $activityApplicantRepository,
        ActivityMemberRepository $activityMemberRepository
    ) {
        $orderNo = 'abcdefg';
        $applicant = factory(\Jihe\Models\ActivityApplicant::class)->make([
            'order_no'      => $orderNo,
            'status'        => 2,
            'attrs'         => null,
        ]);
        $activityApplicantRepository->getApplicantInfoByOrderNo($orderNo)
                                    ->shouldBeCalledTimes(1)
                                    ->willReturn($applicant);
        $activityApplicantRepository->updateStatusAfterPaymentSuccess($orderNo)
                                    ->shouldBeCalledTimes(1)
                                    ->willReturn(true);
        $activityMemberRepository->add([
            'activity_id'   => $applicant['activity_id'],
            'user_id'       => $applicant['user_id'],
            'name'          => $applicant['name'],
            'mobile'        => $applicant['mobile'],
            'attrs'          => $applicant['attrs'],
            'group_id'      => 0,
            'role'          => 0,
        ])->shouldBeCalledTimes(1);

        $this->onActivityApplicantPaymentSuccess($orderNo)->shouldBeNull();
    }

    function it_on_activity_applicant_payment_success_applicant_status_not_pay(
        ActivityApplicantRepository $activityApplicantRepository,
        ActivityMemberRepository $activityMemberRepository
    ) {
        $orderNo = 'abcdefg';
        $applicant = factory(\Jihe\Models\ActivityApplicant::class)->make([
            'order_no'      => $orderNo,
            'status'        => 2,
        ]);
        $activityApplicantRepository->getApplicantInfoByOrderNo($orderNo)
                                    ->shouldBeCalledTimes(1)
                                    ->willReturn($applicant);
        $activityApplicantRepository->updateStatusAfterPaymentSuccess($orderNo)
                                    ->shouldBeCalledTimes(1)
                                    ->willReturn(false);
        $activityMemberRepository->add(Argument::cetera())
                                 ->shouldNotBeCalled();

        $this->onActivityApplicantPaymentSuccess($orderNo)->shouldBeNull();
    }

    public function it_throw_exception_if_applicant_not_exists_when_payment_success(
        ActivityApplicantRepository $activityApplicantRepository,
        ActivityMemberRepository $activityMemberRepository
    ) {
        $orderNo = 'abcdefg';
        $activityApplicantRepository->getApplicantInfoByOrderNo($orderNo)
                                    ->shouldBeCalledTimes(1)
                                    ->willReturn(null);
        $activityApplicantRepository->updateStatusAfterPaymentSuccess($orderNo)
                                    ->shouldNotBeCalled();
        $activityMemberRepository->add(Argument::cetera())
                                 ->shouldNotBeCalled();

        $this->shouldThrow(new \Exception('未查询到报名信息'))
             ->duringOnActivityApplicantPaymentSuccess($orderNo);
    }

    //=============================================
    //           recycleActivityApplicant
    //=============================================
    public function it_recycle_applicant_status_success(
        ActivityService $activityService,
        ActivityApplicantRepository $activityApplicantRepository
    ) {
        $applicantId = 1;
        $applicant = [
            'id'            => $applicantId,
            'activity_id'   => 1,
            'order_no'      => 'abcdefgt',
            'status'        => 2,
            'expire_at'     => date('Y-m-d H:i:s', strtotime('-1 days')),
            'attrs'         => null,
        ];
        $activity = (new \Jihe\Entities\Activity)
                        ->setId(1)
                        ->setBeginTime(date('Y-m-d H:i:s', strtotime('-1 days')));

        $activityApplicantRepository->getApplicantInfoByApplicantId($applicantId)
                                    ->shouldBeCalledTimes(1)
                                    ->willReturn($applicant);
        $activityService->getPublishedActivityById(1)
                        ->shouldBeCalledTimes(1)
                        ->willReturn($activity);
        $activityApplicantRepository->recycleActivityApplicant($applicantId)
                                    ->shouldBeCalledTimes(1)
                                    ->willReturn(true);
        $this->recycleApplicantStatus($applicantId)
             ->shouldReturn(true);
    }

    public function it_recycle_applicant_status_success_if_expired_not_set(
        ActivityService $activityService,
        ActivityApplicantRepository $activityApplicantRepository
    ) {
        $applicantId = 1;
        $applicant = [
            'id'            => $applicantId,
            'activity_id'   => 1,
            'order_no'      => 'abcdefgt',
            'status'        => 2,
            'expire_at'     => null,
            'attrs'         => null,
        ];
        $activity = (new \Jihe\Entities\Activity)
                        ->setId(1)
                        ->setBeginTime(date('Y-m-d H:i:s', strtotime('-1 days')));

        $activityApplicantRepository->getApplicantInfoByApplicantId($applicantId)
                                    ->shouldBeCalledTimes(1)
                                    ->willReturn($applicant);
        $activityService->getPublishedActivityById(1)
                        ->shouldBeCalledTimes(1)
                        ->willReturn($activity);
        $activityApplicantRepository->recycleActivityApplicant($applicantId)
                                    ->shouldBeCalledTimes(1)
                                    ->willReturn(true);

        $this->recycleApplicantStatus($applicantId)
             ->shouldReturn(true);
    }

    public function it_throw_exception_if_applicant_not_exists_on_recycle(
        ActivityService $activityService,
        ActivityApplicantRepository $activityApplicantRepository
    ) {
        $applicantId = 1;

        $activityApplicantRepository->getApplicantInfoByApplicantId($applicantId)
                                    ->shouldBeCalledTimes(1)
                                    ->willReturn(null);
        $activityService->getPublishedActivityById(1)
                        ->shouldNotBeCalled();
        $activityApplicantRepository->recycleActivityApplicant($applicantId)
                                    ->shouldNotBeCalled();

        $this->shouldThrow(new \Exception('报名不存在，无法回收'))
             ->duringRecycleApplicantStatus($applicantId);
    }

    public function it_throw_exception_if_not_expired_on_recycle(
        ActivityService $activityService,
        ActivityApplicantRepository $activityApplicantRepository
    ) {
        $applicantId = 1;
        $applicant = factory(\Jihe\Models\ActivityApplicant::class)->make([
            'id'            => $applicantId,
            'activity_id'   => 1,
            'order_no'      => 'abcdefgt',
            'status'        => 2,
            'expire_at'     => date('Y-m-d H:i:s', strtotime('+5 seconds')),
            'attrs'         => null,
        ]);
        $activity = (new \Jihe\Entities\Activity)
                        ->setId(1)
                        ->setBeginTime(date('Y-m-d H:i:s', strtotime('+1 days')));

        $activityApplicantRepository->getApplicantInfoByApplicantId($applicantId)
                                    ->shouldBeCalledTimes(1)
                                    ->willReturn($applicant);
        $activityService->getPublishedActivityById(1)
                        ->shouldBeCalledTimes(1)
                        ->willReturn($activity);
        $activityApplicantRepository->recycleActivityApplicant($applicantId)
                                    ->shouldNotBeCalled();

        $this->shouldThrow(new \Exception('报名尚未过期，无法回收'))
             ->duringRecycleApplicantStatus($applicantId);
    }

    public function it_throw_exception_if_activity_begin_on_recycle(
        ActivityService $activityService,
        ActivityApplicantRepository $activityApplicantRepository
    ) {
        $applicantId = 1;
        $applicant = [
            'id'            => $applicantId,
            'activity_id'   => 1,
            'order_no'      => 'abcdefg',
            'status'        => 2,
            'expire_at'     => null,
            'attrs'         => null,
        ];
        $activity = (new \Jihe\Entities\Activity)
                        ->setId(1)
                        ->setBeginTime(date('Y-m-d H:i:s', strtotime('1 days')));

        $activityApplicantRepository->getApplicantInfoByApplicantId($applicantId)
                                    ->shouldBeCalledTimes(1)
                                    ->willReturn($applicant);
        $activityService->getPublishedActivityById(1)
                        ->shouldBeCalledTimes(1)
                        ->willReturn($activity);
        $activityApplicantRepository->recycleActivityApplicant($applicantId)
                                    ->shouldNotBeCalled();

        $this->shouldThrow(new \Exception('活动尚未开始，无法回收报名'))
             ->duringRecycleApplicantStatus($applicantId);
    }

    //=============================================
    //          doApplicant
    //=============================================
    /**
     * app报名成功，活动类型：不审核(有人数限制)，需要支付
     */
    public function it_do_applicant_success_from_app_with_audit_pay(
        ActivityService $activityService,
        ActivityApplicantRepository $activityApplicantRepository,
        UserRepository $userRepository,
        ActivityMemberRepository $activityMemberRepository,
        UserService $userService
    ) {
        // mock event
        Event::shouldReceive('fire')
            ->once()
            ->with(Mockery::type(\Jihe\Events\UserApplicantActivityEvent::class), [], false)
            ->andReturn([null]);
        $input = [
            'userId'        => 1,
            'activityId'    => 1,
            'name'          => '张三',
            'mobile'        => '13800138000',
            'channel'       => 0,
            'attrs'         => [
                ['key' => '身高', 'value' => '170cm']],
        ];
        $data = $this->prepareApplicantData($input, [
            'enrollLimit'   => 10,
            'enrollFeeType' => 3,
            'auditType'     => 0,
        ]);
        $activityService->getPublishedActivityById($data->activity->getId())
                        ->shouldBeCalledTimes(1)
                        ->willReturn($data->activity);
        $userService->findUserById($data->userId)
                        ->shouldBeCalledTimes(1)
                        ->willReturn($data->user);
        $activityMemberRepository->count($data->activity->getId())
                                 ->shouldBeCalledTimes(1)
                                 ->willReturn(0);
        $activityApplicantRepository->getNotPayCount($data->activity->getId())
                                    ->shouldBeCalledTimes(1)
                                    ->willReturn(1);
        $activityApplicantRepository->getValidUserApplicant(
            $data->userId, $data->activity->getId())
                                    ->shouldBeCalledTimes(1)
                                    ->willReturn(null);

        $data->applicant->setStatus(2);
        $activityApplicantRepository->saveApplicantInfo(
            Argument::that(function ($arg) use ($data, $input) {
                if (
                    ($arg['name'] != $input['name']) ||
                    ($arg['mobile'] != $data->user->getMobile()) ||
                    ($arg['attrs'] != json_encode($input['attrs'])) ||
                    ($arg['activity_id'] != $input['activityId']) ||
                    ($arg['user_id'] != $input['userId']) ||
                    ($arg['channel'] != $input['channel']) ||
                    ($arg['status'] != 2)
                ) {
                    return false;
                }
                if ( ! isset($arg['expire_at']))
                {
                    return false;
                }
                return true;
            }))->shouldBeCalledTimes(1)
              ->willReturn($data->applicant);
        $activityMemberRepository->add(Argument::any())
                                 ->shouldNotBeCalled();

        $resApplicant = $this->doApplicant(
            $data->userId, $data->activity->getId(),
            $data->rawAttrs, $data->channel
        )->getWrappedObject();

        Assert::assertInstanceOf(\Jihe\Entities\ActivityApplicant::class, $resApplicant);
        Assert::assertInstanceOf(\Jihe\Entities\Activity::class, $resApplicant->getActivity());
        Assert::assertEquals(2, $resApplicant->getStatus());

        $this->laravel->refreshApplication();
    }

    /**
     * app报名成功，活动类型：不审核(有人数限制)，不需要支付
     */
    public function it_do_applicant_success_from_app_with_noaudit_nopay(
        ActivityService $activityService,
        ActivityApplicantRepository $activityApplicantRepository,
        UserRepository $userRepository,
        ActivityMemberRepository $activityMemberRepository,
        UserService $userService
    ) {
        $input = [
            'userId'        => 1,
            'activityId'    => 1,
            'name'          => '张三',
            'mobile'        => '13800138000',
            'channel'       => 0,
            'attrs'         => [
                ['key' => '身高', 'value' => '170cm']],
        ];
        $data = $this->prepareApplicantData($input, [
            'enrollLimit'   => 10,
            'enrollFeeType' => 1,
            'auditType'     => 0,
        ]);
        $activityService->getPublishedActivityById($data->activity->getId())
                        ->shouldBeCalledTimes(1)
                        ->willReturn($data->activity);
        $userService->findUserById($data->userId)
                        ->shouldBeCalledTimes(1)
                        ->willReturn($data->user);
        $activityMemberRepository->count($data->activity->getId())
                                 ->shouldBeCalledTimes(1)
                                 ->willReturn(0);
        $activityApplicantRepository->getNotPayCount($data->activity->getId())
                                    ->shouldNotBeCalled();
        $activityApplicantRepository->getValidUserApplicant(
            $data->userId, $data->activity->getId())
                                    ->shouldBeCalledTimes(1)
                                    ->willReturn(null);

        $data->applicant->setStatus(3);
        $activityApplicantRepository->saveApplicantInfo([
                'name'          => $input['name'],
                'mobile'        => $data->user->getMobile(),
                'attrs'         => json_encode($input['attrs']),
                'activity_id'   => $input['activityId'],
                'user_id'       => $input['userId'],
                'channel'       => $input['channel'],
                'status'        => 3,
            ])->shouldBeCalledTimes(1)
              ->willReturn($data->applicant);
        $activityMemberRepository->add(Argument::that(function ($arg) {
                return ($arg['group'] == 0) && ($arg['role'] == 0) ? true : false;
            }))->shouldBeCalledTimes(1);

        $resApplicant = $this->doApplicant(
            $data->userId, $data->activity->getId(),
            $data->rawAttrs, $data->channel
        )->getWrappedObject();

        Assert::assertInstanceOf(\Jihe\Entities\ActivityApplicant::class, $resApplicant);
        Assert::assertInstanceOf(\Jihe\Entities\Activity::class, $resApplicant->getActivity());
        Assert::assertEquals(3, $resApplicant->getStatus());

        $this->laravel->refreshApplication();
    }

    /**
     * app报名成功，活动类型：审核，不需要支付
     */
    public function it_do_applicant_success_from_app_with_audit_nopay(
        ActivityService $activityService,
        ActivityApplicantRepository $activityApplicantRepository,
        UserRepository $userRepository,
        ActivityMemberRepository $activityMemberRepository,
        UserService $userService
    ) {
        // mock event
        Event::shouldReceive('fire')
            ->once()
            ->with(Mockery::type(\Jihe\Events\UserApplicantActivityEvent::class), [], false)
            ->andReturn([null]);

        $input = [
            'userId'        => 1,
            'activityId'    => 1,
            'name'          => '张三',
            'mobile'        => '13800138000',
            'channel'       => 0,
            'attrs'         => [
                ['key' => '身高', 'value' => '170cm']],
        ];
        $data = $this->prepareApplicantData($input, [
            'enrollLimit'   => 10,
            'enrollFeeType' => 1,
            'auditType'     => 1,
        ]);
        $activityService->getPublishedActivityById($data->activity->getId())
                        ->shouldBeCalledTimes(1)
                        ->willReturn($data->activity);
        $userService->findUserById($data->userId)
                        ->shouldBeCalledTimes(1)
                        ->willReturn($data->user);
        $activityMemberRepository->count($data->activity->getId())
                                 ->shouldBeCalledTimes(1)
                                 ->willReturn(0);
        $activityApplicantRepository->getNotPayCount($data->activity->getId())
                                    ->shouldNotBeCalled();
        $activityApplicantRepository->getValidUserApplicant(
            $data->userId, $data->activity->getId())
                                    ->shouldBeCalledTimes(1)
                                    ->willReturn(null);

        $data->applicant->setStatus(1);
        $activityApplicantRepository->saveApplicantInfo([
                'name'          => $input['name'],
                'mobile'        => $data->user->getMobile(),
                'attrs'         => json_encode($input['attrs']),
                'activity_id'   => $input['activityId'],
                'user_id'       => $input['userId'],
                'channel'       => $input['channel'],
                'status'        => 1,
            ])->shouldBeCalledTimes(1)
              ->willReturn($data->applicant);
        $activityMemberRepository->add(Argument::any())
                                 ->shouldNotBeCalled();

        $resApplicant = $this->doApplicant(
            $data->userId, $data->activity->getId(),
            $data->rawAttrs, $data->channel
        )->getWrappedObject();

        Assert::assertInstanceOf(\Jihe\Entities\ActivityApplicant::class, $resApplicant);
        Assert::assertInstanceOf(\Jihe\Entities\Activity::class, $resApplicant->getActivity());
        Assert::assertEquals(1, $resApplicant->getStatus());

        $this->laravel->refreshApplication();
    }

    /**
     * 报名失败，活动不存在或已下架
     */
    public function it_reject_do_applicant_activity_invalid(
        ActivityService $activityService,
        ActivityApplicantRepository $activityApplicantRepository,
        UserRepository $userRepository,
        ActivityMemberRepository $activityMemberRepository,
        UserService $userService
    ) {
        $activityService->getPublishedActivityById(Argument::any())
                        ->shouldBeCalledTimes(1)
                        ->willReturn(null);
        $this->shouldThrow(new \Exception('活动不存在或已下架'))
             ->duringDoApplicant(1, 1, '', 0);
    }

    /**
     * 报名失败，attrs格式错误: 非json格式
     */
    public function it_reject_do_applicant_attrs_invalid(
        ActivityService $activityService,
        ActivityApplicantRepository $activityApplicantRepository,
        UserRepository $userRepository,
        ActivityMemberRepository $activityMemberRepository,
        UserService $userService
    ) {
        $input = [
            'userId'        => 1,
            'activityId'    => 1,
            'name'          => '张三',
            'mobile'        => '13800138000',
            'channel'       => 0,
            'attrs'         => [
                ['key' => '身高', 'value' => '170cm']],
        ];
        $data = $this->prepareApplicantData($input, [
            'enrollLimit'   => 10,
            'enrollFeeType' => 1,
            'auditType'     => 1,
        ]);
        $activityService->getPublishedActivityById($data->activity->getId())
                        ->shouldBeCalledTimes(1)
                        ->willReturn($data->activity);

        $this->shouldThrow(new \Exception('报名信息错误'))
             ->duringDoApplicant(
                $data->userId, $data->activity->getId(),
                '', $data->channel
               );
    }

    /**
     * 报名失败，attrs格式错误: 手机号，姓名未填写
     */
    public function it_reject_do_applicant_attrs_miss_name_mobile(
        ActivityService $activityService,
        ActivityApplicantRepository $activityApplicantRepository,
        UserRepository $userRepository,
        ActivityMemberRepository $activityMemberRepository,
        UserService $userService
    ) {
        $input = [
            'userId'        => 1,
            'activityId'    => 1,
            'name'          => '张三',
            'mobile'        => '13800138000',
            'channel'       => 0,
            'attrs'         => [
                ['key' => '身高', 'value' => '170cm']],
            'attrsMissNameMobile'   => true,
        ];
        $data = $this->prepareApplicantData($input, [
            'enrollLimit'   => 10,
            'enrollFeeType' => 1,
            'auditType'     => 1,
        ]);
        $activityService->getPublishedActivityById($data->activity->getId())
                        ->shouldBeCalledTimes(1)
                        ->willReturn($data->activity);

        $this->shouldThrow(new \Exception('手机号格式错误'))
             ->duringDoApplicant(
                $data->userId, $data->activity->getId(),
                $data->rawAttrs, $data->channel
               );
    }

    /**
     * 报名失败，attrs格式错误: 报名条件未填写
     */
    public function it_reject_do_applicant_attrs_miss_condition(
        ActivityService $activityService,
        ActivityApplicantRepository $activityApplicantRepository,
        UserRepository $userRepository,
        ActivityMemberRepository $activityMemberRepository,
        UserService $userService
    ) {
        $input = [
            'userId'        => 1,
            'activityId'    => 1,
            'name'          => '张三',
            'mobile'        => '13800138000',
            'channel'       => 0,
            'attrs'         => [],
        ];
        $data = $this->prepareApplicantData($input, [
            'enrollLimit'   => 10,
            'enrollFeeType' => 1,
            'auditType'     => 1,
        ]);
        $activityService->getPublishedActivityById($data->activity->getId())
                        ->shouldBeCalledTimes(1)
                        ->willReturn($data->activity);

        $this->shouldThrow(new \Exception('"身高"未填写'))
             ->duringDoApplicant(
                $data->userId, $data->activity->getId(),
                $data->rawAttrs, $data->channel
               );
    }

    /**
     * app & web 报名失败，未在报名允许时间
     */
    public function it_reject_do_applicant_enroll_time_violate(
        ActivityService $activityService,
        ActivityApplicantRepository $activityApplicantRepository,
        UserRepository $userRepository,
        ActivityMemberRepository $activityMemberRepository,
        UserService $userService
    ) {
        $input = [
            'userId'        => 1,
            'activityId'    => 1,
            'name'          => '张三',
            'mobile'        => '13800138000',
            'channel'       => 0,
            'attrs'         => [
                ['key' => '身高', 'value' => '170cm']],
        ];
        $data = $this->prepareApplicantData($input, [
            'enrollLimit'   => 10,
            'enrollFeeType' => 1,
            'auditType'     => 1,
        ]);
        $data->activity->setEnrollBeginTime(
            date('Y-m-d H:i:s', time() + 86400));

        $activityService->getPublishedActivityById($data->activity->getId())
                        ->shouldBeCalledTimes(1)
                        ->willReturn($data->activity);
        $userService->findUserById($data->userId)
                    ->shouldBeCalledTimes(1)
                    ->willReturn($data->user);

        $this->shouldThrow(new \Exception('该活动尚未开始报名'))
             ->duringDoApplicant(
                $data->userId, $data->activity->getId(),
                $data->rawAttrs, $data->channel
               );
    }

    /**
     * app & web 报名失败，活动已经满员
     */
    public function it_reject_do_applicant_excceed_people_limitaion(
        ActivityService $activityService,
        ActivityApplicantRepository $activityApplicantRepository,
        UserRepository $userRepository,
        ActivityMemberRepository $activityMemberRepository,
        UserService $userService
    ) {
        $input = [
            'userId'        => 1,
            'activityId'    => 1,
            'name'          => '张三',
            'mobile'        => '13800138000',
            'channel'       => 0,
            'attrs'         => [
                ['key' => '身高', 'value' => '170cm']],
        ];
        $data = $this->prepareApplicantData($input, [
            'enrollLimit'   => 10,
            'enrollFeeType' => 1,
            'auditType'     => 1,
        ]);
        $activityService->getPublishedActivityById($data->activity->getId())
                        ->shouldBeCalledTimes(1)
                        ->willReturn($data->activity);
        $userService->findUserById($data->userId)
                    ->shouldBeCalledTimes(1)
                    ->willReturn($data->user);
        $activityMemberRepository->count($data->activity->getId())
                                 ->shouldBeCalledTimes(1)
                                 ->willReturn(100);

        $this->shouldThrow(new \Exception('该活动已经满员'))
             ->duringDoApplicant(
                $data->userId, $data->activity->getId(),
                $data->rawAttrs, $data->channel
               );
    }

    /**
     * app & web 报名失败，加上未支付订单，活动已经满员
     */
    public function it_reject_do_applicant_excceed_people_limitaion_check_waiting_pay(
        ActivityService $activityService,
        ActivityApplicantRepository $activityApplicantRepository,
        UserRepository $userRepository,
        ActivityMemberRepository $activityMemberRepository,
        UserService $userService
    ) {
        $input = [
            'userId'        => 1,
            'activityId'    => 1,
            'name'          => '张三',
            'mobile'        => '13800138000',
            'channel'       => 0,
            'attrs'         => [
                ['key' => '身高', 'value' => '170cm']],
        ];
        $data = $this->prepareApplicantData($input, [
            'enrollLimit'   => 10,
            'enrollFeeType' => 3,
            'auditType'     => 1,
        ]);
        $activityService->getPublishedActivityById($data->activity->getId())
                        ->shouldBeCalledTimes(1)
                        ->willReturn($data->activity);
        $userService->findUserById($data->userId)
                    ->shouldBeCalledTimes(1)
                    ->willReturn($data->user);
        $activityMemberRepository->count($data->activity->getId())
                                 ->shouldBeCalledTimes(1)
                                 ->willReturn(1);
        $activityApplicantRepository->getNotPayCount($data->activity->getId())
                                    ->shouldBeCalledTimes(1)
                                    ->willReturn(100);

        $this->shouldThrow(new \Exception('该活动已经满员'))
             ->duringDoApplicant(
                $data->userId, $data->activity->getId(),
                $data->rawAttrs, $data->channel
               );
    }

    /**
     * app & web 报名失败，仅限团员参加
     */
    public function it_reject_do_applicant_only_team_member(
        ActivityService $activityService,
        ActivityApplicantRepository $activityApplicantRepository,
        UserRepository $userRepository,
        ActivityMemberRepository $activityMemberRepository,
        UserService $userService,
        TeamMemberService $teamMemberService
    ) {
        $input = [
            'userId'        => 1,
            'activityId'    => 1,
            'name'          => '张三',
            'mobile'        => '13800138000',
            'channel'       => 0,
            'attrs'         => [
                ['key' => '身高', 'value' => '170cm']],
        ];
        $data = $this->prepareApplicantData($input, [
            'enrollLimit'   => 10,
            'enrollFeeType' => 1,
            'auditType'     => 1,
            'enrollType'    => 2,
        ]);
        $activityService->getPublishedActivityById($data->activity->getId())
                        ->shouldBeCalledTimes(1)
                        ->willReturn($data->activity);
        $userService->findUserById($data->userId)
                    ->shouldBeCalledTimes(1)
                    ->willReturn($data->user);
        $activityMemberRepository->count($data->activity->getId())
                                 ->shouldBeCalledTimes(1)
                                 ->willReturn(1);
        $teamMemberService->enrolled(
            $data->userId, $data->activity->getTeam()->getId())
                          ->shouldBeCalledTimes(1)
                          ->willReturn(false);

        $this->shouldThrow(new \Exception('该活动仅限该社团成员参加', 10401))
             ->duringDoApplicant(
                $data->userId, $data->activity->getId(),
                $data->rawAttrs, $data->channel
               );
    }

    /**
     * app & web 报名失败，当前用户有pending或已报名成功
     */
    public function it_reject_do_applicant_has_pending(
        ActivityService $activityService,
        ActivityApplicantRepository $activityApplicantRepository,
        UserRepository $userRepository,
        ActivityMemberRepository $activityMemberRepository,
        UserService $userService
    ) {
        $input = [
            'userId'        => 1,
            'activityId'    => 1,
            'name'          => '张三',
            'mobile'        => '13800138000',
            'channel'       => 0,
            'attrs'         => [
                ['key' => '身高', 'value' => '170cm']],
        ];
        $data = $this->prepareApplicantData($input, [
            'enrollLimit'   => 10,
            'enrollFeeType' => 1,
            'auditType'     => 1,
            'enrollType'    => 1,
        ]);
        $activityService->getPublishedActivityById($data->activity->getId())
                        ->shouldBeCalledTimes(1)
                        ->willReturn($data->activity);
        $userService->findUserById($data->userId)
                    ->shouldBeCalledTimes(1)
                    ->willReturn($data->user);
        $activityMemberRepository->count($data->activity->getId())
                                 ->shouldBeCalledTimes(1)
                                 ->willReturn(1);
        $activityApplicantRepository->getValidUserApplicant(
            $data->userId, $data->activity->getId())
                                    ->shouldBeCalledTimes(1)
                                    ->willReturn($data->oldApplicant);

        $this->shouldThrow(new \Exception('您的报名申请在审核中，请耐心等待'))
             ->duringDoApplicant(
                $data->userId, $data->activity->getId(),
                $data->rawAttrs, $data->channel
               );
    }

    /**
     * web报名成功，活动类型：不审核(有人数限制)，需要支付
     */
    public function it_do_applicant_success_from_web_with_noaudit_pay(
        ActivityService $activityService,
        ActivityApplicantRepository $activityApplicantRepository,
        UserRepository $userRepository,
        ActivityMemberRepository $activityMemberRepository,
        UserService $userService
    ) {
        // mock event
        /*
        Event::shouldReceive('fire')
            ->once()
            ->with(Mockery::type(\Jihe\Events\UserApplicantActivityEvent::class), [], false)
            ->andReturn([null]);
            */
        Bus::shouldReceive('dispatch')->with(\Mockery::on(function ($command) {
            // simple check, it should be SendSms that is going to be dispatched
            return ($command instanceof \Jihe\Jobs\SendMessageToUserJob);
        }))->andReturn(null);

        $input = [
            'userId'        => 1,
            'activityId'    => 1,
            'name'          => '张三',
            'mobile'        => '13800138000',
            'channel'       => 1,
            'attrs'         => [
                ['key' => '身高', 'value' => '170cm']],
        ];
        $data = $this->prepareApplicantData($input, [
            'enrollLimit'   => 10,
            'enrollFeeType' => 3,
            'auditType'     => 0,
        ]);
        $activityService->getPublishedActivityById($data->activity->getId())
                        ->shouldBeCalledTimes(1)
                        ->willReturn($data->activity);
        $userService->fetchUserOrRegisterIfUserNotExists('13800138000')
                    ->shouldBeCalledTimes(1)
                    ->willReturn([1, '123456']);
        $activityMemberRepository->count($data->activity->getId())
                                 ->shouldBeCalledTimes(1)
                                 ->willReturn(0);
        $activityApplicantRepository->getNotPayCount($data->activity->getId())
                                    ->shouldBeCalledTimes(1)
                                    ->willReturn(1);
        $activityApplicantRepository->getValidUserApplicant(
            $data->userId, $data->activity->getId())
                                    ->shouldBeCalledTimes(1)
                                    ->willReturn(null);
        $data->applicant->setStatus(2);
        $activityApplicantRepository->saveApplicantInfo(
            Argument::that(function ($arg) use ($data, $input) {
                if (
                    ($arg['name'] != $input['name']) ||
                    ($arg['mobile'] != $input['mobile']) ||
                    ($arg['attrs'] != json_encode($input['attrs'])) ||
                    ($arg['activity_id'] != $input['activityId']) ||
                    ($arg['user_id'] != $input['userId']) ||
                    ($arg['channel'] != $input['channel']) ||
                    ($arg['status'] != 2)
                ) {
                    return false;
                }
                if ( ! isset($arg['expire_at']))
                {
                    return false;
                }
                return true;
            }))->shouldBeCalledTimes(1)
              ->willReturn($data->applicant);
        $activityMemberRepository->add(Argument::any())
                                 ->shouldNotBeCalled();

        $resApplicant = $this->doApplicant(
            null, $data->activity->getId(),
            $data->rawAttrs, $data->channel
        )->getWrappedObject();

        Assert::assertInstanceOf(\Jihe\Entities\ActivityApplicant::class, $resApplicant);
        Assert::assertInstanceOf(\Jihe\Entities\Activity::class, $resApplicant->getActivity());
        Assert::assertEquals(2, $resApplicant->getStatus());

        $this->laravel->refreshApplication();
    }

    /**
     * web报名成功，活动类型：不审核(有人数限制)，无须支付
     */
    public function it_do_applicant_success_from_web_with_noaudit_nopay(
        ActivityService $activityService,
        ActivityApplicantRepository $activityApplicantRepository,
        UserRepository $userRepository,
        ActivityMemberRepository $activityMemberRepository,
        UserService $userService
    ) {
        Bus::shouldReceive('dispatch')->with(\Mockery::on(function ($command) {
            // simple check, it should be SendSms that is going to be dispatched
            return ($command instanceof \Jihe\Jobs\SendMessageToUserJob);
        }))->andReturn(null);

        $input = [
            'userId'        => 1,
            'activityId'    => 1,
            'name'          => '张三',
            'mobile'        => '13800138000',
            'channel'       => 1,
            'attrs'         => [
                ['key' => '身高', 'value' => '170cm']],
        ];
        $data = $this->prepareApplicantData($input, [
            'enrollLimit'   => 10,
            'enrollFeeType' => 1,
            'auditType'     => 0,
        ]);
        $activityService->getPublishedActivityById($data->activity->getId())
                        ->shouldBeCalledTimes(1)
                        ->willReturn($data->activity);
        $userService->fetchUserOrRegisterIfUserNotExists('13800138000')
                    ->shouldBeCalledTimes(1)
                    ->willReturn([1, '123456']);
        $activityMemberRepository->count($data->activity->getId())
                                 ->shouldBeCalledTimes(1)
                                 ->willReturn(0);
        $activityApplicantRepository->getValidUserApplicant(
            $data->userId, $data->activity->getId())
                                    ->shouldBeCalledTimes(1)
                                    ->willReturn(null);
        $data->applicant->setStatus(3);
        $activityApplicantRepository->saveApplicantInfo([
            'name'          => $input['name'],
            'mobile'        => $input['mobile'],
            'attrs'         => json_encode($input['attrs']),
            'activity_id'   => $input['activityId'],
            'user_id'       => $input['userId'],
            'channel'       => $input['channel'],
            'status'        => 3
            ])->shouldBeCalledTimes(1)
              ->willReturn($data->applicant);
        $activityMemberRepository->add(Argument::that(function ($arg) {
                return ($arg['group'] == 0) && ($arg['role'] == 0) ? true : false;
            }))->shouldBeCalledTimes(1);

        $resApplicant = $this->doApplicant(
            null, $data->activity->getId(),
            $data->rawAttrs, $data->channel
        )->getWrappedObject();

        Assert::assertInstanceOf(\Jihe\Entities\ActivityApplicant::class, $resApplicant);
        Assert::assertInstanceOf(\Jihe\Entities\Activity::class, $resApplicant->getActivity());
        Assert::assertEquals(3, $resApplicant->getStatus());

        $this->laravel->refreshApplication();
    }

    /**
     * Single vip录入
     */
    public function it_do_applicant_success_from_single_vip(
        ActivityService $activityService,
        ActivityApplicantRepository $activityApplicantRepository,
        UserRepository $userRepository,
        ActivityMemberRepository $activityMemberRepository,
        UserService $userService
    ) {
        $input = [
            'userId'        => 1,
            'activityId'    => 1,
            'name'          => '张三',
            'mobile'        => '13800138000',
            'channel'       => 2,
            'attrs'         => [],              // attrs check are disabled for vip
        ];
        $data = $this->prepareApplicantData($input, [
            'enrollLimit'   => 10,
            'enrollFeeType' => 1,
            'auditType'     => 0,
        ]);
        $activityService->getPublishedActivityById($data->activity->getId())
                        ->shouldBeCalledTimes(1)
                        ->willReturn($data->activity);
        $userService->fetchUserOrRegisterIfUserNotExists('13800138000')
                    ->shouldBeCalledTimes(1)
                    ->willReturn([1, '123456']);
        $activityMemberRepository->count(Argument::any())
                                 ->shouldNotBeCalled();
        $activityApplicantRepository->getValidUserApplicant(1, 1)
                                    ->shouldBeCalledTimes(1)
                                    ->willReturn(null);
        $data->applicant->setStatus(3);
        $activityApplicantRepository->saveApplicantInfo([
            'name'          => $input['name'],
            'mobile'        => $input['mobile'],
            'attrs'         => json_encode($input['attrs']),
            'activity_id'   => $input['activityId'],
            'user_id'       => $input['userId'],
            'channel'       => $input['channel'],
            'status'        => 3
            ])->shouldBeCalledTimes(1)
              ->willReturn($data->applicant);
        $activityMemberRepository->add(Argument::that(function ($arg) {
                return ($arg['group'] == 0) && ($arg['role'] == 0) ? true : false;
            }))->shouldBeCalledTimes(1);

        $resApplicant = $this->doApplicant(
            null, $data->activity->getId(),
            $data->rawAttrs, $data->channel
        )->getWrappedObject();

        Assert::assertInstanceOf(\Jihe\Entities\ActivityApplicant::class, $resApplicant);
        Assert::assertInstanceOf(\Jihe\Entities\Activity::class, $resApplicant->getActivity());
        Assert::assertEquals(3, $resApplicant->getStatus());

        $this->laravel->refreshApplication();
    }

    private function prepareApplicantData($input,  $activityConfig = [])
    {
        $activityConfig = array_merge([
            'enrollFeeType' => 1,
            'enrollLimit'   => 0,
            'enrollType'    => 1,       // 1 -- all;
                                        // 2 -- only team member
                                        // 3 -- private
            'enrollAttrs'   => ['手机号', '姓名', '身高'],
            'auditType'     => 1,       // 0 -- no audit; 1 -- audit
        ], $activityConfig);
        $input = array_merge([
            'userId'        => 1,
            'activityId'    => 1,
            'name'          => '张三',
            'mobile'        => '13800138000',
            'channel'       => 1,       // 0 -- app; 1 -- web
                                        // 2 -- signle vip
                                        // 3 -- batch vip
            'attrs'         => [[
                                'key'   => '身高',
                                'value' => '170cm',
                                ]],
            'attrsMissNameMobile' => false,
        ], $input);
        $activity = (new \Jihe\Entities\Activity)
            ->setId($input['activityId'])
            ->setTeam((new \Jihe\Entities\Team)->setId(1))
            ->setTitle('校园电商平面模特大赛')
            ->setAuditing($activityConfig['auditType'])
            ->setEnrollBeginTime(date('Y-m-d H:i:s', time() - 3600))
            ->setEnrollEndTime(date('Y-m-d H:i:s', time() + 3600))
            ->setEnrollAttrs($activityConfig['enrollAttrs'])
            ->setEnrollFeeType($activityConfig['enrollFeeType'])
            ->setEnrollLimit($activityConfig['enrollLimit'])
            ->setEnrollType($activityConfig['enrollType']);
        $applicant = (new \Jihe\Entities\ActivityApplicant)
            ->setStatus(1)
            ->setName($input['name'])
            ->setMobile($input['mobile'])
            ->setAttrs($input['attrs'])
            ->setChannel($input['channel']);

        $oldApplicant = (new \Jihe\Entities\ActivityApplicant)
            ->setStatus(1);
        $user = (new \Jihe\Entities\User)
            ->setId($input['userId'])
            ->setMobile('13900139000');

        $data = new \stdClass;
        $data->userId = $input['userId'];
        $data->channel = $input['channel'];
        $data->activity = $activity;
        $data->applicant = $applicant;
        $data->oldApplicant = $oldApplicant;
        $data->user = $user;
        $data->rawAttrs = json_encode(array_merge($input['attrs'],
            ($input['attrsMissNameMobile'] ? [] : [
            ['key' => '手机号', 'value' => $input['mobile']],
            ['key' => '姓名', 'value' => $input['name']],
        ])));

        return $data;
    }

    //=============================================
    //           getUserLatestApplicantInfo
    //=============================================
    function it_get_user_latest_applicant_info_successful(
        UserRepository $userRepository,
        ActivityApplicantRepository $activityApplicantRepository
    ) {
        $mobile = '13800138000';
        $activityId = 1;
        $userRepository->findId($mobile)
                       ->shouldBeCalledTimes(1)
                       ->willReturn(1);
        $activityApplicantRepository->getApplicantInfo(1, $activityId)
                                    ->shouldBeCalledTimes(1)
                                    ->willReturn([
                                        'status' => 3,
                                    ]);

        $this->getUserLatestApplicantInfo($activityId, $mobile)
             ->shouldReturn([
                'status'    => 3,
             ]);
    }

    function it_get_user_latest_applicant_info_failed_user_not_exists(
        UserRepository $userRepository,
        ActivityApplicantRepository $activityApplicantRepository
    ) {
        $mobile = '13800138000';
        $activityId = 1;
        $userRepository->findId($mobile)
                       ->shouldBeCalledTimes(1)
                       ->willReturn(null);
        $activityApplicantRepository->getApplicantInfo(1, $activityId)
                                    ->shouldNotBeCalled();

        $this->getUserLatestApplicantInfo($activityId, $mobile)
             ->shouldBeNull();
    }

    //=============================================
    //      approveApplicants
    //=============================================
    function it_approve_applicants_successful_status_to_pay(
        ActivityService $activityService,
        ActivityApplicantRepository $activityApplicantRepository,
        ActivityMemberRepository $activityMemberRepository
    ) {
        Bus::shouldReceive('dispatch')->with(\Mockery::on(function ($command) {
            // simple check, it should be SendSms that is going to be dispatched
            return ($command instanceof \Jihe\Jobs\SendMessageToUserJob);
        }))->andReturn(null);
        list($user, $activity, $applicants) = $this->prepareAuditData();

        $activityService->checkActivityOwner($activity->getId(), $user->getId())
                        ->shouldBeCalledTimes(1)
                        ->willReturn(true);
        $activityService->getPublishedActivityById($activity->getId())
                        ->shouldBeCalledTimes(1)
                        ->willReturn($activity);
        $activityApplicantRepository->getAuditApplicants($activity->getId(), [1, 2])
                                    ->shouldBeCalledTimes(1)
                                    ->willReturn($applicants);
        $activityMemberRepository->count($activity->getId())
                                 ->shouldBeCalledTimes(1)
                                 ->willReturn(50);
        $activityApplicantRepository->approveToPay(
            [1, 2], new \DateTime($activity->getBeginTime()))
                                    ->shouldBeCalledTimes(1)
                                    ->willReturn(2);
        $activityApplicantRepository->approveToSuccess([1, 2])
                                    ->shouldNotBeCalled();
        $activityMemberRepository->batchAdd(Argument::any())
                                 ->shouldNotBeCalled();

        $this->approveApplicants($user->getId(), $activity->getId(), [1, 2])
             ->shouldBeNull();

        $this->laravel->refreshApplication();
    }

    function it_approve_applicants_successful_not_found(
        ActivityService $activityService,
        ActivityApplicantRepository $activityApplicantRepository,
        ActivityMemberRepository $activityMemberRepository
    ) {
        list($user, $activity, $applicants) = $this->prepareAuditData();

        $activityService->checkActivityOwner($activity->getId(), $user->getId())
                        ->shouldBeCalledTimes(1)
                        ->willReturn(true);
        $activityService->getPublishedActivityById($activity->getId())
                        ->shouldBeCalledTimes(1)
                        ->willReturn($activity);

        $activityApplicantRepository->getAuditApplicants($activity->getId(), [1, 2])
                                    ->shouldBeCalledTimes(1)
                                    ->willReturn([]);
        $activityMemberRepository->count($activity->getId())
                                 ->shouldBeCalledTimes(1)
                                 ->willReturn(50);
        $activityApplicantRepository->approveToPay(Argument::cetera())
                                    ->shouldNotBeCalled();
        $activityMemberRepository->batchAdd(Argument::any())
                                 ->shouldNotBeCalled();
        $activityMemberRepository->batchAdd(Argument::any())
                                 ->shouldNotBeCalled();
        $this->approveApplicants($user->getId(), $activity->getId(), [1, 2])
             ->shouldBeNull();
    }

    function it_approve_applicants_successful_status_to_success(
        ActivityService $activityService,
        ActivityApplicantRepository $activityApplicantRepository,
        ActivityMemberRepository $activityMemberRepository
    ) {
        Bus::shouldReceive('dispatch')->with(\Mockery::on(function ($command) {
            // simple check, it should be SendSms that is going to be dispatched
            return ($command instanceof \Jihe\Jobs\SendMessageToUserJob);
        }))->andReturn(null);
        list($user, $activity, $applicants) = $this->prepareAuditData();
        $activity->setEnrollFeeType(ActivityEntity::ENROLL_FEE_TYPE_FREE);

        $activityService->checkActivityOwner($activity->getId(), $user->getId())
                        ->shouldBeCalledTimes(1)
                        ->willReturn(true);
        $activityService->getPublishedActivityById($activity->getId())
                        ->shouldBeCalledTimes(1)
                        ->willReturn($activity);
        $activityApplicantRepository->getAuditApplicants($activity->getId(), [1, 2])
                                    ->shouldBeCalledTimes(1)
                                    ->willReturn($applicants);
        $activityMemberRepository->count($activity->getId())
                                 ->shouldBeCalledTimes(1)
                                 ->willReturn(50);
        $activityApplicantRepository->approveToPay(Argument::cetera())
                                    ->shouldNotBeCalled();
        $activityApplicantRepository->approveToSuccess([1, 2])
                                    ->shouldBeCalledTimes(1)
                                    ->willReturn(2);
        $members = array_map(function($item) {
            return [
                'activity_id'   => $item->getActivity()->getId(),
                'user_id'       => $item->getUser()->getId(),
                'name'          => $item->getName(),
                'mobile'        => $item->getMobile(),
                'attrs'         => json_encode($item->getAttrs()),
                'group_id'      => ActivityMember::UNGROUPED,
                'role'          => ActivityMember::ROLE_NORMAL,
            ];
        }, $applicants);
        $activityMemberRepository->batchAdd($members)
                                 ->shouldBeCalledTimes(1)
                                 ->willReturn(2);

        $this->approveApplicants($user->getId(), $activity->getId(), [1, 2])
             ->shouldBeNull();

        $this->laravel->refreshApplication();
    }

    //=============================================
    //      refuseApplicants
    //=============================================
    public function it_refuse_applicants_successfully(
        ActivityService $activityService,
        ActivityApplicantRepository $activityApplicantRepository,
        ActivityMemberRepository $activityMemberRepository
    ) {
        Bus::shouldReceive('dispatch')->with(\Mockery::on(function ($command) {
            // simple check, it should be SendSms that is going to be dispatched
            return ($command instanceof \Jihe\Jobs\SendMessageToUserJob);
        }))->andReturn(null);
        list($user, $activity, $applicants) = $this->prepareAuditData();

        $activityService->checkActivityOwner($activity->getId(), $user->getId())
                        ->shouldBeCalledTimes(1)
                        ->willReturn(true);
        $activityService->getPublishedActivityById($activity->getId())
                        ->shouldBeCalledTimes(1)
                        ->willReturn($activity);
        $activityApplicantRepository->getAuditApplicants($activity->getId(), [1, 2])
                                    ->shouldBeCalledTimes(1)
                                    ->willReturn($applicants);
        $activityMemberRepository->count(Argument::any())
                                 ->shouldNotBeCalled();
        $activityApplicantRepository->refuse([1, 2])
                                    ->shouldBeCalledTimes(1)
                                    ->willReturn(2);

        $this->refuseApplicants($user->getId(), $activity->getId(), [1, 2])
             ->shouldBeNull();

        $this->laravel->refreshApplication();

    }

    function it_refuse_applicants_successful_not_found(
        ActivityService $activityService,
        ActivityApplicantRepository $activityApplicantRepository,
        ActivityMemberRepository $activityMemberRepository
    ) {
        list($user, $activity, $applicants) = $this->prepareAuditData();

        $activityService->checkActivityOwner($activity->getId(), $user->getId())
                        ->shouldBeCalledTimes(1)
                        ->willReturn(true);
        $activityService->getPublishedActivityById($activity->getId())
                        ->shouldBeCalledTimes(1)
                        ->willReturn($activity);

        $activityApplicantRepository->getAuditApplicants($activity->getId(), [1, 2])
                                    ->shouldBeCalledTimes(1)
                                    ->willReturn([]);
        $activityApplicantRepository->refuse(Argument::any())
                                    ->shouldNotBeCalled();
        $this->refuseApplicants($user->getId(), $activity->getId(), [1, 2])
             ->shouldBeNull();
    }

    private function prepareAuditData()
    {
        $user = (new UserEntity)->setId(1)->setMobile('13800138001');
        $activity = (new ActivityEntity)
            ->setId(1)
            ->setTitle('成都夜跑')
            ->setBeginTime(date('Y-m-d H:i:s', time() + 3600))
            ->setEnrollLimit(1000)
            ->setEnrollFeeType(ActivityEntity::ENROLL_FEE_TYPE_PAY)
            ->setAuditing(ActivityEntity::AUDITING);
        $applicants = [
            (new ActivityApplicantEntity)
                ->setId(1)
                ->setUser($user)
                ->setActivity($activity)
                ->setName('zhangsan')
                ->setMobile('13800138001')
                ->setChannel(ActivityApplicantEntity::CHANNEL_APP)
                ->setStatus(ActivityApplicantEntity::STATUS_AUDITING),
            (new ActivityApplicantEntity)
                ->setId(2)
                ->setUser($user)
                ->setActivity($activity)
                ->setName('lisi')
                ->setMobile('13800138002')
                ->setChannel(ActivityApplicantEntity::CHANNEL_WEB)
                ->setStatus(ActivityApplicantEntity::STATUS_AUDITING),
        ];

        return [$user, $activity, $applicants];
    }
}
