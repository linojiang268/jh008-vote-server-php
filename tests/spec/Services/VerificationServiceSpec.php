<?php
namespace spec\Jihe\Services;

use PhpSpec\Laravel\LaravelObjectBehavior;
use Prophecy\Argument;
use \PHPUnit_Framework_Assert as Assert;
use Bus;
use Jihe\Contracts\Repositories\VerificationRepository;
use Jihe\Contracts\Repositories\UserRepository;
use Jihe\Contracts\Repositories\ActivityMemberRepository;
use Jihe\Entities\Verification as VerificationEntity;

class VerificationServiceSpec extends LaravelObjectBehavior
{
    function let(VerificationRepository $repository,
                 UserRepository $userRepository,
                 ActivityMemberRepository $activityMemberRepository
    ) {
        // by default there is no configuration
        $this->beAnInstanceOf(\Jihe\Services\VerificationService::class, 
                      [$repository, $userRepository, $activityMemberRepository, []]);
    }

    function letgo()
    {
        \Mockery::close();
    }

    function it_sends_code_for_registeration(
        VerificationRepository $repository,
        UserRepository $userRepository
    ) {
        $mobile = '13800000000';
        $userRepository->findUser($mobile)->shouldBeCalledTimes(1)->willReturn(null);
        $repository->count($mobile, Argument::cetera())->willReturn(0);
        $repository->add(Argument::allOf(Argument::withEntry('mobile', $mobile),
                                         Argument::withKey('code'),
                                         Argument::withKey('expired_at')))
                   ->shouldBeCalled();
        $repository->findLastRequested($mobile)->willReturn(null);
        Bus::shouldReceive('dispatch')->with(\Mockery::on(function ($command) {
            // simple check, it should be SendSms that is going to be dispatched
            return ($command instanceof \Jihe\Jobs\SendMessageToUserJob);
        }))->andReturn(null);

        $this->sendForRegistration($mobile);
    }

    function it_sends_code_for_registeration_with_last_request_not_expired(
        VerificationRepository $repository,
        UserRepository $userRepository
    ) {
        $mobile = '13800000000';
        $userRepository->findUser($mobile)->shouldBeCalledTimes(1)->willReturn(null);
        $lastRequested = new VerificationEntity(1, $mobile, '1234', '2015-08-10 10:00:00');
        $repository->count($mobile, Argument::cetera())->willReturn(0);
        $repository->findLastRequested($mobile)->willReturn($lastRequested);
        $repository->add(Argument::allOf(Argument::withEntry('mobile', $mobile),
                                         Argument::withKey('code', '1234'),
                                         Argument::withKey('expired_at')))
                   ->shouldBeCalled();
        Bus::shouldReceive('dispatch')->with(\Mockery::on(function ($command) {
            // simple check, it should be SendSms that is going to be dispatched
            return ($command instanceof \Jihe\Jobs\SendMessageToUserJob);
        }))->andReturn(null);

        $this->sendForRegistration($mobile);
    }

    function it_sends_code_for_registeration_user_exists_but_not_completed_info(
        VerificationRepository $repository,
        UserRepository $userRepository
    ) {
        $mobile = '13800000000';
        $user = (new \Jihe\Entities\User())->setId(1)->setStatus(\Jihe\Entities\User::STATUS_INCOMPLETE);
        $userRepository->findUser($mobile)->shouldBeCalledTimes(1)->willReturn($user);
        $repository->count($mobile, Argument::cetera())->willReturn(0);
        $repository->add(Argument::allOf(Argument::withEntry('mobile', $mobile),
            Argument::withKey('code'),
            Argument::withKey('expired_at')))
            ->shouldBeCalled();
        $repository->findLastRequested($mobile)->willReturn(null);
        Bus::shouldReceive('dispatch')->with(\Mockery::on(function ($command) {
            // simple check, it should be SendSms that is going to be dispatched
            return ($command instanceof \Jihe\Jobs\SendMessageToUserJob);
        }))->andReturn(null);

        $this->sendForRegistration($mobile);
    }

    function it_throws_exception_if_requested_too_frequently(
        VerificationRepository $repository,
        UserRepository $userRepository,
        ActivityMemberRepository $activityMemberRepository
    ) {
        $mobile = '13800000000';
        $this->beAnInstanceOf(\Jihe\Services\VerificationService::class,
                              [$repository, $userRepository, $activityMemberRepository, [
                                'send_interval' => 120 ]]);
        $userRepository->findUser($mobile)->shouldBeCalledTimes(1)->willReturn(null);
        $repository->count($mobile, Argument::cetera())->willReturn(1);
        
        try {
            $this->sendForRegistration($mobile);
            throw new \Exception('cannot be reached');
        } catch (\Exception $ex) {
            Assert::assertContains('发送频率太高', $ex->getMessage());
        }
    }

