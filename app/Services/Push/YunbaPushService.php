<?php
namespace Jihe\Services\Push;

use Jihe\Contracts\Services\Push\PushService as PushServiceContract;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use Jihe\Entities\Message;
use Jihe\Utils\StringUtil;

class YunbaPushService implements PushServiceContract
{
    /**
     * base url for pushing message
     * @var string
     */
    const PUSH_URL = 'http://rest.yunba.io:8080';

    const RESPONSE_PHRASES = [
        '1' => '参数错误',
        '2' => '内部服务错误',
        '3' => '应用不存在',
        '4' => '发布超时',
        '5' => '未知的别名',
    ];

    const PUSH_TOPIC = 'topic';

    const PUSH_ALIAS = 'alias';

    const PUSH_TYPE = ['topic', 'alias'];

    const METHOD_PUBLISH = "publish";

    const METHOD_PUBLISH_TO_ALIAS = 'publish_to_alias';

    const METHOD_PUBLISH_TO_ALIAS_BATCH = 'publish_to_alias_batch';

    /**
     * http client
     * @var \GuzzleHttp\ClientInterface
     */
    private $client;

    /**
     * AppKey used to push message
     * @var string
     */
    private $appKey;

    /**
     * secretKey used to push message
     * @var string
     */
    private $secretKey;

    /**
     * sms signature - suffix appended to the message
     *
     * @var string
     */
    private $signature;

    /**
     * @param string          $appKey    push application AppKey
     * @param string          $secretKey push application Secret Key
     * @param string          $signature suffix appended to the message
     * @param ClientInterface $client
     */
    public function __construct($appKey, $secretKey, $signature, ClientInterface $client)
    {
        $this->appKey = $appKey;
        $this->secretKey = $secretKey;

        $this->signature = $signature;

        $this->client = $client;
    }

    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Services\Push\PushService::pushTopic()
     */
    public function pushTopic($topic, $message, array $options = [])
    {
        if (empty($topic)) {
            throw new \InvalidArgumentException('推送目标不能为空');
        }
        if (empty($message)) {
            throw new \InvalidArgumentException('推送信息不能为空');
        }
        $this->pushMessages('topic', $topic, $message, $options);
    }

    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Services\Push\PushService::pushAlias()
     */
    public function pushAlias($alias, $message, array $options = [])
    {
        if (empty($alias)) {
            throw new \InvalidArgumentException('推送目标不能为空');
        }
        if (empty($message)) {
            throw new \InvalidArgumentException('推送信息不能为空');
        }

        $this->pushMessages('alias', $alias, $message, $options);
    }

    /**
     * Tissue validation data
     *
     * @param  string       $publishType push type
     * @param  string|array $subscriber  push target
     * @param  array        $message     push message
     * @param  array        $options     possible options for each implementation
     *
     * @throws \Exception
     */
    private function pushMessages($publishType, $subscriber, $message, array $options)
    {
        if (!in_array($publishType, self::PUSH_TYPE)) {
            throw new \InvalidArgumentException('非法的推送操作');
        }
        $post = [];
        if ($publishType == self::PUSH_TOPIC) {
            $post['method'] = self::METHOD_PUBLISH;
            $post['topic'] = $subscriber;
        } else {
            if (is_array($subscriber)) {
                $post['method'] = self::METHOD_PUBLISH_TO_ALIAS_BATCH;
                $post['aliases'] = $subscriber;
            } else {
                $post['method'] = self::METHOD_PUBLISH_TO_ALIAS;
                $post['alias'] = $subscriber;
            }
        }
        $post['appkey'] = $this->appKey;
        $post['seckey'] = $this->secretKey;
        $post['msg'] = $message;
        if (!empty($options)) {
            $post = array_merge($post, ['opts' => $options]);
        }
        $this->doPushMessages($post);
    }

    /**
     * Push message
     *
     * @param array $post push data
     *
     * @throws \Exception
     */
    private function doPushMessages($post)
    {
        $options = [
            'headers' => ["Content-type" => "application/json"],
            'body'    => json_encode($post),
        ];
        $response = $this->client->request('POST', self::PUSH_URL, $options);

        if ($response) {
            $this->parseResponse($response);
        } else {
            throw new \Exception('推送服务异常');
        }
    }

    /**
     * check response data
     *
     * @param Response $response
     *
     * @throws \Exception
     */
    private function parseResponse(Response $response)
    {
        if ($response->getStatusCode() != 200) {
            throw new \Exception(sprintf('推送服务异常(Code: %s)', $response->getStatusCode()));
        }
        $body = $response->getBody();
        $response = StringUtil::safeJsonDecode($body);
        if (!$response) {
            throw new \Exception('推送服务异常,返回值异常', -1);
        }
        if ($response['status'] != 0) {
            throw new \Exception(array_get(self::RESPONSE_PHRASES, $response['status'],
                sprintf('推送异常(%s)', $body)), $response['status']);
        }
    }

}