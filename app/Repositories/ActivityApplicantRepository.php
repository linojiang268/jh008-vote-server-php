<?php

namespace Jihe\Repositories;

use Jihe\Contracts\Repositories\ActivityApplicantRepository as ActivityApplicantRepositoryyContract;
use Jihe\Models\ActivityApplicant;
use DB;
use Jihe\Entities\Activity as ActivityEntity;
use Jihe\Entities\ActivityApplicant as ActivityApplicantEntity;

class ActivityApplicantRepository implements ActivityApplicantRepositoryyContract
{

    private function generateOrderNumber()
    {
        return substr(strtoupper(uniqid('JH' . date('YmdHis'))) . rand(1000, 10000), 0, 32);
    }

    /**
     * @deprecated
     */
    public function addApplicantInfo(array $info)
    {
        ActivityApplicant::where('user_id', $info['user_id'])
            ->where('activity_id', $info['activity_id'])
            ->where('status', '<>', ActivityApplicant::STATUS_INVALID)
            ->update([
                'status' => ActivityApplicant::STATUS_INVALID,
            ]);
        $info['order_no'] = $this->generateOrderNumber();
        return ActivityApplicant::create($info)->toArray();
    }

    /**
     * @param array $applicants
     *
     * @return mixed
     */
    public function multipleAddApplicantInfo(array $applicants)
    {
        if(!empty($applicants)){
            foreach( $applicants as $mobile => $applicant){
                $applicants[$mobile]['order_no'] = $this->generateOrderNumber();
            }
        }
        return DB::table('activity_applicants')->insert($applicants);
    }

    /**
     * (non-PHPDoc)
     *
     * @see \Jihe\Contracts\Repositories\ActivityApplicantRepository::saveApplicantInfo()
     */
    public function saveApplicantInfo(array $info)
    {
        ActivityApplicant::where('user_id', $info['user_id'])
            ->where('activity_id', $info['activity_id'])
            ->where('status', '<>', ActivityApplicantEntity::STATUS_INVALID)
            ->update([
                'status' => ActivityApplicantEntity::STATUS_INVALID,
            ]);
        $info['order_no'] = $this->generateOrderNumber();
        $applicantModel = ActivityApplicant::create($info);
        return $this->convertToEntity($applicantModel);
    }

    public function getApplicantInfo($userId, $activityId)
    {
        $model = ActivityApplicant::where('user_id', $userId)
            ->where('activity_id', $activityId)
            ->where('status', '<>', ActivityApplicant::STATUS_INVALID)
            ->orderBy('id', 'DESC')
            ->first();
        if ($model == null) {
            return null;
        }
        return $model->toArray();
    }

    /**
     * (non-PHPDoc)
     *
     * @see \Jihe\Contracts\Repositories\ActivityApplicantRepository::getUserPendingApplicant()
     */
    public function getValidUserApplicant($userId, $activityId)
    {
        $model = ActivityApplicant::where('user_id', $userId)
            ->where('activity_id', $activityId)
            ->where('status', '<>', ActivityApplicantEntity::STATUS_INVALID)
            ->first();
        return $this->convertToEntity($model);
    }

    public function getApplicantInfoByApplicantId($applicantId)
    {
        $model = ActivityApplicant::where('id', $applicantId)->first();
        if ($model == null) {
            return null;
        }
        return $model->toArray();
    }

    public function getApplicantInfoListByApplicantIds(array $applicantIds, $activityId)
    {
        $model = ActivityApplicant::whereIn('id', $applicantIds)
            ->where('activity_id', $activityId)
            ->get();
        if ($model == null) {
            return null;
        }
        return $model->toArray();
    }

    public function getApplicantInfoByOrderNo($orderNo)
    {
        $model = ActivityApplicant::where('order_no', $orderNo)
            ->first();
        if ($model == null) {
            return null;
        }
        return $model->toArray();
    }

    public function updateApplicantStatus($orderNo, $status)
    {
        if (!in_array($status, [ActivityApplicant::STATUS_INVALID, ActivityApplicant::STATUS_NORMAL, ActivityApplicant::STATUS_AUDITING,
            ActivityApplicant::STATUS_PAY, ActivityApplicant::STATUS_SUCCESS])
        ) {
            return false;
        }
        return ActivityApplicant::where('order_no', $orderNo)
            ->update([
                'status' => $status,
            ]);
    }