    function it_throws_exception_if_limit_reached(
        VerificationRepository $repository,
        UserRepository $userRepository,
        ActivityMemberRepository $activityMemberRepository
    ) {
        $mobile = '13800000000';
        $this->beAnInstanceOf(\Jihe\Services\VerificationService::class,
                              [$repository, $userRepository, $activityMemberRepository, [
                                            'send_interval' => 120, 
                                            'limit_period' => 86400, 
                                            'limit_count' => 10,
                              ]]);
        $userRepository->findUser($mobile)->shouldBeCalledTimes(1)->willReturn(null);
        $repository->count($mobile, Argument::that(function ($time) {
                             // gives it a range of [-5, 5] as a buffer
                             return $this->isTimeAround($time, 120 - 5, 120 + 5);
                        }))
                   ->willReturn(0);  // last 120 seconds, no messages sent
        $repository->count($mobile, Argument::that(function ($time) {
                             // gives it a range of [-5, 5] as a buffer
                             return $this->isTimeAround($time, 86400 - 5, 86400 + 5);
                        }))
                   ->willReturn(10);   // last 86400 seconds, 10 messages sent
                                       // no more messages should be sent
        // Bus should not receive any call
        Bus::shouldReceive('dispatch')->withAnyArgs()->never();
        
        try {
            $this->sendForRegistration($mobile);
            throw new \Exception('cannot be reached');
        } catch (\Exception $ex) {
            Assert::assertContains('发送次数过多', $ex->getMessage());
        }
    }

    function it_throws_exceptions_if_user_exists_and_completed_info(
        UserRepository $userRepository
    ) {
        $mobile = '13800000000';
        $user = (new \Jihe\Entities\User)->setId(1)->setStatus(\Jihe\Entities\User::STATUS_NORMAL);
        $userRepository->findUser($mobile)->shouldBeCalledTimes(1)->willReturn($user);

        $this->shouldThrow(\Jihe\Exceptions\User\UserExistsException::class)
             ->duringSendForRegistration($mobile);
    }

    //===============================
    //          verify
    //===============================

    function it_passes_verification_if_verificaion_code_workable(VerificationRepository $repository)
    {
        $mobile = '13800000000';
        $code   = '123456';
        
        // create an expected Verification entity, which should not expire
        $expected = new VerificationEntity(1, $mobile, $code, date('Y-m-d H:i:s', time() + 120));
        $repository->findLastRequested($mobile)->willReturn($expected);
        $repository->remove(1)->shouldBeCalled();
        
        $this->shouldNotThrow()->duringVerify($mobile, $code);
    }

    function it_throws_exception_if_no_verification_code_sent_before(VerificationRepository $repository)
    {
        $mobile = '13800000000';
        $repository->findLastRequested($mobile, Argument::cetera())->willReturn(null);
        
        $this->shouldThrow(\Jihe\Exceptions\VerificationException::class)
             ->duringVerify($mobile, Argument::any());
    }

    function it_throw_exception_if_verification_code_not_match(VerificationRepository $repository)
    {
        $mobile = '13800000000';
        
        // create an expected Verification entity, which should not expire
        $expected = new VerificationEntity(1, $mobile, '1111', date('Y-m-d H:i:s', time() + 120));
        $repository->findLastRequested($mobile)->willReturn($expected);
        $repository->remove(1)->shouldBeCalled(); // also removal should be called
        
        $this->shouldThrow(\Jihe\Exceptions\VerificationException::class)
             ->duringVerify($mobile, '123456');
    }

    function it_throw_exception_if_send_register_verify_code_expire(
        VerificationRepository $repository,
        UserRepository $userRepository,
        ActivityMemberRepository $activityMemberRepository
    ) {
        $mobile = '13800000000';
        $this->beAnInstanceOf(\Jihe\Services\VerificationService::class,
                              [$repository, $userRepository, $activityMemberRepository, [
                                            'send_interval' => 120,
                                            'limit_period' => 86400,
                                            'limit_count' => 10,
                              ]]);
        
        $expected = new VerificationEntity(1, $mobile, '1111', date('Y-m-d H:i:s', time() - 120));
        $repository->findLastRequested($mobile)->willReturn($expected);
        $repository->remove(1)->shouldBeCalled(); // also removal should be called

        $this->shouldThrow(\Jihe\Exceptions\VerificationException::class)
             ->duringVerify($mobile, '1111');
    }

    function it_removes_expired_verifications_for_registration(
        VerificationRepository $repository,
        UserRepository $userRepository,
        ActivityMemberRepository $activityMemberRepository
    ) {
        $this->beAnInstanceOf(\Jihe\Services\VerificationService::class,
                             [$repository, $userRepository, $activityMemberRepository, [
                                            'send_interval' => 120,
                                            'limit_period' => 86400,
                                            'limit_count' => 10,
                             ]]);
        // '2015-06-30 10:49:20' - 2 * 86400 = '2015-06-28 10:49:20'
        $repository->removeExpiredBefore('2015-06-28 10:49:20')->shouldBeCalled();

        $this->removeExpiredVerificationsForRegistration(strtotime('2015-06-30 10:49:20'));
    }

