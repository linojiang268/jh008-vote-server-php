<?php
namespace spec\Jihe\Services\Sms;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;

class WebChineseSmsServiceSpec extends ObjectBehavior
{
    function let(ClientInterface $client)
    {
        $this->beAnInstanceOf(\Jihe\Services\Sms\WebChineseSmsService::class, [
                'webchinese-account',                // account
                '4A957BE670C76B689BDE207E36AA1183',  // key  md5'd 
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
    
    function it_should_have_message_sent_for_single_subscriber(ClientInterface $client)
    {
        $client->request('GET', 'http://utf8.sms.webchinese.cn/?Uid=webchinese-account&' . 
                                'KeyMD5=4A957BE670C76B689BDE207E36AA1183&smsMob=13800138000&' . 
                                'smsText=this+is+a+testing+message')
               ->willReturn(new Response(200, [], '1'));  // '1' will be responded
        $this->shouldNotThrow()->duringSend('13800138000', 'this is a testing message');
    }
    
    function it_should_have_message_sent_for_multiple_subscribers(ClientInterface $client)
    {
        $client->request('GET', 'http://utf8.sms.webchinese.cn/?Uid=webchinese-account&' .
                                'KeyMD5=4A957BE670C76B689BDE207E36AA1183&smsMob=13800138000%2C13800138001&' . 
                                'smsText=this+is+a+testing+message')
               ->willReturn(new Response(200, [], '2'));  // '2' will be responded
         
        $this->shouldNotThrow()->duringSend(['13800138000', '13800138001'], 'this is a testing message');
    }
    
    function it_should_have_message_sent_for_non_exact_dividable_subscribers(ClientInterface $client)
    {
        $message = str_random(891); // message to be 891 in length, so that 2 subscribers can be sent in one batch
                                    // without subscribers, the url takes 110 in length,
                                    // 1024 - 110 - 12 * 2 (2 subscribers) + 1 = 891
        $subscribers = ['13800138000', '13800138001', '13800138002'];
        
        // '13800138000', '13800138001' will be sent message to in the first batch
        $client->request('GET', 'http://utf8.sms.webchinese.cn/?Uid=webchinese-account&' .
                                'KeyMD5=4A957BE670C76B689BDE207E36AA1183&smsMob=13800138000%2C13800138001' . 
                                '&smsText=' . $message)
               ->willReturn(new Response(200, [], '2'));  // '2' will be responded
        // '13800138002' will be sent message to in the second batch
        $client->request('GET', 'http://utf8.sms.webchinese.cn/?Uid=webchinese-account&' .
                'KeyMD5=4A957BE670C76B689BDE207E36AA1183&smsMob=13800138002&smsText=' . $message)
                ->willReturn(new Response(200, [], '1'));  // '1' will be responded
        
        $this->shouldNotThrow()->duringSend($subscribers, $message);
    }
    
    function it_should_have_message_sent_for_exact_dividable_subscribers(ClientInterface $client)
    {
        $message = str_random(891);
        $subscribers = ['13800138000', '13800138001', '13800138002', '13800138003'];
    
        // '13800138000', '13800138001' will be sent message to in the first batch
        $client->request('GET', 'http://utf8.sms.webchinese.cn/?Uid=webchinese-account&' .
                                'KeyMD5=4A957BE670C76B689BDE207E36AA1183&smsMob=13800138000%2C13800138001&' .
                                'smsText=' . $message)
                ->willReturn(new Response(200, [], '2'));  // '2' will be responded
        // '13800138002', '13800138003' will be sent message to in the second batch
        $client->request('GET', 'http://utf8.sms.webchinese.cn/?Uid=webchinese-account&' .
                                'KeyMD5=4A957BE670C76B689BDE207E36AA1183&smsMob=13800138002%2C13800138003&' . 
                                'smsText=' . $message)
                ->willReturn(new Response(200, [], '1'));  // '1' will be responded
        
        $this->shouldNotThrow()->duringSend($subscribers, $message);
    }

    //=====================================
    //          Query Quota
    //=====================================
    function it_should_have_quota_queried(ClientInterface $client)
    {
        $client->request('GET', 'http://sms.webchinese.cn/web_api/SMS/?Action=SMS_Num&' .
                                'Uid=webchinese-account&KeyMD5=4A957BE670C76B689BDE207E36AA1183')
               ->willReturn(new Response(200, [], '4812'));  // 4812 messages can be sent
        
        $this->queryQuota()->shouldBe(4812);
    }
}
