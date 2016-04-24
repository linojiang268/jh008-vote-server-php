<?php

namespace Jihe\Repositories;

use Jihe\Contracts\Repositories\ActivityCheckInRepository as ActivityCheckInRepositoryContract;
use Jihe\Entities\Activity;
use Jihe\Models\ActivityCheckIn;
use Jihe\Utils\PaginationUtil;
use DB;

class ActivityCheckInRepository implements ActivityCheckInRepositoryContract
{

    public function checkInV1($activityId, $step)
    {
        ActivityCheckIn::where('activity_id', $activityId)->get();
    }

    public function add($userId, $activityId, $step, $processId = 0)
    {
        return ActivityCheckIn::create([
            'user_id'     => $userId,
            'process_id'  => $processId,
            'activity_id' => $activityId,
            'step'        => $step,
        ])->id;
    }

    public function updateProcessId($id)
    {
        return ActivityCheckIn::where('id', $id)->update(['process_id' => 0]) == 1 ;
    }

    public function delete($id)
    {
        return ActivityCheckIn::where('id', $id)->delete() == 1 ;
    }

    public function all($activityId, $userId)
    {
        return ActivityCheckIn::where('user_id', $userId)
            ->where('activity_id', $activityId)
            ->orderBy('step', 'ASC')
            ->get()
            ->toArray();
    }

    /**
     * (non-PHPdoc)
     *
     * @see \Jihe\Contracts\Repositories\ActivityCheckInRepository::getAllByActivityAndStep()
     */
    public function getAllByActivityAndStep($activity, $step, $page, $pageSize)
    {
        $query = ActivityCheckIn::with('user')
            ->where('activity_id', $activity)
            ->where('step', $step)
            ->orderBy('created_at', 'desc');
        $count = $query->count();
        $page = PaginationUtil::genValidPage($page, $count, $pageSize);
        $checkIns = $query->forPage($page, $pageSize)->get()->all();
        $checkIns = array_map([$this, 'convertCheckInDataToArray'], $checkIns);

        return [$count, $checkIns];
    }

    private function convertCheckInDataToArray($model)
    {
        return $model ? [
            'id'         => $model->id,
            'user_id'    => $model->user_id,
            'process_id' => $model->process_id,
            'mobile'     => $model->user ? $model->user->mobile : null,
            'nick_name'  => $model->user ? $model->user->nick_name : null,
            'step'       => $model->step,
            'created_at' => $model->created_at,
        ] : null;
    }

    public function countActivityCheckIn($activities)
    {
        $activitiesCheckInCount = DB::table('activity_check_in')
            ->leftJoin('activities', 'activities.id', '=', 'activity_check_in.activity_id')
            ->where('activities.status', Activity::STATUS_PUBLISHED)
            ->whereIn('activity_check_in.activity_id', $activities)
            ->where('activity_check_in.step', 1)
            ->groupBy('activity_check_in.activity_id')
            ->select(DB::raw('count(activity_check_in.activity_id) as ci, activity_check_in.activity_id'))
            ->get();
        $result = [];
        if ($activitiesCheckInCount) {
            foreach ($activitiesCheckInCount as $activityCheckInCount) {
                $result[$activityCheckInCount->activity_id] = $activityCheckInCount->ci;
            }
        }

        return $result;
    }
}
