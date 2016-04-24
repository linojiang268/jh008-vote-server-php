<?php

namespace Jihe\Repositories;

use Jihe\Contracts\Repositories\ActivityMemberRepository as ActivityMemberRepositoryContract;
use Jihe\Models\ActivityMember;
use Jihe\Services\ActivityApplicantService;
use Jihe\Utils\PaginationUtil;
use DB;

class ActivityMemberRepository implements ActivityMemberRepositoryContract
{

    public function add($member)
    {
        return ActivityMember::create($member)->value('id');
    }

    /**
     * (non-PHPdoc)
     *
     * @see \Jihe\Contracts\Repositories\ActivityMemberRepository::batchAdd()
     */
    public function batchAdd(array $members)
    {
        return DB::table('activity_members')->insert($members);
    }

    /**
     * (non-PHPdoc)
     *
     * @see \Jihe\Contracts\Repositories\ActivityMemberRepository::markAsCheckin()
     */
    public function markAsCheckin($userId, $activityId)
    {
        return ActivityMember::where('user_id', $userId)
            ->where('activity_id', $activityId)
            ->update([
            'checkin' => ActivityMember::CHECKIN_DONE,
        ]);
    }

    /**
     * (non-PHPdoc)
     *
     * @see \Jihe\Contracts\Repositories\ActivityMemberRepository::unsetCheckIn()
     */
    public function unsetCheckIn($userId, $activityId)
    {
        return ActivityMember::where('user_id', $userId)
            ->where('activity_id', $activityId)
            ->update([
            'checkin' => ActivityMember::CHECKIN_WAIT,
        ]);
    }

    public function delete($memberId)
    {
        return ActivityMember::where('id', intval($memberId))
            ->delete();
    }

    public function get($userId, $activityId)
    {
        $member = ActivityMember::where('activity_id', intval($activityId))
            ->where('user_id', intval($userId))
            ->first();
        return $member ? $member->toArray() : null;
    }

    public function updateGroup($activityId, array $memberIds, $groupId)
    {
        return ActivityMember::where('activity_id', intval($activityId))
            ->whereIn('id', $memberIds)
            ->update([
                'group_id' => $groupId,
            ]);
    }

    public function updateLocation($userId, $activityId, $lat, $lng)
    {
        return ActivityMember::where('activity_id', intval($activityId))
            ->where('user_id', intval($userId))
            ->update([
                'location' => DB::raw("POINT({$lat},{$lng})"),
            ]);
    }

    public function reset($activityId)
    {
        return ActivityMember::where('activity_id', intval($activityId))
            ->update([
                'group_id' => ActivityMember::UNGROUPED,
            ]);
    }

    public function count($activityId, $groupId = null)
    {
        if ($groupId == null) {
            return ActivityMember::where('activity_id', intval($activityId))
                ->count();
        }
        return ActivityMember::where('activity_id', intval($activityId))
            ->where('group_id', intval($groupId))
            ->count();
    }

    public function all($activityId, $groupId = null)
    {
        $members = $groupId == null ?
            ActivityMember::with('user')
                ->select('id', 'activity_id', 'user_id', 'mobile', 'name', 'attrs', 'group_id', 'role', DB::raw('X(location) as lat'), DB::raw('Y(location) as lng'))
                ->where('activity_id', intval($activityId))
                ->get()
                ->toArray() :
            ActivityMember::with('user')
                ->select('id', 'activity_id', 'user_id', 'mobile', 'name', 'attrs', 'group_id', 'role', DB::raw('X(location) as lat'), DB::raw('Y(location) as lng'))
                ->where('activity_id', intval($activityId))
                ->where('group_id', intval($groupId))
                ->get()
                ->toArray();

        return array_map(function ($member) {
            $member['attrs'] = json_decode($member['attrs']);
            return $member;
        }, $members);
    }

    public function some($activityId, $howMany)
    {
        return ActivityMember::with('user')
            ->where('activity_id', intval($activityId))
            ->take($howMany ?: 10)
            ->get()
            ->toArray();
    }

