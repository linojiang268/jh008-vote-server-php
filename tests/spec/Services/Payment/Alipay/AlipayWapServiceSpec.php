<?php
namespace spec\Jihe\Services\Payment\Alipay;

use PhpSpec\ObjectBehavior;
use GuzzleHttp\ClientInterface;
use Prophecy\Argument;
use GuzzleHttp\Psr7\Response;
use \PHPUnit_Framework_Assert as Assert;

class AlipayWapServiceSpec extends ObjectBehavior
{
    function let(ClientInterface $httpClient)
    {
        $config = [
            'partner'           => '2088701892019087',
            'cert_file'         => __DIR__ . '/test-data/rsa_private_key.pem',
            'ali_cert_file'     => __DIR__ . '/test-data/alipay_wap_cert.pem',
            'notify_url'        => 'http://localhost/trade.php',
            'seller'            => 'alipay@zero2all.com',
            'refund_notify_url' => 'http://localhost/refund.php',
            'secure_key'        => 'cwygc4fpvwevu45m2jnh43w54vir9eqw',
            'success_url'       => 'http://localhost/payment_success.php',
            'abort_url'         => 'http://localhost/payment_abort.php',
        ];
        
        $this->beAnInstanceOf(\Jihe\Services\Payment\Alipay\AlipayWapService::class, 
                              [$config, $httpClient]);
    }
    
    //================================
    //        prepareTrade
    //================================
    function it_prepares_trade_well(ClientInterface $httpClient)
    {
        $httpClient->request('POST', 'http://wappaygw.alipay.com/service/rest.htm', Argument::cetera())
                   ->willReturn(new Response(200, [], file_get_contents(__DIR__ . '/test-data/wap_authorize_token_success.txt')));
        
        $result = $this->prepareTrade('201304101204101002', 0.01)->getWrappedObject();
        Assert::assertTrue(is_object($result));
        Assert::assertEquals('<auth_and_execute_req><request_token>20100830e8085e3e0868a466b822350ede5886e8</request_token></auth_and_execute_req>', $result->params['req_data']);
        Assert::assertEquals('alipay.wap.auth.authAndExecute', $result->params['service']);
        Assert::assertNotEmpty($result->formAction);
    }
    
    function its_authorization_can_fail_when_preparing_trade(ClientInterface $httpClient)
    {
        $httpClient->request('POST', 'http://wappaygw.alipay.com/service/rest.htm', Argument::cetera())
                   ->willReturn(new Response(200, [], file_get_contents(__DIR__ . '/test-data/wap_authorize_token_failed.txt')));
    
        $this->shouldThrow(new \Exception('合作伙伴没有开通接口访问权限'))
             ->duringPrepareTrade('201304101204101002', 0.01);
    }
    
    //================================
    //        tradePaid
    //================================
    function it_will_receive_sync_notification(ClientInterface $httpClient)
    {
        parse_str(file_get_contents(__DIR__.'/test-data/wap_payment_success.txt'), $notification);
        
        $result = $this->tradePaid($notification)->getWrappedObject();
        Assert::assertEquals('2014021067095547', $result->tradeNo);
        Assert::assertEquals('00001_52f85fd9f97644135c8b4570', $result->orderNo);
        Assert::assertNotEmpty($result->authorizeToken);
        Assert::assertEquals('SUCCESS', $result->status);
    }
    
    //================================
    //        tradeUpdated
    //================================
    function it_receives_trade_updated_async(ClientInterface $httpClient)
    {
        parse_str(file_get_contents(__DIR__.'/test-data/wap_trade_updated.txt'), $notification);
    
        $this->tradeUpdated($notification, function ($trade) {
            Assert::assertEquals('2014021004393977', $trade->tradeNo);
            Assert::assertEquals('00001_52f86166f976440e5c8b4572', $trade->orderNo);
            Assert::assertEquals('TRADE_SUCCESS', $trade->status);
            Assert::assertEquals(81.00, $trade->fee, '', 1E-6);
            Assert::assertEquals('2014-02-10 13:22:55', $trade->creationTime);
            Assert::assertEquals('2014-02-10 13:46:56', $trade->notifyTime);
            Assert::assertEquals('2014-02-10 13:22:56', $trade->paymentTime);
        });
    }
    
}