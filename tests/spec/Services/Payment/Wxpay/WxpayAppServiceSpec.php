<?php
namespace spec\Jihe\Services\Payment\Wxpay;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;


class WxpayAppServiceSpec extends ObjectBehavior
{
    function let(ClientInterface $httpClient)
    {
        $config = [
            'appid'         => 'wx432432432',
            'mchid'         => '423454543535',
            'key'           => 'fsfgdgfsuwrwnuojojlm',
            'cert_file'     => __DIR__ . '/test-data/wx_merchant_cert.pem',
            'sslkey_file'   => __DIR__ . '/test-data/wx_merchant_sslkey.pem',
            'notify_url'    => 'http://localhost/trade.php',
        ];

        $this->beAnInstanceOf(\Jihe\Services\Payment\Wxpay\WxpayAppService::class, 
            [$config, $httpClient]);
    }


    //=========================================
    //          place unified order
    //=========================================
    function it_place_order_successfully(ClientInterface $httpClient)
    {
        $httpClient->request('POST', 'https://api.mch.weixin.qq.com/pay/unifiedorder', Argument::cetera())
            ->willReturn(new Response(200, [], file_get_contents(__DIR__ . '/test-data/place_unified_order_sync_success.xml')));

        $result = $this->placeOrder('201506072227000001', 1, '报名费', '8.8.8.8')
             ->getWrappedObject();
        \PHPUnit_Framework_Assert::assertEquals('SUCCESS', $result->responseCode);
        \PHPUnit_Framework_Assert::assertEmpty($result->errMsg);
        \PHPUnit_Framework_Assert::assertEmpty($result->errCode);
        \PHPUnit_Framework_Assert::assertNotEmpty($result->prepayId);
    }


    function it_rejects_if_place_duplicated_orderno(ClientInterface $httpClient)
    {
        $httpClient->request('POST', 'https://api.mch.weixin.qq.com/pay/unifiedorder', Argument::cetera())
            ->willReturn(new Response(200, [], file_get_contents(
                       __DIR__ . '/test-data/place_unified_order_sync_falied_with_duplicated_order.xml')));

        $result = $this->placeOrder('201506072227000001', 1, '报名费', '8.8.8.8')
            ->getWrappedObject();
        \PHPUnit_Framework_Assert::assertEquals('FAIL', $result->responseCode);
        \PHPUnit_Framework_Assert::assertEquals('OUT_TRADE_NO_USED', $result->errCode);
    }


    function it_rejects_on_bad_signature(ClientInterface $httpClient)
    {
        $httpClient->request('POST', 'https://api.mch.weixin.qq.com/pay/unifiedorder', Argument::cetera())
            ->willReturn(new Response(200, [], file_get_contents(
                        __DIR__ . '/test-data/place_unified_order_sync_falied_with_bad_signature.xml')));

        $result = $this->placeOrder('201506072227000001', 1, '报名费', '8.8.8.8')
            ->getWrappedObject();
        \PHPUnit_Framework_Assert::assertEquals('FAIL', $result->responseCode);
        \PHPUnit_Framework_Assert::assertEquals('SIGNERROR', $result->errCode);
    }


    function it_place_order_rejects_on_bad_response(ClientInterface $httpClient)
    {
        $httpClient->request('POST', 'https://api.mch.weixin.qq.com/pay/unifiedorder', Argument::cetera())
            ->willReturn(new Response(500, [], 'bad response'));

        $this->shouldThrow(new \Exception('bad response from wxpay: bad response'))
            ->duringPlaceOrder('201506072227000001', 1, '报名费', '8.8.8.8');
    }

    //=========================================
    //          payed async notification
    //=========================================
    function it_trade_update_successfully()
    {
        parse_str(file_get_contents(__DIR__ . '/test-data/trade_update_success.txt'), $notification);
        $this->tradeUpdated($notification, function($trade) {
            \PHPUnit_Framework_Assert::assertEquals('201506072227000001', $trade->orderNo);
            \PHPUnit_Framework_Assert::assertEquals('SUCCESS', $trade->returnCode);
            \PHPUnit_Framework_Assert::assertEquals('SUCCESS', $trade->resultCode);
            \PHPUnit_Framework_Assert::assertEquals('wxd930ea5d5a258f4f', $trade->openid);
            \PHPUnit_Framework_Assert::assertEquals('APP', $trade->tradeType);
            \PHPUnit_Framework_Assert::assertEquals('CMC', $trade->bankType);
            \PHPUnit_Framework_Assert::assertEquals('1', $trade->fee);
            \PHPUnit_Framework_Assert::assertEquals('1217752501201407033233368018', $trade->transactionId);
            \PHPUnit_Framework_Assert::assertEquals('', $trade->attach);
            \PHPUnit_Framework_Assert::assertEquals('20150707195723', $trade->paymentTime);
            return true;
        })->shouldEqual('SUCCESS');
    }


