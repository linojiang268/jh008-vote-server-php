<?php

namespace Jihe\Contracts\Repositories;

interface ActivityMemberRepository
{

    /**
     * @param array $member structure to Jihe\Models\ActivityMember
     *
     * @return int member id
     */
    public function add($member);

    /**
     * batch add members
     *
     * @param array $member elements are member attributes array
     *
     * @return integer affected rows
     */
    public function batchAdd(array $members);

    /**
     * mark specified activity memeber as checkin
     *
     * @param integer $userId user id
     * @param integer $activityId activity id
     *
     * @return integer affected rows
     */
    public function markAsCheckin($userId, $activityId);

    /**
     * @param int $userId
     * @param int $activityId
     *
     * @return array member info
     */
    public function get($userId, $activityId);

    /**
     * @param int $memberId
     *
     * @return true|false
     */
    public function delete($memberId);

    /**
     * @param int   $activityId
     * @param array $memberIds array of member id
     * @param int   $groupId
     *
     * @return bool true if success
     */
    public function updateGroup($activityId, array $memberIds, $groupId);

    /**
     * @param int $userId
     * @param int $activityId
     * @param int $lat
     * @param int $lng
     *
     * @return bool true if success
     */
    public function updateLocation($userId, $activityId, $lat, $lng);

    /**
     * reset all members group id
     *
     * @param int $activityId
     *
     * @return bool
     */
    public function reset($activityId);

    /**
     * reset count all members of the given activity id and group id
     *
     * @param int $activityId
     * @param int $groupId
     *
     * @return int
     */
    public function count($activityId, $groupId = null);

    /**
     * get the list of all members of the given activity id and group id ,with user basic profile
     *
     * @param int $activityId
     * @param int $groupId
     *
     * @return array
     */
    public function all($activityId, $groupId = null);

    /**
     * take some activity members
     *
     * @param int $activityId
     * @param int $howMany how many members should get
     *
     * @return array the member list
     */
    public function some($activityId, $howMany);

    /**
     * get the list of members not in the given activity id and group ids
     *
     * @param int   $activityId
     * @param array $groupIds
     *
     * @return bool
     */
    public function notIn($activityId, array $groupIds);

    /**
     * check whether given user id is the member of activity
     *
     * @param int   $activityId
     * @param array $userId
     *
     * @return bool
     */
    public function exists($activityId, $userId);

    /**
     * user score given activity
     *
     * @param int   $activity    id of activity
     * @param int   $user        id of user
     * @param array $score       array of score attrs, keys taken:
     *                           - score             (int)    activity score that user given
     *                           - score_attributes  (array)  attributes of that score
     *                           - score_memo        (string) user's memo with that score
     *
     * @return boolean
     */
    public function updateScore($activity, $user, array $score);

    /**
     * whether user has scored for given activity
     *
     * @param int $activity id of activity
     * @param int $user     id of user
     *
     * @return boolean
     */
    public function scored($activity, $user);

    /**
     * count total num of activity members
     *
     * @param int $activity id of activity
     *
     * @return int            total num of activity members
     */
    public function countMembers($activity);

    /**
     * count total num of activity members that has scored
     *
     * @param int $activity id of activity
     *
     * @return int            total num of activity members that has scored
     */
    public function countScoredMembers($activity);

    /**
     * score sum of activity that has scored
     *
     * @param int $activity id of activity
     *
     * @return int            score sum of activity that has scored
     */
    public function sumScored($activity);

    /**
     * get user score is null
     *
     * @param int $user user id
     *
     * @return mixed
     */
    public function findMemberWhereScoreIsNull($user);


    /**
     * find activities which are not over
     *
     * @param int $userId
     * @param int $teamId
     *
     * @return array activity id list
     */
    public function findNotOverActivitiesOfMember($userId, $teamId = null);

    /**
     * @param $userId
     *
     * @return array activity id list
     */
    public function findAllUserActivity($userId);

    /**
     * @param int $activityId
     * @param int $page
     * @param int $size
     *
     * @return array count & member list
     */
    public function getActivityMembers($activityId, $page, $size = 20);

    /**
     * @param array $activityIds
     *
     * @return array
     */
    public function getMemberCount(array $activityIds);

    /**
     * find teams that user ever joined activities
     *
     * @param $user   id of user
     *
     * @return mixed
     */
    public function findTeamsOfJoinedActivities($user);

    /**
     * Get user activity checkin list
     *
     * @param integer $activityId activity id
     * @param integer $type
     * @param integer $page
     * @param integer $size
     *
     * @return array    elements
     */
    public function getCheckinList($activityId, $type, $page, $size);

    /**
     * Search user checkin info by mobile or name
     *
     * @param integer $activityId activity id
     * @param string  $mobile     user mobile number
     * @param string  $name       user applicant name
     *
     * @return \Illuminate\Support\Collection   element is \Jihe\Entities\ActivityMember.
     */
    public function searchCheckinInfo($activityId, $mobile = null, $name = null);

    /**
     * check member is set
     *
     * @param int   $activityId
     * @param array $mobiles
     *
     * @return mixed
     */
    public function getActivityMembersByMobiles($activityId, Array $mobiles);

    /**
     * unset user check in
     *
     * @param int $userId
     * @param integer $activityId activity id
     *
     * @return mixed
     */
    public function unsetCheckIn($userId, $activityId);

    /**
     * get export members
     *
     * @param int $activityId
     * @param int $page
     * @param int $size
     *
     * @return mixed
     */
    public function listActivityMembers($activityId, $page, $size = 20);
}