    public function notIn($activityId, array $groupIds)
    {
        return ActivityMember::with('user')
            ->where('activity_id', intval($activityId))
            ->whereNotIn('group_id', $groupIds)
            ->get()
            ->toArray();
    }

    public function exists($activityId, $userId)
    {
        return NULL != ActivityMember::where('activity_id', intval($activityId))
            ->where('user_id', intval($userId))
            ->first();
    }

    /**
     * @inheritdoc
     * @see \Jihe\Contracts\Repositories\ActivityMemberRepository::updateScore()
     */
    public function updateScore($activity, $user, array $score)
    {
        if (is_array(array_get($score, 'score_attributes'))) {
            $score['score_attributes'] = json_encode(array_values($score['score_attributes']));
        }

        return 1 == ActivityMember::where('activity_id', $activity)
            ->where('user_id', $user)
            ->whereNull('score')
            ->update($score);
    }

    /**
     * @inheritdoc
     * @see \Jihe\Contracts\Repositories\ActivityMemberRepository::scored()
     */
    public function scored($activity, $user)
    {
        return null != ActivityMember::where('activity_id', $activity)
            ->where('user_id', $user)
            ->whereNotNull('score')
            ->value('id');
    }

    /**
     * @inheritdoc
     * @see \Jihe\Contracts\Repositories\ActivityMemberRepository::countMembers()
     */
    public function countMembers($activity)
    {
        return ActivityMember::where('activity_id', $activity)->count();
    }

    /**
     * @inheritdoc
     * @see \Jihe\Contracts\Repositories\ActivityMemberRepository::countScoredMembers()
     */
    public function countScoredMembers($activity)
    {
        return ActivityMember::where('activity_id', $activity)
            ->whereNotNull('score')
            ->count();
    }

    /**
     * @inheritdoc
     * @see \Jihe\Contracts\Repositories\ActivityMemberRepository::sumScored()
     */
    public function sumScored($activity)
    {
        return ActivityMember::where('activity_id', $activity)
            ->whereNotNull('score')
            ->sum('score');
    }

    /**
     * @inheritdoc
     * @see \Jihe\Contracts\Repositories\ActivityMemberRepository::countScored()
     */
    public function findMemberWhereScoreIsNull($user)
    {
        return ActivityMember::select('activity_id')
            ->where('user_id', $user)
            ->where('score', null)
            ->get()
            ->toArray();
    }


    /**
     * find activities which are not over
     *
     * @param int $userId
     * @param int $teamId
     *
     * @return array activity id list
     */
    public function findNotOverActivitiesOfMember($userId, $teamId = null)
    {
        if ($teamId == null) {
            $list = ActivityMember::leftJoin('activities', 'activities.id', '=', 'activity_members.activity_id')
                ->where('activity_members.user_id', $userId)
                ->where(DB::raw('UNIX_TIMESTAMP(activities.end_time)'), '>', time())
                ->select('activities.id')
                ->get()
                ->toArray();
        } else {
            $list = ActivityMember::leftJoin('activities', 'activities.id', '=', 'activity_members.activity_id')
                ->where('activity_members.user_id', $userId)
                ->where('activities.team_id', $teamId)
                ->where(DB::raw('UNIX_TIMESTAMP(activities.end_time)'), '>', time())
                ->select('activities.id')
                ->get()
                ->toArray();
        }
        return array_map(function ($activity) {
            return $activity['id'];
        }, $list);
    }

    public function findAllUserActivity($userId)
    {
        $list = ActivityMember::where('user_id', $userId)
            ->select('activity_id')
            ->get()
            ->toArray();
        return array_map(function ($info) {
            return $info['activity_id'];
        }, $list);
    }

    public function getActivityMembersByMobiles($activityId, Array $mobiles)
    {
        $mobileMembers = array_combine($mobiles, array_fill(0, count($mobiles), null));
        $members = ActivityMember::where('activity_id', intval($activityId))
            ->whereIn('mobile', $mobiles)
            ->get()
            ->toArray();
        array_map(function ($member) use (&$mobileMembers) {
            $mobileMembers[$member['mobile']] = $member['id'];
        }, $members);

        return $mobileMembers;
    }

