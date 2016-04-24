<?php

namespace Jihe\Jobs;

use Jihe\Jobs\Job;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldQueue;
use Jihe\Services\ActivityApplicantService;

class RecycleActivityApplicantStatusJob extends Job implements SelfHandling, ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    /**
     * Activity applicant id
     * @var integer
     */
    protected $applicant;

    /**
     * Create a new job instance.
     *
     * @param integer $applicant    activity applicant id
     *
     * @return void
     */
    public function __construct($applicant)
    {
        $this->applicant = $applicant;
    }

    /**
     * Execute the job.
     *
     * @param \Jihe\Service\ActivityApplicantService $activityApplicantService
     *
     * @return void
     */
    public function handle(ActivityApplicantService $activityApplicantService)
    {
        if ($this->attempts() > 3) {
            $this->delete();
        }

        try {
            $activityApplicantService->recycleApplicantStatus($this->applicant);
        } catch (\Exception $ex) {
            // do nothing
        }
    }
}
