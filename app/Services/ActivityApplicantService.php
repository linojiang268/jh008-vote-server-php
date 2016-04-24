<?php

namespace Jihe\Services;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Jihe\Contracts\Repositories\ActivityApplicantRepository;
use Jihe\Contracts\Repositories\ActivityMemberRepository;
use Jihe\Contracts\Repositories\UserRepository;
use Jihe\Dispatches\DispatchesMessage;
use Jihe\Entities\Activity;
use Jihe\Entities\User;
use Jihe\Entities\ActivityApplicant as ActivityApplicantEntity;
use Jihe\Events\UserApplicantActivityEvent;
use Jihe\Exceptions\Team\UserNotTeamMemberException;
use Jihe\Models\ActivityApplicant;
use Jihe\Models\ActivityMember;
use Jihe\Utils\PushTemplate;
use Jihe\Utils\SmsTemplate;
use Jihe\Services\UserService;
use Jihe\Services\ActivityService;
use Jihe\Services\VerificationService;
use DB;

class ActivityApplicantService
{
    use DispatchesJobs, DispatchesMessage;

    const PAYMENT_INTERVAL = 1800;
    const ATTR_MOBILE = '手机号';
    const ATTR_NAME = '姓名';

    const CHANNEL_APP = 0;
    const CHANNEL_WEB = 1;

    private $activityApplicantRepository;
    private $activityService;
    private $activityMemberRepository;
    private $teamMemberService;
    private $userAttributeService;
    private $userRepository;
    private $userService;
    private $verificationService;

    public function __construct(ActivityApplicantRepository $activityApplicantRepository,
                                ActivityMemberRepository $activityMemberRepository,
                                UserRepository $userRepository,
                                ActivityService $activityService,
                                TeamMemberService $teamMemberService,
                                UserAttributeService $userAttributeService,
                                UserService $userService,
                                VerificationService $verificationService
    ) {
        $this->activityApplicantRepository = $activityApplicantRepository;
        $this->activityService = $activityService;
        $this->activityMemberRepository = $activityMemberRepository;
        $this->teamMemberService = $teamMemberService;
        $this->userAttributeService = $userAttributeService;
        $this->userRepository = $userRepository;
        $this->userService = $userService;
        $this->verificationService = $verificationService;
    }

    private function hasPermission($creatorId, $activityId)
    {
        return $this->activityService->checkActivityOwner($activityId, $creatorId);
    }

    /**
     * Make user applicant successful after user payment successful
     *
     * @param string $orderNo order#, identify a order
     *
     * @return null
     *
     * @throw \Exception
     */
    public function onActivityApplicantPaymentSuccess($orderNo)
    {
        $applicantInfo = $this->activityApplicantRepository->getApplicantInfoByOrderNo($orderNo);
        if (empty($applicantInfo)) {
            throw new \Exception('未查询到报名信息');
        }

        if ($this->activityApplicantRepository
            ->updateStatusAfterPaymentSuccess($orderNo)
        ) {
            $member['activity_id'] = $applicantInfo['activity_id'];
            $member['user_id'] = $applicantInfo['user_id'];
            $member['name'] = $applicantInfo['name'];
            $member['mobile'] = $applicantInfo['mobile'];
            $member['attrs'] = $applicantInfo['attrs'];
            $member['group_id'] = ActivityMember::UNGROUPED;
            $member['role'] = ActivityMember::ROLE_NORMAL;

            $this->activityMemberRepository->add($member);
        }
    }

    public function getApplicantInfoForPayment($orderNo)
    {
        $applicantInfo = $this->activityApplicantRepository->getApplicantInfoByOrderNo($orderNo);
        if (empty($applicantInfo)) {
            throw new \Exception('未查询到报名信息');
        }

        if ($applicantInfo['status'] == ActivityApplicant::STATUS_INVALID) {
            throw new \Exception('报名信息已失效');
        } elseif ($applicantInfo['status'] == ActivityApplicant::STATUS_SUCCESS) {
            throw new \Exception('已经报名成功,无需缴费');
        } elseif ($applicantInfo['status'] != ActivityApplicant::STATUS_PAY) {
            throw new \Exception('状态异常');
        }

        $activity = $this->activityService->getPublishedActivityById($applicantInfo['activity_id']);
        if (!$activity) {
            throw new \Exception('该活动不存在或已下架');
        }

        $applicantInfo['fee'] = $activity->getEnrollFee();
        $applicantInfo['activity_title'] = $activity->getTitle();
        return $applicantInfo;
    }

