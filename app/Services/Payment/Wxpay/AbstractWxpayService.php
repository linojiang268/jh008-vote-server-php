<?php
namespace Jihe\Services\Payment\Wxpay;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;

use Jihe\Utils\StringUtil;


class AbstractWxpayService
{
    const UNIFIED_ORDER_URL = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
    const ORDER_QUERY_URL = 'https://api.mch.weixin.qq.com/pay/orderquery';
    const REFUND_URL = 'https://api.mch.weixin.qq.com/secapi/pay/refund';
    const REFUND_QUERY_URL = 'https://api.mch.weixin.qq.com/pay/refundquery';

    const RESPONSE_SUCCESS  = 'SUCCESS';
    const RESPONSE_FAIL     = 'FAIL';

    const TRADE_TYPE_APP = 'APP';
    const TRADE_TYPE_WEB = 'JSAPI';

    const SIGN_TYPE = 'MD5';

    const ERROR_CODE_COMMUNICATION          = 'COMMUNICATION';         // 通信错误
    const ERROR_CODE_NOAUTH                 = 'NOAUTH';                 // 商户无此接口权限
    const ERROR_CODE_NOTENOUGH              = 'NOTENOUGH';              // 余额不足
    const ERROR_CODE_ORDERPAID              = 'ORDERPAID';              // 商户订单已支付
    const ERROR_CODE_ORDERCLOSED            = 'ORDERCLOSED';            // 订单已关闭
    const ERROR_CODE_SYSTEMERROR            = 'SYSTEMERROR';            // 系统错误
    const ERROR_CODE_APPID_NOT_EXIST        = 'APPID_NOT_EXIST';        // APPID不存在
    const ERROR_CODE_MCHID_NOT_EXIST        = 'MCHID_NOT_EXIST';        // MCHID不存在
    const ERROR_CODE_APPID_MCHID_NOT_MATCH  = 'APPID_MCHID_NOT_MATCH';  // appid和mch_id不匹配
    const ERROR_CODE_LACK_PARAMS            = 'LACK_PARAMS';            // 缺少参数
    const ERROR_CODE_OUT_TRADE_NO_USED      = 'OUT_TRADE_NO_USED';      // 商户订单号重复
    const ERROR_CODE_SIGNERROR              = 'SIGNERROR';              // 签名错误
    const ERROR_CODE_XML_FORMAT_ERROR       = 'XML_FORMAT_ERROR';       // xml格式错误
    const ERROR_CODE_REQUIRE_POST_METHOD    = 'REQUIRE_POST_METHOD';    // 请使用post方法
    const ERROR_CODE_POST_DATA_EMPTY        = 'POST_DATA_EMPTY';        // post数据为空
    const ERROR_CODE_NOT_UTF8               = 'NOT_UTF8';               // 编码格式错误
    const ERROR_CODE_ORDERNOTEXIST          = 'ORDERNOTEXIST';          // 此交易订单号不存在
    const ERROR_CODE_INVALID_TRANSACTIONID  = 'INVALID_TRANSACTIONID';  // 无效transaction_id
    const ERROR_CODE_UNKNOWN                = 'UNKNOWN';                // 未知错误

    // order state list
    const TRADE_STATE_SUCCESS               = 'SUCCESS';                // 支付成功
    const TRADE_STATE_REFUND                = 'REFUND';                 // 转入退款
    const TRADE_STATE_NOTPAY                = 'NOTPAY';                 // 未支付
    const TRADE_STATE_CLOSED                = 'CLOSED';                  // 已关闭
    const TRADE_STATE_REVOKED               = 'REVOKED';                // 已撤销
    const TRADE_STATE_USERPAYING            = 'USERPAYING';             // 用户支付中
    const TRADE_STATE_PAYERROR              = 'PAYERROR';               // 支付失败
    const TRADE_STATE_UNKNOWN               = 'UNKNOWN';                // 未知错误

