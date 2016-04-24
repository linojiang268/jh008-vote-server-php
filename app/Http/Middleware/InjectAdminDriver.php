<?php
namespace Jihe\Http\Middleware;

use Closure;
use Auth;

class InjectAdminDriver
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        Auth::setDefaultDriver('extended-eloquent-admin');

        return $next($request);
    }
}
