<?php

namespace Jihe\Jobs;

use Jihe\Contracts\Services\Search\SearchService;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldQueue;

class TeamSearchIndexRefreshJob extends Job implements SelfHandling, ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    /**
     * @var string target data id
     */
    protected $targetId;

    /**
     * @param int    $targetId     the id of the data must be indexed
     */
    public function __construct($targetId)
    {
        $this->targetId = $targetId;
    }

    /**
     * Execute the job.
     *
     * @param SearchService $searchService
     */
    public function handle(SearchService $searchService)
    {
        $searchService->indexTeam($this->targetId);
    }
}
