<?php
namespace Jihe\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Foundation\Application;
use Jihe\Exceptions\ExceptionCode;
use Jihe\Http\Responses\RespondsJson;
use Jihe\Services\DeviceAuthService;
use Jihe\Services\UserService;

class DeviceAuthenticate
{
    use RespondsJson;

    /**
     * The URIs that should be excluded from user complete status checking.
     *
     */
    private $except = [
        'community/login',
        'community/logout',
        'api/login',
        'api/logout',
        'api/alias/bound',
    ];

    /**
     * @var \Illuminate\Contracts\Foundation\Application
     */
    private $app;

    /**
     * The Guard implementation.
     *
     * @var Guard
     */
    protected $auth;

    /**
     * @var DeviceAuthService
     */
    protected $deviceAuth;

    /**
     * @var UserService
     */
    protected $userService;

    /**
     * Create a new filter instance.
     *
     * @param  Guard  $auth
     * @param  DeviceAuthService $deviceAuth
     * @param  Application $app
     */
    public function __construct(
        Guard $auth,
        DeviceAuthService $deviceAuth,
        Application $app,
        UserService $userService
    ) {
        $this->auth = $auth;
        $this->deviceAuth = $deviceAuth;
        $this->app = $app;
        $this->userService = $userService;
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
        return $next($request);

        // close kick temporary
        /*
        if ( ! $this->shouldPassThrough($request) && ! $this->deviceAuth->check($this->auth->user()->mobile)) {
            $this->userService->logout();
            if ($request->ajax()) {
                return $this->json(
                        '您的帐号在另一地点登录，您被迫下线。' .
                        '如果这不是您本人的操作，那么您的密码很可能已泄露。',
                        ExceptionCode::USER_KICKED);
            } else {
                return redirect()->guest('/community');
            }
        }

        return $next($request);
        */
    }

    /**
     * Determine if the request has a URI that should pass through user status checking.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    private function shouldPassThrough($request)
    {
        // check except
        foreach ($this->except as $except) {
            if ($request->is($except)) {
                return true;
            }
        }

        return (! $this->enabled() || $this->auth->guest()) ? true : false;

    }

    private function enabled()
    {
        return ! $this->app->bound('middleware.auth.device.disable') ||
               $this->app->make('middleware.auth.device.disable') === false;
    }
}
