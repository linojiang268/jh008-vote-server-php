<?php
namespace spec\Jihe\Services;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Jihe\Services\Payment\Wxpay\WxpayAppService;
use Jihe\Services\Payment\Wxpay\WxpayWebService;
use Jihe\Services\Payment\Alipay\AlipayService;
use Jihe\Services\ActivityApplicantService;
use Jihe\Contracts\Repositories\ActivityEnrollPaymentRepository;
use Jihe\Contracts\Repositories\ActivityEnrollIncomeRepository;
use Jihe\Entities\ActivityEnrollPayment;
use Jihe\Entities\Activity;
use PhpSpec\Laravel\LaravelObjectBehavior;

class PaymentServiceSpec extends LaravelObjectBehavior
{
    function let(WxpayAppService $wxpayAppService,
                 WxpayWebService $wxpayWebService,
                 AlipayService $alipayService,
                 ActivityApplicantService $activityApplicantService,
                 ActivityEnrollPaymentRepository $activityEnrollPaymentRepository,
                 ActivityEnrollIncomeRepository $activityEnrollIncomeRepository
    ) {
        $this->beAnInstanceOf(\Jihe\Services\PaymentService::class, [
            $wxpayAppService,
            $wxpayWebService,
            $alipayService,
            $activityApplicantService,
            $activityEnrollPaymentRepository,
            $activityEnrollIncomeRepository
        ]);
    }

    //================================================
    //          prepareWxAppTrade
    //================================================
    public function it_prepare_wxapp_trade_succussfully(
        WxpayAppService $wxpayAppService,
        ActivityApplicantService $activityApplicantService,
        ActivityEnrollPaymentRepository $activityEnrollPaymentRepository
    ) {
        $orderNo = 'T8986DC81A8CAAAAEEB9D219B68D6ZIP';
        $activityId = 1;
        $clientIp = '114.1.1.1';
        $userId = 1;
        $fee = 100;
        $expireAt = date('Y-m-d H:i:s', strtotime('+1 day'));
        $activityApplicantService->getApplicantInfoForPayment($orderNo)
                                ->shouldBeCalledTimes(1)
                                ->willReturn([
                                    'user_id'           => 1,
                                    'activity_id'       => $activityId,
                                    'activity_title'    => '成都荧光跑',
                                    'fee'               => $fee,
                                    'expire_at'         => $expireAt,
                                ]);
        $result = new \stdClass;
        $result->responseCode = 'SUCCESS';
        $result->errCode = '';
        $result->errMsg = '';
        $result->prepayId = '12345678901234567890';
        $result->appid = '123456789';
        $result->mchid = 'abcdefg';
        $result->nonceStr = '5K8264ILTKCH16CQ2502SI8ZNMTM67VS';
        $wxpayAppService->placeOrder($orderNo, $fee, '成都荧光跑', $clientIp, Argument::any())
                        ->shouldBeCalledTimes(1)
                        ->willReturn($result);
        $activityEnrollPaymentRepository->findOneByOrderNo($orderNo)
                                        ->shouldBeCalledTimes(1)
                                        ->willReturn(null);
        $activityEnrollPaymentRepository->add([
            'activity_id'   => $activityId,
            'user_id'       => $userId,
            'fee'           => $fee,
            'order_no'      => $orderNo,
            'channel'       => ActivityEnrollPayment::CHANNEL_WXPAY,
        ])->shouldBeCalledTimes(1)->willReturn(1);

        $payParams = $this->prepareWxAppTrade($userId, $orderNo, $clientIp)
                          ->getWrappedObject();
        \PHPUnit_Framework_Assert::assertEquals('123456789', $payParams->appid);
        \PHPUnit_Framework_Assert::assertEquals('5K8264ILTKCH16CQ2502SI8ZNMTM67VS',
                                                $payParams->nonceStr);
        \PHPUnit_Framework_Assert::assertEquals('12345678901234567890',
                                                $payParams->prepayId);
    }

