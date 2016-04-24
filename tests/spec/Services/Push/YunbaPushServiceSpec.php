<?php
namespace spec\Jihe\Services\Push;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;

class YunbaPushServiceSpec extends ObjectBehavior
{
    function let(ClientInterface $client)
    {
        $this->beAnInstanceOf(\Jihe\Services\Push\YunbaPushService::class, [
            'appKey',
            'secretKey',
            '',
            $client,
        ]);
    }

    function it_throws_exception_if_pushtopic_topic_empty()
    {
        $this->shouldThrow(new \Exception('推送目标不能为空'))
            ->duringPushTopic('', "test");
    }

    function it_throws_exception_if_pushtopic_message_empty()
    {
        $this->shouldThrow(new \Exception('推送信息不能为空'))
            ->duringPushTopic('topic', "");
    }

    function it_throws_exception_if_pushalias_alias_empty()
    {
        $this->shouldThrow(new \Exception('推送目标不能为空'))
            ->duringPushAlias('', "test");
    }

    function it_throws_exception_if_pushalias_message_empty()
    {
        $this->shouldThrow(new \Exception('推送信息不能为空'))
            ->duringPushAlias('alias', "");
    }

    function it_should_have_message_pushTopic_for_single_subscriber(ClientInterface $client)
    {
        $client->request('POST',
            'http://rest.yunba.io:8080',
            ['headers' => ['Content-type' => 'application/json'],
             'body'    => json_encode([
                 'method' => 'publish',
                 'topic'  => 'topic',
                 'appkey' => 'appKey',
                 'seckey' => 'secretKey',
                 'msg'    => [
                     'content'    => '推送消息',
                     'type'       => 'text',
                     'attributes' => [],
                 ],
                 'opts'   => [
                     'apn_json' => [
                         'aps' => [
                             "alert"      => "推送消息",
                             "sound"      => "default",
                             "type"       => "text",
                             "attributes" => []],
                     ],
                 ],
             ]),
            ]
        )->willReturn(new Response(200, [], '{"status":0,"messageId":474931688976158720}'));

        $this->shouldNotThrow()->duringPushTopic('topic', [
            'content'    => '推送消息',
            'type'       => 'text',
            'attributes' => [],
        ], [
            'apn_json' => [
                'aps' => [
                    "alert"      => '推送消息',
                    'sound'      => 'default',
                    'type'       => 'text',
                    'attributes' => [],
                ],
            ],
        ]);
    }

    function it_should_have_message_pushAlias_for_single_subscriber(ClientInterface $client)
    {
        $client->request('POST',
            'http://rest.yunba.io:8080',
            ['headers' => ['Content-type' => 'application/json'],
             'body'    => json_encode([
                 'method' => 'publish_to_alias',
                 'alias'  => 'alias',
                 'appkey' => 'appKey',
                 'seckey' => 'secretKey',
                 'msg'    => [
                     "content" => "推送消息",
                     "type"    => "text",
                     "attributes" => [],
                 ],
                 'opts'   => [
                     'apn_json' => [
                         'aps' => [
                             "alert"      => "推送消息",
                             "sound"      => "default",
                             "type"       => "text",
                             "attributes" => []
                         ],
                     ],
                 ],
             ]),
            ]
        )->willReturn(new Response(200, [], '{"status":0,"messageId":474931688976158720}'));

        $this->shouldNotThrow()->duringPushAlias('alias', [
            "content" => "推送消息",
            "type"    => "text",
            "attributes" => [],
        ], [
            'apn_json' => [
                'aps' => [
                    "alert"      => '推送消息',
                    'sound'      => 'default',
                    'type'       => 'text',
                    'attributes' => [],
                ],
            ],
        ]);
    }

    function it_should_have_message_pushAlias_for_multiple_subscriber(ClientInterface $client)
    {
        $client->request('POST',
            'http://rest.yunba.io:8080',
            ['headers' => ['Content-type' => 'application/json'],
             'body'    => json_encode([
                 'method'  => 'publish_to_alias_batch',
                 'aliases' => ['alias1', 'alias2'],
                 'appkey'  => 'appKey',
                 'seckey'  => 'secretKey',
                 'msg'     => [
                     "content" => "推送消息",
                     "type"    => "text",
                     "attributes" => [],
                 ],
                 'opts'    => [
                     'apn_json' => [
                         'aps' => [
                             "alert"      => "推送消息",
                             "sound"      => "default",
                             "type"       => "text",
                             "attributes" => []],
                     ],
                 ],
             ]),
            ]
        )->willReturn(new Response(200, [], '{"status":0,"messageId":474931688976158720}'));

        $this->shouldNotThrow()->duringPushAlias(['alias1', 'alias2'], [
            "content" => "推送消息",
            "type"    => "text",
            "attributes" => [],
        ], [
            'apn_json' => [
                'aps' => [
                    "alert"      => '推送消息',
                    'sound'      => 'default',
                    'type'       => 'text',
                    'attributes' => [],
                ],
            ],
        ]);
    }

}