    //=====================================
    //          sendForResetPassword
    //=====================================
    function it_sends_code_for_reset_password(
        VerificationRepository $repository,
        UserRepository $userRepository
    ) {
        $mobile = '13800000000';
        $userRepository->findId($mobile)->shouldBeCalledTimes(1)->willReturn(1);
        $repository->count($mobile, Argument::cetera())->willReturn(0);
        $repository->add(Argument::allOf(Argument::withEntry('mobile', $mobile),
                                         Argument::withKey('code'),
                                         Argument::withKey('expired_at')))
                   ->shouldBeCalled();
        $repository->findLastRequested($mobile)->willReturn(null);
        Bus::shouldReceive('dispatch')->with(\Mockery::on(function ($command) {
            // simple check, it should be SendSms that is going to be dispatched
            return ($command instanceof \Jihe\Jobs\SendMessageToUserJob);
        }))->andReturn(null);

        $this->sendForResetPassword($mobile);
    }

    function it_throws_exception_if_user_not_exists_when_send_for_reset_password(
        VerificationRepository $repository,
        UserRepository $userRepository
    ) {
        $mobile = '13800000000';
        $userRepository->findId($mobile)->shouldBeCalledTimes(1)->willReturn(null);

        $this->shouldThrow(\Jihe\Exceptions\User\UserNotExistsException::class)
             ->duringSendForResetPassword($mobile);
    }

    //=====================================
    //          sendForActivityCheckIn
    //=====================================
    function it_sends_code_for_activity_check_in(
        VerificationRepository $repository,
        UserRepository $userRepository,
        ActivityMemberRepository $activityMemberRepository
    ) {
        $mobile = '13800000000';
        $userRepository->findId($mobile)->shouldBeCalledTimes(1)->willReturn(1);
        $activityMemberRepository->exists(1, 1)
                                    ->shouldBeCalledTimes(1)
                                    ->willReturn(['id' => 1]);
        $repository->count($mobile, Argument::cetera())->willReturn(0);
        $repository->add(Argument::allOf(Argument::withEntry('mobile', $mobile),
                                         Argument::withKey('code'),
                                         Argument::withKey('expired_at')))
                   ->shouldBeCalled();
        $repository->findLastRequested($mobile)->willReturn(null);
        Bus::shouldReceive('dispatch')->with(\Mockery::on(function ($command) {
            // simple check, it should be SendSms that is going to be dispatched
            return ($command instanceof \Jihe\Jobs\SendMessageToUserJob);
        }))->andReturn(null);

        $this->sendForActivityCheckIn($mobile, 1);
    }

    function it_throws_exception_if_user_not_exists_when_send_for_activity_check_in(
        VerificationRepository $repository,
        UserRepository $userRepository
    ) {
        $mobile = '13800000000';
        $userRepository->findId($mobile)->shouldBeCalledTimes(1)->willReturn(null);

        $this->shouldThrow(\Jihe\Exceptions\User\UserNotExistsException::class)
             ->duringSendForActivityCheckIn($mobile, 1);
    }

    function it_throws_exception_if_applicant_not_success(
        VerificationRepository $repository,
        UserRepository $userRepository,
        ActivityMemberRepository $activityMemberRepository
    ) {
        $mobile = '13800000000';
        $userRepository->findId($mobile)->shouldBeCalledTimes(1)->willReturn(1);
        $activityMemberRepository->exists(1, 1)
                                    ->shouldBeCalledTimes(1)
                                    ->willReturn(null);

        $this->shouldThrow(new \Exception('您未报名活动，请先报名'))
             ->duringSendForActivityCheckIn($mobile, 1);
    }

    //=======================================
    //          sendForActivityWebApplicant
    //=======================================
    public function it_send_code_for_activity_web_applicant(
        VerificationRepository $repository
    ) {
        $mobile = '13800138000';
        $repository->count($mobile, Argument::cetera())->willReturn(0);
        $repository->add(Argument::allOf(Argument::withEntry('mobile', $mobile),
                                         Argument::withKey('code'),
                                         Argument::withKey('expired_at')))
                   ->shouldBeCalled();
        $repository->findLastRequested($mobile)->willReturn(null);
        Bus::shouldReceive('dispatch')->with(\Mockery::on(function ($command) {
            // simple check, it should be SendSms that is going to be dispatched
            return ($command instanceof \Jihe\Jobs\SendMessageToUserJob);
        }))->andReturn(null);

        $this->sendForActivityWebApplicant($mobile);
    }

    function isTimeAround($time, $lowerBound, $upperBound, $base = null)
    {
        $time = strtotime($time);  // convert to timestamp
        return $time <= strtotime(sprintf('%s seconds ago', $lowerBound), $base ?: time()) &&
               $time >= strtotime(sprintf('%s seconds ago', $upperBound), $base ?: time());
    }
}
