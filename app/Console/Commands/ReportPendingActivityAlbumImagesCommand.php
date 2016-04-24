<?php
namespace Jihe\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Jihe\Dispatches\DispatchesMessage;
use Jihe\Utils\SmsTemplate;
use Symfony\Component\Console\Input\InputOption;
use Jihe\Services\ActivityService;

class ReportPendingActivityAlbumImagesCommand extends Command
{
    use DispatchesJobs, DispatchesMessage;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'activity:report:pendingalbumimages';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Report pending activity album images to activity sponor.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(ActivityService $activityService)
    {
        $activities = $this->input->hasOption('activity') ? $this->option('activity') : null;
        if (!empty($activities) && !is_array($activities)) {
            $activities = [$activities];
        }
        
        $stats = $activityService->statPendingAlbumImages($activities);
        if (empty($stats)) {
            return;
        }

        foreach ($stats as $activityId => $stat) {
            $activity = $stat['activity'];
            $this->sendToUsers([$activity['telephone']], [
                'content' => SmsTemplate::generalMessage(SmsTemplate::SOME_ACTIVITY_MEMBER_ALBUM_IMAGE_REQUEST_PENDING,
                                                         $activity['title'])
            ], [
                'sms' => true
            ]);
        }
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['activity', 'a', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'specific activity(s)', []],
        ];
    }
}