    /**
     * activityEnrollPaymentRepository::add will not be called
     */
    public function it_prepare_wxapp_trade_succussfully_called_not_first_time(
        WxpayAppService $wxpayAppService,
        ActivityApplicantService $activityApplicantService,
        ActivityEnrollPaymentRepository $activityEnrollPaymentRepository
    ) {
        $orderNo = 'T8986DC81A8CAAAAEEB9D219B68D6ZIP';
        $activityId = 1;
        $clientIp = '114.1.1.1';
        $userId = 1;
        $fee = 100;
        $activityApplicantService->getApplicantInfoForPayment($orderNo)
                                ->shouldBeCalledTimes(1)
                                ->willReturn([
                                    'id'                => 1,
                                    'user_id'           => 1,
                                    'activity_id'       => $activityId,
                                    'activity_title'    => '成都荧光跑',
                                    'fee'               => $fee,
                                    'expire_at'         => date('Y-m-d H:i:s', strtotime('+1 day')),
                                ]);
        $result = new \stdClass;
        $result->responseCode = 'SUCCESS';
        $result->errCode = '';
        $result->errMsg = '';
        $result->prepayId = '12345678901234567890';
        $result->appid = '123456789';
        $result->mchid = 'abcdefg';
        $result->nonceStr = '5K8264ILTKCH16CQ2502SI8ZNMTM67VS';
        $wxpayAppService->placeOrder($orderNo, $fee, '成都荧光跑', $clientIp, Argument::any())
                        ->shouldBeCalledTimes(1)
                        ->willReturn($result);

        $activityEnrollPaymentRepository->findOneByOrderNo($orderNo)
                                        ->shouldBeCalledTimes(1)
                                        ->willReturn((new ActivityEnrollPayment())->setId(1));
        $activityEnrollPaymentRepository->add(Argument::any())->shouldNotBeCalled();

        $activityEnrollPaymentRepository->updateById(1, ['fee' => 100, 'channel' => 2])
                                        ->willReturn(true);

        $payParams = $this->prepareWxAppTrade($userId, $orderNo, $clientIp)
                          ->getWrappedObject();
        \PHPUnit_Framework_Assert::assertEquals('123456789', $payParams->appid);
        \PHPUnit_Framework_Assert::assertEquals('5K8264ILTKCH16CQ2502SI8ZNMTM67VS',
                                                $payParams->nonceStr);
        \PHPUnit_Framework_Assert::assertEquals('12345678901234567890',
                                                $payParams->prepayId);
    }

    public function it_throw_exception_if_trade_closed(
        WxpayAppService $wxpayAppService,
        ActivityApplicantService $activityApplicantService,
        ActivityEnrollPaymentRepository $activityEnrollPaymentRepository
    ) {
        $orderNo = 'T8986DC81A8CAAAAEEB9D219B68D6ZIP';
        $activityId = 1;
        $clientIp = '114.1.1.1';
        $userId = 1;
        $fee = 100;
        $activityApplicantService->getApplicantInfoForPayment($orderNo)
                                ->shouldBeCalledTimes(1)
                                ->willReturn([
                                    'id'                => 1,
                                    'user_id'           => 1,
                                    'activity_id'       => $activityId,
                                    'activity_title'    => '成都荧光跑',
                                    'fee'               => $fee,
                                    'expire_at'         => date('Y-m-d H:i:s', strtotime('+1 day')),
                                    'status'            => 4,
                                ]);
        $result = new \stdClass;
        $result->responseCode = 'SUCCESS';
        $result->errCode = '';
        $result->errMsg = '';
        $result->prepayId = '12345678901234567890';
        $wxpayAppService->placeOrder($orderNo, $fee, '成都荧光跑', $clientIp, Argument::any())
                        ->shouldBeCalledTimes(1)
                        ->willReturn($result);

        $activityEnrollPaymentRepository->findOneByOrderNo($orderNo)
                                        ->shouldBeCalledTimes(1)
                                        ->willReturn((new ActivityEnrollPayment())
                                                        ->setId(1)
                                                        ->setStatus(4)
                                                        );

        $this->shouldThrow(new \Exception('支付已关闭'))
             ->duringPrepareWxAppTrade($userId, $orderNo, $clientIp);

    }

    public function it_throw_exception_if_user_illegal(
        ActivityApplicantService $activityApplicantService
    ) {
        $orderNo = 'T8986DC81A8CAAAAEEB9D219B68D6ZIP';
        $activityApplicantService->getApplicantInfoForPayment($orderNo)
                                ->shouldBeCalledTimes(1)
                                ->willReturn([
                                    'user_id'       => 1,
                                    'activity_id'   => 1,
                                    'fee'           => 100,
                                    'expire_at'     => date('Y-m-d H:i:s', strtotime('+1 day')),
                                ]);
        $this->shouldThrow(new \Exception('您不能支付该报名'))
            ->duringPrepareWxAppTrade(2, $orderNo, '8.8.8.8');
    }