    function it_trade_update_failed_with_forged_notification()
    {
        parse_str(file_get_contents(__DIR__ . '/test-data/trade_update_forged.txt'), $notification);

        $this->shouldThrow(new \Exception('Forged trade notification'))
            ->duringTradeUpdated($notification, function($trade) {
                // don't care since this method won't be called
                return true;
            });
    }


    function it_trade_update_failed_on_bad_signature()
    {
        parse_str(file_get_contents(__DIR__ . '/test-data/trade_update_bad_signature.txt'), $notification);

        $this->shouldThrow(new \Exception('Signature verification failed'))
            ->duringTradeUpdated($notification, function($trade) {
                // don't care since this method won't be called
                return true;
            });
    }


    //=========================================
    //         query order 
    //=========================================
    function it_query_order_successfully(ClientInterface $httpClient)
    {
        $httpClient->request('POST', 'https://api.mch.weixin.qq.com/pay/orderquery', Argument::cetera())
            ->willReturn(new Response(200, [], file_get_contents(__DIR__ . '/test-data/query_order_success.xml')));

        $result = $this->queryOrder('201506072227000001')
            ->getWrappedObject();
        \PHPUnit_Framework_Assert::assertEquals('SUCCESS', $result->responseCode);
        \PHPUnit_Framework_Assert::assertEmpty($result->errCode);
        \PHPUnit_Framework_Assert::assertEmpty($result->errMsg);
        \PHPUnit_Framework_Assert::assertEquals('SUCCESS', $result->tradeState);
    }
    

    function it_query_order_failed_on_bad_response(ClientInterface $httpClient)
    {
        $httpClient->request('POST', 'https://api.mch.weixin.qq.com/pay/orderquery', Argument::cetera())
            ->willReturn(new Response(200, [], '<xml></xml>'));
        $this->shouldThrow(new \Exception('bad response from wxpay: <xml></xml>'))
            ->duringQueryOrder('201506072227000001');
    }


    //=========================================
    //         refund application
    //=========================================
    function it_refund_apply_successfully(ClientInterface $httpClient)
    {
        $httpClient->request('POST', 'https://api.mch.weixin.qq.com/secapi/pay/refund', Argument::cetera())
            ->willReturn(new Response(200, [], file_get_contents(__DIR__ . '/test-data/refund_apply_success.xml')));

        $result = $this->refundTradeApply('201506072227000001', 10, 1)
            ->getWrappedObject();
        \PHPUnit_Framework_Assert::assertEquals('SUCCESS', $result->responseCode);
        \PHPUnit_Framework_Assert::assertEmpty($result->errCode);
        \PHPUnit_Framework_Assert::assertEmpty($result->errMsg);
        \PHPUnit_Framework_Assert::assertEquals(32, strlen($result->refundNo));
    }


    function it_refund_apply_failed_if_business_error(ClientInterface $httpClient)
    {
        $httpClient->request('POST', 'https://api.mch.weixin.qq.com/secapi/pay/refund', Argument::cetera())
            ->willReturn(new Response(200, [], file_get_contents(__DIR__ . '/test-data/refund_apply_business_error.xml')));

        $result = $this->refundTradeApply('201506072227000001', 10, 1)
            ->getWrappedObject();
        \PHPUnit_Framework_Assert::assertEquals('FAIL', $result->responseCode);
        \PHPUnit_Framework_Assert::assertEquals('APPID_MCHID_NOT_MATCH', $result->errCode);
        \PHPUnit_Framework_Assert::assertEquals('appid和mch_id不匹配', $result->errMsg);
        \PHPUnit_Framework_Assert::assertEquals(32, strlen($result->refundNo));
    }

    //=========================================
    //         refund query
    //=========================================
    function it_refund_query_successfully(ClientInterface $httpClient)
    {
        $httpClient->request('POST', 'https://api.mch.weixin.qq.com/pay/refundquery', Argument::cetera())
            ->willReturn(new Response(200, [], file_get_contents(__DIR__ . '/test-data/refund_query_success.xml')));

        $result = $this->queryRefund('20150607222756abcdef123456700001', '201506072227000001')
            ->getWrappedObject();
        \PHPUnit_Framework_Assert::assertEquals('SUCCESS', $result->responseCode);
        \PHPUnit_Framework_Assert::assertEmpty($result->errMsg);
        \PHPUnit_Framework_Assert::assertEquals('PROCESSING', $result->refundStatus);
    }
}
