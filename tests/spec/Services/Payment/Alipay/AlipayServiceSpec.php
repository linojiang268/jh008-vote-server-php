<?php
namespace spec\Jihe\Services\Payment\Alipay;

use PhpSpec\ObjectBehavior;
use GuzzleHttp\ClientInterface;
use Prophecy\Argument;
use GuzzleHttp\Psr7\Response;
class AlipayServiceSpec extends ObjectBehavior
{
    function let(ClientInterface $httpClient)
    {
        $config = [
            'partner'           => '2088701892019087',
            'cert_file'         => __DIR__ . '/test-data/rsa_private_key.pem',
            'ali_cert_file'     => __DIR__ . '/test-data/alipay_cert.pem',
            'notify_url'        => 'http://localhost/trade.php',
            'seller'            => 'alipay@zero2all.com',
            'refund_notify_url' => 'http://localhost/refund.php',
            'secure_key'        => 'cwygc4fpvwevu45m2jnh43w54vir9eqw',
        ];
        
        $this->beAnInstanceOf(\Jihe\Services\Payment\Alipay\AlipayService::class, 
                              [$config, $httpClient]);
    }
    
    //================================
    //        prepareTrade
    //================================
    function it_prepares_trade_well()
    {
        $this->prepareTrade('201304101204101002', 0.01)
             ->shouldBe('_input_charset="utf-8"&body=" "&notify_url="http://localhost/trade.php"&' .
                        'out_trade_no="201304101204101002"&partner="2088701892019087"&payment_type="1"&' . 
                        'seller_id="alipay@zero2all.com"&service="mobile.securitypay.pay"&subject=" "&' . 
                        'total_fee="0.01"&sign="Bv6XueAux6kdnLPLT%2FJrDeJAjV%2BaM07f5eFxvfZ3avqYtZbUrxaj' .
                        'OZtCGo%2BvALulLptFjT94sjk3Rq2TUR%2B4CfcfvjMHvT9LPPCgJoi48fhchOhFtZcvS1sTkNcaTSu' .
                        'O8jbrHKVJNkX27keFrZt3mj3r2qgXpToJVsCwBNmITWY%3D"&sign_type="RSA"');
    }
    
    function it_rejects_if_forged_update_detected()
    {
        parse_str(file_get_contents(__DIR__ . '/test-data/trade_finished_forged.txt'), $notification);
        // cannot pass the signature verification
        $this->shouldThrow(new \Exception('Forged trade notification'))
             ->duringTradeUpdated($notification, function() {
                 // don't care since this method won't be called
                 throw new \Exception('should never be reached');
             });
    }
    
    function it_rejcts_on_bad_signature()
    {
        parse_str(file_get_contents(__DIR__ . '/test-data/trade_finished_bad_sig.txt'), $notification);
        $this->shouldThrow(new \Exception('Signature verification failed'))
             ->duringTradeUpdated($notification, function () {
                 // don't care since this method won't be called
                 throw new \Exception('should never be reached');
             });
    }
    
    function it_accepts_valid_trade_update()
    {
        parse_str(file_get_contents(__DIR__ . '/test-data/trade_finished.txt'), $notification);
        
        ob_start(); // capture the echo-ed output
        $this->tradeUpdated($notification, function ($trade) {
            \PHPUnit_Framework_Assert::assertEquals('00001_52b01e7571b14fa7768b4569', $trade->orderNo);
            \PHPUnit_Framework_Assert::assertEquals('TRADE_SUCCESS', $trade->status);
            \PHPUnit_Framework_Assert::assertEquals('2013121763945981', $trade->tradeNo);
            \PHPUnit_Framework_Assert::assertEquals('0.01', $trade->fee);
            \PHPUnit_Framework_Assert::assertEquals('2013-12-17 17:50:57', $trade->creationTime);
            \PHPUnit_Framework_Assert::assertEquals('2013-12-17 17:50:58', $trade->paymentTime);
            \PHPUnit_Framework_Assert::assertEquals('2013-12-17 17:54:32', $trade->notifyTime);
            
            return true;
        });
        
        \PHPUnit_Framework_Assert::assertEquals('success', ob_get_clean());
    }
    
