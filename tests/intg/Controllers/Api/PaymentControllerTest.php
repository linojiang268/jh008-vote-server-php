<?php
namespace intg\Jihe\Controllers\Api;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use intg\Jihe\TestCase;
use intg\Jihe\RequestSignCheck;
use intg\Jihe\AuthDeviceCheck;
use \PHPUnit_Framework_Assert as Assert;

class PaymentControllerTest extends TestCase
{
    use DatabaseTransactions;
    use RequestSignCheck;
    use AuthDeviceCheck;

    //=========================================
    //      wxpayAppPrepareTrade
    //=========================================
    public function testWxpayAppPrepareTradeSuccessfully()
    {
        // prepare data
        $orderNo = 'T8986DC81A8CAAAAEEB9D219B68D6ZIP';
        $user = $this->prepareApplicantData($orderNo);
        $this->mockWxpayAppService();

        $this->actingAs($user)
            ->ajaxPost('api/payment/wxpay/app/prepay', [
                'order_no'  => $orderNo,
            ])->seeJsonContains([
                'code'      => 0,
                'prepay_id' => '1234567890',
            ]);

        $this->seeInDatabase('activity_enroll_payments', [
            'activity_id'   => 1,
            'user_id'       => 1,
            'fee'           => 100,
            'channel'       => \Jihe\Entities\ActivityEnrollPayment::CHANNEL_WXPAY,
            'order_no'      => $orderNo,
            'trade_no'      => null,
            'payed_at'      => null,
            'status'        => \Jihe\Entities\ActivityEnrollPayment::STATUS_WAIT,
        ]);
    }

    public function testWxpayAppPrepareTradeMoreThanOnceSuccessfully()
    {
        // prepare data
        $orderNo = 'T8986DC81A8CAAAAEEB9D219B68D6ZIP';
        $user = $this->prepareApplicantData($orderNo);
        factory(\Jihe\Models\ActivityEnrollPayment::class)->create([
            'activity_id'   => 1,
            'user_id'       => 1,
            'fee'           => 100,
            'channel'       => \Jihe\Entities\ActivityEnrollPayment::CHANNEL_WXPAY,
            'order_no'      => $orderNo,
            'trade_no'      => null,
            'payed_at'      => null,
            'status'        => \Jihe\Entities\ActivityEnrollPayment::STATUS_WAIT,
        ]);

        $this->mockWxpayAppService();

        $this->actingAs($user)
            ->ajaxPost('api/payment/wxpay/app/prepay', [
                'order_no'  => $orderNo,
            ])->seeJsonContains([
                'code'      => 0,
                'prepay_id' => '1234567890',
            ]);
    }

    public function testWxpayAppPrepareTradeNoPermission()
    {
        // prepare data
        $orderNo = 'T8986DC81A8CAAAAEEB9D219B68D6ZIP';
        $this->prepareApplicantData($orderNo);
        // 代支付用户与活动报名用户不同，不能支付
        $user = factory(\Jihe\Models\User::class)->create([
            'id'        => 3,
            'mobile'    => '13800138001',
        ]);

        $this->mockWxpayAppService();

        $this->actingAs($user)
            ->ajaxPost('api/payment/wxpay/app/prepay', [
                'order_no'  => $orderNo,
            ])->seeJsonContains([ 'code' => 10000 ]);
        $this->assertContains(
            '"\u60a8\u4e0d\u80fd\u652f\u4ed8\u8be5\u62a5\u540d"',   // 您不能支付该报名
            $this->response->getContent()
        );
    }

    public function testWxpayAppPrepareTradeApplicantExpired()
    {
        // prepare data
        $orderNo = 'T8986DC81A8CAAAAEEB9D219B68D6ZIP';
        $user = $this->prepareApplicantData($orderNo, [
                    'expire_at' => date('Y-m-d H:i:s', strtotime('-10 day'))
                    ]);

        $this->mockWxpayAppService();

        $this->actingAs($user)
            ->ajaxPost('api/payment/wxpay/app/prepay', [
                'order_no'  => $orderNo,
            ])->seeJsonContains([ 'code' => 10000 ]);
        $this->assertContains(
            // 报名申请过期了，请重新报名
            '"\u62a5\u540d\u7533\u8bf7\u8fc7\u671f\u4e86\uff0c\u8bf7\u91cd\u65b0\u62a5\u540d"',
            $this->response->getContent()
        );
    }