    /**
     * (non-PHPdoc)
     *
     * @see \Jihe\Contracts\Repositories\ActivityApplicantRepository::updateStatusAfterPaymentSuccess()
     */
    public function updateStatusAfterPaymentSuccess($orderNo)
    {
        return 1 == ActivityApplicant::where('order_no', $orderNo)
            ->where('status', ActivityApplicant::STATUS_PAY)
            ->update(['status' => ActivityApplicant::STATUS_SUCCESS]);
    }

    public function updateApplicantPaymentExpireTime($orderNo, $datetime)
    {
        return ActivityApplicant::where('order_no', $orderNo)
            ->update([
                'expire_at' => $datetime,
            ]);
    }

    public function getActivityApplicantsList($activityId, array $status, $page, $size, $sort = 'ASC')
    {
        $model = ActivityApplicant::where('activity_id', $activityId)
            ->whereIn('status', $status)
            ->orderBy('id', $sort)
            ->forPage($page, $size)
            ->get();
        if ($model == null) {
            return [0, []];
        }


        $count = ActivityApplicant::where('activity_id', $activityId)
            ->whereIn('status', $status)
            ->count();

        return [$count, array_map(function ($applicant) {
            $applicant['attrs'] = json_decode($applicant['attrs']);
            //$applicant['expire'] = empty($applicant['expire_at']) ? 1 : (strtotime($applicant['expire_at']) - time() > 0 ? 1 : 0 );
            return $applicant;
        }, $model->toArray())];
    }

    /**
     * (non-PHPdoc)
     *
     * @see \Jihe\Contracts\Repositories\ActivityApplicantRepository::getActivityApplicantsPageById()
     */
    public function getActivityApplicantsPageById(
        $activityId, $applicantId, $status, $size, $sortDesc, $isPre
    ) {
        $sort = $sortDesc ? 'DESC' : 'ASC';
        $direction = $isPre ? 'PRE' : 'NEXT';

        $map = [
            'NEXT_ASC'  => 'getNextApplicantsPageByIdOrderAsc',
            'NEXT_DESC' => 'getNextApplicantsPageByIdOrderDesc',
            'PRE_ASC'   => 'getPreApplicantsPageByIdOrderAsc',
            'PRE_DESC'  => 'getPreApplicantsPageByIdOrderDesc',
        ];
        $handler = $map[$direction . '_' . $sort];
        return call_user_func([$this, $handler], $activityId, $applicantId, $status, $size);
    }

    private function getNextApplicantsPageByIdOrderAsc(
        $activityId, $applicantId, $status, $size 
    ) {
        $query = ActivityApplicant::with('activity')
                                  ->where('activity_id', $activityId)
                                  ->where('status', $status);
        $total = $query->count();

        if ($applicantId !== null) {
            $query = $query->where('id', '>', $applicantId);
        }
        $applicants = $query->orderBy('id', 'ASC')->take($size)->get();

        if ( ! $applicants->isEmpty()) {
            $firstId = $applicants->first()->id;
            $lastId = $applicants->last()->id;
        } else {
            $firstId = $lastId = $applicantId + 1;
        }

        return [
            $total, $firstId, $lastId,
            $applicants->map(function ($item, $key) {
                return $this->convertToEntity($item);
            })
        ];
    }

    private function getNextApplicantsPageByIdOrderDesc(
        $activityId, $applicantId, $status, $size 
    ) {
        $query = ActivityApplicant::with('activity')
                                  ->where('activity_id', $activityId)
                                  ->where('status', $status);
        $total = $query->count();

        if ($applicantId !== null) {
            $query = $query->where('id', '<', $applicantId);
        }

        $applicants = $query->orderBy('id', 'DESC')->take($size)->get();

        if ( ! $applicants->isEmpty()) {
            $firstId = $applicants->first()->id;
            $lastId = $applicants->last()->id;
        } else {
            $firstId = $lastId = 0;
        }

        return [
            $total, $firstId, $lastId,
            $applicants->map(function ($item, $key) {
                return $this->convertToEntity($item);
            })
        ];
    }

