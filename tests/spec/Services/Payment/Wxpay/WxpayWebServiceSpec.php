<?php
namespace spec\Jihe\Services\Payment\Wxpay;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;


class WxpayWebServiceSpec extends ObjectBehavior
{
    function let(ClientInterface $httpClient)
    {
        $config = [
            'appid'         => 'wx2421b1c4370ec43b',
            'mchid'         => '10000100',
            'key'           => 'fsfgdgfsuwrwnuojojlm',
            'cert_file'     => __DIR__ . '/test-data/wx_merchant_cert.pem',
            'sslkey_file'   => __DIR__ . '/test-data/wx_merchant_sslkey.pem',
            'notify_url'    => 'http://localhost/trade.php',
        ];

        $this->beAnInstanceOf(\Jihe\Services\Payment\Wxpay\WxpayWebService::class, 
            [$config, $httpClient]);
    }

    //=========================================
    //          place unified order
    //=========================================
    public function it_place_order_successfully(ClientInterface $httpClient)
    {
        $httpClient->request('POST', 'https://api.mch.weixin.qq.com/pay/unifiedorder', Argument::cetera())
            ->willReturn(new Response(200, [], file_get_contents(__DIR__ . '/test-data/place_unified_order_sync_jsapi_success.xml')));

        $result = $this->placeOrder('oUpF8uMuAJO_M2pxb1Q9zNjWeS6o', '201506072227000001', 1, '报名费', '8.8.8.8')
             ->getWrappedObject();
        \PHPUnit_Framework_Assert::assertEquals('SUCCESS', $result->responseCode);
        \PHPUnit_Framework_Assert::assertEmpty($result->errMsg);
        \PHPUnit_Framework_Assert::assertEmpty($result->errCode);
        \PHPUnit_Framework_Assert::assertObjectHasAttribute('jsApiParams', $result);
        \PHPUnit_Framework_Assert::assertEquals('wx2421b1c4370ec43b', $result->jsApiParams->appId);
        \PHPUnit_Framework_Assert::assertEquals('prepay_id=wx201411101639507cbf6ffd8b0779950874', $result->jsApiParams->package);
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
}