    public function getUserApplicantInfo($userId, $activityId)
    {
        return $this->activityApplicantRepository->getApplicantInfo($userId, $activityId);
    }

    //=====================================
    //      Process applicant action begin
    //=====================================
    /**
     * Do user applicant for a specified activity
     *
     * @param integer|null $userId      we should fetch mobile from applicant
     *                                  attrs, and query user id by mobile
     * @param integer $activity         activity id
     * @param string $attrs             json string, contain user applicant info
     * @param integer $channel          indicate user applicant channel
     *
     * @param \Jihe\Entities\ActivityApplicant.
     */
    public function doApplicant($userId, $activity, $attrs, $channel)
    {
        $channel = (int) $channel;
        $activity = $this->activityService
                         ->getPublishedActivityById($activity);
        if ( ! $activity) {
            throw new \Exception('活动不存在或已下架');
        }

        // Check attr
        list($mobile, $name, $attrs) = $this
            ->checkAndParseApplicantAttrs($activity, $attrs, $channel);

        // Set userId use attrs if $userId is null
        $newUserPassword = null;
        if ( ! $userId) {
            list($userId, $newUserPassword) = $this->userService
                ->fetchUserOrRegisterIfUserNotExists($mobile);
        } else {
            // Replace mobile passed in with user's mobile
            $mobile = $this->userService->findUserById($userId)->getMobile();
        }

        // Check whether user can do applicants
        $this->ensureUserCanApplicant($userId, $activity, $channel);

        // Do applicant
        $applicant = $this->handleApplicant(
            $userId, $activity, $mobile, $name, $attrs, $channel
        );

        // fire applicant event to recycle applicant status
        if ($applicant->getStatus() != ActivityApplicantEntity::STATUS_SUCCESS) {
            event(new UserApplicantActivityEvent($applicant->getId()));
        }

        // Send message
        $this->notifyUserAfterApplicant($applicant, $newUserPassword);

        return $applicant;
    }

    /**
     * Do user applicant for a specified activity from web
     *
     * @param integer $activity         activity id
     * @param string $attrs             json string, contain user applicant info
     * @param string $code              verify code
     *
     * @param \Jihe\Entities\ActivityApplicant.
     */
    public function doApplicantFromWeb($activity, $attrs, $code)
    {
        $channel = ActivityApplicantEntity::CHANNEL_WEB;
        $activity = $this->activityService
                         ->getPublishedActivityById($activity);
        if ( ! $activity) {
            throw new \Exception('活动不存在或已下架');
        }

        // Check attr
        list($mobile, $name, $attrs) = $this
            ->checkAndParseApplicantAttrs($activity, $attrs, $channel);

        $this->verificationService->verify($mobile, $code);

        // Set userId use attrs if $userId is null
        $newUserPassword = null;
        list($userId, $newUserPassword) = $this->userService
            ->fetchUserOrRegisterIfUserNotExists($mobile);

        // Check whether user can do applicants
        $this->ensureUserCanApplicant($userId, $activity, $channel);

        // Do applicant
        $applicant = $this->handleApplicant(
            $userId, $activity, $mobile, $name, $attrs, $channel
        );

        // fire applicant event to recycle applicant status
        if ($applicant->getStatus() != ActivityApplicantEntity::STATUS_SUCCESS) {
            event(new UserApplicantActivityEvent($applicant->getId()));
        }

        // Send message
        $this->notifyUserAfterApplicant($applicant, $newUserPassword);

        return $applicant;
    }

