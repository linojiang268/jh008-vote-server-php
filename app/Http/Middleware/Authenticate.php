<?php

namespace Jihe\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Guard;
use Jihe\Exceptions\ExceptionCode;
use Jihe\Http\Responses\RespondsJson;

class Authenticate
{
    use RespondsJson;
    
    /**
     * The Guard implementation.
     *
     * @var Guard
     */
    protected $auth;

    /**
     * Create a new filter instance.
     *
     * @param  Guard  $auth
     */
    public function __construct(Guard $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($this->auth->guest()) {
            if ($request->ajax()) {
                return $this->json('需要登录/授权', ExceptionCode::USER_UNAUTHORIZED);
            } else {
                return redirect()->guest('community');
            }
        }

        return $next($request);
    }
}
