<?php
namespace Jihe\Services;

use Illuminate\Foundation\Bus\DispatchesJobs;

use Jihe\Contracts\Repositories\VerificationRepository;
use Jihe\Contracts\Repositories\UserRepository;
use Jihe\Contracts\Repositories\ActivityMemberRepository;
use Jihe\Entities\Verification as VerificationEntity;
use Jihe\Dispatches\DispatchesMessage;
use Jihe\Utils\SmsTemplate;
use Jihe\Utils\StringUtil;
use Jihe\Exceptions\VerificationException;
use Jihe\Exceptions\User\UserNotExistsException;
use Jihe\Exceptions\User\UserExistsException;

/**
 * This class provides services on verification code in mobile text message
 *
 */
class VerificationService
{
    use DispatchesJobs, DispatchesMessage;

    /**
     * 
     * @var \Jihe\Contracts\Repositories\VerificationRepository
     */
    private $verificationRepository;

    /**
     *
     * @var \Jihe\Contracts\Repositories\UserRepository
     */
    private $userRepository;

    /**
     * @var \Jihe\Contracts\Repositories\ActivityMemberRepository
     */
    private $activityMemberRepository;
    
    /**
     * the minimum time interval (in seconds) after which message is allowed 
     * to be sent since last. 0 for no limit.
     * 
     * @var int
     */
    private $interval;
    
    // the maximum number of messages that can be sent within a period of time
    private $limit,          // max #. of messages,   0 for no limit
            $limitPeriod;    // within period (in seconds)

    public function __construct(
        VerificationRepository $verificationRepository,
        UserRepository $userRepository,
        ActivityMemberRepository $activityMemberRepository,
        array $config
    ) {
        $this->verificationRepository = $verificationRepository;
        $this->userRepository = $userRepository;
        $this->activityMemberRepository = $activityMemberRepository;
        
        // rate limit defines two metrics
        // 1). the minimum time interval (in seconds) after which message is
        //     allowed to be sent since last
        $this->interval = (int) array_get($config, 'send_interval',
                                    VerificationEntity::DEFAULT_EXPIRE_INTERVAL);

        $this->expiredAt = (int) array_get($config, 'expired_at',
                                    VerificationEntity::DEFAULT_EXPIRED_AT);
        
        // 2). the maximum number of messages that can be sent within a period
        //     of time
        $this->limit = (int) array_get($config, 'limit_count', 0);
        $this->limitPeriod = (int) array_get($config, 'limit_period', 0);
    }

    /**
     * send text message for user registration
     * 
     * @param string $mobile   mobile to send message to
     *
     * @return array          [0] is the verification
     *                        [1] send interval
     */
    public function sendForRegistration($mobile)
    {
        $this->ensureMessageForRegistrationCanBeRequested($mobile);
        $code = $this->generateCodeAndStore($mobile);
        $message = SmsTemplate::generalMessage(SmsTemplate::SMS_VERIFICATION_CODE,
                        $code, ceil($this->expiredAt / 60));
        $this->send($mobile, $message);
        
        return [
            $code,
            $this->interval,
        ];
    }

    /**
     * send text message for user reset password
     * 
     * @param string $mobile   mobile to send message to
     *
     * @return array          [0] is the verification
     *                        [1] send interval
     */
    public function sendForResetPassword($mobile)
    {
        $this->ensureMessageForResetPasswordCanBeRequested($mobile);
        $code = $this->generateCodeAndStore($mobile);
        $message = SmsTemplate::generalMessage(SmsTemplate::SMS_VERIFICATION_CODE,
                        $code, ceil($this->expiredAt / 60));
        $this->send($mobile, $message);
        
        return [
            $code,
            $this->interval,
        ];
    }

    /**
     * send text message for user check in activity
     *
     * @param string   $mobile      mobile to send message to
     * @param interval $activityId  activity user should check in
     *
     * @return array          [0] is the verification
     *                        [1] send interval
     */
    public function sendForActivityCheckIn($mobile, $activityId)
    {
        $this->ensureMessageForActivityCheckInCanBeRequested($mobile, $activityId);
        $code = $this->generateCodeAndStore($mobile);
        $message = SmsTemplate::generalMessage(SmsTemplate::SMS_VERIFICATION_CODE,
                        $code, ceil($this->expiredAt / 60));
        $this->send($mobile, $message);

        return [
            $code,
            $this->interval,
        ];
    }

    /**
     * Send text message for activity web applicant
     * we don't check whether user exists or not, because we
     * will create a account for user if user not registered
     *
     * @param string $mobile   mobile to send message to
     *
     * @return array          [0] is the verification
     *                        [1] send interval, unit: seconds
     */
    public function sendForActivityWebApplicant($mobile)
    {
        $this->ensureMessageForActivityWebApplicantCanBeRequested($mobile);
        $code = $this->generateCodeAndStore($mobile);
        $message = SmsTemplate::generalMessage(SmsTemplate::SMS_VERIFICATION_CODE,
                        $code, ceil($this->expiredAt / 60));
        $this->send($mobile, $message);

        return [
            $code,
            $this->interval,
        ];
    }

