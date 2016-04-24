<?php
namespace Jihe\Http\Middleware;

use Closure;
use Jihe\Services\TeamService;
use Jihe\Http\Responses\RespondsJson;
use Illuminate\Contracts\Auth\Guard;

class InjectTeam
{
    use RespondsJson;
    
    /**
     * @var \Illuminate\Contracts\Auth\Guard
     */
    private $auth;
    
    /**
     * @var \Jihe\Services\TeamService
     */
    private $service;
    
    public function __construct(Guard $auth, TeamService $service)
    {
        $this->auth = $auth;
        $this->service = $service;
    }

    /**
     * Inject team to request when handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // inject team to request from session if session exists
        //if (!is_null($team = $request->getSession()->get('team'))) {
        //    $request['team'] = $team;
        //    return $next($request);
        //}
        
        $teams = $this->service->getTeamsByCreator($this->auth->user()->getAuthIdentifier());
        // only team creator can visit team backstage
        if (empty($teams)) {
            if ($request->ajax()) {
                return $this->jsonException('非法团长');
            } else {
                return redirect()->guest('community/team/profile');
            }
        }
        
        // inject team to request, set team in session
        $request['team'] = $teams[0];
        //$request->getSession()->set('team', $teams[0]);
        
        return $next($request);
    }
}