    private function getPreApplicantsPageByIdOrderAsc(
        $activityId, $applicantId, $status, $size 
    ) {
        $query = ActivityApplicant::with('activity')
                                  ->where('activity_id', $activityId)
                                  ->where('status', $status);
        $total = $query->count();

        if ($applicantId === null) {
            return [$total, null, null, collect([])];
        }

        $applicants = $query->where('id', '<', $applicantId)
                ->orderBy('id', 'DESC')
                ->take($size)
                ->get()
                ->reverse();

        if ( ! $applicants->isEmpty()) {
            $firstId = $applicants->first()->id;
            $lastId = $applicants->last()->id;
        } else {
            $firstId = $lastId = 0;
        }

        return [
            $total, $firstId, $lastId,
            $applicants->map(function ($item, $key) {
                return $this->convertToEntity($item);
            })
        ];
    }

    private function getPreApplicantsPageByIdOrderDesc(
        $activityId, $applicantId, $status, $size 
    ) {
        $query = ActivityApplicant::with('activity')
                                  ->where('activity_id', $activityId)
                                  ->where('status', $status);
        $total = $query->count();

        if ($applicantId === null) {
            return [$total, null, null, collect([])];
        }

        $applicants = $query->where('id', '>', $applicantId)
                ->orderBy('id', 'ASC')
                ->take($size)
                ->get()
                ->reverse();

        if ( ! $applicants->isEmpty()) {
            $firstId = $applicants->first()->id;
            $lastId = $applicants->last()->id;
        } else {
            $firstId = $lastId = $applicantId + 1;
        }

        return [
            $total, $firstId, $lastId,
            $applicants->map(function ($item, $key) {
                return $this->convertToEntity($item);
            })
        ];
    }

    public function getNotPayCount($activityId)
    {
        return ActivityApplicant::where('activity_id', $activityId)
            ->where('status', ActivityApplicant::STATUS_PAY)
            ->where('expire_at', '<', 'now()')
            ->count();
    }

    public function getApplicantsCount(array $activityIds)
    {
        $results = ActivityApplicant::whereIn('activity_id', $activityIds)
            ->select(DB::raw('activity_id, count(*) AS total'))
            ->groupBy('activity_id')
            ->get()
            ->toArray();

        $resultWithKey = [];
        foreach ($results as $result) {
            $resultWithKey[$result['activity_id']] = $result['total'];
        }
        foreach ($activityIds as $activityId) {
            if (!isset($resultWithKey[$activityId])) {
                $resultWithKey[$activityId] = 0;
            }
        }
        return $resultWithKey;
    }

    public function getLatestApplicants($activityId, $howMany)
    {
        $applicantList = ActivityApplicant::with('user')
            ->where('activity_id', intval($activityId))
            ->where('user_id', '>', 0)
            ->take($howMany ?: 10)
            ->orderBy('id', 'DESC')
            ->get()
            ->toArray();

        return array_map(function ($applicant) {
            return ['user_id' => $applicant['user_id'],
                'name' => $applicant['name'],
                'avatar_url' => $applicant['user']['avatar_url'],
                'gender' => $applicant['user']['gender'],
            ];
        }, $applicantList);
    }

    public function getApplicantsList($activityId, $page, $size)
    {
        $applicantList = ActivityApplicant::with('user')
            ->where('activity_id', intval($activityId))
            ->where('user_id', '>', 0)
            ->where('status', '<>', ActivityApplicant::STATUS_INVALID)
            ->orderBy('id', 'DESC')
            ->forPage($page, $size)
            ->get()
            ->toArray();

        return array_map(function ($applicant) {
            return ['user_id' => $applicant['user_id'],
                'name' => $applicant['name'],
                'avatar_url' => $applicant['user']['avatar_url'],
                'gender' => $applicant['user']['gender'],
            ];
        }, $applicantList);
    }

    public function getUserApplicantsList($userId, $status = null)
    {
        $query = ActivityApplicant::where('user_id', $userId)
            ->orderBy('id', 'DESC')
            ->select('activity_id');

        if (!is_null($status)) {
            $query->where('status', $status);
        }

        return $query->get()->toArray();
    }

