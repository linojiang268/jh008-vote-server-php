<?php
namespace intg\Jihe\Controllers\Api;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use intg\Jihe\TestCase;
use intg\Jihe\RequestSignCheck;
use intg\Jihe\AuthDeviceCheck;
use \PHPUnit_Framework_Assert as Assert;

class WechatControllerTest extends TestCase
{
    use DatabaseTransactions;
    use RequestSignCheck;
    use AuthDeviceCheck;

    //=========================================
    //      goToOauth
    //=========================================
    public function testGoToOauthUrlSuccessfully()
    {
        $url = '/wap/wechat/oauth/go?' . http_build_query([
            'redirect_url'  => 'http://domain/logic',
        ]);
        $response = $this->call('GET', $url);
        Assert::assertEquals(302, $response->status());     // check redirect status
        Assert::assertInstanceOf('Illuminate\Http\RedirectResponse', $response);
        Assert::assertEquals(true, 0 === strpos($response->getTargetUrl(), 'https://open.weixin.qq.com/connect/oauth2/authorize'));
    }

    public function testGoToOauthUrlFailed_MissRedirectUrl()
    {
        $response = $this->call('GET', '/wap/wechat/oauth/go');

        Assert::assertEquals(302, $response->status());     // check redirect status
        Assert::assertInstanceOf('Illuminate\Http\RedirectResponse', $response);
        Assert::assertEquals(true, false === strpos($response->getTargetUrl(), 'https://open.weixin.qq.com/connect/oauth2/authorize'));
    }

    public function testGoToOauthUrlFailed_WrongRedirectUrl()
    {
        $url = '/wap/wechat/oauth/go?' . http_build_query([
            'redirect_url'  => 'domain.com/logic',  // scheme missed
        ]);
        $response = $this->call('GET', $url);

        Assert::assertEquals(302, $response->status());     // check redirect status
        Assert::assertInstanceOf('Illuminate\Http\RedirectResponse', $response);
        Assert::assertEquals(true,  false === strpos($response->getTargetUrl(), 'https://open.weixin.qq.com/connect/oauth2/authorize'));
    }

    //=========================================
    //      doOauth
    //=========================================
    public function testDoOauthSuccessfully()
    {
        $this->mockWechatService(1234);
        $response = $this->call('GET', 'wap/wechat/oauth?code=1234&state=' . urlencode('https://domain:8080/logic?openid=test'));

        Assert::assertEquals(302, $response->status());     // check redirect status
        Assert::assertInstanceOf('Illuminate\Http\RedirectResponse', $response);
        Assert::assertEquals('https://domain:8080/logic?openid=OPENID', $response->getTargetUrl());
    }

    private function mockWechatService($code)
    {
        $wechatService = \Mockery::mock(\Jihe\Services\WechatService::class);
        $oauthInfo = (new \Jihe\Entities\WechatToken())
            ->setOpenid('OPENID')
            ->setWebTokenAccess('ACCESS_TOKEN')
            ->setWebTokenExpireAt(new \DateTime(date('Y-m-d H:i:s', time() + 7200)))
            ->setWebTokenRefresh('REFRESH_TOKEN');
        $wechatService->shouldReceive('getWebOauthInfo')->with($code)->andReturn($oauthInfo);

        $this->app[\Jihe\Services\WechatService::class] = $wechatService;

        return $wechatService;
    }
}