    public function getActivityMembers($activityId, $page, $size = 20)
    {
        $count = ActivityMember::where('activity_id', $activityId)->count();
        $members = ActivityMember::with('user')
            ->where('activity_id', $activityId)
            ->forPage($page, $size)
            ->get()
            ->toArray();

        if (empty($members)) {
            return [$count, []];
        }

        $list = array_map(function ($member) {
            if (!empty($member['user'])) {
                return [
                    'id'        => $member['user']['id'],
                    'mobile'    => $member['user']['mobile'],
                    'name'      => $member['user']['nick_name'],
                    'gender'    => $member['user']['gender'],
                    'avatar'    => $member['user']['avatar_url'],
                    'signature' => $member['user']['signature'],
                    'status'    => $member['user']['status'],
                ];
            }
        }, $members);

        return [$count, $list];
    }

    public function listActivityMembers($activityId, $page, $size = 20)
    {
        $query = ActivityMember::where('activity_id', $activityId);
        $count = $query->count();
        $members = $query->forPage($page, $size)->select('mobile', 'name', 'attrs', 'checkin')->get()->toArray();
        if (empty($members)) {
            return [$count, []];
        }
        $list = array_map(function ($member) {
            $tmp = [$member['mobile'], $member['name']];
            $data = json_decode($member['attrs'], 1);
            if (!empty($data)) {
                foreach ($data as $value) {
                    $tmp[] = $value['value'];
                }
            }
            $tmp[] = $member['checkin'] ? '是' : '否';
            return $tmp;
        }, $members);
        return [$count, $list];
    }

    public function getMemberCount(array $activityIds)
    {
        return ActivityMember::whereIn('activity_id', $activityIds)
            ->select(DB::raw('activity_id, count(*) AS total'))
            ->groupBy('activity_id')
            ->get()
            ->toArray();
    }

    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\ActivityMemberRepository::findTeamsOfJoinedActivities()
     */
    public function findTeamsOfJoinedActivities($user)
    {
        $teams = DB::table('activity_members')
            ->leftJoin('activities', 'activities.id', '=', 'activity_members.activity_id')
            ->where('activity_members.user_id', $user)
            ->where('activities.status', \Jihe\Entities\Activity::STATUS_PUBLISHED)
            ->select(DB::raw('distinct(activities.team_id)'))
            ->get();

        return array_map(function ($team) {
            return $team->team_id;
        }, $teams);
    }

    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\ActivityMemberRepository::getCheckinList()
     */
    public function getCheckinList($activityId, $type, $page, $size)
    {
        $checkinType = $type ? ActivityMember::CHECKIN_DONE
            : ActivityMember::CHECKIN_WAIT;

        $query = ActivityMember::where('activity_id', $activityId)
            ->where('checkin', $checkinType);
        $total = $query->count();

        $page = PaginationUtil::genValidPage($page, $total, $size);

        $query = ActivityMember::with(['checkins' => function ($query) use ($activityId) {
            $query->where('activity_id', $activityId)
                ->where('step', 1);
        }])->where('activity_id', $activityId);
        $data = $query->where('checkin', $checkinType)
            ->forPage($page, $size)
            ->get()
            ->map(function ($item, $key) {
                return $this->convertToEntity($item);
            });

        return [$total, $data];
    }

    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\ActivityMemberRepository::searchCheckinInfo()
     */
    public function searchCheckinInfo($activityId, $mobile = null, $name = null)
    {
        if (!$mobile && !$name) {
            return collect([]);
        }

        $query = ActivityMember::with(['checkins' => function ($query) use ($activityId) {
            $query->where('activity_id', $activityId)
                ->where('step', 1);
        }])->where('activity_id', $activityId);
        if ($mobile) {
            $query = $query->where('mobile', $mobile);
        }
        if ($name) {
            $query = $query->where('name', 'like', "%{$name}%");
        }

        return $query->get()->map(function ($item, $key) {
            return $this->convertToEntity($item);
        });
    }

    private function convertToEntity($activityMember)
    {
        return $activityMember ? $activityMember->toEntity() : null;
    }
}
