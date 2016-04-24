<?php
namespace spec\Jihe\Services;

use PhpSpec\Laravel\LaravelObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Cookie;
use Illuminate\Contracts\Cookie\QueueingFactory as CookieJar;
use Illuminate\Http\Request;
use Jihe\Contracts\Repositories\LoginDeviceRepository;
use Jihe\Entities\LoginDevice;
use Bus;

class DeviceAuthServiceSpec extends LaravelObjectBehavior
{
    function let(CookieJar $cookieJar,
                 Request $request,
                 LoginDeviceRepository $loginDeviceRepository
    ) {
        $this->beAnInstanceOf(\Jihe\Services\DeviceAuthService::class, [
            $cookieJar,
            $request,
            $loginDeviceRepository
        ]);
    }

    //=============================================
    //           attachDeviceAfterLogin
    //=============================================
    function it_attach_device_after_login_successfully_without_kick(
        CookieJar $cookieJar,
        Request $request,
        LoginDeviceRepository $loginDeviceRepository
    ) {
        $mobile = '13800138000';
        $identifier = 'sG4nhfF6kVS0NH36x0KwePd9kudoRfS0g4sLaqnFmKxUX0Mq';
        $cookieName = 'jihe_deviceno';
        $request->segment(1)
                ->shouldBeCalledTimes(1)
                ->willReturn('api');
        $request->cookie('jihe_deviceno')
                ->shouldBeCalledTimes(1)
                ->willReturn($identifier);
        $loginDeviceRepository
            ->addOrUpdateIdentifierIfExists($mobile, 1, $identifier)
            ->shouldBeCalledTimes(1)
            ->willReturn(null);

        $cookie = new Cookie($cookieName);
        $cookieJar->forever('jihe_deviceno', $identifier)
                  ->shouldBeCalledTimes(1)
                  ->willReturn($cookie);
        $cookieJar->queue($cookie)
                  ->shouldBeCalledTimes(1)
                  ->willReturn(null);

        $this->attachDeviceAfterLogin($mobile)->shouldBe($identifier);
    }

    function it_stop_attach_if_source_invalid(
        Request $request,
        LoginDeviceRepository $loginDeviceRepository
    ) {
        $mobile = '13800138000';
        $request->segment(1)->shouldBeCalledTimes(1)->willReturn('abc');
        $loginDeviceRepository
            ->addOrUpdateIdentifierIfExists(Argument::cetera())
            ->shouldNotBeCalled();
        $this->attachDeviceAfterLogin($mobile)->shouldBe(null);
    }

    //================================================
    //           checkDeviceAndKickUserOnOtherDevice
    //================================================
    function it_check_device_and_kick_user_on_other_device(
        Request $request,
        LoginDeviceRepository $loginDeviceRepository
    ) {
        $mobile = '13800138000';
        $identifier = 'sG4nhfF6kVS0NH36x0KwePd9kudoRfS0g4sLaqnFmKxUX0Mq';
        $oldIdentifier = 'ttttttF6kVS0NH36x0KwePd9kudoRfS0g4sLaqnFmKxUX0Mq';
        Bus::shouldReceive('dispatch')->with(\Mockery::on(function ($command) use ($oldIdentifier) {
            // simple check, it should be SendSms that is going to be dispatched
            return ($command instanceof \Jihe\Jobs\PushToAliasMessageJob) &&
                        $command->alias == $oldIdentifier;
        }))->andReturn(null);
        $loginDevice = (new \Jihe\Entities\LoginDevice)
                            ->setId(1)
                            ->setIdentifier($identifier)
                            ->setOldIdentifier($oldIdentifier);
        $request->segment(1)
                ->shouldBeCalledTimes(1)
                ->willReturn('api');
        $loginDeviceRepository->findOneByMobileAndSource($mobile, 1)
                              ->shouldBeCalledTimes(1)
                              ->willReturn($loginDevice);
        $this->checkDeviceAndKickUserOnOtherDevice($mobile)->shouldBeNull();
    }

    function it_check_device_and_kick_user_on_other_device_same_user(
        Request $request,
        LoginDeviceRepository $loginDeviceRepository
    ) {
        $mobile = '13800138000';
        $identifier = 'sG4nhfF6kVS0NH36x0KwePd9kudoRfS0g4sLaqnFmKxUX0Mq';
        $loginDevice = (new \Jihe\Entities\LoginDevice)
                            ->setId(1)
                            ->setIdentifier($identifier)
                            ->setOldIdentifier($identifier);
        $request->segment(1)
                ->shouldBeCalledTimes(1)
                ->willReturn('api');
        $loginDeviceRepository->findOneByMobileAndSource($mobile, 1)
                              ->shouldBeCalledTimes(1)
                              ->willReturn($loginDevice);
        $this->checkDeviceAndKickUserOnOtherDevice($mobile)->shouldBeNull();
    }

