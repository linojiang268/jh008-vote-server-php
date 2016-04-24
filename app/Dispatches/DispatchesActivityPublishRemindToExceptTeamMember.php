<?php
namespace Jihe\Dispatches;

use Jihe\Jobs\ActivityPublishRemindToExceptTeamMemberJob;

trait DispatchesActivityPublishRemindToExceptTeamMember
{
    /**
     * @param int                     $team
     * @param \Jihe\Entities\Activity $activity
     */
    protected function dispatchActivityPublishRemindToExceptTeamMember($team, $activity)
    {
        $this->dispatch(new ActivityPublishRemindToExceptTeamMemberJob($team, $activity));
    }


}


