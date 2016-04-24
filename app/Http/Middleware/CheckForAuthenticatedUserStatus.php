<?php
namespace Jihe\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Guard;
use Jihe\Entities\User as UserEntity;
use Jihe\Http\Responses\RespondsJson;
use Jihe\Exceptions\ExceptionCode;

class CheckForAuthenticatedUserStatus
{
    use RespondsJson;
    
    /**
     * The Guard implementation.
     *
     * @var Guard
     */
    protected $auth;

    /**
     * The URIs that should be excluded from user complete status checking.
     *
     */
    private $except = [
        'community/login',
        'community/logout',
        'community/complete',
        'api/login',
        'api/logout',
        'api/register/verifycode',
        'api/register',
        'api/user/profile/complete',
        'api/password/reset/verifycode',
        'api/password/reset',
    ];


    /**
     * Create a new filter instance.
     *
     * @param Guard $auth
     */
    public function __construct(Guard $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Check user status, if the user have not completed his or her profile, 
     * a json response will be returned
     */
    public function handle($request, Closure $next)
    {
        if ( ! $this->shouldPassThrough($request) && ($user = $this->auth->user()) 
                && $user->status == UserEntity::STATUS_INCOMPLETE) {
            if ($request->ajax()) {
                return $this->json('为了给您提供更好的服务,请先完善您的个人资料', ExceptionCode::USER_INFO_INCOMPLETE);
            } else {
                return redirect()->guest('community/complete');
            }
        }

        return $next($request);
    }


    /**
     * Determine if the request has a URI that should pass through user status checking.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function shouldPassThrough($request)
    {
        foreach ($this->except as $except) {
            if ($request->is($except)) {
                return true;
            }
        }

        return false;
    }

}