    //=========================================
    //      wxpayWebPrepareTrade
    //=========================================
    public function testWxpayWebPrepareTradeSuccessfully()
    {
        // prepare data
        $orderNo = 'T8986DC81A8CAAAAEEB9D219B68D6ZIP';
        $openId = 'oUpF8uMuAJO_M2pxb1Q9zNjWeS6o';
        $user = $this->prepareApplicantData($orderNo);

        $this->mockWxpayWebService();

        $this->ajaxPost('api/payment/wxpay/web/prepay', [
                'mobile'    => $user->mobile,
                'openid'    => $openId,
                'order_no'  => $orderNo,
            ])->seeJsonContains([
                'code'      => 0,
                'appId'     => '123456',
                'timeStamp' => '1441857383',
                'nonceStr'  => 'abcdefg',
                'package'   => 'prepay_id=wx201411101639507cbf6ffd8b0779950874',
                'signType'  => 'MD5',
                'paySign'   => '0CB01533B8C1EF103065174F50BCA001',
            ]);

        $this->seeInDatabase('activity_enroll_payments', [
            'activity_id'   => 1,
            'user_id'       => 1,
            'fee'           => 100,
            'channel'       => \Jihe\Entities\ActivityEnrollPayment::CHANNEL_WXPAY,
            'order_no'      => $orderNo,
            'trade_no'      => null,
            'payed_at'      => null,
            'status'        => \Jihe\Entities\ActivityEnrollPayment::STATUS_WAIT,
        ]);
    }

    //=========================================
    //     wxpayNotify
    //=========================================
    public function testWxpayNotifySuccessfully()
    {
        // prepare data
        $orderNo = 'JH2015081111093555C9676FC1C32386';
        $this->prepareApplicantData($orderNo);
        $this->mockWxpayConfig();
        factory(\Jihe\Models\ActivityEnrollPayment::class)->create([
            'activity_id'   => 1,
            'user_id'       => 1,
            'fee'           => 100,
            'channel'       => \Jihe\Entities\ActivityEnrollPayment::CHANNEL_WXPAY,
            'order_no'      => $orderNo,
            'trade_no'      => null,
            'payed_at'      => null,
            'status'        => \Jihe\Entities\ActivityEnrollPayment::STATUS_WAIT,
        ]);
        factory(\Jihe\Models\ActivityEnrollIncome::class)->create([
            'activity_id'   => 1,
            'team_id'       => 1,
            'total_fee'     => 100,
        ]);

        $content = file_get_contents(__DIR__ . '/test-data/wxpay_payed_notify.txt');
        $this->call('POST', 'api/payment/wxpay/pay/notify', [], [], [], [], $content);
        $this->seeStatusCode(200);
        $response = $this->response->getContent();
        $response = simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA);
        Assert::assertEquals('SUCCESS', $response->return_code);