    /**
     * Store user applicant info
     *
     * @param integer $userId
     * @param \Jihe\Entities\Activity $activity
     * @param string $mobile                        user applicant mobile
     * @param string $name                          user applicant name
     * @param array $attrs                          user applicant attributes
     * @param integer $channel                      user applicant channel
     *
     * @return \Jihe\Entities\ActivityApplicant.
     */
    private function handleApplicant(
        $userId, Activity $activity, $mobile, $name, array $attrs, $channel
    ) {
        $applicantData = [
            'name'          => $name,
            'mobile'        => $mobile,
            'attrs'         => json_encode($attrs),
            'activity_id'   => $activity->getId(),
            'user_id'       => $userId,
            'channel'       => $channel,
        ];

        if (in_array($channel, [
            ActivityApplicantEntity::CHANNEL_SINGLE_VIP,
            ActivityApplicantEntity::CHANNEL_BATCH_VIP,
        ])) {
            $applicantData['status'] = ActivityApplicantEntity::STATUS_SUCCESS;
        } elseif ($activity->getAuditing() == Activity::AUDITING) {
            $applicantData['status'] = ActivityApplicantEntity::STATUS_AUDITING;
        } else {
            if ($activity->getEnrollFeeType() == Activity::ENROLL_FEE_TYPE_PAY) {
                $applicantData['status'] = ActivityApplicantEntity::STATUS_PAY;
                $applicantData['expire_at'] = date(
                    'Y-m-d H:i:s', time() + self::PAYMENT_INTERVAL);
            } else {
                $applicantData['status'] = ActivityApplicantEntity::STATUS_SUCCESS;
            }
        }

        $applicant = $this->activityApplicantRepository
            ->saveApplicantInfo($applicantData);
        if ($applicantData['status'] == ActivityApplicantEntity::STATUS_SUCCESS) {
            $member = $applicantData;
            $member['group'] = 0;
            $member['role'] = 0;
            // TODO Add Channel to member
            $this->activityMemberRepository->add($member);
        }
        $applicant->setActivity($activity);

        return $applicant;
    }

    /**
     * Check user applicant attrs json string, then return valid attrs array
     *
     * @param \Jihe\Entities\Activity $activity
     * @param string $attrs     json string, user applicant attrs, the format
     *                          after decode as below:
     *                          [
     *                              {
     *                                  'key'   => '姓名',
     *                                  'value' => '张三',
     *                              },
     *                              ...
     *                          ]
     * @param integer $channel  user applicant channel
     *
     * @return array            0 - mobile
     *                          1 - attrs array key is applicant attribute name,
     *                              value is attribute value filled by user
     * @throw \Exception        exception will be thrown if attr format error
     */
    private function checkAndParseApplicantAttrs($activity, $attrs, $channel)
    {
        $attrs = json_decode($attrs, true);
        if ( ! $attrs) {
            throw new \Exception('报名信息错误');
        }
        // Contain all attrs except for mobile and name
        $filteredAttrs = [];
        $filledApplicantFields = [];
        foreach ($attrs as $attr) {
            if ($attr['key'] == ActivityApplicantEntity::ATTR_MOBILE) {
                $mobile = $attr['value'];
            } elseif ($attr['key'] == ActivityApplicantEntity::ATTR_NAME) {
                $name = $attr['value'];
            } else {
                $filteredAttrs[] = $attr;
            }
            $filledApplicantFields[] = $attr['key'];
        }
        if (empty($mobile) || ! is_numeric($mobile) || strlen($mobile) != 11) {
            throw new \Exception('手机号格式错误');
        }
        if (empty($name)) {
            throw new \Exception('姓名未填写');
        }

        // Check whether all applicant attrs aready be filled
        // Only applicant from user should check activity enroll time
        if ($activity->getEnrollAttrs() != null && in_array($channel, [
            ActivityApplicantEntity::CHANNEL_APP,
            ActivityApplicantEntity::CHANNEL_WEB,
        ])) {
            foreach ($activity->getEnrollAttrs() as $applicantField) {
                if ( ! in_array($applicantField, $filledApplicantFields)) {
                    throw new \Exception("\"{$applicantField}\"未填写");
                }
            }
        }

        return [$mobile, $name, $filteredAttrs];
    }

