<?php
namespace Jihe\Http\Middleware;

use Closure;
use Jihe\Exceptions\ExceptionCode;
use Jihe\Http\Responses\RespondsJson;
use Auth;

class AdminAuthenticate
{
    use RespondsJson;
    
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (Auth::guest()) {
            if ($request->ajax()) {
                return $this->json('需要管理员登录/授权', ExceptionCode::USER_UNAUTHORIZED);
            } else {
                return redirect()->guest('admin');
            }
        }

        return $next($request);
    }
}
