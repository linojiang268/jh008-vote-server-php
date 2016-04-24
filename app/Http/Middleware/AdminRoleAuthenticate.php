<?php

namespace Jihe\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Guard;
use Jihe\Exceptions\ExceptionCode;
use Jihe\Http\Responses\RespondsJson;
use Jihe\Models\Admin\User;
use Jihe\Services\Admin\UserService;
use Auth;

class AdminRoleAuthenticate
{
    use RespondsJson;
    
    private $userService;

    /**
     * Create a new filter instance.
     *
     * @param UserService $userService
     */
    public function __construct(UserService $userService)
    {
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
        $user = $this->userService->getUser(Auth::user()->id);

        if (!in_array($user->getRole(), $this->getAllowedRoles($request))) {
            if ($request->ajax()) {
                return $this->json('您没有此权限', ExceptionCode::USER_UNAUTHORIZED);
            } else {
                return redirect()->back()->withErrors('您没有此权限');
            }
        }

        return $next($request);
    }
    
    /**
     * get roles given request allowed
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return array   array of roles that request allowed
     */
    private function getAllowedRoles($request) 
    {
        $route = $request->route()->getAction();
        if (empty($route['roles'])) {
            return false;
        }
        
        return 1 == count($route['roles']) ? [$route['roles']] : $route['roles'];
    }
}
