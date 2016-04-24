<?php
namespace Jihe\Http\Middleware;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Jihe\Services\SignatureService;
use Jihe\Exceptions\SignatureException;

class CheckRequestSignature
{
    /**
     * @var \Illuminate\Contracts\Foundation\Application
     */
    private $app;
    
    /**
     * @var \Jihe\Services\SignatureService
     */
    private $service;
    
    public function __construct(Application $app, SignatureService $service)
    {
        $this->app = $app;
        $this->service = $service;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     *
     * @throws SignatureException    if signature verification fails
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $shouldCheck = $request->isMethod('POST') &&    // check POST request only
                       $this->enabled();                // check only when it's enabled

        // check signature if we should
        // 
        // $request->all() include file input, which should not be signed. And hence,
        // we use $request->input() instead.
        //
        if ($shouldCheck && !$this->service->verify($request->input())) {
            throw new SignatureException();
        }

        return $next($request);
    }

    private function enabled()
    {
        return !$this->app->bound('middleware.request.sign.disable') ||        // as long as it's not disabled
               $this->app->make('middleware.request.sign.disable') === false;
    }
}