    public function it_throw_exception_if_applicant_expired(
        ActivityApplicantService $activityApplicantService
    ) {
        $orderNo = 'T8986DC81A8CAAAAEEB9D219B68D6ZIP';
        $activityApplicantService->getApplicantInfoForPayment($orderNo)
                                ->shouldBeCalledTimes(1)
                                ->willReturn([
                                    'user_id'        => 1,
                                    'activity_id'    => 1,
                                    'fee'           => 100,
                                    'expire_at'      => date('Y-m-d H:i:s', strtotime('-1 day')),
                                ]);
        $this->shouldThrow(new \Exception('报名申请过期了，请重新报名'))
            ->duringPrepareWxAppTrade(1, $orderNo, '8.8.8.8');
    }

    public function it_throw_exception_if_call_wxpay_failed(
        ActivityApplicantService $activityApplicantService,
        WxpayAppService $wxpayAppService
    ) {
        $orderNo = 'T8986DC81A8CAAAAEEB9D219B68D6ZIP';
        $activityId = 1;
        $clientIp = '114.1.1.1';
        $userId = 1;
        $fee = 100;
        $activityApplicantService->getApplicantInfoForPayment($orderNo)
                                ->shouldBeCalledTimes(1)
                                ->willReturn([
                                    'user_id'           => 1,
                                    'activity_id'       => $activityId,
                                    'activity_title'    => '成都荧光跑',
                                    'fee'               => $fee,
                                    'expire_at'         => date('Y-m-d H:i:s', strtotime('+1 day')),
                                ]);
        $result = new \stdClass;
        $result->responseCode = 'FAIL';
        $result->errCode = 'APPID_NOT_EXIST';
        $result->errMsg = 'APPID不存在';
        $wxpayAppService->placeOrder($orderNo, $fee, '成都荧光跑', $clientIp, Argument::any())
                        ->shouldBeCalledTimes(1)
                        ->willReturn($result);

        $this->shouldThrow(\Exception::class)
            ->duringPrepareWxAppTrade($userId, $orderNo, $clientIp);
    }

    //================================================
    //          prepareWxWebTrade
    //================================================
    public function it_prepare_wxweb_trade_succussfully(
        WxpayWebService $wxpayWebService,
        ActivityApplicantService $activityApplicantService,
        ActivityEnrollPaymentRepository $activityEnrollPaymentRepository
    ) {
        $orderNo = 'T8986DC81A8CAAAAEEB9D219B68D6ZIP';
        $activityId = 1;
        $clientIp = '114.1.1.1';
        $userId = 1;
        $openId = 'oUpF8uMuAJO_M2pxb1Q9zNjWeS6o';
        $fee = 100;
        $expireAt = date('Y-m-d H:i:s', strtotime('+1 day'));
        $activityApplicantService->getApplicantInfoForPayment($orderNo)
                                ->shouldBeCalledTimes(1)
                                ->willReturn([
                                    'user_id'           => 1,
                                    'activity_id'       => $activityId,
                                    'activity_title'    => '成都荧光跑',
                                    'fee'               => $fee,
                                    'expire_at'         => $expireAt,
                                ]);
        $result = new \stdClass;
        $result->responseCode = 'SUCCESS';
        $result->errCode = '';
        $result->errMsg = '';
        $jsApiParams = new \stdClass;
        $jsApiParams->appId = '123456789';
        $jsApiParams->timeStamp = '1441804259';
        $jsApiParams->nonceStr = 'abcde12345';
        $jsApiParams->package = 'prepay_id=1234567890';
        $jsApiParams->signType = 'MD5';
        $result->jsApiParams = $jsApiParams;
        $wxpayWebService->placeOrder($openId, $orderNo, $fee, '成都荧光跑', $clientIp, Argument::any())
                        ->shouldBeCalledTimes(1)
                        ->willReturn($result);
        $activityEnrollPaymentRepository->findOneByOrderNo($orderNo)
                                        ->shouldBeCalledTimes(1)
                                        ->willReturn(null);
        $activityEnrollPaymentRepository->add([
            'activity_id'   => $activityId,
            'user_id'       => $userId,
            'fee'           => $fee,
            'order_no'      => $orderNo,
            'channel'       => ActivityEnrollPayment::CHANNEL_WXPAY,
        ])->shouldBeCalledTimes(1)->willReturn(1);

        $payParams = $this->prepareWxWebTrade($userId, $openId, $orderNo, $clientIp)
                          ->getWrappedObject();
        \PHPUnit_Framework_Assert::assertEquals('123456789', $payParams->appId);
        \PHPUnit_Framework_Assert::assertEquals('prepay_id=1234567890',
                                                $payParams->package);
        \PHPUnit_Framework_Assert::assertEquals('MD5', $payParams->signType);
    }