    /**
     * Ensure user have privlige to applicant activity
     *
     * @param integer $userId
     * @param \Jihe\Entities\Activity   $activity
     * @param integer $channel          user applicant channel
     */
    private function ensureUserCanApplicant($userId, $activity, $channel)
    {
        // Check activity limitaion
        // Only applicant from user should check activity enroll time
        if (in_array($channel, [
            ActivityApplicantEntity::CHANNEL_APP,
            ActivityApplicantEntity::CHANNEL_WEB,
        ])) {
            if (strtotime($activity->getEnrollBeginTime()) > time()) {
                throw new \Exception('该活动尚未开始报名');
            }
            if (strtotime($activity->getEnrollEndTime()) < time()) {
                throw new \Exception('该活动已经报名结束');
            }

            // member count limitation
            if ($activity->getEnrollLimit() > 0) {
                $currentMembers = $this
                    ->activityMemberRepository->count($activity->getId());
                if ($currentMembers >= $activity->getEnrollLimit()) {
                    throw new \Exception('该活动已经满员');
                }

                if ($activity->getEnrollFeeType() == Activity::ENROLL_FEE_TYPE_PAY) {
                    $waitingPayCount = $this->activityApplicantRepository
                        ->getNotPayCount($activity->getId());
                    if (($currentMembers + $waitingPayCount) > $activity->getEnrollLimit()) {
                        throw new \Exception('该活动已经满员');
                    }
                }
            }

            // privilege limitation
            if ($activity->getEnrollType() == Activity::ENROLL_TEAM) {
                if ( ! $this->teamMemberService->enrolled(
                    $userId, $activity->getTeam()->getId()
                )) {
                    throw new UserNotTeamMemberException(
                        '该活动仅限该社团成员参加'
                    );
                }
            } elseif ($activity->getEnrollType() == Activity::ENROLL_PRIVATE) {
                throw new \Exception('私密人员尚未开放');
            }
        }

        // Check pending applicant record
        $history = $this->activityApplicantRepository
                        ->getValidUserApplicant($userId, $activity->getId());
        if ($history) {
            switch ($history->getStatus()) {
                case ActivityApplicantEntity::STATUS_AUDITING :
                    throw new \Exception('您的报名申请在审核中，请耐心等待');
                case ActivityApplicantEntity::STATUS_PAY:
                    if ($history->getExpireAt()->getTimestamp() > time()) {
                        throw new \Exception('您已报名，请前去完成支付');
                    }
                    break;
                case ActivityApplicantEntity::STATUS_SUCCESS :
                    throw new \Exception('您已报名，请勿重复报名');
            }
        }
    }

    /**
     * Send notification to applicant user
     *
     * @param \Jihe\Entities\ActivityApplicant $applicant
     * @param string $newUserPassword
     */
    private function notifyUserAfterApplicant(
        ActivityApplicantEntity $applicant,
        $newUserPassword
    ) {
        switch ($applicant->getChannel()) {
            case ActivityApplicantEntity::CHANNEL_WEB :
                $this->notifyUserAfterApplicantFromWeb($applicant, $newUserPassword);
                break;
        }
    }

    private function notifyUserAfterApplicantFromWeb(
        ActivityApplicantEntity $applicant,
        $newUserPassword
    ) {
        $mobile = $applicant->getMobile();
        $applicantStatus = $applicant->getStatus();
        $newUserAccountMsg = $newUserPassword ? SmsTemplate::generalMessage(
            SmsTemplate::USER_REGISTED_SUCCESSFUL_FROM_WEB,
            substr($mobile, 0, 3), substr($mobile, -4), $newUserPassword
        ) : '';

        $content = '';
        switch ($applicant->getStatus()) {
            case ActivityApplicantEntity::STATUS_AUDITING :
                $content = SmsTemplate::generalMessage(
                    SmsTemplate::ACTIVITY_MEMBER_ENROLLMENT_REQUEST_PENDING_FORM_WEB,
                    $newUserAccountMsg
                );
                break;
            case ActivityApplicantEntity::STATUS_PAY :
                $content = SmsTemplate::generalMessage(
                    SmsTemplate::ACTIVITY_MEMBER_ENROLLMENT_REQUEST_WAITING_FOR_PAYMENT_FORM_WEB,
                    $newUserAccountMsg
                );
                break;
            case ActivityApplicantEntity::STATUS_SUCCESS :
                $content = SmsTemplate::generalMessage(
                    SmsTemplate::ACTIVITY_MEMBER_ENROLLED_SUCCESSFUL_FORM_WEB,
                    $applicant->getActivity()->getTitle(), $newUserAccountMsg
                );
                break;
        }
        if ( ! $content) {
            return;
        }
        $this->sendToUsers([$mobile], ['content' => $content], ['sms' => true]);
    }

    //=====================================
    //      Process applicant action end
    //=====================================

