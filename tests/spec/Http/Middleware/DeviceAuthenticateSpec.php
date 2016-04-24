<?php
namespace spec\Jihe\Http\Middleware;

use \PHPUnit_Framework_Assert as Assert;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Jihe\Services\DeviceAuthService;
use Jihe\Services\UserService;

class DeviceAuthenticateSpec extends ObjectBehavior
{
    function let(Guard $auth,
                 DeviceAuthService $deviceAuth,
                 Application $app,
                 UserService $userService
    ) {
        $this->beAnInstanceOf(\Jihe\Http\Middleware\DeviceAuthenticate::class,
            [$auth, $deviceAuth, $app, $userService]);
    }

    // close kick temporary
/*
    function it_goes_next_if_auth_passed(
        Request $request,
        Guard $auth,
        DeviceAuthService $deviceAuth,
        Application $app
    ) {
        $authUser = (new \Jihe\Models\User);
        $authUser->mobile = 13800138000;
        $this->setMiddlewareEnabled($app);
        $auth->guest()->shouldBeCalledTimes(1)->willReturn(false);
        $auth->user()->shouldBeCalledTimes(1)->willReturn($authUser);
        $deviceAuth->check('13800138000')->willReturn(true);

        $called = false;
        $this->handle($request, function() use (&$called) {
            $called = true;
        });
        Assert::assertTrue($called, 'will pass authenticate if session valid and device identifier check passed');
    }

    function it_goes_next_if_middleware_disabled(
        Request $request,
        Guard $auth,
        Application $app
    ) {
        $this->setMiddlewareEnabled($app, false);
        $auth->guest()->shouldNotBeCalled();
        $called = false;
        $this->handle($request, function() use (&$called) {
            $called = true;
        });
        Assert::assertTrue($called, 'will pass authenticate if session valid and device identifier check passed');
    }

    function it_goes_next_if_middleware_url_except(
        Request $request,
        Guard $auth,
        Application $app
    ) {
        $this->setMiddlewareEnabled($app);
        $request->is(Argument::any())->shouldBeCalled()->willReturn(true);
        $auth->guest()->shouldNotBeCalled();
        $called = false;
        $this->handle($request, function() use (&$called) {
            $called = true;
        });
        Assert::assertTrue($called, 'will pass authenticate if session valid and device identifier check passed');

    }

    function it_goes_next_if_basic_auth_failed(
        Request $request,
        Guard $auth,
        Application $app
    ) {
        $this->setMiddlewareEnabled($app);
        $auth->guest()->shouldBeCalledTimes(1)->willReturn(true);
        $called = false;
        $this->handle($request, function() use (&$called) {
            $called = true;
        });
        Assert::assertTrue($called, 'will pass authenticate if session valid and device identifier check passed');
    }

    private function setMiddlewareEnabled(Application $app, $enabled = true)
    {
        $app->bound('middleware.auth.device.disable')->willReturn( ! $enabled);
        $app->make('middleware.auth.device.disable')->willReturn( ! $enabled);
    }
    */
}
