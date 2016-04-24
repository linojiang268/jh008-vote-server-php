<?php
namespace Jihe\Services;

use Jihe\Services\Payment\Wxpay\WxpayAppService;
use Jihe\Services\Payment\Wxpay\WxpayWebService;
use Jihe\Services\Payment\Alipay\AlipayService;
use Jihe\Services\ActivityApplicantService;
use Jihe\Contracts\Repositories\ActivityEnrollPaymentRepository;
use Jihe\Contracts\Repositories\ActivityEnrollIncomeRepository;
use Jihe\Entities\ActivityEnrollPayment;
use DB;

class PaymentService
{
    private $wxpayAppService;


    public function __construct(WxpayAppService $wxpayAppService,
                                WxpayWebService $wxpayWebService,
                                AlipayService $alipayService,
                                ActivityApplicantService $activityApplicantService,
                                ActivityEnrollPaymentRepository $activityEnrollPaymentRepository,
                                ActivityEnrollIncomeRepository $activityEnrollIncomeRepository
    ) {
        $this->wxpayAppService = $wxpayAppService;
        $this->wxpayWebService = $wxpayWebService;
        $this->alipayService = $alipayService;
        $this->activityApplicantService = $activityApplicantService;
        $this->activityEnrollPaymentRepository = $activityEnrollPaymentRepository;
        $this->activityEnrollIncomeRepository = $activityEnrollIncomeRepository;
    }

    //====================================
    //          Wxpay
    //====================================
    /**
     * Prepare wxpay trade and return payment data
     *
     * @param int    $userId
     * @param string $orderNo
     * @param string $clientIp      format as 8.8.8.8
     *
     * @return \stdClass            pay param used by client side to invode a payment
     *                              action, the property list below:
     *                              -- appid string     appid
     *                              -- prepayId string  the prepare order id created by wxpay
     *                              -- nonceStr string  random string
     *
     * @throw \Exception            exception will be throw if prepare trade failed
     */
    public function prepareWxAppTrade($userId, $orderNo, $clientIp)
    {
        $applicant = $this->getApplicantInfo($userId, $orderNo);

        $result = $this->wxpayAppService->placeOrder($orderNo,
                                           $applicant['fee'],
                                           $applicant['activity_title'],
                                           $clientIp,
                                           $applicant['expire_after']);
        if ($result->responseCode != 'SUCCESS')
        {
            throw new \Exception('微信支付失败. 错误代码: ' . $result->errCode .
                             ' 错误原因: ' . $result->errMsg);
        }

        // create payment record, save orderNo, prepayId
        $this->createPaymentRecord($orderNo, $userId, $applicant['activity_id'], 
                                   $applicant['fee'], ActivityEnrollPayment::CHANNEL_WXPAY);

        $payParam = new \stdClass;
        $payParam->appid = $result->appid;
        $payParam->mchid = $result->mchid;
        $payParam->nonceStr = $result->nonceStr;
        $payParam->prepayId = $result->prepayId;

        return $payParam;
    }

    /**
     * Prepare wxpay trade and return payment data
     *
     * @param int    $userId
     * @param string $openId        user unique id in wechat gongzonghao
     * @param string $orderNo
     * @param string $clientIp      format as 8.8.8.8
     *
     * @return \stdClass            pay param used by client side to invode a payment
     *                              action, the property list below:
     *                              - appId
     *                              - timeStamp
     *                              - nonceStr
     *                              - package       prepay_id=xxxxxx
     *                              - signType
     *                              - paySign
     *
     * @throw \Exception            exception will be throw if prepare trade failed
     */
    public function prepareWxWebTrade($userId, $openId, $orderNo, $clientIp)
    {
        $applicant = $this->getApplicantInfo($userId, $orderNo);

        $result = $this->wxpayWebService->placeOrder($openId,
                                           $orderNo,
                                           $applicant['fee'],
                                           $applicant['activity_title'],
                                           $clientIp,
                                           $applicant['expire_after']);
        if ($result->responseCode != 'SUCCESS')
        {
            throw new \Exception('微信支付失败. 错误代码: ' . $result->errCode .
                             ' 错误原因: ' . $result->errMsg);
        }

        // create payment record, save orderNo, prepayId
        $this->createPaymentRecord($orderNo, $userId, $applicant['activity_id'],
                                   $applicant['fee'], ActivityEnrollPayment::CHANNEL_WXPAY);

        return $result->jsApiParams;

    }

    /**
     * wx payment notification
     *
     * @param array $notification   notification (the whole $_POST) from Wxpay
     *
     * @return string               SUCCESS or FAIL
     */
    public function notifyWxpayment(array $notification)
    {
        try {
            $result = $this->wxpayAppService->tradeUpdated($notification, function($trade) {
                return $this->notifyPaymentHandler([
                    'orderNo'       => $trade->orderNo,
                    'tradeNo'       => $trade->transactionId,
                    'paymentTime'   => new \DateTime($trade->paymentTime),
                    'fee'           => $trade->fee,
                ]);
            });
        } catch(\Exception $ex) {
            return 'FAIL';
        }

        return $result == 'SUCCESS' ? 'SUCCESS' : 'FAIL';
    }

    /**
     * wx payment notification
     *
     * @param array $notification   notification (the whole $_POST) from Wxpay
     *
     * @return string               SUCCESS or FAIL
     */
    public function notifyWxWebpayment(array $notification)
    {
        try {
            $result = $this->wxpayWebService->tradeUpdated($notification, function($trade) {
                return $this->notifyPaymentHandler([
                    'orderNo'       => $trade->orderNo,
                    'tradeNo'       => $trade->transactionId,
                    'paymentTime'   => new \DateTime($trade->paymentTime),
                    'fee'           => $trade->fee,
                ]);
            });
        } catch(\Exception $ex) {
            return 'FAIL';
        }

        return $result == 'SUCCESS' ? 'SUCCESS' : 'FAIL';
    }

