<?php
namespace Jihe\Contracts\Repositories;

use Jihe\Models\ActivityEnrollIncome;

interface ActivityEnrollIncomeRepository
{
    /**
     * Get all activity enrollment total income of a team
     *
     * @param int $teamId
     * @param int $page         the current page number
     * @param int $pageSize     the number of data per page
     *
     * @return array
     */
    public function findAllIncomesByTeam($teamId, $page, $pageSize);

    /**
     * Get all activity enrollment total income
     *
     * @param int $status
     * @param string $beginTime search begin time for activity applicant enroll end time
     * @param string $endTime   search end time for activity applicant enroll end time
     * @param int $page         the current page number
     * @param int $pageSize     the number of data per page
     *
     * @return array            first element is total count, second is array which
     *                          element is \Jihe\Entities\ActivityEnrollIncome
     */
    public function findAllIncomes($status, $beginTime, $endTime, $page, $pageSize);

    /**
     * increase total fee after payment successful
     *
     * @param int $activity     activity id
     */
    public function increaseTotalFeeForPaymentSuccess($activityId, $fee);

    /**
     * add one activity enroll income record into database
     *
     * @param array $income
     *
     * @retrun int      insert id
     */
    public function add(array $income);

    /**
     * find one income
     *
     * @param int $activityId
     *
     * @return \Jihe\Entities\ActivityEnrollIncome|null
     */
    public function findOneByActivityId($activityId);

    /**
     * find one by id
     *
     * @param int $id
     *
     * @return \Jihe\Entities\ActivityEnrollIncome|null
     */
    public function findOneById($id);

    /**
     * update status
     *
     * @param int $income       income id
     * @param int $status
     *
     * @return boolean
     */
    public function updateStatus($income, $status);

    /**
     * update status
     *
     * @param int $income       income id 
     * @param int $fee
     * @param string $evidence  url point to evidence
     *
     * @return boolean
     */
    public function updateConfirm($income, $fee, $evidence);

    /**
     * update by id
     *
     * @param int $income       income id
     * @param array $updates
     *
     * @return boolean
     */
    public function update($income, array $updates);

    /**
     * get activity amount
     *
     * @param array $activities
     *
     * @return array
     */
    public function countActivitiesAmount($activities);
}