    //================================================
    //          notifyWxpayment
    //================================================
    public function it_notify_wxpayment_with_closure_successfully(
        WxpayAppService $wxpayAppService,
        ActivityEnrollPaymentRepository $activityEnrollPaymentRepository,
        ActivityEnrollIncomeRepository $activityEnrollIncomeRepository,
        ActivityApplicantService $activityApplicantService
    ) {
        $trade = $this->gen_wxpayment_mock(
                                    $activityEnrollPaymentRepository,
                                    $activityEnrollIncomeRepository,
                                    $activityApplicantService);

        $wxpayAppService->tradeUpdated([], Argument::that(function($arg) use ($trade) {
            call_user_func($arg, $trade);
            return true;            // tell spec argument 1 match a closure if true
        }))->willReturn('SUCCESS');

        $this->notifyWxpayment([])->shouldBe('SUCCESS');
    }

    public function it_notify_wxpayment_with_closure_if_failed(
        WxpayAppService $wxpayAppService,
        ActivityEnrollPaymentRepository $activityEnrollPaymentRepository,
        ActivityEnrollIncomeRepository $activityEnrollIncomeRepository,
        ActivityApplicantService $activityApplicantService
    ) {
        $trade = $this->gen_wxpayment_mock(
                                    $activityEnrollPaymentRepository,
                                    $activityEnrollIncomeRepository,
                                    $activityApplicantService);
        $wxpayAppService->tradeUpdated([], Argument::that(function($arg) use ($trade) {
            call_user_func($arg, $trade);
            return true;            // tell spec argument 1 match a closure if true
        }))->willReturn('FAIL');;

        $this->notifyWxpayment([])->shouldBe('FAIL');
    }

    public function it_notify_wxpayment_with_closure_if_exception_throwed(
        WxpayAppService $wxpayAppService,
        ActivityEnrollPaymentRepository $activityEnrollPaymentRepository,
        ActivityEnrollIncomeRepository $activityEnrollIncomeRepository,
        ActivityApplicantService $activityApplicantService
    ) {
        $trade = $this->gen_wxpayment_mock(
                                    $activityEnrollPaymentRepository,
                                    $activityEnrollIncomeRepository,
                                    $activityApplicantService);
        $wxpayAppService->tradeUpdated([], Argument::that(function($arg) use ($trade) {
            call_user_func($arg, $trade);
            return true;            // tell spec argument 1 match a closure if true
        }))->willThrow(\Exception::class);;

        $this->notifyWxpayment([])->shouldBe('FAIL');
    }

    //================================================
    //          notifyWxpayment
    //================================================
    public function it_notify_wx_webpayment_with_closure_successfully(
        WxpayWebService $wxpayWebService,
        ActivityEnrollPaymentRepository $activityEnrollPaymentRepository,
        ActivityEnrollIncomeRepository $activityEnrollIncomeRepository,
        ActivityApplicantService $activityApplicantService
    ) {
        $trade = $this->gen_wxpayment_mock(
                                    $activityEnrollPaymentRepository,
                                    $activityEnrollIncomeRepository,
                                    $activityApplicantService);

        $wxpayWebService->tradeUpdated([], Argument::that(function($arg) use ($trade) {
            call_user_func($arg, $trade);
            return true;            // tell spec argument 1 match a closure if true
        }))->willReturn('SUCCESS');

        $this->notifyWxWebpayment([])->shouldBe('SUCCESS');
    }