        $this->seeInDatabase('activity_applicants', [
            'order_no'      => $orderNo,
            'status'        => \Jihe\Models\ActivityApplicant::STATUS_SUCCESS,
        ]);
        $this->seeInDatabase('activity_enroll_payments', [
            'activity_id'   => 1,
            'order_no'      => $orderNo,
            'trade_no'      => '1002120192201508110596327442',  // trade no has been filled
            'payed_at'      => '2015-08-11 11:11:29',   // payed_at has been filled
        ]);
        $this->seeInDatabase('activity_enroll_incomes', [
            'team_id'       => 1,
            'activity_id'   => 1,
            'total_fee'     => 200,     // total fee increased to 200
        ]);
        $this->seeInDatabase('activity_members', [
            'activity_id'   => 1,
            'user_id'       => 1,
            'group_id'      => 0,
            'role'          => 0,
        ]);
    }

    public function testWxpayNotifyMoreThanOnceSuccessful()
    {
        // prepare data
        $orderNo = 'JH2015081111093555C9676FC1C32386';
        $this->prepareApplicantData($orderNo);
        $this->mockWxpayConfig();
        factory(\Jihe\Models\ActivityEnrollPayment::class)->create([
            'activity_id'   => 1,
            'user_id'       => 1,
            'fee'           => 100,
            'channel'       => \Jihe\Entities\ActivityEnrollPayment::CHANNEL_WXPAY,
            'order_no'      => $orderNo,
            'trade_no'      => '1004400740201409030005092168',
            'payed_at'      => '2014-09-03 13:15:40',
            'status'        => \Jihe\Entities\ActivityEnrollPayment::STATUS_SUCCESS,
        ]);
        factory(\Jihe\Models\ActivityEnrollIncome::class)->create([
            'activity_id'   => 1,
            'team_id'       => 1,
            'total_fee'     => 100,
        ]);

        $content = file_get_contents(__DIR__ . '/test-data/wxpay_payed_notify.txt');
        $this->call('POST', 'api/payment/wxpay/pay/notify', [], [], [], [], $content);
        $this->seeStatusCode(200);
        $response = $this->response->getContent();
        $response = simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA);
        Assert::assertEquals('SUCCESS', $response->return_code);

        $this->seeInDatabase('activity_enroll_payments', [
            'activity_id'   => 1,
            'order_no'      => $orderNo,
            'trade_no'      => '1004400740201409030005092168',
            'payed_at'      => '2014-09-03 13:15:40',
        ]);
        $this->seeInDatabase('activity_enroll_incomes', [
            'team_id'       => 1,
            'activity_id'   => 1,
            'total_fee'     => 100,     // total fee not change
        ]);
    }

    public function testWxpayNotifyNotFoundOrderSuccessfully()
    {
        $orderNo = 'JH2015081111093555C9676FC1C32386';
        $this->prepareApplicantData($orderNo);
        $this->mockWxpayConfig();

        $content = file_get_contents(__DIR__ . '/test-data/wxpay_payed_notify.txt');

        $this->call('POST', 'api/payment/wxpay/pay/notify', [], [], [], [], $content);
        $this->seeStatusCode(200);
        $response = $this->response->getContent();
        $response = simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA);
        Assert::assertEquals('SUCCESS', $response->return_code);
    }

    public function testWxpayNotifyWithErrorReturnCode()
    {
        $content = file_get_contents(__DIR__ . '/test-data/wxpay_payed_notify_error.txt');

        $this->call('POST', 'api/payment/wxpay/pay/notify', [], [], [], [], $content);
        $this->seeStatusCode(200);
        $response = $this->response->getContent();
        $response = simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA);
        Assert::assertEquals('FAIL', $response->return_code);
    }

    //=========================================
    //     wxpayWebNotify
    //=========================================
    public function testWxpayWebNotifySuccessfully()
    {
        // prepare data
        $orderNo = 'JH2015081111093555C9676FC1C32386';
        $this->prepareApplicantData($orderNo);
        $this->mockWxpayMpConfig();
        factory(\Jihe\Models\ActivityEnrollPayment::class)->create([
            'activity_id'   => 1,
            'user_id'       => 1,
            'fee'           => 100,
            'channel'       => \Jihe\Entities\ActivityEnrollPayment::CHANNEL_WXPAY,
            'order_no'      => $orderNo,
            'trade_no'      => null,
            'payed_at'      => null,
            'status'        => \Jihe\Entities\ActivityEnrollPayment::STATUS_WAIT,
        ]);
        factory(\Jihe\Models\ActivityEnrollIncome::class)->create([
            'activity_id'   => 1,
            'team_id'       => 1,
            'total_fee'     => 100,
        ]);

        $content = file_get_contents(__DIR__ . '/test-data/wxpay_payed_notify.txt');
        $this->call('POST', 'api/payment/wxpay/webpay/notify', [], [], [], [], $content);
        $this->seeStatusCode(200);
        $response = $this->response->getContent();
        $response = simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA);
        Assert::assertEquals('SUCCESS', $response->return_code);

        $this->seeInDatabase('activity_applicants', [
            'order_no'      => $orderNo,
            'status'        => \Jihe\Models\ActivityApplicant::STATUS_SUCCESS,
        ]);
        $this->seeInDatabase('activity_enroll_payments', [
            'activity_id'   => 1,
            'order_no'      => $orderNo,
            'trade_no'      => '1002120192201508110596327442',  // trade no has been filled
            'payed_at'      => '2015-08-11 11:11:29',   // payed_at has been filled
        ]);
        $this->seeInDatabase('activity_enroll_incomes', [
            'team_id'       => 1,
            'activity_id'   => 1,
            'total_fee'     => 200,     // total fee increased to 200
        ]);
        $this->seeInDatabase('activity_members', [
            'activity_id'   => 1,
            'user_id'       => 1,
            'group_id'      => 0,
            'role'          => 0,
        ]);
    }

    //=========================================
    //      alipayAppPrepareTrade
    //=========================================
    public function testAlipayAppPrepareTradeSuccessfully()
    {
        // prepare data
        $orderNo = 'T8986DC81A8CAAAAEEB9D219B68D6ZIP';
        $user = $this->prepareApplicantData($orderNo);
        $this->mockAlipayConfig();

        $this->actingAs($user)
            ->ajaxPost('api/payment/alipay/app/prepay', [
                'order_no'      => $orderNo,
            ])->seeJsonContains([
                'code'      => 0,
            ]);
        $response = json_decode($this->response->getContent(), true);
        parse_str($response['payment_data'], $paymentData);
        self::assertEquals('"' . $orderNo . '"', $paymentData['out_trade_no']);

        $this->seeInDatabase('activity_enroll_payments', [
            'activity_id'   => 1,
            'user_id'       => 1,
            'fee'           => 100,
            'channel'       => \Jihe\Entities\ActivityEnrollPayment::CHANNEL_ALIPAY,
            'order_no'      => $orderNo,
            'trade_no'      => null,
            'payed_at'      => null,
            'status'        => \Jihe\Entities\ActivityEnrollPayment::STATUS_WAIT,
        ]);
    }

    //=========================================
    //     alipayNotify
    //=========================================
    public function testAlipayNotifySuccessfully()
    {
        // prepare data
        $orderNo = '00001_52b01e7571b14fa7768b4569';
        $this->prepareApplicantData($orderNo);
        $this->mockAlipayConfig();
        factory(\Jihe\Models\ActivityEnrollPayment::class)->create([
            'activity_id'   => 1,
            'user_id'       => 1,
            'fee'           => 100,
            'channel'       => \Jihe\Entities\ActivityEnrollPayment::CHANNEL_ALIPAY,
            'order_no'      => $orderNo,
            'trade_no'      => null,
            'payed_at'      => null,
            'status'        => \Jihe\Entities\ActivityEnrollPayment::STATUS_WAIT,
        ]);
        factory(\Jihe\Models\ActivityEnrollIncome::class)->create([
            'activity_id'   => 1,
            'team_id'       => 1,
            'total_fee'     => 100,
        ]);

        parse_str(file_get_contents(__DIR__ . '/test-data/alipay_payed_notify.txt'), $post);
        $this->call('POST', 'api/payment/alipay/pay/notify', $post);
        $this->seeStatusCode(200);
        $response = $this->response->getContent();
        Assert::assertEquals('success', $response);

        $this->seeInDatabase('activity_applicants', [
            'order_no'      => $orderNo,
            'status'        => \Jihe\Models\ActivityApplicant::STATUS_SUCCESS,
        ]);
        $this->seeInDatabase('activity_enroll_payments', [
            'activity_id'   => 1,
            'order_no'      => $orderNo,
            'trade_no'      => '2013121763945981',      // trade no has been filled
            'payed_at'      => '2013-12-17 17:50:58',   // payed_at has been filled
        ]);
        $this->seeInDatabase('activity_enroll_incomes', [
            'team_id'       => 1,
            'activity_id'   => 1,
            'total_fee'     => 200,     // total fee increased to 200
        ]);
    }

    private function prepareApplicantData($orderNo, array $applicants = [])
    {
        // prepare data
        factory(\Jihe\Models\City::class)->create([
            'id'    => 1,
        ]);
        // team creator
        factory(\Jihe\Models\User::class)->create([
            'id'        => 2,
            'mobile'    => '13900139000',
        ]);
        $applicantUser = factory(\Jihe\Models\User::class)->create([
            'id'        => 1,
            'mobile'    => '13800138000',
        ]);
        factory(\Jihe\Models\Team::class)->create([
            'id'            => 1,
            'city_id'       => 1,
            'creator_id'    => 2,
        ]);
        factory(\Jihe\Models\Activity::class)->create([
            'id'            => 1,
            'city_id'       => 1,
            'team_id'       => 1,
            'enroll_fee'    => 100,
        ]);
        $expireAt = isset($applicants['expire_at']) ? $applicants['expire_at'] :
                        date('Y-m-d H:i:s', strtotime('240 seconds'));
        factory(\Jihe\Models\ActivityApplicant::class)->create([
            'order_no'      => $orderNo,
            'activity_id'   => 1,
            'user_id'       => $applicantUser->id,
            'mobile'        => '13800138000',
            'status'        => \Jihe\Models\ActivityApplicant::STATUS_PAY,
            'expire_at'     => $expireAt,
        ]);

        return $applicantUser;
    }

    private function mockWxpayAppService()
    {
        $wxpayAppService = \Mockery::mock(\Jihe\Services\Payment\Wxpay\WxpayAppService::class);

        $response = new \stdClass;
        $response->responseCode = 'SUCCESS';
        $response->prepayId = '1234567890';
        $response->appid = '123456';
        $response->mchid = 'aaaaaaa';
        $response->nonceStr = 'abcdefg';
        $wxpayAppService->shouldReceive('placeOrder')->withAnyArgs()->andReturn($response);

        $this->app[\Jihe\Services\Payment\Wxpay\WxpayAppService::class] = $wxpayAppService;
        return $wxpayAppService;
    }

    private function mockWxpayWebService()
    {
        $wxpayWebService = \Mockery::mock(\Jihe\Services\Payment\Wxpay\WxpayWebService::class);
        $response = new \stdClass;
        $response->responseCode = 'SUCCESS';
        $jsApiParams = new \stdClass;
        $jsApiParams->appId = '123456';
        $jsApiParams->timeStamp = 1441857383;
        $jsApiParams->nonceStr = 'abcdefg';
        $jsApiParams->package = 'prepay_id=wx201411101639507cbf6ffd8b0779950874';
        $jsApiParams->signType = 'MD5';
        $jsApiParams->paySign = '0CB01533B8C1EF103065174F50BCA001';
        $response->jsApiParams = $jsApiParams;
        $wxpayWebService->shouldReceive('placeOrder')
                        ->withAnyArgs()
                        ->andReturn($response);
        $this->app[\Jihe\Services\Payment\Wxpay\WxpayWebService::class] = $wxpayWebService;

        return $wxpayWebService;
    }

    private function mockWxpayConfig()
    {
        $config = app()->make('config');
        $config['payment.wx_app_pay'] = [
            'appid' => 'wx12345678',
            'mchid' => '1900000109',
            'key'   => 'abcedfghigk',
        ];
    }

    private function mockWxpayMpConfig()
    {
        $config = app()->make('config');
        $config['payment.wx_mp_pay'] = [
            'appid' => 'wx12345678',
            'mchid' => '1900000109',
            'key'   => 'abcedfghigk',
        ];
    }

    private function mockAlipayConfig()
    {
        $config = app()->make('config');
        $config['payment.alipay'] = [
            'partner'   => '2088701892019087',
            'cert_file'         => __DIR__ . '/test-data/rsa_private_key.pem',
            'ali_cert_file'     => __DIR__ . '/test-data/alipay_cert.pem',
            'notify_url'        => 'http://localhost/trade.php',
            'seller'            => 'alipay@zero2all.com',
            'refund_notify_url' => 'http://localhost/refund.php',
            'secure_key'        => 'cwygc4fpvwevu45m2jnh43w54vir9eqw',
        ];
    }
}
