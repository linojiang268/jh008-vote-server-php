<?php

namespace Jihe\Contracts\Repositories;

interface ActivityApplicantRepository
{

    /**
     * @deprecated
     * @param array $info user's applicantions of activity
     *                 it's elements snhould contains the structure of Jihe\Models\ActivityApplicant
     * @return int id of this applicant info
     */
    public function addApplicantInfo(array $info);

    /**
     * @param array $applicants
     *
     * @return mixed
     */
    public function multipleAddApplicantInfo(array $applicants);

    /**
     * Persist user activity applicant info
     *
     * @param array $info
     *
     * @return \Jihe\Entities\ActivityApplicant
     */
    public function saveApplicantInfo(array $info);

    /**
     * @param int $iserId user id
     * @param int $activityId activity id
     * @return array applicant info
     */
    public function getApplicantInfo($userId, $activityId);

    /**
     * Get valid user applicant info
     *
     * @param integer $userId       applicant user id
     * @param integer $activityId   activity id which user applicant
     *
     * @return \Jihe\Entities\ActivityApplicant|null
     */
    public function getValidUserApplicant($userId, $activityId);

    /**
     * @param int $applicantId applicant id
     *
     * @return array applicant info
     */
    public function getApplicantInfoByApplicantId($applicantId);

    /**
     * @param array $applicantIds applicant set
     * @param int $activityId activity id
     * @return array applicant info
     */
    public function getApplicantInfoListByApplicantIds(array $applicantIds, $activityId);

    /**
     * @param string $orderNo
     * @return array applicant info
     */
    public function getApplicantInfoByOrderNo($orderNo);

    /**
     * @param string $orderNo
     * @param int $status Jihe\Models\ActivityApplicant::[STATUS_INVALID,STATUS_NORMAL, STATUS_PAY, STATUS_SUCESS]
     * @return true|false
     */
    public function updateApplicantStatus($orderNo, $status);

    /**
     * @param string $orderNo
     * @param string $datetime Y-m-d H:i:s
     * @return true|false
     */
    public function updateApplicantPaymentExpireTime($orderNo, $datetime);

    /**
     * @param int $activityId activity id
     * @param array $status Jihe\Models\ActivityApplicant::[STATUS_INVALID,STATUS_NORMAL, STATUS_PAY, STATUS_SUCESS]
     * @param int $page
     * @param int $size page size
     * @param string $sort 'ASC' or 'DESC'
     * @return array list of applicant info
     */
    public function getActivityApplicantsList($activityId, array $status, $page, $size, $sort = 'ASC');

    /**
     * Get applicants by id, use applicant id as pagination identifier,
     * we will fetch a list of applicants which id large than or less than
     * the id specified by user
     *
     * @param integer $activityId       activity number
     * @param int|null $applicantId     applicant id as pagination identifier
     *                                  if null, means to show first page
     * @param integer $status           applicant status
     * @param integer $size             number of applicant should be fetch
     * @param boolean $sortDesc         specify data sort type, true is DESC,
     *                                  false if ASC
     * @param boolean $isPre            specify pagination direction
     *
     * @return array            elements explain as below:
     *                          0 - count integer total number of applicants
     *                          1 - preId integer id for specifying a point 
     *                              pre page will start
     *                          2 - nextId integer id for specifying a point
     *                              next page will start
     *                          3 - applicants array each element contain
     *                              applicant entity
     */
    public function getActivityApplicantsPageById(
        $activityId, $applicantId, $status, $size, $sortDesc, $isPre
    );

    /**
     * @param int $activityId
     * @return int count
     */
    public function getNotPayCount($activityId);

    /**
     * @param array $activityIds
     * @return array
     */
    public function getApplicantsCount(array $activityIds);

    /**
     * @param int $activityId
     * @param int $howMany
     * @return array
     */
    public function getLatestApplicants($activityId, $howMany);

    /**
     * @param int $activityId
     * @param int $page
     * @param int $size page size
     * @return array
     */
    public function getApplicantsList($activityId, $page, $size);

    /**
     * @param int $userId
     * @param int $status
     * @return array
     */
    public function getUserApplicantsList($userId, $status = null);

    /**
     * get user successful applicant for specified activity
     *
     * @param integer $userId           user id
     * @param integer $activityId       activity id for user applicant
     *
     * @return array|null
     */
    public function getUserSuccessfulApplicant($userId, $activityId);

    /**
     * Update applicant status to success after payment successfully
     * only status equal STATUS_PAY be updated
     *
     * @param string $orderNo       order no
     *
     * @return boolean
     */
    public function updateStatusAfterPaymentSuccess($orderNo);

    /**
     * Recycle activity applicant
     *
     * @param integer $applicant
     * 
     * @return boolean
     */
    public function recycleActivityApplicant($applicant);

    /**
     * count activity applicants
     *
     * @param array $activities activity id
     *
     * @return mixed
     */
    public function countActivityApplicant($activities);

    /**
     * find teams that user ever requested activities
     *
     * @param $user   id of user
     * @return mixed
     */
    public function findTeamsOfRequestedActivities($user);

    /**
     * get a number of user applicants which status is auditing
     *
     * @param integer $activityId   activity id
     * @param array $applicantIds   elements are activity applicant id
     *
     * @return array                elements are \Jihe\Entities\ActivityApplicant
     */
    public function getAuditApplicants($activityId, array $applicantIds);

    /**
     * Change applicants status to wait for payment, and set payment
     * expire time
     *
     * @param array $applicantIds   elements are applicant id
     * @param \DateTime $expireAt   applicant expire time
     *
     * @return integer affected rows
     */
    public function approveToPay(array $applicantIds, \DateTime $expireAt);

    /**
     * Change applicants status to success
     *
     * @param array $applicantIds   elements are applicant id
     *
     * @return integer affected rows
     */
    public function approveToSuccess(array $applicantIds);

    /**
     * Change applicants status to invalid
     *
     * @param array $applicantIds   elements are applicant id
     *
     * @return integer affected rows
     */
    public function refuse(array $applicantIds);

    /**
     * Remark activity applicant
     *
     * @param integer $activityId
     * @param integer $applicantId
     * @param string $content
     *
     * @return integer affected rows
     */
    public function remark($activityId, $applicantId, $content);

    /**
     * check user applicants
     *
     * @param int     $activityId
     * @param array $mobiles
     *
     * @return mixed
     */
    public function getActivityApplicantsByMobiles($activityId, Array $mobiles);
}
