<?php
namespace Jihe\Services\Sms;

use GuzzleHttp\RequestOptions;
use Jihe\Contracts\Services\Sms\SmsService as SmsServiceContract;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;

/**
 * Short message service implemented with Guodu's api
 */
class GuoduSmsService implements SmsServiceContract
{
    /**
     * base url for sending short message
     * @var string
     */
    const SEND_URL = 'http://221.179.180.158:9007/QxtSms/QxtFirewall';
    
    /**
     * base url for querying quota
     * @var string
     */
    const QUOTA_URL = 'http://221.179.180.158:8081/QxtSms_surplus/surplus?';

    /**
     * message will be delivered as 普通短信
     */
    const SEND_TYPE_PLAIN      = 8;
    /**
     * message will be delivered as 长短信
     */
    const SEND_TYPE_LONG       = 15;

    const RESPONSE_PHRASES = [
        '00' => '短信提交成功',  // 批量短信
        '01' => '短信提交成功',  // 个性化短信
        '02' => 'IP限制',
        '03' => '短信提交成功',  // 单条
        '04' => '用户名错误',
        '05' => '密码错误',
        '06' => '自定义短信手机号个数与内容个数不相等',
        '07' => '发送时间错误',
        '08' => '短信包含敏感内容',  // 黑内容
        '09' => '同天内不能向用户重复发送该短信内容',
        '10' => '扩展号错误',
        '11' => '余额不足',
        '-1' => '短信服务器异常',
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
     * @param string $signature         (optional) sms signature - suffix appended to the message
     * @param ClientInterface $client   client used to sending http request
     */
    public function __construct($account, $password, $affix = null, $signature = '【集合】', ClientInterface $client)
    {
        $this->account   = $account;
        $this->password  = $password;
        $this->affix     = $affix;
        $this->signature = $signature;
        
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
        $response = $this->client->request('GET', $this->buildRequestUrlForConsulting());
        
        /* @var $response \GuzzleHttp\Psr7\Response */
        if ($response->getStatusCode() != 200) {
            throw new \Exception('短信服务器异常');
        }

        $response = simplexml_load_string((string)$response->getBody());
        /*
         * sample response:
         * <?xml version="1.0" encoding="GBK"?><resRoot><rcode>10</rcode></resRoot>
         */
        return intval((string)$response->rcode);
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
        // message to send should be converted into 'GBK' encoding
        // for example, url encoded '中文短信abc' in GBK encoding should be '%D6%D0%CE%C4%B6%CC%D0%C5abc'

        // when will this message be delivered, if null/empty, the message will
        // be delivered at once
        $sendTime = array_get($options, 'send_time');
        $sendType = array_get($options, 'send_type', self::SEND_TYPE_PLAIN);
        if (!in_array($sendType, [self::SEND_TYPE_PLAIN, self::SEND_TYPE_LONG])) {
            $sendType = self::SEND_TYPE_PLAIN;
        }
        // message can be temporarily stored at message server, and we're allowed to give it an expiry time
        $expiresAt = array_get($options, 'expires_at', date('YmdHis', time() + 86400 /* 1 day */));

        return [
            'OperID'      => $this->account,
            'OperPass'    => $this->password,
            'SendTime'    => $sendTime,
            'ValidTime'   => $expiresAt,
            'AppendID'    => $this->affix,   // 附加号码
            'DesMobile'   => implode(',', $subscribes),
            'Content'     => mb_convert_encoding($message, 'gbk', 'utf-8'),
            'ContentType' => $sendType,
        ];
    }
    
    // build http request for querying quota
    private function buildRequestUrlForConsulting()
    {
        return self::QUOTA_URL . http_build_query([
            'OperID'      => $this->account,
            'OperPass'    => $this->password,
        ]);
    }
    
    private function parseResponse(Response $response) 
    {
        if ($response->getStatusCode() != 200) {
            throw new \Exception('短信服务异常');
        }
        
        // response for sending message is just an XML with <response> as its root
        // <response> has a <code> child to show the status, and followed by a list
        // of <message>s, each of which shows the message id of the sending to specific
        // subscriber
        $response = simplexml_load_string((string)$response->getBody());

        // succeeded?
        if (!in_array($response->code, ['00', '01', '03'])) { // no
            throw new \Exception(array_get(self::RESPONSE_PHRASES, $response->code,
                                           sprintf('短信发送异常(%s)', $response->code)));
        }

        // TODO: the message id counts?
    }
}