    function it_check_device_and_kick_user_on_other_device_first_bind(
        Request $request,
        LoginDeviceRepository $loginDeviceRepository
    ) {
        $mobile = '13800138000';
        $identifier = 'sG4nhfF6kVS0NH36x0KwePd9kudoRfS0g4sLaqnFmKxUX0Mq';
        $loginDevice = (new \Jihe\Entities\LoginDevice)
                            ->setId(1)
                            ->setIdentifier($identifier);
        $request->segment(1)
                ->shouldBeCalledTimes(1)
                ->willReturn('api');
        $loginDeviceRepository->findOneByMobileAndSource($mobile, 1)
                              ->shouldBeCalledTimes(1)
                              ->willReturn($loginDevice);
        $this->checkDeviceAndKickUserOnOtherDevice($mobile)->shouldBeNull();
    }

    //=============================================
    //           check
    //=============================================
    function it_check_passed(
        Request $request,
        LoginDeviceRepository $loginDeviceRepository
    ) {
        $mobile = '13800138000';
        $identifier = 'sG4nhfF6kVS0NH36x0KwePd9kudoRfS0g4sLaqnFmKxUX0Mq';
        $request->segment(1)
                ->shouldBeCalledTimes(1)
                ->willReturn('api');
        $request->cookie('jihe_deviceno')
                ->shouldBeCalledTimes(1)
                ->willReturn($identifier);
        $loginDevice = (new LoginDevice())
            ->setIdentifier($identifier);
        $loginDeviceRepository->findOneByMobileAndSource($mobile, 1)
                              ->shouldBeCalledTimes(1)
                              ->willReturn($loginDevice);

        $this->check($mobile)->shouldBe(true);
    }

    function it_check_passed_if_source_not_need_check(Request $request)
    {
        $mobile = '13800138000';
        $request->segment(1)
                ->shouldBeCalledTimes(1)
                ->willReturn('abc');
        $request->cookie('jihe_deviceno')->shouldNotBeCalled();
        $this->check($mobile)->shouldBe(true);
    }

    function it_check_failed_if_identifier_not_found_in_cookie(
        Request $request
    ) {
        $mobile = '13800138000';
        $request->segment(1)
                ->shouldBeCalledTimes(1)
                ->willReturn('api');
        $request->cookie('jihe_deviceno')
                ->shouldBeCalledTimes(1)
                ->willReturn(null);

        $this->check($mobile)->shouldBe(false);
    }

    function it_check_failed_if_login_device_map_not_exists(
        Request $request,
        LoginDeviceRepository $loginDeviceRepository
    ) {
        $mobile = '13800138000';
        $identifier = 'sG4nhfF6kVS0NH36x0KwePd9kudoRfS0g4sLaqnFmKxUX0Mq';

        $request->segment(1)
                ->shouldBeCalledTimes(1)
                ->willReturn('api');
        $request->cookie('jihe_deviceno')
                ->shouldBeCalledTimes(1)
                ->willReturn($identifier);
        $loginDeviceRepository->findOneByMobileAndSource($mobile, 1)
                              ->shouldBeCalledTimes(1)
                              ->willReturn(null);

        $this->check($mobile)->shouldBe(false);
    }

    function it_check_failed_if_identifier_not_match(
        Request $request,
        LoginDeviceRepository $loginDeviceRepository
    ) {
        $mobile = '13800138000';
        $request->segment(1)
                ->shouldBeCalledTimes(1)
                ->willReturn('api');
        $request->cookie('jihe_deviceno')
                ->shouldBeCalledTimes(1)
                ->willReturn('sG4nhfF6kVS0NH36x0KwePd9kudoRfS0g4sLaqnFmKxUX0Mq');
        $loginDevice = (new LoginDevice())
            ->setIdentifier('AAAAAAF6kVS0NH36x0KwePd9kudoRfS0g4sLaqnFmKxUcccc');
        $loginDeviceRepository->findOneByMobileAndSource($mobile, 1)
                              ->shouldBeCalledTimes(1)
                              ->willReturn($loginDevice);

        $this->check($mobile)->shouldBe(false);
    }
}