    //================================
    //        refundTrade
    //================================
    function it_can_successfully_refund(ClientInterface $httpClient)
    {
        $httpClient->request('POST', 'https://mapi.alipay.com/gateway.do', Argument::cetera())
                   ->willReturn(new Response(200, [], file_get_contents(__DIR__.'/test-data/refund_trade_sync_success.xml')));
        
        $result = $this->refundTrade('TRADE#', 100)->getWrappedObject();
        \PHPUnit_Framework_Assert::assertEquals('REFUND_SUCCESS', $result->status);
        \PHPUnit_Framework_Assert::assertEmpty($result->msg);
        \PHPUnit_Framework_Assert::assertNotEmpty($result->seqNo);
    }
    
    function it_fails_to_refund_on_bad_batch_no(ClientInterface $httpClient)
    {
        $httpClient->request('POST', 'https://mapi.alipay.com/gateway.do', Argument::cetera())
                   ->willReturn(new Response(200, [], file_get_contents(__DIR__.'/test-data/refund_trade_sync_failed.xml')));
    
        $result = $this->refundTrade('TRADE#', 100)->getWrappedObject();
        \PHPUnit_Framework_Assert::assertEquals('REFUND_FAILED', $result->status);
        \PHPUnit_Framework_Assert::assertEquals('BATCH_NO_FORMAT_ERROR', $result->msg);
    }
    
    //================================
    //        refundTradeUpdated
    //================================
    function it_can_encounter_failed_status_on_refunding_notification_details()
    {
        parse_str(file_get_contents(__DIR__ . '/test-data/refund_trade_async_finished.txt'), $notification);
        
        ob_start();   // capture the echo-ed output
        $this->refundTradeUpdated($notification, function ($trade) {
            \PHPUnit_Framework_Assert::assertEquals('20060702001', $trade->seqNo);
            \PHPUnit_Framework_Assert::assertEquals('2009-08-12 11:08:32', $trade->notifyTime);
            \PHPUnit_Framework_Assert::assertCount(1, $trade->details);
            
            $detail = $trade->details[0];
            \PHPUnit_Framework_Assert::assertEquals('2010031206252779', $detail->tradeNo);
            \PHPUnit_Framework_Assert::assertEquals(10.00, $detail->fee, '', 1E-6);
            \PHPUnit_Framework_Assert::assertEquals('NOT_THIS_PARTNERS_TRADE', $detail->status);
            
            return false;
        });
        
       \PHPUnit_Framework_Assert::assertEquals('fail', ob_get_clean());
    }
    
    function it_can_successfully_notified_on_funding()
    {
        parse_str(file_get_contents(__DIR__ . '/test-data/refund_trade_async_success.txt'), $notification);
        
        ob_start();   // capture the echo-ed output
        $this->refundTradeUpdated($notification, function ($trade) {
            \PHPUnit_Framework_Assert::assertEquals('20060702001', $trade->seqNo);
            \PHPUnit_Framework_Assert::assertEquals('2009-08-12 11:08:32', $trade->notifyTime);
            \PHPUnit_Framework_Assert::assertCount(1, $trade->details);
            
            $detail = $trade->details[0];
            \PHPUnit_Framework_Assert::assertEquals('2010031206252779', $detail->tradeNo);
            \PHPUnit_Framework_Assert::assertEquals(10.00, $detail->fee, '', 1E-6);
            \PHPUnit_Framework_Assert::assertEquals('REFUND_SUCCESS', $detail->status);  // SUCESS shoulde be converted to REFUND_SUCCESS
        
            return true;
        });
        \PHPUnit_Framework_Assert::assertEquals('success', ob_get_clean());
    }
}
