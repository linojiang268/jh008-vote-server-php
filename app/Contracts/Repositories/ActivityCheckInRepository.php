<?php

namespace Jihe\Contracts\Repositories;

interface ActivityCheckInRepository {
    /**
     * @param int $activityId
     * @param int $userId
     * @return array array contains checked steps of activity
     */
    public function all($activityId,$userId);
    
    /**
     * @param $userId
     * @param $activityId
     * @param $step
     * @param $processId
     * @return int id of check in step
     */
    public function add($userId, $activityId, $step, $processId);
    
    /**
     * fetch all check in data in activity and filter by step
     *
     * @param integer $activity         activity id
     * @param integer $step             check in step
     * @param integer $page             the current page number
     * @param integer $pageSize         the number of data per page
     *
     * @return array                    [0] total record count
     *                                  [1] check in datas, element as below:
     *                                  id          integer     check in id
     *                                  user_id     integer     user id
     *                                  nick_name   string      user nick name
     *                                  mobile      string      user mobile number
     *                                  step        integer     check in step
     *                                  create_at   datetime    check in time
     */
    public function getAllByActivityAndStep($activity, $step, $page, $pageSize);

    /**
     * @param array $activities
     *
     * @return array
     */
    public function countActivityCheckIn($activities);

    /**
     * Data status update, the administrator check in data coverage for users to check in
     *
     * @param int $id
     *
     * @return mixed
     */
    public function updateProcessId($id);

    /**
     * delete check in data by id
     *
     * @param int $id
     *
     * @return mixed
     */
    public function delete($id);
}
