<?php
namespace Jihe\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Jihe\Contracts\Repositories\ActivityRepository;
use Jihe\Dispatches\DispatchesMessage;
use Jihe\Entities\Activity;
use Jihe\Entities\Message;
use Jihe\Utils\PushTemplate;

class ActivityPublishRemindToExceptTeamMembersCommand extends Command
{
    use DispatchesJobs, DispatchesMessage;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'activity:publish:remind:exceptteammembers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remind non team members of the published of the activity.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(ActivityRepository $activityRepository)
    {
        $start = date('Y-m-d H:00:00');
        $end = date('Y-m-d H:00:00', strtotime('+1 hours'));
        list($total, $activities) = $activityRepository->searchTeamActivitiesByActivityTime($start, $end, null, 1, 20000);
        if($activities){
            foreach($activities as $activity){
                $phones = $activityRepository->findParticipatedInTeamOfActivitiesUser($activity->getTeam()->getId());
                $this->pushToMembers($phones, $activity);
            }
        }
    }

    private function pushToMembers(array $phones, Activity $activity)
    {
        $this->sendToUsers($phones, [
            //'content'    => PushTemplate::generalMessage(PushTemplate::ACTIVITY_PUBLISHED_BY_ENROLLED_TEAM,
            //    $activity->getTeam()->getName(),
            //    $activity->getTitle()),
            'title'      => $activity->getTeam()->getName(),
            'content'    => PushTemplate::generalMessage(PushTemplate::ACTIVITY_PUBLISHED_BY_ENROLLED_TEAM, $activity->getTitle()),
            'type'       => Message::TYPE_ACTIVITY,
            'attributes' => ['activity_id' => $activity->getId()],
        ], [
            'push' => true,
        ]);
    }

}