    // refund status list
    const REFUND_STATUS_SUCCESS             = 'SUCCESS';                // 退款成功
    const REFUND_STATUS_FAIL                = 'FAIL';                   // 退款失败
    const REFUND_STATUS_PROCESSING          = 'PROCESSING';             // 退款处理中
    const REFUND_STATUS_NOTSURE             = 'NOTSURE';                // 未确定，需商户原退款单号重新发起
    const REFUND_STATUS_CHANGE              = 'CHANGE';                 // 转入代发
    const REFUND_STATUS_UNKNOWN             = 'UNKNOWN';                // 未知状态


    /** 
     * unique identification in weixin open platform or weixin public account. 
     * @var string
     */
    protected $appid;    

    /**
     * commercial tenant (商户) id
     *
     * @var string
     */
    protected $mchid;

    /**
     * used for generating signature for transaction
     *
     * @var string
     */
    protected $key;

    /**
     * url to be notified when trade status changes
     *
     * @var string
     */
    protected $notifyUrl;

    /**
     * file path to merchant's certificate
     *
     * @var string
     */
    private $certFile;

    /**
     * file path to merchant's private key.
     *
     * @var string
     */
    private $sslKeyFile;

    /**
     * Http client
     *
     * @var \GuzzleHttp\ClientInterface
     */
    protected $httpClient;


    public function __construct(array $config, ClientInterface $httpClient)
    {
        $this->appid            = array_get($config, 'appid');
        $this->mchid            = array_get($config, 'mchid');
        $this->key              = array_get($config, 'key');
        $this->notifyUrl        = array_get($config, 'notify_url');
        $this->certFile         = array_get($config, 'cert_file');
        $this->sslKeyFile       = array_get($config, 'sslkey_file');

        $this->httpClient = $httpClient;
    }


    /**
     * place a unified order and get payment argument
     *
     * @param array $params request params
     *
     * @return \stdClass            return of this trade. with following fields set:
     *                              - responseCode  'SUCCESS' for trade success, 
     *                                              'FAIL'  for trade failure,
     *                              - errCode       indicate the error code if responseCode equals 'FAIL'
     *                              - errMsg        description for errCode
     *                              the following fields are available if responseCode equals 'SUCCESS' 
     *                              - tradeType     available values are: APP, NATIVE
     *                              - prepayId      the prepare order id created by wxpay
     *                              - nonceStr      nonce string
     *                              - appid         appid
     *                              - mchid         mchid
     *                              - codeUrl       QR link, valid when tradeType is NATIVE, please see constant TRADE_TYPE_XXXX defined in this class
     */
    public function placeUnifiedOrder(array $params)
    {
        $params['notify_url'] = $this->notifyUrl;
        $this->setCommonSettingForRequestParams($params);
        $params['sign'] = $this->signData($params);

        return $this->postUnifiedOrderRequestAndParse($params);
    }