    //===============================================
    //          prepareAliAppTrade
    //===============================================
    public function it_prepare_aliapp_trade_successfully(
        AlipayService $alipayService,
        ActivityApplicantService $activityApplicantService,
        ActivityEnrollPaymentRepository $activityEnrollPaymentRepository
    ) {
        $orderNo = 'T8986DC81A8CAAAAEEB9D219B68D6ZIP';
        $activityId = 1;
        $userId = 1;
        $fee = 100;
        $paymentData = 'test';
        $expireAt = date('Y-m-d H:i:s', strtotime('+1 day'));
        $activityApplicantService->getApplicantInfoForPayment($orderNo)
                                ->shouldBeCalledTimes(1)
                                ->willReturn([
                                    'user_id'           => 1,
                                    'activity_id'       => $activityId,
                                    'activity_title'    => '成都荧光跑',
                                    'fee'               => $fee,
                                    'expire_at'         => $expireAt,
                                ]);
        $alipayService->prepareTrade($orderNo, $fee / 100, '成都荧光跑', '成都荧光跑', 1, Argument::any())
                      ->shouldBeCalledTimes(1)
                      ->willReturn($paymentData);

        $activityEnrollPaymentRepository->findOneByOrderNo($orderNo)
                                        ->shouldBeCalledTimes(1)
                                        ->willReturn(null);
        $activityEnrollPaymentRepository->add([
            'activity_id'   => $activityId,
            'user_id'       => $userId,
            'fee'           => $fee,
            'order_no'      => $orderNo,
            'channel'       => ActivityEnrollPayment::CHANNEL_ALIPAY,
        ])->shouldBeCalledTimes(1)->willReturn(1);

        $this->prepareAliAppTrade($userId, $orderNo)
            ->shouldBe($paymentData);
    }

    //================================================
    //          notifyAlipayment
    //================================================
    public function it_notify_alipay_with_closure_successfully(
        AlipayService $alipayService,
        ActivityEnrollPaymentRepository $activityEnrollPaymentRepository,
        ActivityEnrollIncomeRepository $activityEnrollIncomeRepository,
        ActivityApplicantService $activityApplicantService
    ) {
        $trade = $this->gen_alipayment_mock(
                                    $activityEnrollPaymentRepository,
                                    $activityEnrollIncomeRepository,
                                    $activityApplicantService);

        $alipayService->tradeUpdated([], Argument::that(function ($arg) use ($trade) {
            call_user_func($arg, $trade);
            return true;            // tell spec argument 1 match a closure if true
        }))->will(function() {
            echo 'success';         // test echo. tradeUpdated should echo 'success'
        });

        $this->notifyAlipayment([])->shouldBe('success');
    }

    public function it_notify_alipay_with_closure_output_nothing_successfully(
        AlipayService $alipayService,
        ActivityEnrollPaymentRepository $activityEnrollPaymentRepository,
        ActivityEnrollIncomeRepository $activityEnrollIncomeRepository,
        ActivityApplicantService $activityApplicantService
    ) {
        $trade = $this->gen_alipayment_mock(
                                    $activityEnrollPaymentRepository,
                                    $activityEnrollIncomeRepository,
                                    $activityApplicantService);

        $alipayService->tradeUpdated([], Argument::that(function ($arg) use ($trade) {
            call_user_func($arg, $trade);
            return true;            // tell spec argument 1 match a closure if true
        }))->will(function() {});

        $this->notifyAlipayment([])->shouldBe('success');
    }

    public function it_notify_alipay_with_closure_output_fail(
        AlipayService $alipayService,
        ActivityEnrollPaymentRepository $activityEnrollPaymentRepository,
        ActivityEnrollIncomeRepository $activityEnrollIncomeRepository,
        ActivityApplicantService $activityApplicantService
    ) {
        $trade = $this->gen_alipayment_mock(
                                    $activityEnrollPaymentRepository,
                                    $activityEnrollIncomeRepository,
                                    $activityApplicantService);

        $alipayService->tradeUpdated([], Argument::that(function ($arg) use ($trade) {
            call_user_func($arg, $trade);
            return true;            // tell spec argument 1 match a closure if true
        }))->will(function() {
            echo 'fail';            // test echo. tradeUpdated should echo 'fail'
        });

        $this->notifyAlipayment([])->shouldBe('fail');
    }