    public function approveApplicant($creatorId, $applicantId, $activityId)
    {
        if (!$this->hasPermission($creatorId, $activityId)) {
            throw new \Exception('你没有权限进行此操作');
        }

        $activity = $this->activityService->getPublishedActivityById($activityId);
        if (!$activity) {
            throw new \Exception('该活动不存在');
        }

        if ($activity->getAuditing() != Activity::AUDITING) {
            throw new \Exception('不需审核的活动无法进行此操作');
        }

        $applicantInfo = $this->activityApplicantRepository->getApplicantInfoByApplicantId($applicantId);
        if (empty($applicantInfo)) {
            throw new \Exception('未查询到相关报名信息');
        }

        if (!in_array($applicantInfo['status'], [ActivityApplicant::STATUS_INVALID, ActivityApplicant::STATUS_NORMAL, ActivityApplicant::STATUS_AUDITING,
            ActivityApplicant::STATUS_SUCCESS, ActivityApplicant::STATUS_PAY])
        ) {
            throw new \Exception('未知的报名状态');
        }

        if ($applicantInfo['status'] == ActivityApplicant::STATUS_AUDITING) {
            if ($activity->getEnrollFeeType() == Activity::ENROLL_FEE_TYPE_PAY) {
                $datetime = $activity->getBeginTime();
                $this->activityApplicantRepository->updateApplicantPaymentExpireTime($applicantInfo['order_no'], $datetime);
                $this->activityApplicantRepository->updateApplicantStatus($applicantInfo['order_no'], ActivityApplicant::STATUS_PAY);

                return ['user_id' => $applicantInfo['user_id'], 'activity_title' => $activity->getTitle(), 'status' => ActivityApplicant::STATUS_PAY, 'mobile' => $applicantInfo['mobile'], 'channel' => $applicantInfo['channel']];
            } else {
                if ($activity->getEnrollLimit() > 0) {
                    $currentMembers = $this->activityMemberRepository->count($activityId);
                    if ($currentMembers >= $activity->getEnrollLimit()) {
                        throw new \Exception('该活动已经满员');
                    }
                }

                if ($this->activityApplicantRepository->updateApplicantStatus($applicantInfo['order_no'], ActivityApplicant::STATUS_SUCCESS)) {
                    $member['activity_id'] = $applicantInfo['activity_id'];
                    $member['user_id'] = $applicantInfo['user_id'];
                    $member['name'] = $applicantInfo['name'];
                    $member['mobile'] = $applicantInfo['mobile'];
                    $member['attrs'] = $applicantInfo['attrs'];
                    $member['group_id'] = ActivityMember::UNGROUPED;
                    $member['role'] = ActivityMember::ROLE_NORMAL;
                    if (!$this->activityMemberRepository->add($member)) {
                        $this->activityApplicantRepository->updateApplicantStatus($applicantInfo['order_no'], ActivityApplicant::STATUS_AUDITING);
                        throw new \Exception('添加到活动成员列表失败');
                    }

                    return ['user_id' => $applicantInfo['user_id'], 'activity_title' => $activity->getTitle(), 'status' => ActivityApplicant::STATUS_SUCCESS, 'mobile' => $applicantInfo['mobile'], 'channel' => $applicantInfo['channel']];
                }
            }
        } else {
            throw new \Exception('报名状态:' . ActivityApplicant::parseStatusToString($applicantInfo['status']) . ',无法进行此操作');
        }
        return false;
    }

    /**
     * Batch approve applicants
     *
     * @param integer $teamCreatorId    team creator user id
     * @param integer $activityId       activity id
     * @param array   $applicantIds     elements are activity applicant id
     *
     * @return void
     */
    public function approveApplicants($teamCreatorId, $activityId, array $applicantIds)
    {
        // logic check
        list($activity, $applicants) = $this->auditApplicantCheck(
            $teamCreatorId, $activityId, $applicantIds
        );

        // if nothing be found, do nothing
        if ( ! $applicants) {
            return;
        }

        // prepare data
        $needPay = $activity->getEnrollFeeType() == Activity::ENROLL_FEE_TYPE_PAY;
        $applicantIds = [];
        $mobiles = [];
        $members = [];
        foreach ($applicants as $applicant) {
            $applicantIds[] = $applicant->getId();
            $channel = $applicant->getChannel();
            if ( ! $needPay) {
                $members[] = [
                    'activity_id'   => $applicant->getActivity()->getId(),
                    'user_id'       => $applicant->getUser()->getId(),
                    'name'          => $applicant->getName(),
                    'mobile'        => $applicant->getMobile(),
                    'attrs'         => json_encode($applicant->getAttrs()),
                    'group_id'      => ActivityMember::UNGROUPED,
                    'role'          => ActivityMember::ROLE_NORMAL,
                ];
            }

            $mobiles[$channel]['mobiles'][] = $applicant->getMobile();
            if ( ! isset($mobiles[$channel]['content'])) {
                $mobiles[$channel]['content'] = $this->genApproveSmsContent(
                    $activity->getTitle(), $needPay, $channel
                );
            }
        }

        DB::transaction(function () use (
            $needPay, $applicantIds, $activity, $members
        ) {
            // update applicant status
            if ($needPay) {
                $this->activityApplicantRepository
                     ->approveToPay(
                        $applicantIds,
                        new \DateTime($activity->getBeginTime()));
            } else {
                $this->activityApplicantRepository
                     ->approveToSuccess($applicantIds);
            }
            // add member
            if ($members) {
                $this->activityMemberRepository->batchAdd($members);
            }
        });

        // send message to users
        foreach ($mobiles as $channel => $details) {
            $receivers = $details['mobiles'];
            $content = $details['content'];
            $this->sendToUsers(
                $details['mobiles'], ['content' => $details['content']],
                ['sms' => true]);
        }
    }