    /**
     * query a order and fetch order's detail
     *
     * @param string $orderNo       order# of merchant
     * @param string $transactionId identified a order in Wxpay system, order# will be ignored if transaction id supplied
     *
     * @return \stdClass    return of order detail. with following fields set:
     *                      -- responseCode 'SUCCESS' for query success
     *                      -- errCode      indicate the error code if responseCode equals 'FAIL'
     *                      -- errMsg       description for errCode
     *                      the following fields are available if responseCode equals 'SUCCESS'
     *                      -- deviceInfo   device# which invoke a payment
     *                      -- openid       user unique identifier in current merchant
     *                      -- isSubScribe  whether a user subscribe merchant's wx open account
     *                      -- tradeType    trade type, such as APP, NATIVE ets.
     *                      -- tradeState   available value as below:
     *                                      1) 'SUCCESS';         // 支付成功
     *                                      2) 'REFUND';          // 转入退款
     *                                      3) 'NOTPAY';          // 未支付
     *                                      4) 'CLOSED';          // 已关闭
     *                                      5) 'REVOKED';         // 已撤销
     *                                      6) 'USERPAYING';      // 用户支付中
     *                                      7) 'PAYERROR';        // 支付失败
     *                                      8) 'UNKNOWN';         // 未知错误
     *                      -- tradeStateDesc description for tradeState
     *                      -- bankType     扣款银行, such as CMC
     *                      -- fee          total payment fee, unit: fen
     *                      -- feeType      payment fee type, such as CNY
     *                      -- cashFee      total fee of cash payment order 
     *                      -- cashFeeType  cash payment fee type, such as CNY
     *                      -- couponFee    代金券或立减优惠金额
     *                      -- couponCount  代金券或立减优惠使用数量
     *                      -- transactionId payment order# in Wxpay system
     *                      -- orderNo      order# in merchant system
     *                      -- attach       transparent value supplied by user, 
     *                                      Wxpay return this value without any changes
     *                      -- paymentTime  order payed time, format: yyyyMMddHHmmss

     * @throw \Exception    exception will be throw if query request failed 
     *                          or field return_code missed in response
     */
    public function queryOrder($orderNo, $transactionId = '')
    {
        $params = [
            'transaction_id'    => $transactionId,
            'out_trade_no'      => $orderNo,
        ];
        $this->setCommonSettingForRequestParams($params);
        $params['sign'] = $this->signData($params);

        return $this->postQueryOrderRequestAndParse($params);
    }


    /**
     * refund money for a trade
     *
     * @params string $orderNo      order# of merchant
     * @params integer $fee         total money of order, unit: fen
     * @params integer $refundFee   refund money, unit: fee
     * @param string $transactionId identified a order in Wxpay system, order# will be ignored if transaction id supplied
     *
     * @return \stdClass            result of this refund. with following fields set:
     *                              -- responseCode SUCCESS for refund apply be accepted by Wxpay
     *                              -- errCode      indicate the error code if responseCode equals 'FAIL'
     *                              -- errMsg       description for errCode
     *                              -- refundNo     refund#, unique identifier to a trade apply
     * 
     */
    public function refundTradeApply($orderNo, $fee, $refundFee, $transactionId = '')
    {
        $params = [
            'transaction_id'    => $transactionId,
            'out_trade_no'      => $orderNo,
            'out_refund_no'     => $this->genRefundTradeNo(),
            'total_fee'         => $fee,
            'refund_fee'        => $refundFee,
            'refund_fee_type'   => 'CNY',
            'op_user_id'        => $this->mchid,
            ];
        $this->setCommonSettingForRequestParams($params);
        $params['sign'] = $this->signData($params);

        return $this->postRefundRequestAndParse($params);
    }


    /**
     * query refund status after a refund application
     *
     * @params string $refundNo     unique identifier to a trade application
     * @params string $orderNo      order# related a refund trade
     * @param string $transactionId identified a order in Wxpay system, order# will be ignored if transaction id supplied
     *
     * @return \stdClass            result of this refund. with following fields set:
     *                              -- responseCode 'SUCCESS' stands for request successfully
     *                              -- errMsg       error message if responseCode not equals to 'SUCCESS'
     *                              the following fields available if responseCode equals to 'SUCCESS'
     *                              -- refundFee    refund money, unit: fen
     *                              -- refundStatus available value as belows:
     *                                1) 'SUCCESS';        // 退款成功
     *                                2) 'FAIL';           // 退款失败
     *                                3) 'PROCESSING';     // 退款处理中
     *                                4) 'NOTSURE';        // 未确定，需商户原退款单号重新发起
     *                                5) 'CHANGE';         // 转入代发
     */
    public function queryRefund($refundNo, $orderNo, $transactionId = '')
    {
        $params = [
            'transaction_id'    => $transactionId,
            'out_trade_no'      => $orderNo,
            'out_refund_no'     => $refundNo,
        ];
        $this->setCommonSettingForRequestParams($params);
        $params['sign'] = $this->signData($params);

        return $this->postQueryRefundRequestAndParse($params);
    }


