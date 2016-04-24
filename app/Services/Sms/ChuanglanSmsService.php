<?php
namespace Jihe\Services\Sms;

use GuzzleHttp\RequestOptions;
use Jihe\Contracts\Services\Sms\SmsService as SmsServiceContract;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;

/**
 * Short message service implemented with Chuanglan's api
 */
class ChuanglanSmsService implements SmsServiceContract
{
    /**
     * base url for sending short message
     * @var string
     */
    const SEND_URL = 'http://222.73.117.158/msg/HttpBatchSendSM';
    
    /**
     * base url for querying quota
     * @var string
     */
    const QUOTA_URL = 'http://222.73.117.158/msg/QueryBalance';

    const RESPONSE_PHRASES = [
        '0'   => '提交成功',
        '101' => '无此用户',
        '102' => '密码错',
        '103' => '提交过快（提交速度超过流速限制）',
        '104' => '系统忙（因平台侧原因，暂时无法处理提交的短信）',
        '105' => '敏感短信（短信内容包含敏感词）',
        '106' => '消息长度错（>536或<=0）',
        '107' => '包含错误的手机号码',
        '108' => '手机号码个数错（群发>50000或<=0;单发>200或<=0）',
        '109' => '无发送额度（该用户可用短信数已使用完）',
        '110' => '不在发送时间内',
        '111' => '超出该账户当月发送额度限制',
        '112' => '无此产品，用户没有订购该产品',
        '113' => 'extno格式错（非数字或者长度不对）',
        '115' => '自动审核驳回',
        '116' => '签名不合法，未带签名（用户必须带签名的前提下）',
        '117' => 'IP地址认证错,请求调用的IP地址不是系统登记的IP地址',
        '118' => '用户没有相应的发送权限',
        '119' => '用户已过期',
    ];
    
    /**
     * http client
     * @var \GuzzleHttp\ClientInterface
     */
    private $client;
    
    /**
     * account used to send message
     * @var string
     */
    private $account;
    
    /**
     * password corresponding to account
     * @var string
     */
    private $password;

    /**
     * a part of sender's number that will be used to send the message
     * @var null|string
     */
    private $affix;

    /**
     * sms signature - suffix appended to the message
     *
     * @var string
     */
    private $signature;

    /**
     * @param string $account           account used to send message
     * @param string $password          password paired with account, should be MD5'd
     * @param string $affix             (optional) 附加号码 a part of sender's number that will be used to
     *                                  send the message. not more than 6 digits, suggested 4.
     * @param ClientInterface $client   client used to sending http request
     */
    public function __construct($account, $password, $affix = null, ClientInterface $client)
    {
        $this->account   = $account;
        $this->password  = $password;
        $this->affix     = $affix;

        $this->client = $client;
    }
    
    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Services\Sms\SmsService::send()
     *
     * @param array $options     available options include:
     *                           - send_time   optinal (time in YYYYMMDDHHIISS format) when will this message be
     *                                         delivered, if null/empty, the message will
     *                           - send_type   optional  message will be delivered as 普通短信 (SEND_TYPE_PLAIN, default)
     *                                         or 长短信(SEND_TYPE_LONG)
     *                           - expires_at  optional (time in YYYYMMDDHHIISS format) message can be temporarily stored
     *                                         at message server, and we're allowed to give it an expiry time
     */
    public function send($subscriber, $message, array $options = [])
    {
        // check subscriber(s)
        if (empty($subscriber)) {
            throw new \InvalidArgumentException('短信接收用户未指定');
        }
        
        // check message to send
        $message = $message ? trim($message) : $message;
        if (empty($message)) {
            throw new \InvalidArgumentException('短信内容为空');
        }
        if (mb_strlen($message) > 500) {
            throw new \InvalidArgumentException('短信内容过长');
        }
        
        $this->sendMessages((array)$subscriber, $message . $this->signature, $options);
    }
    
    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Services\Sms\SmsService::queryQuota()
     */
    public function queryQuota()
    {
        $response = $this->client->request('POST', self::QUOTA_URL,
                                           [RequestOptions::FORM_PARAMS => $this->buildRequestUrlForConsulting()]);

        /* @var $response \GuzzleHttp\Psr7\Response */
        if ($response->getStatusCode() != 200) {
            throw new \Exception('短信服务器异常');
        }

        $response = preg_split("/[,\r\n]/", (string)$response->getBody());

        if (0 != $response[1]) {
            throw new \Exception(array_get(self::RESPONSE_PHRASES, $response[1],
                                           sprintf('短信额度查询异常(%s)', $response[1])));
        }

        return intval((string)$response[3]);
    }
    
    // send messages in batch
    private function sendMessages(array $subscribers, $message, array $options) {
        // #. of subscribers per batch, Guodu restricts it to be 200
        $numberOfSubscribersPerBatch = 200;
        $numberOfBatch = ceil(count($subscribers) / $numberOfSubscribersPerBatch);
        $offset = 0;
        
        for ($batch = 0; $batch < $numberOfBatch; $batch++) {
            // find subscribers for each batch
            $subscribersPerBatch = array_slice($subscribers, $offset, $numberOfSubscribersPerBatch);
            $offset += $numberOfSubscribersPerBatch;
            if (!empty($subscribersPerBatch)) { // got some subscriber
                $this->doSendMessages($subscribersPerBatch, $message, $options);
                
                // if the actual #. of subscribers is less than batch size
                // we're sure that the last batch was just processed
                if (count($subscribersPerBatch) < $numberOfSubscribersPerBatch) {
                    break; 
                } else {
                    continue;
                }
            }
            
            break;  // no subscriber(s)
        }
    }
    
    // do the actual work of sending short message
    private function doSendMessages($subscribers, $message, array $options = [])
    {
        // send request and parse response
        $response = $this->client->request('POST', self::SEND_URL,
                      [RequestOptions::FORM_PARAMS => $this->buildRequestForSending($subscribers, $message, $options)]);
            
        if ($response) {
            $this->parseResponse($response);
        } else {
            throw new \Exception('短信服务异常');
        }
    }
    
    // build http request for sending message
    private function buildRequestForSending(array $subscribes, $message, array $options = [])
    {
        return [
            'account'    => $this->account,
            'pswd'       => $this->password,
            'mobile'     => implode(',', $subscribes),
            'msg'        => $message,
            'needstatus' => false,
            // 'product'    => '',
            'extno'      => $this->affix ?: null,
        ];
    }

    // build http request for querying quota
    private function buildRequestUrlForConsulting()
    {
        return [
            'account'    => $this->account,
            'pswd'       => $this->password,
        ];
    }
    
    private function parseResponse(Response $response) 
    {
        if ($response->getStatusCode() != 200) {
            throw new \Exception('短信服务异常');
        }

        $response = preg_split("/[,\r\n]/", (string)$response->getBody());

        if (0 != $response[1]) {
            throw new \Exception(array_get(self::RESPONSE_PHRASES, $response[1],
                                           sprintf('短信发送异常(%s)', $response[1])));
        }

        // TODO: the message id counts?
    }
}