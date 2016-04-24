<?php
namespace spec\Jihe\Services\Sms;

use GuzzleHttp\RequestOptions;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;

class GuoduSmsServiceSpec extends ObjectBehavior
{
    function let(ClientInterface $client)
    {
        $this->beAnInstanceOf(\Jihe\Services\Sms\GuoduSmsService::class, [
                'account',    // account
                'password',   // password
                '1234',       // affix
                '',           // signature
                $client
        ]);
    }
    
    //=====================================
    //          Send Message
    //=====================================
    function it_throws_exception_if_message_too_long(ClientInterface $client)
    {
        $this->shouldThrow(new \Exception('短信内容过长'))
             ->duringSend('13800138000', str_random(1000));
    }
    
    function it_throws_exception_if_no_subscribers_given(ClientInterface $client)
    {
        $this->shouldThrow(new \Exception('短信接收用户未指定'))
             ->duringSend('', str_random());
    }
//
    function it_should_have_message_sent_for_single_subscriber(ClientInterface $client)
    {
        $client->request('POST', 'http://221.179.180.158:9007/QxtSms/QxtFirewall',
            Argument::that(function (array $request) {
                $request = $request[RequestOptions::FORM_PARAMS];
                return $request['OperID']    == 'account' &&
                       $request['OperPass']  == 'password' &&
                       $request['AppendID']  == '1234' &&
                       $request['DesMobile'] == '13800138000' &&
                       // it GBK-encoded
                       mb_convert_encoding($request['Content'], 'utf-8', 'gbk')  == '发送消息';
            }))->willReturn(new Response(200, [], file_get_contents(__DIR__ . '/test-data/Guodu/single_success.xml')));

        $this->shouldNotThrow()->duringSend('13800138000', '发送消息');
    }
//
    function it_should_have_message_sent_for_multiple_subscribers(ClientInterface $client)
    {
        $client->request('POST', 'http://221.179.180.158:9007/QxtSms/QxtFirewall',
            Argument::that(function (array $request) {
                $request = $request[RequestOptions::FORM_PARAMS];
                return $request['OperID']    == 'account' &&
                $request['OperPass']  == 'password' &&
                $request['AppendID']  == '1234' &&
                $request['DesMobile'] == '13800138000,13800138001' &&
                // it GBK-encoded
                mb_convert_encoding($request['Content'], 'utf-8', 'gbk')  == '发送消息';
        }))->shouldBeCalled()->willReturn(new Response(200, [],
                                              file_get_contents(__DIR__ . '/test-data/Guodu/multi_success.xml')));

        $this->shouldNotThrow()->duringSend(['13800138000', '13800138001'], '发送消息');
    }

    function it_should_have_message_sent_for_subscribers_that_exceeds_the_limit(ClientInterface $client)
    {
        $subscribersBatchOne = $this->makeSubscribers('13800',   1, 200); // 200 subscribers
        $subscribersBatchTwo = $this->makeSubscribers('13800', 201, 300); // 100 subscribers

        $client->request('POST', 'http://221.179.180.158:9007/QxtSms/QxtFirewall',
            Argument::that(function (array $request) use ($subscribersBatchOne) {
                $request = $request[RequestOptions::FORM_PARAMS];
                return $request['OperID']    == 'account' &&
                $request['OperPass']  == 'password' &&
                $request['AppendID']  == '1234' &&
                $request['DesMobile'] == implode(',', $subscribersBatchOne) &&
                $request['Content']== 'message';
            }))->shouldBeCalled()->willReturn(new Response(200, [],
                file_get_contents(__DIR__ . '/test-data/Guodu/multi_success.xml')));
        $client->request('POST', 'http://221.179.180.158:9007/QxtSms/QxtFirewall',
            Argument::that(function (array $request) use ($subscribersBatchTwo) {
                $request = $request[RequestOptions::FORM_PARAMS];
                return $request['OperID']    == 'account' &&
                $request['OperPass']  == 'password' &&
                $request['AppendID']  == '1234' &&
                $request['DesMobile'] == implode(',', $subscribersBatchTwo) &&
                $request['Content']== 'message';
            }))->shouldBeCalled()->willReturn(new Response(200, [],
            file_get_contents(__DIR__ . '/test-data/Guodu/multi_success.xml')));

        $this->shouldNotThrow()->duringSend(array_merge($subscribersBatchOne, $subscribersBatchTwo), 'message');
    }

    private function makeSubscribers($prefix, $from, $to)
    {
        $pad = 11 - strlen($prefix);  // each mobile number takes 11 in length

        $subscribers = [];
        for ($i = $from; $i <= $to; $i++) {
            $subscribers[] = $prefix . str_pad($i, $pad, STR_PAD_LEFT);
        }

        return $subscribers;
    }

    //=====================================
    //          Query Quota
    //=====================================
    function it_should_have_quota_queried(ClientInterface $client)
    {
        $client->request('GET', 'http://221.179.180.158:8081/QxtSms_surplus/surplus?OperID=account&OperPass=password')
               ->willReturn(new Response(200, [], file_get_contents(__DIR__ . '/test-data/Guodu/surplus.xml')));

        $this->queryQuota()->shouldBe(10);
    }
}