    private function postQueryRefundRequestAndParse($params)
    {
        $xml = $this->postRequest(self::REFUND_QUERY_URL, $params);

        $result = new \stdClass();
        $result->responseCode = self::RESPONSE_FAIL;
        $result->errMsg = isset($xml->return_msg) ? (string) $xml->return_msg : null;
        if ((string) $xml->return_code == 'SUCCESS') {
            $result->responseCode = self::RESPONSE_SUCCESS;
            $result->errMsg = null;
            $result->refundFee = (string) $xml->refund_fee_0;
            $result->refundStatus = self::transferRefundStatus((string) $xml->refund_status_0);
        }

        return $result;
    }


    private function postRefundRequestAndParse($params)
    {
        $xml = $this->postRequest(self::REFUND_URL, $params, true);

        $result = new \stdClass();
        $result->responseCode = self::RESPONSE_FAIL;
        $result->errCode = self::ERROR_CODE_COMMUNICATION;
        $result->errMsg = isset($xml->return_msg) ? (string) $xml->return_msg : null;
        $result->refundNo = $params['out_refund_no'];
        if ((string) $xml->return_code == 'SUCCESS') {
            if ((string) $xml->result_code == 'SUCCESS') {
                $result->responseCode = self::RESPONSE_SUCCESS;
                $result->errCode = null;
                $result->errMsg = null;
            } else {
                $result->errCode = isset($xml->err_code) ? 
                    self::transferErrorCode((string) $xml->err_code) : self::ERROR_CODE_UNKNOWN;; 
                $result->errMsg = isset($xml->err_code_des) ? 
                    (string) $xml->err_code_des : null;
            }
        }

        return $result;
    }


    private function genRefundTradeNo()
    {
        // constraint: 退款日期(8位当天日期)+流水号(24位)
        //
        // Our implementation:
        // - 14 chars     current date(8) and time (6)
        // - 13 chars     generate with uniqid()
        // - 5  chars     random number between [1, 99999]. if not 5 in length, left pad with '0'
        //
        return date('YmdHis') . uniqid() . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
    }


    private function postUnifiedOrderRequestAndParse($params)
    {
        $xml = $this->postRequest(self::UNIFIED_ORDER_URL, $params);

        $result = new \stdClass();
        $result->responseCode = self::RESPONSE_FAIL;
        $result->errCode = self::ERROR_CODE_COMMUNICATION;
        $result->errMsg = isset($xml->return_msg) ? (string) $xml->return_msg : null;
        if ((string) $xml->return_code == 'SUCCESS') {
            if ((string) $xml->result_code == 'SUCCESS') {
                $result->responseCode = self::RESPONSE_SUCCESS;
                $result->errCode = null;
                $result->errMsg = null;
                $result->tradeType = (string) $xml->trade_type;
                $result->prepayId = (string) $xml->prepay_id;
                $result->nonceStr = (string) $xml->nonce_str;
                $result->appid = $this->appid;
                $result->mchid = $this->mchid;
                $result->codeUrl = isset($xml->code_url) ? (string) $xml->code_url : null;
            } else {
                $result->errCode = isset($xml->err_code) ? 
                    self::transferErrorCode((string) $xml->err_code) : self::ERROR_CODE_UNKNOWN;; 
                $result->errMsg = isset($xml->err_code_des) ? 
                    (string) $xml->err_code_des : null;
            }
        }

        return $result;
    }


