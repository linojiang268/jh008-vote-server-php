<?php
namespace Jihe\Dispatches;

use Jihe\Jobs\ActivitySearchIndexRefreshJob;
use Jihe\Jobs\TeamSearchIndexRefreshJob;

trait DispatchesSearchIndexRefresh
{
    protected function dispatchActivitySearchIndexRefresh($targetId)
    {
        $this->dispatch(new ActivitySearchIndexRefreshJob($targetId));
    }

    protected function dispatchTeamSearchIndexRefresh($targetId)
    {
        $this->dispatch(new TeamSearchIndexRefreshJob($targetId));
    }

}