    // generate verification code and persist it
    private function generateCodeAndStore($mobile)
    {
        // fetch last valid requested
        $verification = $this->verificationRepository->findLastRequested($mobile);
        if ($verification) {
            $code = $verification->getCode();
        } else {
            // generate verification code
            $code = $this->generateCode(4);
        }
        
        // save the verification code
        $this->verificationRepository->add([
            'mobile' => $mobile,
            'code'   => $code,
            'expired_at' => date('Y-m-d H:i:s', strtotime(sprintf('%s seconds', $this->expiredAt))),
        ]);
        
        return $code;
    }
    
    private function ensureMessageForRegistrationCanBeRequested($mobile)
    {
        $user = $this->userRepository->findUser($mobile);
        if ($user && ! $user->isNeedComplete()) {
            throw new UserExistsException($mobile, '您已经注册，请登录');
        }
        $this->checkSendBaseLimitaion($mobile);
    }

    private function ensureMessageForResetPasswordCanBeRequested($mobile)
    {
        if ( ! $this->userRepository->findId($mobile)) {
            throw new UserNotExistsException($mobile, '您还未注册，请先完成注册');
        }

        $this->checkSendBaseLimitaion($mobile);
    }

    private function ensureMessageForActivityCheckInCanBeRequested($mobile, $activityId)
    {
        if ( ! ($userId = $this->userRepository->findId($mobile))) {
            throw new UserNotExistsException($mobile, '您还未注册，请先完成注册');
        }

        if ( ! $this->activityMemberRepository->exists($activityId, $userId)) {
            throw new \Exception('您未报名活动，请先报名');
        }

        $this->checkSendBaseLimitaion($mobile);
    }

    private function ensureMessageForActivityWebApplicantCanBeRequested($mobile)
    {
        $this->checkSendBaseLimitaion($mobile);
    }

    private function checkSendBaseLimitaion($mobile)
    {
        // rule#1. the next message cannot be sent within the specified time interval
        if ($this->interval > 0) {
            if ($this->verificationRepository->count($mobile, 
                                                     $this->aheadOf($this->interval)) > 0) {
                // don't tell user the exact time left, typically human beings
                // can read the time remaining from UI
                throw new \Exception('短信发送频率太高, 请稍后再试');
            }
        }
        
        // rule #2. message rate limit
        if ($this->limit > 0 && $this->limitPeriod > 0) {
            if ($this->verificationRepository->count($mobile, 
                                                     $this->aheadOf($this->limitPeriod)) >= $this->limit) {
                throw new \Exception('短信发送次数过多, 请稍后再试');
            }
        }
    }
    
    // generate verification code
    private function generateCode($length)
    {
        return StringUtil::quickRandom($length, '0123456789');
    }

    // send sms
    private function send($subscriber, $message)
    {
        return $this->sendToUsers(
                [
                    $subscriber,
                ], [
                    'content' => $message,
                ], [
                    'sms'   => true,
                ]);
    }
    
    /**
     * compute the time which goes ahead of given time($time) in seconds
     * 
     * @param int $seconds  seconds ahead
     * @param int $time     the time base
     * 
     * @return string  'Y-m-d H:i:s' format of the computed time
     */
    private function aheadOf($seconds, $time = null) {
        return date('Y-m-d H:i:s', strtotime(sprintf('%s seconds ago', $seconds), 
                                             $time ?: time()));
    }
    
    /**
     * check verification code
     * 
     * @param string $mobile  mobile to check
     * @param string $code    code to check
     * @throws VerificationException     in case verification fails, exception will be thrown
     */
    public function verify($mobile, $code)
    {
        $verification = $this->verificationRepository->findLastRequested($mobile);
        if (!$verification) { // no verification for this mobile, hack-ed?
            throw new VerificationException('验证码错误');
        }
        
        // mark the verification code is used no matter this
        // verification passes or not
        $this->verificationRepository->remove($verification->getId());
        
        if ($verification->isExpired()) {
            throw new VerificationException('验证码已过期,请重新获取');
        }
        
        if ($verification->getCode() != $code) {
            throw new VerificationException('验证码错误,请重新获取');
        }
    }

    /**
     * remove expired verifications for registration
     * 
     * @param int $time     the base of expiry time
     */
    public function removeExpiredVerificationsForRegistration($time = null)
    {
        // calculate the expired time
        // 
        //             |------------------|------------------|
        //   expired _/    limitPeriod          limitPeriod   \__ $time
        //
        // In fact, verifications whose expired time <= $time - limitPeriod are supposed
        // to be removable. But to keep it safe, we extend that range to be 2 * limitPeriod,
        // as some logic should still take effect during that period of time, i.e., [-limitPeriod, 0]
        // 
        $expiredAt = date('Y-m-d H:i:s', ($time ?: time()) - 2 * $this->limitPeriod);

        $this->verificationRepository->removeExpiredBefore($expiredAt);
    }
}
