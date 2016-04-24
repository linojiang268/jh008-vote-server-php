<?php
namespace Jihe\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Jihe\Contracts\Repositories\ActivityRepository;
use Jihe\Dispatches\DispatchesMessage;
use Jihe\Entities\Activity;
use Jihe\Entities\Message;
use Jihe\Utils\PushTemplate;
use Jihe\Utils\SmsTemplate;

class ActivityBeginRemindCommand extends Command
{
    use DispatchesJobs, DispatchesMessage;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'activity:begin:remind
                            {day? : A few days in advance to remind}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remind activity members of the beginning of the activity.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(ActivityRepository $activityRepository)
    {
        $days = $this->argument('day');
        if($days == null) {
            $days = 1;
        }

        $activities = $activityRepository->activityAdvanceRemind($days);

        if(!empty($activities)){
            foreach($activities as $activity){
                $this->pushToActivityMembers($activity);
            }
        }

    }

    private function pushToActivityMembers(Activity $activity)
    {
        $this->sendToActivityMembers($activity, null, [
            'title'      => $activity->getTeam()->getName(),
            'content'    => PushTemplate::generalMessage(SmsTemplate::ACTIVITY_ABOUT_TO_BEGIN,
                $activity->getTeam()->getName(),
                $activity->getTitle()),
        ], [
            'sms' => true,
        ]);

        // not need to remind managers of activity
//        $this->sendToUsers([$activity->getTelephone()], [
//            'content'    => PushTemplate::generalMessage(SmsTemplate::ACTIVITY_ABOUT_TO_BEGIN,
//                $activity->getTeam()->getName(),
//                $activity->getTitle()),
//        ], [
//            'sms' => true,
//        ]);
    }

}