    /**
     * Batch refuse applicants
     *
     * @param integer $teamCreatorId    team creator user id
     * @param integer $activityId       activity id
     * @param array   $applicantIds     elements are activity applicant id
     *
     * @return void
     */
    public function refuseApplicants($teamCreatorId, $activityId, array $applicantIds)
    {
        // logic check
        list($activity, $applicants) = $this->auditApplicantCheck(
            $teamCreatorId, $activityId, $applicantIds, false
        );

        // if nothing be found, do nothing
        if ( ! $applicants) {
            return;
        }

        // prepare data
        $applicantIds = [];
        $mobiles = [];
        foreach ($applicants as $applicant) {
            $applicantIds[] = $applicant->getId();
            $mobiles[] = $applicant->getMobile();
        }

        // update status
        $this->activityApplicantRepository->refuse($applicantIds);

        // send message to users
        $content = SmsTemplate::generalMessage(
            SmsTemplate::ACTIVITY_MEMBER_ENROLLEDMENT_REQUEST_REJECTED,
            $activity->getTitle()
        );
        $this->sendToUsers(
            $mobiles, ['content' => $content], ['sms' => true]
        );
    }

    private function genApproveSmsContent($activityTitle, $needPay, $channel)
    {
        if ($needPay && $channel == ActivityApplicantEntity::CHANNEL_APP) {
            return SmsTemplate::generalMessage(
                SmsTemplate::ACTIVITY_MEMBER_ENROLLED_SUCCESSFUL_AND_WAITINT_FOR_PAYMENT,
                $activityTitle
            );
        }

        if ($needPay && $channel == ActivityApplicantEntity::CHANNEL_WEB) {
            return SmsTemplate::generalMessage(
                SmsTemplate::ACTIVITY_MEMBER_ENROLLED_SUCCESSFUL_AND_WAITINT_FOR_PAYMENT_FORM_WEB,
                $activityTitle
            );
        }

        if ( ! $needPay && $channel == ActivityApplicantEntity::CHANNEL_APP) {
            return SmsTemplate::generalMessage(
                SmsTemplate::ACTIVITY_MEMBER_ENROLLMENT_REQUEST_APPROVED_AND_ENROLLED_SUCCESSFUL,
                $activityTitle
            );
        }

        if ( ! $needPay && $channel == ActivityApplicantEntity::CHANNEL_WEB) {
            return SmsTemplate::generalMessage(
                SmsTemplate::ACTIVITY_MEMBER_ENROLLMENT_REQUEST_APPROVED_AND_ENROLLED_SUCCESSFUL_FORM_WEB,
                $activityTitle
            );
        }
    }

    private function auditApplicantCheck(
        $teamCreatorId, $activityId, array $applicantIds, $isApprove = true
    ) {
        if ( ! $this->hasPermission($teamCreatorId, $activityId)) {
            throw new \Exception('你没有权限进行此操作');
        }

        $activity = $this->activityService->getPublishedActivityById($activityId);
        if ( ! $activity) {
            throw new \Exception('该活动不存在');
        }

        if ($activity->getAuditing() != Activity::AUDITING) {
            throw new \Exception('不需审核的活动无法进行此操作');
        }

        $applicants = $this->activityApplicantRepository
            ->getAuditApplicants($activityId, $applicantIds);

        if ($isApprove && $activity->getEnrollLimit() > 0) {
            $currentMembers = $this->activityMemberRepository->count($activityId);
            $validCount = $activity->getEnrollLimit() - $currentMembers;
            if ($validCount <= 0) {
                throw new \Exception('该活动已经满员');
            }
            if ($validCount < count($applicants)) {
                throw new \Exception(sprintf(
                    '该活动还剩%d人满员，您当前选择了%d人',
                    $validCount, count($applicants)
                ));
            }
        }

        return [$activity, $applicants];
    }

