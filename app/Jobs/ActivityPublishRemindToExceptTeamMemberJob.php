<?php
namespace Jihe\Jobs;

use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Jihe\Entities\Message;
use Jihe\Repositories\ActivityRepository;
use Jihe\Services\MessageService;
use Jihe\Utils\PushTemplate;

class ActivityPublishRemindToExceptTeamMemberJob extends Job implements SelfHandling, ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    /**
     * push activity team id
     * @var string
     */
    protected $team;

    /**
     * activity id to push message to
     * @var string
     */
    protected $activity;

    /**
     * @param int                     $team
     * @param \Jihe\Entities\Activity $activity
     */
    public function __construct($team, $activity)
    {
        $this->activity = $activity;
        $this->team = $team;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(ActivityRepository $activityRepository, MessageService $service)
    {
        $phones = $activityRepository->findParticipatedInTeamOfActivitiesUser($this->team);
        if (empty($phones)) {
            return;
        }
        $service->sendToUsers($phones, [
            'title'      => $this->activity->getTeam()->getName(),
            'content'    => PushTemplate::generalMessage(PushTemplate::ACTIVITY_PUBLISHED_BY_ACTIVITY_EVER_ENROLLED,
                                                         $this->activity->getTitle()),
            'type'       => Message::TYPE_ACTIVITY,
            'attributes' => ['activity_id' => $this->activity->getId()],
        ], [
            'push' => true,
        ]);

    }

}