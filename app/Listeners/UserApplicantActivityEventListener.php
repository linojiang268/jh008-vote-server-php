<?php

namespace Jihe\Listeners;

use Jihe\Events\UserApplicantActivityEvent;
use Illuminate\Queue\QueueManager;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Jihe\Services\ActivityApplicantService;

class UserApplicantActivityEventListener implements ShouldQueue
{
    use DispatchesJobs;
    use InteractsWithQueue;

    /**
     * When applicant expired, we still wait DELAY_BUFFER seconds for keeping payment
     * callback has enough time to change applicant status
     */
    const DELAY_BUFFER = 30;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(
        ActivityApplicantService $activityApplicantService
    ) {
        $this->activityApplicantService = $activityApplicantService;
    }

    /**
     * Handle the event.
     *
     * @param  UserApplicantActivityEvent  $event
     * @return void
     */
    public function handle(UserApplicantActivityEvent $event)
    {
        try {
            $this->activityApplicantService->recycleApplicantStatus($event->applicant);
        } catch (\Exception $ex) {
            $this->release(self::DELAY_BUFFER);
        }
    }

    /**
     * Change the behaviour of listener be queued, the listener will be
     * push onto a queue after a specified seconds.
     *
     * @param \Illuminate\Queue\QueueManager    $queue
     * @param string $job       job will be push onto a queue
     * @param array $payload    element data is array, which first element
     *                          is serialized event
     */
    public function queue($queue, $job, $payload)
    {
        try {
            $event = unserialize($payload['data'])[0];
            $delaySeconds = $this->getDelayTime($event->applicant);
            $queue->later($delaySeconds, $job, $payload);
        } catch (\Exception $ex) {
            // do nothing
        }
    }

    /**
     * get delay time from applicant record
     *
     * @param integer $applicant    activity applicant id
     *
     * @return integer              delay time, unit: seconds
     */
    private function getDelayTime($applicant)
    {
        $activityApplicantRepository = app(
            \Jihe\Contracts\Repositories\ActivityApplicantRepository::class
        );
        $applicant = $activityApplicantRepository
                        ->getApplicantInfoByApplicantId($applicant);
        if ( ! $applicant) {
            throw new \Exception('报名不存在');
        }

        $activityService = app(\Jihe\Services\ActivityService::class);
        $activity = $activityService->getPublishedActivityById($applicant['activity_id']);
        if ( ! $activity) {
            throw new \Exception('活动不存在');
        }
        $activityBeginTime = strtotime($activity->getBeginTime());

        $delayAfter = self::DELAY_BUFFER;
        if ($applicant['expire_at']) {
            $expireTime = strtotime($applicant['expire_at']);
            $delayAfter += min([$activityBeginTime, $expireTime]) - time();

        } else {
            $delayAfter += strtotime($activity->getBeginTime()) - time();
        }

        return $delayAfter;
    }
}