    //=================================
    //          Alipay
    //=================================
    /**
     * Prepare alipay trade and return pay text, which be userd to call payment in app
     *
     * @param int    $userId
     * @param string $orderNo
     *
     * @return string               payment argument used by app side
     */
    public function prepareAliAppTrade($userId, $orderNo)
    {
        $applicant = $this->getApplicantInfo($userId, $orderNo);

        $expireAfter = $applicant['expire_after'] / 60 . 'm'; // convert seconds to minutes
        $paymentArgument = $this->alipayService
                        ->prepareTrade($orderNo,
                                       $applicant['fee'] / 100,     // fen to yuan
                                       $applicant['activity_title'],
                                       $applicant['activity_title'],
                                       1,                           // 1 for product purchase
                                       $expireAfter);
        $this->createPaymentRecord($orderNo, $userId, $applicant['activity_id'],
                                   $applicant['fee'], ActivityEnrollPayment::CHANNEL_ALIPAY);

        return $paymentArgument;
    }

    /**
     * ali payment notification
     *
     * @param array $notification   notification (the whole $_POST) from alipay
     *
     * @return string   success or fail
     */
    public function notifyAlipayment(array $notification)
    {
        try {
            ob_start();
            $this->alipayService->tradeUpdated($notification, function($trade) {
                return $this->notifyPaymentHandler([
                    'orderNo'       => $trade->orderNo,
                    'tradeNo'       => $trade->tradeNo,
                    'paymentTime'   => new \DateTime($trade->paymentTime),
                    'fee'           => $trade->fee * 100,   // yuan to fen
                ]);
            });
            $res = ob_get_contents();
            ob_end_clean();
            // if res is empty, it indicate that alipay should not send notification again
            // so, we should tell alipay current response is success
            return ($res == 'success' || empty($res)) ? 'success' : 'fail';
        } catch (\Exception $ex) {
            ob_end_clean();
            return 'fail';
        }
    }

    /**
     * create a payment, if which not exist
     *
     * @param string $orderNo
     * @param int $userId
     * @param int $activityId
     * @param int $fee
     * @param int $channel      payment channel
     *
     * @return void
     */
    private function createPaymentRecord($orderNo, $userId, $activityId, $fee, $channel)
    {
        // create payment record, save orderNo, prepayId
        $payment = $this->activityEnrollPaymentRepository
                        ->findOneByOrderNo($orderNo);
        if ( ! $payment) {
            $this->activityEnrollPaymentRepository->add([
                'activity_id'   => $activityId,
                'user_id'       => $userId,
                'fee'           => $fee,
                'order_no'      => $orderNo,
                'channel'       => $channel,
            ]);

            return;
        }

        if ($payment->getStatus() == ActivityEnrollPayment::STATUS_SUCCESS) {
            throw new \Exception('您已经完成支付');
        }

        if ($payment->getStatus() == ActivityEnrollPayment::STATUS_CLOSED) {
            throw new \Exception('支付已关闭');
        }

        $this->activityEnrollPaymentRepository->updateById($payment->getId(), [
            'fee'       => $fee,
            'channel'   => $channel,
        ]);
    }

    /**
     * handle wxpay notification
     *
     * @param array elements lists as below:
     *          1). orderNo     string      -- the order# related to the trade
     *          2). fee         int         -- total fee user payed
     *          3). tradeNo     string      -- payment trade#
     *          4). paymentTime \DateTime   -- payment end time. 
     */
    private function notifyPaymentHandler($trade)
    {
        // Wrap logic in a transaction
        DB::transaction(function() use ($trade) {
            // if notification already handled, just do nothing
            $payment = $this->activityEnrollPaymentRepository
                            ->findOneByOrderNo($trade['orderNo'], true);
            /*
             * if payment not found, do nothing, just finish logic and return wxpay SUCCESS
             * if payment's status equals to STATUS_SUCCESS, it indicated that this
             * notification aready handled, so do nothing
             */
            if ( ! $payment || $payment->getStatus() == ActivityEnrollPayment::STATUS_SUCCESS) {
                return;
            }

            // update payment status, and increase total fee of enroll income
            $this->activityEnrollPaymentRepository
                ->updateAfterPayed($trade['orderNo'], ActivityEnrollPayment::STATUS_SUCCESS,
                                $trade['tradeNo'], $trade['paymentTime']);

            $this->activityEnrollIncomeRepository
                ->increaseTotalFeeForPaymentSuccess($payment->getActivity()->getId(),
                                                    $payment->getFee());

            // notify activity applicant
            $this->activityApplicantService
                ->onActivityApplicantPaymentSuccess($trade['orderNo']);
        });

        return true;
    }

    private function getApplicantInfo($userId, $orderNo)
    {
        $applicant = $this->activityApplicantService->getApplicantInfoForPayment($orderNo);
        if ($applicant['user_id'] != $userId)
        {
            throw new \Exception('您不能支付该报名');
        }
        if ($applicant['expire_at'] < date('Y-m-d H:i:s')) {
            throw new \Exception('报名申请过期了，请重新报名');
        }

        // convert expireAt to expireTime
        $applicant['expire_after'] = strtotime($applicant['expire_at']) - time();

        return $applicant;
    }
}
