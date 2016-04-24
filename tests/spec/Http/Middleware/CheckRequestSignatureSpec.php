<?php
namespace spec\Jihe\Http\Middleware;

use PhpSpec\ObjectBehavior;
use Illuminate\Contracts\Foundation\Application;
use Jihe\Services\SignatureService;
use Illuminate\Http\Request;
use \PHPUnit_Framework_Assert as Assert;
use Prophecy\Argument;

class CheckRequestSignatureSpec extends ObjectBehavior
{
    function let(Application $app, SignatureService $service)
    {
        $this->beAnInstanceOf(\Jihe\Http\Middleware\CheckRequestSignature::class, [$app, $service]);
    }
    
    function it_checks_only_post_request(Request $request)
    {
        $request->isMethod('POST')->willReturn(false);
        
        $called = false;
        $this->handle($request, function () use(&$called) {
            $called = true;
        });
        Assert::assertTrue($called, 'Non POST request will pass signature check');
    }
    
    function it_checks_only_if_middleware_is_enabled(Request $request, Application $app)
    {
        $this->setMiddlewareEnabled($app, false); // disable the middleware
        $request->isMethod('POST')->willReturn(true);
    
        $called = false;
        $this->handle($request, function () use(&$called) {
            $called = true;
        });
        Assert::assertTrue($called, 'will pass signature check if middleware is disabled');
    }
    
    function it_rejects_if_signature_check_fails(Request $request, Application $app, SignatureService $service)
    {
        $this->setMiddlewareEnabled($app);  // enable the middleware
        $request->isMethod('POST')->willReturn(true);
        $request->input()->willReturn([]);
        $service->verify(Argument::cetera())->willReturn(false);
    
        $this->shouldThrow(\Jihe\Exceptions\SignatureException::class)
             ->duringHandle($request, function () {});
    }
    
    function it_goes_next_if_signature_check_passes(Request $request, Application $app, SignatureService $service)
    {
        $this->setMiddlewareEnabled($app);  // enable the middleware
        $request->isMethod('POST')->willReturn(true);
        $request->input()->willReturn([]);
        $service->verify(Argument::cetera())->willReturn(true);
    
        $called = false;
        $this->handle($request, function () use(&$called) {
            $called = true;
        });
        Assert::assertTrue($called, 'will pass signature check if signature check is passed');
    }
    
    private function setMiddlewareEnabled(Application $app, $enabled = true)
    {
        $app->bound('middleware.request.sign.disable')->willReturn(!$enabled);
        $app->make('middleware.request.sign.disable')->willReturn(!$enabled);
    }
}