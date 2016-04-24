<?php
namespace Jihe\Contracts\Repositories;

interface ActivityRepository
{
    /**
     * get the number of activity created by given team
     * only published
     *
     * @param int $teamId team create of activity
     *
     * @return int      number of activity created
     */
    public function getTeamActivitiesCount($teamId);

    /**
     * get the number of activity created by given city
     * only published
     *
     * @param int $cityId team create of activity
     *
     * @return int      number of activity created
     */
    public function getCityActivitiesCount($cityId);

    /**
     * search team or all activity by activity time
     *
     * @param  string   $start activity begin time
     * @param  string   $end   activity end time
     * @param  int|null $team  team id
     * @param  int      $page  page number
     * @param  int      $size  page size
     *
     * @return array        activity entity array
     */
    public function searchTeamActivitiesByActivityTime($start, $end, $team, $page, $size);

    /**
     * The list of activities for the team to obtain a list of activities is
     * contained only in the event of a publication
     *
     * @param int $team team id
     * @param int $page the current page number
     * @param int $size the number of data per page
     *
     * @return array
     */
    public function findPublishedActivitiesInTeam($team, $page, $size);

    /**
     * Get team in addition to delete all of the activities
     *
     * @param int $team team id
     * @param int $page the current page number
     * @param int $size the number of data per page
     *
     * @return array
     */
    public function findActivitiesInTeam($team, $page, $size);

    /**
     * find all published activities in given city
     *
     * @param int $city city id
     * @param int $page page number
     * @param int $size page size
     *
     * @return array
     */
    public function findPublishedActivitiesInCity($city, $page, $size);

    /**
     *
     * get publish activities by name
     *
     * @param string $keyword activity name
     * @param int    $page    the current page number
     * @param int    $size    the number of data per page
     *
     * @return array
     */
    public function searchPublishedActivitiesByTitle($keyword, $page, $size);

    /**
     *
     * get one team activities by name
     *
     * @param string $keyword activity name
     * @param int    $team    team id
     * @param int    $page    the current page number
     * @param int    $size    the number of data per page
     *
     * @return array
     */
    public function searchTeamActivitiesByTitle($keyword, $team, $page, $size);

    /**
     *
     * get one city activities by name
     *
     * @param string $keyword activity name
     * @param int    $city    city id
     * @param int    $page    the current page number
     * @param int    $size    the number of data per page
     *
     * @return array
     */
    public function searchCityActivitiesByTitle($keyword, $city, $page, $size);

    /**
     * get activity entity by given id of activity
     *
     * @param int $id id of activity
     *
     * @return \Jihe\Entities\Activity
     */
    public function findActivityById($id);

    /**
     *
     * find has album activities by team
     *
     * @param int $team team id
     * @param int $page the current page number
     * @param int $size the number of data per page
     *
     * @return array
     */
    public function findActivitiesHasAlbum($team, $page, $size);

    /**
     * get activity entity by given id of published activity
     *
     * @param int $id id of activity
     *
     * @return \Jihe\Entities\Activity
     */
    public function findPublishedActivityById($id);

    /**
     * Find the end of  yesterday the activity through ID array
     *
     * @param array $ids activity id array
     *
     * @return array    ActivityEntity  array
     */
    public function findEndOfYesterdayActivitiesByIds($ids);

    /**
     * add activity
     *
     * @param array $activity                        According to the structure
     *                                               of the activity data
     *                                               table, the data is
     *                                               assigned to the
     *                                               corresponding field array
     *                                               Example：
     *                                               $activity = ['team_id' =>
     *                                               1,
     *                                               'city_id' => 1];
     *
     * @return  int   insert id
     */
    public function add($activity);

    /**
     * update activity
     *
     * @param int   $id                              activity id
     * @param array $activity                        According to the structure
     *                                               of the activity data
     *                                               table, the data is
     *                                               assigned to the
     *                                               corresponding field array
     *                                               Example：
     *                                               $activity = ['id' => 1,
     *                                               'team_id' => 1,
     *                                               'city_id' => 1];
     *
     * @return  boolean
     */
    public function updateOnce($id, $activity);

    /**
     * @param array $conditions update conditions
     * @param array $activity   update fields
     *
     * @return mixed
     */
    public function updateMultiple($conditions, $activity);

    /**
     * @param  int    $id        activity id
     * @param  string $qrCodeUrl activity qrCodeUrl
     *
     * @return Boolean
     */
    public function updateStatusPublish($id, $qrCodeUrl);

    /**
     * @param  int $id activity id
     *
     * @return Boolean
     */
    public function updateHasAlbum($id);

    /**
     * @param  int $id activity id
     *
     * @return Boolean
     */
    public function deleteActivityById($id);

    /**
     * @param  int $id activity id
     *
     * @return Boolean
     */
    public function restoreActivityById($id);

    /**
     * @param  array   $point    user gps coordinate
     * @param  integer $distance select radius distance
     * @param  int     $page     the current page number
     * @param  int     $size     the number of data per page
     *
     * @return array
     */
    public function findActivitiesByPoint($point, $distance, $page, $size);


    /**
     * @param int $id activity id
     *
     * @return true|false
     */
    public function exists($id);

    /**
     * @param string|null $keyword    search activity title keyword
     * @param bool        $tags       --0 all
     *                                --1 have a tags
     *                                --2 have no tags
     * @param bool        $status     --0 search all activities
     *                                --1 search not delete activities
     *                                --2 search delete activities
     * @param  int        $page       the current page number
     * @param  int        $size       the number of data per page
     *
     * @return array
     */
    public function searchActivityTitleByTagsAndStatus($keyword, $tags, $status, $page, $size);

    /**
     * search activities by ids
     *
     * @param array      $ids           activities id of array
     * @param null|array $orderBy       search activity order by
     * @param bool       $excludeDelete --true  search activity exclude delete
     *                                  --false search activity include delete
     *
     * @return mixed
     */
    public function findActivitiesByIds($ids, $orderBy = null, $excludeDelete = true);

    /**
     * search not end activities by ids
     *
     * @param array $ids activities id of array
     *
     * @return mixed
     */
    public function findNotEndActivitiesByIds($ids);

    /**
     * search my activities by applicant status
     *
     * @param int    $userId           login user id
     * @param string $type             --All   every one
     *                                 --NotBeginning   auditing pass and activity not beginning
     *                                 --WaitPay   no pay
     *                                 --EndOf     auditing pass and activity end of
     * @param  int   $page             the current page number
     * @param  int   $size             the number of data per page
     *
     * @return mixed
     */
    public function findUserActivities($userId, $type, $page, $size);

    /**
     * count team activity total
     *
     * @param array $teamIds    select team id
     * @param bool  $includeEnd include end activity
     *
     * @return mixed
     */
    public function getTeamsActivitiesCount($teamIds, $includeEnd);

    /**
     * @param int $days  a few days activity advance remind
     *
     * @return mixed
     */
    public function activityAdvanceRemind($days);

    /**
     * find participated in team of activities user
     *
     * @param int $teamId
     *
     * @return mixed
     */
    public function  findParticipatedInTeamOfActivitiesUser($teamId);

    /**
     * @param int $id
     *
     * @return mixed
     */
    public function find($id);

    /**
     * @param int $userId
     * @param int $endDelay
     *
     * @return mixed
     */
    public function findHomPageUserActivities($userId, $endDelay);

    /**
     * @param string $start
     * @param string $end
     * @param int $page
     * @param int $size
     *
     * @return array
     */
    public function searchEndActivityByTime($start, $end, $page, $size);
}
