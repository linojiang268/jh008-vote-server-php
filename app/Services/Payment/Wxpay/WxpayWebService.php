<?php
namespace Jihe\Services\Payment\Wxpay;


class WxpayWebService extends AbstractWxpayService
{
    /**
     * place a unified order and get payment argument
     *
     * @param string $openid        user openid in weixin
     * @param string $orderNo       order number of merchant
     * @param integer $fee          payment total fee, unit: fen
     * @param string $description   order's description
     * @param string $clientIp      ip of user who request a payment. format as: 8.8.8.8
     * @param integer $expireAfter  order will be expired after $expireAfter seconds.
     *                              a expired order can not be payed
     * @param string $detail        order's products detail
     * @param string $attach        a transparent value, wxpay will transfer back this value
     *                              without any changes
     *
     * @return \stdClass            return of this trade. with following fields set:
     *                              - responseCode  'SUCCESS' for trade success, 
     *                                              'FAIL'  for trade failure,
     *                              - errCode       indicate the error code if responseCode equals 'FAIL'
     *                              - errMsg        description for errCode
     *                              the following fields are available if responseCode equals 'SUCCESS' 
     *                              - tradeType     available values are: APP, NATIVE
     *                              - appid
     *                              - jsApiParams   params will be call in Weixin jsapi, properties as below
     *                                  - appId     appid
     *                                  - timeStamp
     *                                  - nonceStr
     *                                  - package   prepay_id=xxxx
     *                                  - signType  MD5
     *                                  - paySign
     *
     * @throw \Exception            exception will be throw if query request failed 
     *                                  or field return_code missed in response
     */
    public function placeOrder($openid, $orderNo, $fee, $description, $clientIp,
                                    $expireAfter = 3600,
                                    $detail = '',
                                    $attach = ''
    ) {
        $timeExpire = $expireAfter ? 
            date('YmdHis', strtotime('+' . $expireAfter . ' seconds')) : '';
        $params = [
            'openid'            => $openid,
            'out_trade_no'      => $orderNo,
            'total_fee'         => $fee,
            'body'              => $description,
            'spbill_create_ip'  => $clientIp,
            'time_start'        => date('YmdHis'),
            'time_expire'       => $timeExpire,
            'detail'            => $detail,
            'attach'            => $attach,
            'trade_type'        => self::TRADE_TYPE_WEB,
        ];

        $order = parent::placeUnifiedOrder($params);
        $this->setJsApiParams($order);

        return $order;
    }

    /**
     * called wehn a trade's status changes in asynchronous manner.
     *
     * @param array $notification   notification (typically the whole $_POST) from Wxpay
     * @param callable $callback    callback will be passed, the parsed trade as its param
     *                                  callback receive a trade object, which properities lists as below:
     *                                  1). orderNo         -- the order# related to the trade
     *                                  2). openid          -- user unique identifier in merchant appid
     *                                  3). tradeType       -- trade type, such as APP, NATIVE, JSAPI etc.
     *                                  4). bankType        -- bank type. such as CMC
     *                                  5). fee             -- total fee user payed
     *                                  6). transactionId   -- Wxpay payment order#
     *                                  7). attach          -- transparent value, Wxpay not change it
     *                                  8). paymentTime    -- payment end time. format: yyyyMMddHHmmss
     *
     * @return string               SUCCESS or FAIL
     *
     * @throws \Exception exception will thrown in case of invalid signature
     *                              or bad trade status
     */
    public function tradeUpdated(array $notification, callable $callback)
    {
        if ($notification['return_code'] != 'SUCCESS') {
            return 'FAIL';
        }
        $this->ensureResponseNotForged($notification);

        $trade = $this->parseTradeUpdateNotification($notification);

        if ('SUCCESS' == $trade->returnCode && 'SUCCESS' == $trade->resultCode) {
            if (call_user_func($callback, $trade)) {
                return 'SUCCESS';
            } else {
                return 'FAIL';
            }
        }

        return 'FAIL';
    }


    /**
     * find trade's related order#, status, etc. from given text
     *
     * @param array $notification       notification from Wxpay
     *
     * @return object   an object with the following fields set
     *                  1). orderNo         -- the order# related to the trade
     *                  2). returnCode      -- 'SUCCESS' for communication success.
     *                  3). resultCode      -- 'SUCCESS' for trade success.
     *                  4). openid          -- user unique identifier in merchant appid
     *                  5). tradeType       -- trade type, such as APP, NATIVE, JSAPI etc.
     *                  6). bankType        -- bank type. such as CMC
     *                  7). fee             -- total fee user payed
     *                  8). transactionId   -- Wxpay payment order#
     *                  9). attach          -- transparent value, Wxpay not change it
     *                  10). paymentTime    -- payment end time. format: yyyyMMddHHmmss
     */
    private function parseTradeUpdateNotification(array $notification)
    {
        $trade = new \stdClass();
        $trade->orderNo         = $notification['out_trade_no'];
        $trade->returnCode      = $notification['return_code'];
        $trade->resultCode      = $notification['result_code'];
        $trade->openid          = $notification['openid'];
        $trade->tradeType       = $notification['trade_type'];
        $trade->bankType        = $notification['bank_type'];
        $trade->fee             = $notification['total_fee'];
        $trade->transactionId   = $notification['transaction_id'];
        $trade->attach          = array_get($notification, 'attach');
        $trade->paymentTime     = $notification['time_end'];

        return $trade;
    }

    private function setJsApiParams($order)
    {
        if ($order->responseCode == 'SUCCESS') {
            $jsApiParams = new \stdClass;
            $jsApiParams->appId = $this->appid;
            $jsApiParams->timeStamp = time();
            $jsApiParams->nonceStr = $this->getNonceStr();
            $jsApiParams->package = 'prepay_id=' . $order->prepayId;
            $jsApiParams->signType = self::SIGN_TYPE;
            $jsApiParams->paySign = $this->signData([
                'appId'     => $jsApiParams->appId,
                'timeStamp' => $jsApiParams->timeStamp,
                'nonceStr'  => $jsApiParams->nonceStr,
                'package'   => $jsApiParams->package,
                'signType'  => $jsApiParams->signType,
            ]);

            $order->jsApiParams = $jsApiParams;
        }
    }
}
