<?php

namespace Jihe\Http\Middleware;

use Closure;
use Jihe\Contracts\Repositories\PromotionActivityManagerRepository;

class PromotionActivityManager
{
    /**
     * @var \Jihe\Contracts\Repositories\PromotionActivityManagerRepository.
     */
    protected $repository;

    public function __construct(
        PromotionActivityManagerRepository $repository
    ) {
        $this->repository = $repository;
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
        $activityName = $request->route('name');
        $activity = $this->repository->findOneActivity($activityName);
        if ( ! $activity) {
            if ($request->ajax()) {
                return $this->json('404');
            } else {
                abort(404);
            }
        }

        $request['activityName'] = $activity->getActivityName();
        $request['templateSegment'] = $activity->getTemplateSegment();

        return $next($request);
    }
}
