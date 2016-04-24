<?php

namespace Jihe\Services;

use Jihe\Contracts\Repositories\ActivityMemberRepository;
use Jihe\Contracts\Repositories\ActivityGroupRepository;
use Jihe\Entities\Activity;
use Jihe\Services\Excel\ExcelWriter;

class ActivityMemberService
{

    private $activityMemberRepository;
    private $activityGroupRepository;

    public function __construct(ActivityMemberRepository $activityMemberRepository,
                                ActivityGroupRepository $activityGroupRepository)
    {
        $this->activityMemberRepository = $activityMemberRepository;
        $this->activityGroupRepository = $activityGroupRepository;
    }

    /**
     * get count members of activity group, if the group is not given, it will return how many members of this activity
     *
     * @param int $activityId
     * @param int $groupId
     *
     * @return int
     */
    public function totalMemberOf($activityId, $groupId = null)
    {
        return $this->activityMemberRepository->count($activityId, $groupId);
    }

    /**
     *
     * @param int $activityId
     * @param int $groupId
     *
     * @return array the list of activity
     */
    public function allMemberOf($activityId, $groupId = null)
    {
        return $this->activityMemberRepository->all($activityId, $groupId);
    }

    /**
     * @param int $userId
     * @param int $activityId
     * @param double $lat
     * @param  double $lng
     * @return true|false
     */
    public function updateLocation($userId, $activityId, $lat, $lng)
    {
        return $this->activityMemberRepository->updateLocation($userId, $activityId, $lat, $lng);
    }

    /**
     * update group id of members by member id
     *
     * @param int $activityId
     * @param array $memberIds
     * @param int $groupId
     *
     * @return boolean
     * @throws \Exception
     */
    public function setGroup($activityId, array $memberIds, $groupId)
    {
        if (!$this->activityGroupRepository->exists($activityId, $groupId)) {
            throw new \Exception('无此活动组');
        }

        return $this->activityMemberRepository->updateGroup($activityId, $memberIds, $groupId);
    }

    /**
     * reset all members' group id
     *
     * @param int $activityId
     *
     * @return boolean
     * @throws \Exception
     */
    public function resetGroupId($activityId)
    {
        return $this->activityMemberRepository->reset($activityId);
    }

    /**
     * member list of not grouped
     *
     * @param int $activityId
     * @param array $groupIds
     *
     * @return int
     */
    public function membersNotInGroupIds($activityId, array $groupIds)
    {
        return $this->activityMemberRepository->notIn($activityId, $groupIds);
    }

    /**
     * @param int $userId
     * @param int $activityId
     *
     * @return true|false
     */
    public function isActivityMember($userId, $activityId)
    {
        return $this->activityMemberRepository->exists($activityId, $userId);
    }

    public function getActivityMemberInfo($userId, $activityId)
    {
        return $this->activityMemberRepository->get($userId, $activityId);
    }

    public function getSomeActivityMembers($activityId, $howMany)
    {
        return $this->activityMemberRepository->some($activityId, $howMany);
    }

    /**
     * @param int $user
     *
     * @return array|mixed
     */
    public function getMemberWhereScoreIsNull($user)
    {
        $activityIds = $this->activityMemberRepository->findMemberWhereScoreIsNull($user);
        if ($activityIds) {
            $tmp = [];
            foreach ($activityIds as $activityId) {
                $tmp[] = $activityId['activity_id'];
            }
            $activityIds = $tmp;
        }

        return $activityIds;
    }

    /**
     *
     * @param \Jihe\Entities\Activity $activity
     * @param int                      id of user
     * @param array $score params of score, keys taken:
     *                                  - score            (int)
     *                                  - score_attributes (array)
     *                                  - score_memo       (string)
     */
    public function score($activity, $user, $score)
    {
        $this->ensureScoreable($activity, $user);

        return $this->activityMemberRepository
            ->updateScore(
                $activity->getId(),
                $user,
                $score);
    }