    public function remark(
        $teamCreatorId, $activityId, $applicantId, $content
    ) {
        if ( ! $this->hasPermission($teamCreatorId, $activityId)) {
            throw new \Exception('你没有权限进行此操作');
        }

        $this->activityApplicantRepository->remark(
            $activityId, $applicantId, $content
        );
    }

    public function refuseApplicant($creatorId, $applicantId, $activityId)
    {
        if (!$this->hasPermission($creatorId, $activityId)) {
            throw new \Exception('你没有权限进行此操作');
        }

        $activity = $this->activityService->getPublishedActivityById($activityId);
        if (!$activity) {
            throw new \Exception('该活动不存在');
        }

        if ($activity->getAuditing() == Activity::AUDITING) {
            if (strtotime($activity->getEnrollEndTime()) > time()) {
                //throw new \Exception('该活动报名尚未结束，无法进行此操作');
            }
        } else {
            throw new \Exception('不需审核的活动无法进行此操作');
        }

        $applicantInfo = $this->activityApplicantRepository->getApplicantInfoByApplicantId($applicantId);
        if (empty($applicantInfo)) {
            throw new \Exception('未查询到相关报名信息');
        }

        if (!in_array($applicantInfo['status'], [ActivityApplicant::STATUS_INVALID, ActivityApplicant::STATUS_NORMAL, ActivityApplicant::STATUS_AUDITING,
            ActivityApplicant::STATUS_SUCCESS, ActivityApplicant::STATUS_PAY])
        ) {
            throw new \Exception('未知的报名状态');
        }

        if ($applicantInfo['status'] == ActivityApplicant::STATUS_AUDITING) {
            $this->activityApplicantRepository->updateApplicantStatus($applicantInfo['order_no'], ActivityApplicant::STATUS_INVALID);
            return ['activity_title' => $activity->getTitle(), 'status' => ActivityApplicant::STATUS_INVALID, 'mobile' => $applicantInfo['mobile'], 'channel' => $applicantInfo['channel']];
        } else {
            throw new \Exception('报名状态:' . ActivityApplicant::parseStatusToString($applicantInfo['status']) . ',无法进行此操作');
        }
        return false;
    }

    public function setPaymentExpireTime($creatorId, $applicantIds, $activityId)
    {
        if (!$this->hasPermission($creatorId, $activityId)) {
            throw new \Exception('你没有权限进行此操作');
        }

        $activity = $this->activityService->getPublishedActivityById($activityId);
        if (!$activity) {
            throw new \Exception('该活动不存在');
        }

        if ($activity->getAuditing() == Activity::AUDITING) {
            if (strtotime($activity->getEnrollEndTime()) > time()) {
                //throw new \Exception('该活动报名尚未结束，无法进行此操作');
            }
        } else {
            throw new \Exception('不需审核的活动无法进行此操作');
        }

        $applicantInfoList = $this->activityApplicantRepository->getApplicantInfoListByApplicantIds($applicantIds, $activityId);
        if (empty($applicantInfoList)) {
            throw new \Exception('未查询到相关报名信息');
        }

        foreach ($applicantInfoList as $applicantInfo) {
            if ($applicantInfo['status'] != ActivityApplicant::STATUS_PAY) {
                throw new \Exception("[{$applicantInfo['id']}]支付状态错误");
            }
        }

        $datetime = date('Y-m-d H:i:s', time() + self::PAYMENT_INTERVAL);
        foreach ($applicantInfoList as $applicantInfo) {
            if (empty($applicantInfo['expire_at'])) {
                $this->activityApplicantRepository->updateApplicantPaymentExpireTime($applicantInfo['order_no'], $datetime);
            } else {
                $applicantInfo['expire_at'] = $datetime;
                unset($applicantInfo['id']);
                unset($applicantInfo['created_at']);
                unset($applicantInfo['updated_at']);
                $this->activityApplicantRepository->addApplicantInfo($applicantInfo);
            }
        }

        return true;
    }

    public function getActivityApplicantsCount(array $activityIds)
    {
        return $this->activityApplicantRepository->getApplicantsCount($activityIds);
    }

    public function getLatestApplicants($activityId, $howMany)
    {
        return $this->activityApplicantRepository->getLatestApplicants($activityId, $howMany);
    }

    public function getApplicantsListByStatus($activityId, array $status, $page, $size, $sort = 'ASC')
    {
        return $this->activityApplicantRepository->getActivityApplicantsList($activityId, $status, $page, $size, $sort);
    }

