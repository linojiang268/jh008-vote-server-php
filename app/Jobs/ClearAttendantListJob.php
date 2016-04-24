<?php
namespace Jihe\Jobs;

use Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldQueue;
use Jihe\Repositories\AttendantRepository;

class ClearAttendantListJob extends Job implements SelfHandling, ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    private $page;

    public function __construct($page)
    {
        $this->page = $page;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(AttendantRepository $repository)
    {
        try {
            $repository->clearAttendantsListInCache($this->page);
        } catch (\Exception $ex) {
            Log::error($ex->getMessage());
        }
    }
    
    public function failed()
    {
        $this->release();
    }
}
