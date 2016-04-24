<?php
namespace Jihe\Dispatches;

use Jihe\Jobs\ClearAttendantListJob;

trait DispatchesAttendantClear
{
    protected function clearAllAttendantList()
    {
        $this->dispatch(new ClearAttendantListJob());
    }

    protected function clearAttendantListOfOnePage($page)
    {
        $this->dispatch(new ClearAttendantListJob($page));
    }
}