    public function it_notify_alipayment_with_closure_if_exception_throwed(
        AlipayService $alipayService,
        ActivityEnrollPaymentRepository $activityEnrollPaymentRepository,
        ActivityEnrollIncomeRepository $activityEnrollIncomeRepository,
        ActivityApplicantService $activityApplicantService
    ) {
        $trade = $this->gen_alipayment_mock(
                                    $activityEnrollPaymentRepository,
                                    $activityEnrollIncomeRepository,
                                    $activityApplicantService);
        $alipayService->tradeUpdated([], Argument::that(function ($arg) use ($trade) {
            call_user_func($arg, $trade);
            return true;            // tell spec argument 1 match a closure if true
        }))->willThrow(\Exception::class);;

        $this->notifyAlipayment([])->shouldBe('fail');
    }

    private function gen_wxpayment_mock(
        ActivityEnrollPaymentRepository $activityEnrollPaymentRepository,
        ActivityEnrollIncomeRepository $activityEnrollIncomeRepository,
        ActivityApplicantService $activityApplicantService
    ) {
        $payment = (new ActivityEnrollPayment)
            ->setStatus(ActivityEnrollPayment::STATUS_WAIT)
            ->setActivity((new Activity)->setId(1))
            ->setFee(100);
        $trade = new \stdClass;
        $trade->orderNo = 'T8986DC81A8CAAAAEEB9D219B68D6ZIP';
        $trade->transactionId = '1217752501201407033233368018';
        $trade->paymentTime = '20150722133525';
        $trade->fee = 100;

        $this->gen_notify_payment_handler_mock(
            $activityEnrollPaymentRepository,
            $activityEnrollIncomeRepository,
            $activityApplicantService,
            [
                'orderNo'       => $trade->orderNo,
                'tradeNo'       => $trade->transactionId,
                'paymentTime'   => new \DateTime($trade->paymentTime),
                'fee'           => $trade->fee,
            ], $payment);

        return $trade;
    }

    private function gen_alipayment_mock(
        ActivityEnrollPaymentRepository $activityEnrollPaymentRepository,
        ActivityEnrollIncomeRepository $activityEnrollIncomeRepository,
        ActivityApplicantService $activityApplicantService
    ) {
        $payment = (new ActivityEnrollPayment)
            ->setStatus(ActivityEnrollPayment::STATUS_WAIT)
            ->setActivity((new Activity)->setId(1))
            ->setFee(100);
        $trade = new \stdClass;
        $trade->orderNo = 'T8986DC81A8CAAAAEEB9D219B68D6ZIP';
        $trade->tradeNo = '1217752501201407033233368018';
        $trade->paymentTime = '2015-07-22 13:35:25';
        $trade->fee = 1;

        $this->gen_notify_payment_handler_mock(
            $activityEnrollPaymentRepository,
            $activityEnrollIncomeRepository,
            $activityApplicantService,
            [
                'orderNo'       => $trade->orderNo,
                'tradeNo'       => $trade->tradeNo,
                'paymentTime'   => new \DateTime($trade->paymentTime),
                'fee'           => $trade->fee * 100,       // yuan to fen
            ], $payment);

        return $trade;
    }


    private function gen_notify_payment_handler_mock(
        ActivityEnrollPaymentRepository $activityEnrollPaymentRepository,
        ActivityEnrollIncomeRepository $activityEnrollIncomeRepository,
        ActivityApplicantService $activityApplicantService,
        array $trade, ActivityEnrollPayment $payment
    ) {
        $activityEnrollPaymentRepository->findOneByOrderNo($trade['orderNo'], true)
                                        ->shouldBeCalledTimes(1)
                                        ->willReturn($payment);
        $activityEnrollPaymentRepository->updateAfterPayed($trade['orderNo'],
            ActivityEnrollPayment::STATUS_SUCCESS,
            $trade['tradeNo'], $trade['paymentTime']
        )->shouldBeCalledTimes(1);
        $activityEnrollIncomeRepository->increaseTotalFeeForPaymentSuccess(
            $payment->getActivity()->getId(), $payment->getFee()
        )->shouldBeCalledTimes(1);
        $activityApplicantService->onActivityApplicantPaymentSuccess($trade['orderNo'])
                                ->shouldBeCalledTimes(1);
    }
}