    private function postQueryOrderRequestAndParse($params)
    {
        $xml = $this->postRequest(self::ORDER_QUERY_URL, $params);

        $result = new \stdClass();
        $result->responseCode = self::RESPONSE_FAIL;
        $result->errCode = self::ERROR_CODE_COMMUNICATION;
        $result->errMsg = isset($xml->return_msg) ? (string) $xml->return_msg : null;
        if ((string) $xml->return_code == 'SUCCESS') {
            if ((string) $xml->result_code == 'SUCCESS') {
                $result->responseCode = self::RESPONSE_SUCCESS;
                $result->errCode = null;
                $result->errMsg = null;
                $result->deviceInfo = isset($xml->device_info) ? (string) $xml->device_info : null;
                $result->openid = (string) $xml->openid;
                $result->isSubScribe = ((string) $xml->is_subscribe) == 'Y' ? true : false;
                $result->tradeType = (string) $xml->trade_type;
                $result->tradeState = self::transferTradeState((string) $xml->trade_state);
                $result->tradeStateDesc = (string) $xml->trade_state_desc;
                $result->bankType = (string) $xml->bank_type;
                $result->fee = (int) $xml->total_fee;
                $result->feeType = isset($xml->fee_type) ? (string) $xml->fee_type : 'CNY';
                $result->cashFee = (int) $xml->cash_fee;
                $result->cashFeeType = isset($xml->cash_fee_type) ? (string) $xml->cash_fee_type : 'CNY';
                $result->couponFee = isset($xml->coupon_fee) ? (int) $xml->coupon_fee : null;
                $result->couponCount = isset($xml->coupon_count) ? (int) $xml->coupon_count : null;
                $result->transactionId = (string) $xml->transaction_id;
                $result->orderNo = (string) $xml->out_trade_no;
                $result->attach = isset($xml->attach) ? (string) $xml->attach : null;
                $result->paymentTime = (string) $xml->time_end;      // yyyyMMddHHmmss
            } else {
                $result->responseCode = self::RESPONSE_FAIL;
                $result->errCode = isset($xml->err_code) ? 
                    self::transferErrorCode((string) $xml->err_code) : null; 
                $result->errMsg = isset($xml->err_code_des) ? 
                    (string) $xml->err_code_des : null;
            }
        }

        return $result;
    }


    /**
     * post xml request
     *
     * @params string $url
     * @params array $params
     * @params boolean $useCert cert required if true.
     */
    private function postRequest($url, $params, $useCert = false)
    {
        $xmlBody = $this->data2Xml($params);
        $options = [
            RequestOptions::BODY    => $xmlBody,
            RequestOptions::VERIFY  => true,
        ];
        if ($useCert) {
            $options[RequestOptions::CERT] = $this->certFile;
            $options[RequestOptions::SSL_KEY] = $this->sslKeyFile;
        }
        $response = $this->httpClient->request('POST', $url, $options);
        if ($response->getStatusCode() != 200) {
            throw new \Exception('bad response from wxpay: ' . (string)$response->getBody());
        }

        $xml = simplexml_load_string((string) $response->getBody());
        if (empty($xml->return_code)) {
            throw new \Exception('bad response from wxpay: ' . (string)$response->getBody());
        }

        return $xml;
    }


    private function data2Xml(array $data)
    {
        $xml = '<xml>';
        foreach ($data as $k => $v) {
            if (is_numeric($v)) {
                $xml .= '<' . $k . '>' . $v . '</' . $k . '>';
            } else {
                $xml .= '<' . $k . '><![CDATA[' . $v . ']]></' . $k . '>';
            }
        }
        $xml .= '</xml>';

        return $xml;
    }

    
    // generate a random string with specified length
    protected function getNonceStr($length = 32)
    {
        return StringUtil::quickRandom($length);
    }


    /**
     * Generate request params signature
     *
     * @param array $data
     * 
     * @return string signed text
     */
    protected function signData(array $data)
    {
        // Step one: sort $data by key and convert to string
        ksort($data);
        $string = $this->toUrlParams($data);

        // Step two: append key to string
        $string .= '&key=' . $this->key;

        // Step three: md5 string
        $string = md5($string);

        // Step four: converting strings to uppercase
        $signed = strtoupper($string);

        return $signed;
    }