    /**
     * ensure user can score in given activity
     *
     * @param \Jihe\Entities\Activity $activity
     * @param int                      id of user
     * @throws \Exception
     */
    private function ensureScoreable($activity, $user)
    {
        // rule#1. current day is over day of activity ended
        if (!$this->isNextDayToday($activity->getEndTime())) {
            throw new \Exception('活动结束后一天才能评分');
        }

        // rule#2. given user has not scored in given activity
        if (!$this->activityMemberRepository->exists($activity->getId(), $user)) {
            throw new \Exception('非活动成员不能评分');
        }
    }

    /**
     * whether today is next day of time now
     * @param string $time
     */
    private function isNextDayToday($time)
    {
        return date('Y-m-d') > date('Y-m-d', strtotime($time));
    }

    /**
     * check whether user scored in given activity
     * @param int $activity id of activity
     * @param int $user id of user
     */
    private function scored($activity, $user)
    {
        return $this->activityMemberRepository->scored($activity, $user);
    }

    /**
     * get current average score of activity, include:
     *                             - scored by members
     *                             - members not scored regard as 5
     *
     * @param \Jihe\Entities\Activity $activity
     * @return int
     */
    public function getAverageScore($activity)
    {
        $countMembers = $this->activityMemberRepository
            ->countMembers($activity->getId());

        if (0 == $countMembers) {
            return 0.0;
        }

        $countScoredMembers = $this->activityMemberRepository
            ->countScoredMembers($activity->getId());
        $sumScored = $this->activityMemberRepository
            ->sumScored($activity->getId());

        $totalScore = $sumScored + 5.0 * ($countMembers - $countScoredMembers);

        return floatval($this->sprintfScore($totalScore / $countMembers));
    }

    /**
     * @param floot $score
     * @return float   score that after a decimal point
     */
    private function sprintfScore($score)
    {
        return sprintf('%.1f', substr(sprintf('%.2f', $score), 0, -1));
    }

    /**
     * @param int $userId
     * @param int $teamId
     * @return array activity id list
     */
    public function getNotOverActivitiesOfUser($userId, $teamId = null)
    {
        return $this->activityMemberRepository->findNotOverActivitiesOfMember($userId, $teamId);
    }

    /**
     * @param int $userId
     * @return array activity id list
     */
    public function getUserParticipateInActivities($userId)
    {
        return $this->activityMemberRepository->findAllUserActivity($userId);
    }

    /**
     * @param int $activityId
     * @param int $page
     * @param int $size
     * @return array [count, []]
     */
    public function getActivityMemberList($activityId, $page, $size)
    {
        return $this->activityMemberRepository->getActivityMembers($activityId, $page, $size);
    }

    public function judgeActivitiesWhetherUserParticipatedIn(array $activityIds, $userId)
    {
        if (empty($activityIds)) {
            return false;
        }
        $activityIdsParticipatedIn = $this->getUserParticipateInActivities($userId);

        $result = [];
        foreach ($activityIds as $activityId) {
            $result[$activityId] = in_array($activityId, $activityIdsParticipatedIn) ? 1 : 0;
        }
        return $result;
    }

    public function getActivityMembersCount(array $activityIds)
    {
        $results = $this->activityMemberRepository->getMemberCount($activityIds);
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

    /**
     * get teams of given user ever joined activities
     *
     * @param $user  entity of user
     */
    public function getTeamsOfJoinedActivities(\Jihe\Entities\User $user)
    {
        return $this->activityMemberRepository->findTeamsOfJoinedActivities($user->getId());
    }

    /**
     * export members
     *
     * @param Activity $activity
     */
    public function exportMembers(Activity $activity)
    {
        $headers = $activity->getEnrollAttrs();
        $headers[] = '是否签到';
        $writer = ExcelWriter::fromScratch();
        $writer->writeHeader($headers);
        $page = $pages = 1;
        $size = 500;
        do {
            list($count, $members) = $this->activityMemberRepository->listActivityMembers($activity->getId(), $page, $size);
            $writer->write($members);
            if($pages <= 1){
                $pages = ceil($count/$size);
            }
            $page++;
        } while ($page < $pages);

        $writer->save();
    }
}