    /**
     * Get applicants by id, use applicant id as pagination identifier,
     * we will fetch a list of applicants which id large than or less than
     * the id specified by user
     *
     * @param integer $activityId       activity number
     * @param integer $applicantId      applicant id as pagination identifier
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
     *                              applicant data
     */
    public function getApplicantsListById(
        $activityId, $applicantId, $status, $size, $sortDesc, $isPre
    ) {
        return $this->activityApplicantRepository
                    ->getActivityApplicantsPageById(
                        $activityId, $applicantId, $status, $size,
                        $sortDesc, $isPre
                    );
    }

    /**
     * get user activity applicant status
     *
     * @param type $userId
     * @param type $activityId
     *
     * @return type
     */
    public function getUserApplicantStatus($userId, $activityId)
    {
        $info = $this->getUserApplicantInfo($userId, $activityId);
        if (empty($info)) {
            return ActivityApplicant::STATUS_NORMAL;
        }

        if ($info['status'] == ActivityApplicant::STATUS_PAY && strtotime($info['expire_at']) <= time()) {
            return ActivityApplicant::STATUS_PAY_EXPIRED;
        }
        return $info['status'];
    }

    public function getUserAuditingActivities($userId)
    {
        return array_map(function ($applicant) {
            return $applicant['activity_id'];
        }, $this->activityApplicantRepository->getUserApplicantsList($userId, ActivityApplicant::STATUS_AUDITING));
    }

    public function getApplicantInfoForPaymentByActivityId($userId, $activityId)
    {
        $applicantInfo = $this->activityApplicantRepository->getApplicantInfo($userId, $activityId);
        if (empty($applicantInfo)) {
            throw new \Exception('未查询到报名信息');
        }

        if ($applicantInfo['status'] == ActivityApplicant::STATUS_INVALID) {
            throw new \Exception('报名信息已失效');
        } elseif ($applicantInfo['status'] == ActivityApplicant::STATUS_SUCCESS) {
            throw new \Exception('已经报名成功,无需缴费');
        } elseif ($applicantInfo['status'] == ActivityApplicant::STATUS_PAY) {
            if (strtotime($applicantInfo['expire_at']) <= time()) {
                throw new \Exception('支付已过期，请重新报名');
            }
        } else {
            throw new \Exception('状态异常');
        }

        $activity = $this->activityService->getPublishedActivityById($applicantInfo['activity_id']);
        if (!$activity) {
            throw new \Exception('该活动不存在或已下架');
        }

        $applicantInfo['fee'] = number_format($activity->getEnrollFee() / 100, 2);
        $applicantInfo['activity_title'] = $activity->getTitle();
        return $applicantInfo;
    }

    /**
     * Recycle activity applicant status, expired applicant or activity aready started
     *
     * @param integer $applicantId applicant id
     *
     * @return boolean
     *
     * @throw \Exception
     */
    public function recycleApplicantStatus($applicantId)
    {
        $applicant = $this->activityApplicantRepository
            ->getApplicantInfoByApplicantId($applicantId);
        if (!$applicant) {
            throw new \Exception('报名不存在，无法回收');
        }

        $activity = $this->activityService->getPublishedActivityById(
            $applicant['activity_id']
        );

        if (!$activity) {
            throw new \Exception('该活动不存在，无法回收');
        }
        $activityBegintTime = strtotime($activity->getBeginTime());

        if ($applicant['expire_at']) {
            $expireTime = strtotime($applicant['expire_at']);
            if (min([$activityBegintTime, $expireTime]) > time()) {
                throw new \Exception('报名尚未过期，无法回收');
            };
        } else {
            if ($activityBegintTime > time()) {
                throw new \Exception('活动尚未开始，无法回收报名');
            }
        }

        return $this->activityApplicantRepository
            ->recycleActivityApplicant($applicantId);
    }

    /**
     * Get user latest activity applicant info in specified activity
     *
     * @param integer $activityId
     * @param string $mobile        user mobile number
     *
     * @return array|null           user latest applicant info
     */
    public function getUserLatestApplicantInfo($activityId, $mobile)
    {
        $user = $this->userRepository->findId($mobile);
        if ( ! $user) {
            return null;
        }

        return $this->activityApplicantRepository
                    ->getApplicantInfo($user, $activityId);
    }

    /**
     * get teams of given user ever requested activities
     *
     * @param $user  entity of user
     */
    public function getTeamsOfRequestedActivities(\Jihe\Entities\User $user)
    {
        return $this->activityApplicantRepository->findTeamsOfRequestedActivities($user->getId());
    }
}