    /**
     * (non-PHPdoc)
     *
     * @see \Jihe\Contracts\Repositories\ActivityApplicantRepository::getSuccessfulApplicant()
     */
    public function getUserSuccessfulApplicant($userId, $activityId)
    {
        $model = ActivityApplicant::where('user_id', $userId)
            ->where('activity_id', $activityId)
            ->where('user_id', $userId)
            ->where('status', ActivityApplicant::STATUS_SUCCESS)
            ->first();

        return $model ? $model->toArray() : null;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \Jihe\Contracts\Repositories\ActivityApplicantRepository::recycleActivityApplicant()
     */
    public function recycleActivityApplicant($applicant)
    {
        $invalidStatus = [
            ActivityApplicant::STATUS_INVALID,
            ActivityApplicant::STATUS_SUCCESS,
        ];
        return 1 == ActivityApplicant::where('id', $applicant)
                        ->whereNotIn('status', $invalidStatus)
                        ->update([
                            'status' => ActivityApplicant::STATUS_INVALID
                        ]);
    }

    public function countActivityApplicant($activities)
    {
        $activitiesApplicantCount = DB::table('activity_applicants')
            ->leftJoin('activities', 'activities.id', '=', 'activity_applicants.activity_id')
            ->where('activities.status', ActivityEntity::STATUS_PUBLISHED)
            ->whereIn('activity_applicants.activity_id', $activities)
            ->groupBy('activity_applicants.activity_id')
            ->select(DB::raw('count(distinct(activity_applicants.user_id)) as ci, activity_applicants.activity_id'))
            ->get();
        $result = [];
        if($activitiesApplicantCount){
            foreach ($activitiesApplicantCount as $activityApplicantCount) {
                $result[$activityApplicantCount->activity_id] = $activityApplicantCount->ci;
            }
        }

        return $result;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \Jihe\Contracts\Repositories\ActivityApplicantRepository::findTeamsOfRequestedActivities()
     */
    public function findTeamsOfRequestedActivities($user)
    {
        $teams = DB::table('activity_applicants')
            ->leftJoin('activities', 'activities.id', '=', 'activity_applicants.activity_id')
            ->where('activity_applicants.user_id', $user)
            ->where('activities.status', \Jihe\Entities\Activity::STATUS_PUBLISHED)
            ->select(DB::raw('distinct(activities.team_id)'))
            ->get();

        return array_map(function ($team) {
            return $team->team_id;
        }, $teams);
    }

    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\ActivityApplicantRepository::getAuditApplicants()
     */
    public function getAuditApplicants($activityId, array $applicantIds)
    {
        $applicants = ActivityApplicant::with('activity', 'user')
            ->where('activity_id', $activityId)
            ->whereIn('id', $applicantIds)
            ->get()
            ->all();

        return array_map([$this, 'convertToEntity'], $applicants);
    }

    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\ActivityApplicantRepository::approveToPay()
     */
    public function approveToPay(array $applicantIds, \DateTime $expireAt)
    {
        return ActivityApplicant::whereIn('id', $applicantIds)
            ->where('status', ActivityApplicantEntity::STATUS_AUDITING)
            ->update([
                'status'    => ActivityApplicantEntity::STATUS_PAY,
                'expire_at' => $expireAt->format('Y-m-d H:i:s'),
            ]);
    }

    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\ActivityApplicantRepository::approveToSuccess()
     */
    public function approveToSuccess(array $applicantIds)
    {
        return ActivityApplicant::whereIn('id', $applicantIds)
            ->where('status', ActivityApplicantEntity::STATUS_AUDITING)
            ->update(['status' => ActivityApplicantEntity::STATUS_SUCCESS]);
    }

    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\ActivityApplicantRepository::refuse()
     */
    public function refuse(array $applicantIds)
    {
        return ActivityApplicant::whereIn('id', $applicantIds)
            ->where('status', ActivityApplicantEntity::STATUS_AUDITING)
            ->update(['status' => ActivityApplicantEntity::STATUS_INVALID]);
    }

    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\ActivityApplicantRepository::refuse()
     */
    public function remark($activityId, $applicantId, $content)
    {
        return ActivityApplicant::where('activity_id', $activityId)
                ->where('id', $applicantId)
                ->where('status', ActivityApplicantEntity::STATUS_SUCCESS)
                ->update([
                    'remark'    => $content,
                ]);
    }

    private function convertToEntity($activityApplicant)
    {
        return $activityApplicant ? $activityApplicant->toEntity() : null;
    }

    public function getActivityApplicantsByMobiles($activityId, Array $mobiles)
    {
        $mobileApplicants = array_combine($mobiles, array_fill(0, count($mobiles), null));
        $applicants = ActivityApplicant::where('activity_id', intval($activityId))
            ->whereIn('mobile', $mobiles)
            ->where('status', '!=', ActivityApplicant::STATUS_INVALID)
            ->get()
            ->toArray();
        array_map(function ($applicant)  use (&$mobileApplicants) {
            $mobileApplicants[$applicant['mobile']] = $applicant['id'];
        }, $applicants);

        return $mobileApplicants;
    }

}
