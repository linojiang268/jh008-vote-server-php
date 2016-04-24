<?php
namespace Jihe\Console\Commands;

use Illuminate\Console\Command;
use Jihe\Contracts\Repositories\ActivityAlbumRepository;
use Jihe\Contracts\Repositories\ActivityApplicantRepository;
use Jihe\Contracts\Repositories\ActivityCheckInRepository;
use Jihe\Contracts\Repositories\ActivityEnrollIncomeRepository;
use Jihe\Contracts\Repositories\ActivityMemberRepository;
use Jihe\Contracts\Repositories\ActivityRepository;
use Jihe\Entities\ActivityAlbumImage;
use Symfony\Component\Console\Input\InputOption;
use Log;
use Mail;

class EndActivityStatisticsCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'statistics:end-activities';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'End activity statistics';

    /**
     * Execute the console command.
     *
     * @param ActivityRepository $searchService
     */
    public function handle(ActivityRepository $activityRepository,
                           ActivityCheckInRepository $activityCheckInRepository,
                           ActivityMemberRepository $activityMemberRepository,
                           ActivityApplicantRepository $activityApplicantRepository,
                           ActivityEnrollIncomeRepository $activityEnrollIncomeRepository,
                           ActivityAlbumRepository $activityAlbumRepository)
    {
        $start = $this->option('start');
        $delay   = $this->option('delay');
        $mailTo   = $this->option('mailTo');
        $startDateTime = date('Y-m-d 00:00:00', strtotime('-'.$start.' day'));
        $endDateTime = date('Y-m-d 00:00:00', strtotime('-'.$delay.' day'));
        $activities = $activityRepository->searchEndActivityByTime($startDateTime, $endDateTime, 1, 1000);
        if(empty($activities)){
           return null;
        }
        $activityIds = [];
        $activitiesData = [];
        foreach($activities as $activity){
            $activityIds[] = $activity->getId();
        }
        $sponsorAlbumCount = $this->getAlbumCount($activityAlbumRepository, $activityIds, ActivityAlbumImage::SPONSOR, ActivityAlbumImage::STATUS_APPROVED);
        $userAlbumCount = $this->getAlbumCount($activityAlbumRepository, $activityIds, ActivityAlbumImage::USER, ActivityAlbumImage::STATUS_APPROVED);
        $checkInCount = $this->getCheckInCount($activityCheckInRepository, $activityIds);
        $activityMemberCount = $this->getActivityMemberCount($activityMemberRepository, $activityIds);
        $activityApplicantCount = $this->getApplicantCount($activityApplicantRepository, $activityIds);
        $activityAmountCount = $this->getAmountCount($activityEnrollIncomeRepository, $activityIds);
        $i = 0;
        foreach($activities as $activity){
            if($i%2 == 0){
                $style = 'tg-0ord';
            }else{
                $style = 'tg-ifyx';
            }
            $id = $activity->getId();
            $activitiesData[$id] = [
                'title' => $activity->getTitle(),
                'applicant' => isset($activityApplicantCount[$id])?$activityApplicantCount[$id]:0,
                'member' => isset($activityMemberCount[$id])?$activityMemberCount[$id]:0,
                'amount' => isset($activityAmountCount[$id])?number_format($activityAmountCount[$id] / 100, 2):0,
                'checkIn' => isset($checkInCount[$id])?$checkInCount[$id]:0,
                'userAlbum' => isset($userAlbumCount[$id])?$userAlbumCount[$id]:0,
                'sponsorAlbum' => isset($sponsorAlbumCount[$id])?$sponsorAlbumCount[$id]:0,
                'style' => $style,
            ];
            $i++;
        }
        $this->sendMail($activitiesData, $mailTo);
    }

    private function sendMail($activitiesData, $mailTo)
    {
        try {
            Mail::send('emails.activity_statistics', ['activitiesData' => $activitiesData],
                function ($m) use ($mailTo) {
                    $m->to($mailTo, 'System')->subject('[' . strtoupper(app('env')) .'] End activity statistics');
                });
        } catch (\Exception $ex) {
            Log::error($ex->getMessage());
        }
    }

    private function getAlbumCount(ActivityAlbumRepository $activityAlbumRepository, $activityIds, $creatorType, $status)
    {
        return $activityAlbumRepository->countActivityImages($activityIds, $creatorType, $status);
    }

    private function getCheckInCount(ActivityCheckInRepository $activityCheckInRepository, $activityIds)
    {
        return $activityCheckInRepository->countActivityCheckIn($activityIds);
    }

    private function getAmountCount(ActivityEnrollIncomeRepository $activityEnrollIncomeRepository, $activityIds)
    {
        return $activityEnrollIncomeRepository->countActivitiesAmount($activityIds);
    }

    private function getApplicantCount(ActivityApplicantRepository $activityApplicantRepository, $activityIds)
    {
       return $activityApplicantRepository->countActivityApplicant($activityIds);
    }

    private function getActivityMemberCount(ActivityMemberRepository $activityMemberRepository, $activityIds)
    {
        $results = $activityMemberRepository->getMemberCount($activityIds);
        $resultWithKey = [];
        foreach ($results as $result) {
            $resultWithKey[$result['activity_id']] = $result['total'];
        }

        return $resultWithKey;
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['start',   null, InputOption::VALUE_OPTIONAL, 'How many days ago', 60],
            ['delay',   null, InputOption::VALUE_OPTIONAL, 'How many days after the end of the activity will start to count', 2],
            ['mailTo',   null, InputOption::VALUE_OPTIONAL, 'send mail to', 'chenguoliang@jh008.com'],
        ];
    }
}