    /**
     * ensure that response comes from Wxpay.
     *
     * @param array $response       the notification to verify
     *
     * @throw \Exception
     */
    protected function ensureResponseNotForged(array $response)
    {
        if (empty($response['sign'])) {
            throw new \Exception('Forged trade notification');
        }

        if ($response['sign'] != $this->signData($response)) {
            throw new \Exception('Signature verification failed');
        }
    }


    private function toUrlParams(array $data)
    {
        $str = '';
        foreach ($data as $k => $v)
        {
            // key will be discard if it's value is empty
            if ($k != 'sign' && $v != '') {
                $str .= $k . '=' . $v . '&';
            }
        }
        $str = trim($str, '&');

        return $str;
    }


    private function setCommonSettingForRequestParams(& $params)
    {
        $params['appid'] = $this->appid;
        $params['mch_id'] = $this->mchid;
        $params['nonce_str'] = $this->getNonceStr();
    }

    
    private static function transferTradeState($state)
    {
        static $refundTradeMap = [
            'SUCCESS'       => self::TRADE_STATE_SUCCESS,
            'REFUND'        => self::TRADE_STATE_REFUND,
            'NOTPAY'        => self::TRADE_STATE_NOTPAY,
            'CLOSED'        => self::TRADE_STATE_CLOSED,
            'REVOKED'       => self::TRADE_STATE_REVOKED,
            'USERPAYING'    => self::TRADE_STATE_USERPAYING,
            'PAYERROR'      => self::TRADE_STATE_PAYERROR,
        ];

        return array_get($refundTradeMap, $state, self::TRADE_STATE_UNKNOWN);
    }


    protected static function transferErrorCode($code)
    {
        static $errCodeMap = [
            'NOAUTH'                => self::ERROR_CODE_NOAUTH,
            'NOTENOUGH'             => self::ERROR_CODE_NOTENOUGH,
            'ORDERPAID'             => self::ERROR_CODE_ORDERPAID,
            'ORDERCLOSED'           => self::ERROR_CODE_ORDERCLOSED,
            'SYSTEMERROR'           => self::ERROR_CODE_SYSTEMERROR,
            'APPID_NOT_EXIST'       => self::ERROR_CODE_APPID_NOT_EXIST,
            'MCHID_NOT_EXIST'       => self::ERROR_CODE_MCHID_NOT_EXIST,
            'APPID_MCHID_NOT_MATCH' => self::ERROR_CODE_APPID_MCHID_NOT_MATCH,
            'LACK_PARAMS'           => self::ERROR_CODE_LACK_PARAMS,
            'OUT_TRADE_NO_USED'     => self::ERROR_CODE_OUT_TRADE_NO_USED,
            'SIGNERROR'             => self::ERROR_CODE_SIGNERROR,
            'XML_FORMAT_ERROR'      => self::ERROR_CODE_XML_FORMAT_ERROR,
            'REQUIRE_POST_METHOD'   => self::ERROR_CODE_REQUIRE_POST_METHOD,
            'POST_DATA_EMPTY'       => self::ERROR_CODE_POST_DATA_EMPTY,
            'NOT_UTF8'              => self::ERROR_CODE_NOT_UTF8,
            'ORDERNOTEXIST'         => self::ERROR_CODE_ORDERNOTEXIST,
            'INVALID_TRANSACTIONID' => self::ERROR_CODE_INVALID_TRANSACTIONID,
        ];
        
        return array_get($errCodeMap, $code, self::ERROR_CODE_UNKNOWN);
    }


    private static function transferRefundStatus($status)
    {
        static $statusMap = [
            'SUCCESS'       => self::REFUND_STATUS_SUCCESS,
            'FAIL'          => self::REFUND_STATUS_FAIL,
            'PROCESSING'    => self::REFUND_STATUS_PROCESSING,
            'NOTSURE'       => self::REFUND_STATUS_NOTSURE,
            'CHANGE'        => self::REFUND_STATUS_CHANGE,
        ];

        return array_get($statusMap, $status, self::REFUND_STATUS_UNKNOWN);
    }
}
