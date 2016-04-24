<?php
namespace Jihe\Services\Sms;

use Jihe\Contracts\Services\Sms\SmsService as SmsServiceContract;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;

/**
 * Short message service implemented with webchinese's api 
 * API document can be found at http://sms.webchinese.com.cn/api.shtml
 * 
 */
class WebChineseSmsService implements SmsServiceContract
{
    /**
     * base url for sending short message
     * @var string
     */
    const SEND_URL = 'http://utf8.sms.webchinese.cn/?';
    
    /**
     * base url for querying quota
     * @var string
     */
    const QUOTA_URL = 'http://sms.webchinese.cn/web_api/SMS/?';
    
    const RESPONSE_PHRASES = [
        '-1'  => '没有该用户账户',
        '-2'  => '接口密钥不正确',
        '-3'  => '短信数量不足',
        '-4'  => '手机号格式不正确',
        '-6'  => 'IP限制',
        '-11' => '该用户被禁用',
        '-14' => '短信内容出现非法字符',
        '-21' => 'MD5接口密钥加密不正确',
        '-41' => '手机号码为空',
        '-42' => '短信内容为空',
        '-51' => '短信签名格式不正确',
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
     * key corresponding to account
     * @var string
     */
    private $key;
    
    /**
     * 
     * @param string $account           account used to send message
     * @param string $key               key paired with account, should be MD5'd
     * @param ClientInterface $client   client used to sending http request
     */
    public function __construct($account, $key, ClientInterface $client) 
    {
        $this->account = $account;
        $this->key     = $key;
        
        $this->client = $client;
    }
    
    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Services\Sms\SmsService::send()
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
        
        $this->sendMessages((array)$subscriber, $message);
    }
    
    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Services\Sms\SmsService::queryQuota()
     */
    public function queryQuota()
    {
        $response = $this->client->request('GET', $this->buildRequestUrlForConsulting());
        
        /* @var $response GuzzleHttp\Psr7\Response */
        if ($response->getStatusCode() != 200) {
            throw new \Exception('短信服务器异常');
        }
        
        // the response contains the number of message quota
        return intval((string)$response->getBody());
    }
    
    // send messages in batch
    private function sendMessages(array $subscribers, $message) {
        // since the short message sending API uses HTTP GET request, we're limited
        // to the length for GET method, typically, it is 1024. It's better to send
        // messages in batch
        
        // first calculate how many subscribers can be sent message to in a batch
        // HTTP protocol always communicates in ASCII, so it's safe to use strlen()
        $remaining = 1024 - strlen($this->buildRequestUrlForSending([], $message));
        // each subscriber takes 11 in space, and we have to append a comma(',') to
        // each subscriber. therefore, it's assumed that one subscriber takes 12 in space
        if ($remaining < 12) { // eliminate negative numbers as well
            throw new \Exception('短信内容过长');
        }
        
        // #. of subscribers per batch, webchinese restricts it to be 100
        // in fact, n subscribers takes (n * 12) - 1, as the comma won't be appended to
        // the last subscriber
        $numberOfSubscribersPerBatch = min(floor(($remaining + 1) / 12), 100);
        $numberOfBatch = ceil(count($subscribers) / $numberOfSubscribersPerBatch);
        $offset = 0;
        
        for ($batch = 0; $batch < $numberOfBatch; $batch++) {
            // find subscribers for each batch
            $subscribersPerBatch = array_slice($subscribers, $offset, $numberOfSubscribersPerBatch);
            $offset += $numberOfSubscribersPerBatch;
            if (!empty($subscribersPerBatch)) { // got some subscriber
                $this->doSendMessages($subscribersPerBatch, $message);
                
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
    private function doSendMessages($subscribers, $message)
    {
        // build message sending request
        $response = $this->client->request('GET', $this->buildRequestUrlForSending($subscribers, $message));
            
        // send request and parse response
        if ($response) {
            $this->parseResponse($response);
        } else {
            throw new \Exception('短信服务异常');
        }
    }
    
    // build http request for sending message
    private function buildRequestUrlForSending(array $subscribes, $message)
    {
        return self::SEND_URL . http_build_query([
            'Uid'     => $this->account,
            'KeyMD5'  => $this->key, 
            'smsMob'  => implode(',', $subscribes),
            'smsText' => $message,
        ]);
    }
    
    // build http request for querying quota
    private function buildRequestUrlForConsulting()
    {
        return self::QUOTA_URL . http_build_query([
                'Action' => 'SMS_Num',
                'Uid'    => $this->account,
                'KeyMD5' => $this->key
        ]);
    }
    
    private function parseResponse(Response $response) 
    {
        if ($response->getStatusCode() != 200) {
            throw new \Exception('短信服务异常');
        }
        
        // response for sending message is just a plain string, denoting 
        // business logic code
        $code = (string)$response->getBody();
        
        // a positive number for $code (#. of messages sent) indicates the 
        // success. in other cases, a negative number will be given
        if (intval($code) > 0) { // success
            return;
        }
        
        if (array_key_exists($code, self::RESPONSE_PHRASES)) {
            throw new \Exception(self::RESPONSE_PHRASES[$code]);
        }
        
        // self::RESPONSE_PHRASES does not cover all cases
        throw new \Exception(sprintf('未知错误码(%s)', $code));
    }
}