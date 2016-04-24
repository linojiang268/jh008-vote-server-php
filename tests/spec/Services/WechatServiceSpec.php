<?php
namespace spec\Jihe\Services;

use PhpSpec\Laravel\LaravelObjectBehavior;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use Prophecy\Argument;
use PHPUnit_Framework_Assert as Assert;
use Cache;
use Event;
use Mockery;
use Storage;
use Jihe\Contracts\Repositories\WechatTokenRepository;
use Jihe\Contracts\Repositories\WechatUserRepository;

class WechatServiceSpec extends LaravelObjectBehavior
{
    function let(ClientInterface $httpClient,
                 WechatTokenRepository $wechatTokenRepository,
                 WechatUserRepository $wechatUserRepository
    ) {
        $this->beAnInstanceOf(
            \Jihe\Services\WechatService::class,
            [
                $httpClient,
                $wechatTokenRepository,
                $wechatUserRepository,
                [
                    'app_id' => 'appid',
                    'app_secret' => 'appsecret',
                    'company'    => false,
                ],
            ]
        );
    }

    //========================================
    //           Get Sign Package
    //========================================
    function it_get_sign_package_successful_not_cache_not_storage(ClientInterface $httpClient)
    {
        Cache::shouldReceive('has')->with('wechat_ticket')->andReturn(false);
        Cache::shouldReceive('has')->with('wechat_access_token')->andReturn(false);
        Cache::shouldReceive('forever')->andReturn(null);

        Storage::shouldReceive('exists')->with('wechat_ticket')->andReturn(false);
        Storage::shouldReceive('exists')->with('wechat_access_token')->andReturn(false);
        Storage::shouldReceive('put')->andReturn(null);

        $httpClient->request('GET',
                             Argument::that(function ($url) {
                                 return (strpos($url, "https://api.weixin.qq.com/cgi-bin/token") === 0);
                             }),
                             Argument::any())
                   ->shouldBeCalled()
                   ->willReturn(new Response(200, [], '{"access_token":"access_token", "expires_in":11111}'));

        $httpClient->request('GET',
                             Argument::that(function ($url) {
                                 return (strpos($url, "https://api.weixin.qq.com/cgi-bin/ticket/getticket") === 0);
                             }),
                             Argument::any())
                   ->shouldBeCalled()
                   ->willReturn(new Response(200, [], '{"ticket":"jsapi_ticket", "expires_in":11111}'));

        $this->getJsSignPackage('http://localhost/wap/activity/detail?act=1')->shouldNotBeNull();
    }
    
    //============================================
    //           oauth step one: getOauthUrl
    //============================================
    function it_get_oauth_url_successful_with_base_grant_type()
    {
        $doOauthUrl = 'http://domain/oauth';
        $redirectUrl = 'http://domain/logic';
        $this->getOauthUrl($doOauthUrl, $redirectUrl, false)
             ->shouldReturn('https://open.weixin.qq.com/connect/oauth2/authorize?' .
                    'appid=appid&redirect_uri=' . urlencode($doOauthUrl) . '&' .
                    'response_type=code&scope=snsapi_base&state=' . urlencode($redirectUrl) . '#wechat_redirect');
    }

    function it_get_oauth_url_successful_with_userinfo_grant_type()
    {
        $doOauthUrl = 'http://domain/oauth';
        $redirectUrl = 'http://domain/logic';
        $this->getOauthUrl($doOauthUrl, $redirectUrl, true)
             ->shouldReturn('https://open.weixin.qq.com/connect/oauth2/authorize?' .
                    'appid=appid&redirect_uri=' . urlencode($doOauthUrl) . '&' .
                    'response_type=code&scope=snsapi_userinfo&state=' . urlencode($redirectUrl) . '#wechat_redirect');
    }

    //============================================
    //           oauth step two: getWebOauthInfo
    //============================================
    function it_get_web_oauth_info_successfully(
        ClientInterface $httpClient,
        WechatTokenRepository $wechatTokenRepository
    ) {
        // mock event
        Event::shouldReceive('fire')
            ->once()
            ->with(Mockery::type(\Jihe\Events\WechatOauthDoneEvent::class), [], false)
            ->andReturn([null]);

        $code = '1234';
        $oauthUrl = 'https://api.weixin.qq.com/sns/oauth2/access_token?' . http_build_query([
            'appid'         => 'appid',
            'secret'        => 'appsecret',
            'code'          => $code,
            'grant_type'    => 'authorization_code',
        ]);
        $httpClient->request('GET',
                             Argument::that(function ($url) use ($oauthUrl) {
                                    return $url === $oauthUrl;
                             }),
                             Argument::any())
                   ->shouldBeCalledTimes(1)
                   ->willReturn(new Response(200, [], json_encode([
                                    'access_token'  => 'ACCESS_TOKEN',
                                    'expires_in'    => 7200,
                                    'refresh_token' => 'REFRESH_TOKEN',
                                    'openid'        => 'OPENID',
                                    'scope'         => 'snsapi_userinfo',
                               ])));

        $wechatToken = (new \Jihe\Entities\WechatToken())
            ->setOpenid('OPENID')
            ->setWebTokenAccess('ACCESS_TOKEN')
            ->setWebTokenExpireAt(date('Y-m-d H:i:s', time() + 7200))
            ->setWebTokenRefresh('REFRESH_TOKEN');
        $wechatTokenRepository->saveWebAccessToken(
            'OPENID', 'ACCESS_TOKEN', 7200, 'REFRESH_TOKEN'
        )->shouldBeCalledTimes(1)->willReturn($wechatToken);

        $oauthInfo = $this->getWebOauthInfo($code)->getWrappedObject();
        Assert::assertEquals('ACCESS_TOKEN', $oauthInfo->getWebTokenAccess());
        Assert::assertEquals('REFRESH_TOKEN', $oauthInfo->getWebTokenRefresh());
        Assert::assertEquals('OPENID', $oauthInfo->getOpenid());
    }

    function it_get_web_oauth_info_failed_with_wrong_wechat_response(
        ClientInterface $httpClient
    ) {
        $code = '1234';
        $oauthUrl = 'https://api.weixin.qq.com/sns/oauth2/access_token?' . http_build_query([
            'appid'         => 'appid',
            'secret'        => 'appsecret',
            'code'          => $code,
            'grant_type'    => 'authorization_code',
        ]);
        $httpClient->request('GET',
                             Argument::that(function ($url) use ($oauthUrl) {
                                    return $url === $oauthUrl;
                             }),
                             Argument::any())
                   ->shouldBeCalledTimes(1)
                   ->willReturn(new Response(200, [], json_encode([
                                    'errcode'   => '40029',
                                    'errmsg'    => 'invalid code',
                               ])));

        $this->getWebOauthInfo($code)->shouldBeNull();
    }

    //=============================================
    //           createOrUpdateUserUsingWebToken
    //=============================================
    public function it_create_or_update_user_using_web_token_success(
        ClientInterface $httpClient,
        WechatTokenRepository $wechatTokenRepository,
        WechatUserRepository $wechatUserRepository
    ) {
        $data = $this->prepareData();
        $userUrl = 'https://api.weixin.qq.com/sns/userinfo?' . http_build_query([
            'access_token'  => $data['wechatToken']->getWebTokenAccess(),
            'openid'        => $data['openid'],
            'lang'          => 'zh_CN',
        ]);

        $wechatTokenRepository->fetchToken($data['openid'])
                              ->shouldBeCalledTimes(1)
                              ->willReturn($data['wechatToken']);
        $httpClient->request('GET',
                             Argument::that(function ($url) use ($userUrl) {
                                    return $url == $userUrl;
                             }),
                             Argument::any())
                   ->shouldBeCalledTimes(1)
                   ->willReturn(new Response(200, [], json_encode([
                                    'openid'        => 'OPENID',
                                    'nickname'      => 'NICK_NAME',
                                    'sex'           => 0,
                                    'country'       => 'china',
                                    'province'      => 'sichuan',
                                    'city'          => 'chengdu',
                                    'headimgurl'    => 'http://domain/headimg',
                                    'unionid'       => '',
                               ])));
        $wechatUserRepository->saveUser([
                'openid'        => 'OPENID',
                'nick_name'     => 'NICK_NAME',
                'gender'        => 0,
                'country'       => 'china',
                'province'      => 'sichuan',
                'city'          => 'chengdu',
                'headimgurl'    => 'http://domain/headimg',
                'unionid'       => '',
            ])->shouldBeCalledTimes(1)
              ->willReturn($data['wechatUser']);

        $this->createOrUpdateUserUsingWebToken($data['openid'])
             ->shouldReturn($data['wechatUser']);
    }

    public function it_create_or_update_user_using_web_token_success_refresh_token(
        ClientInterface $httpClient,
        WechatTokenRepository $wechatTokenRepository,
        WechatUserRepository $wechatUserRepository
    ) {
        $data = $this->prepareData();
        // Make access token expired
        $data['wechatToken']->setWebTokenExpireAt(
            new \DateTime(date('Y-m-d H:i:s', time() - 7200))
        );

        $refreshUrl = 'https://api.weixin.qq.com/sns/oauth2/refresh_token?' . http_build_query([
            'appid'         => 'appid',
            'grant_type'    => 'refresh_token',
            'refresh_token' => $data['wechatToken']->getWebTokenRefresh()
        ]);
        $userUrl = 'https://api.weixin.qq.com/sns/userinfo?' . http_build_query([
            'access_token'  => $data['wechatToken']->getWebTokenAccess(),
            'openid'        => $data['openid'],
            'lang'          => 'zh_CN',
        ]);

        $wechatTokenRepository->fetchToken($data['openid'])
                              ->shouldBeCalledTimes(1)
                              ->willReturn($data['wechatToken']);
        $httpClient->request('GET', $refreshUrl, Argument::any())
                   ->shouldBeCalledTimes(1)
                   ->willReturn(new Response(200, [], json_encode([
                        'openid'    => $data['wechatToken']->getOpenid(),
                        'access_token'  => $data['wechatToken']->getWebTokenAccess(),
                        'expires_in'    => 7200,
                        'refresh_token' => $data['wechatToken']->getWebTokenRefresh()
                   ])));
        $wechatTokenRepository->saveWebAccessToken(
            $data['wechatToken']->getOpenid(),
            $data['wechatToken']->getWebTokenAccess(),
            7200,
            $data['wechatToken']->getWebTokenRefresh()
        )->shouldBeCalledTimes(1)
         ->willReturn($data['wechatToken']);

        $httpClient->request('GET', $userUrl, Argument::any())
                   ->shouldBeCalledTimes(1)
                   ->willReturn(new Response(200, [], json_encode([
                                    'openid'        => 'OPENID',
                                    'nickname'      => 'NICK_NAME',
                                    'sex'           => 0,
                                    'country'       => 'china',
                                    'province'      => 'sichuan',
                                    'city'          => 'chengdu',
                                    'headimgurl'    => 'http://domain/headimg',
                                    'unionid'       => '',
                               ])));
        $wechatUserRepository->saveUser([
                'openid'        => 'OPENID',
                'nick_name'     => 'NICK_NAME',
                'gender'        => 0,
                'country'       => 'china',
                'province'      => 'sichuan',
                'city'          => 'chengdu',
                'headimgurl'    => 'http://domain/headimg',
                'unionid'       => '',
            ])->shouldBeCalledTimes(1)
              ->willReturn($data['wechatUser']);

        $this->createOrUpdateUserUsingWebToken($data['openid']);
    }

    public function it_create_or_update_user_using_web_token_failed_not_oauth(
        WechatTokenRepository $wechatTokenRepository
    ) {
        $wechatTokenRepository->fetchToken('OPENID')
                              ->shouldBeCalledTimes(1)
                              ->willReturn(null);

        $this->shouldThrow(new \Exception('用户未授权'))
             ->duringCreateOrUpdateUserUsingWebToken('OPENID');
    }

    public function it_create_or_update_user_using_web_token_failed_refresh_token(
        ClientInterface $httpClient,
        WechatTokenRepository $wechatTokenRepository,
        WechatUserRepository $wechatUserRepository
    ) {
        $data = $this->prepareData();
        // Make access token expired
        $data['wechatToken']->setWebTokenExpireAt(
            new \DateTime(date('Y-m-d H:i:s', time() - 7200))
        );

        $refreshUrl = 'https://api.weixin.qq.com/sns/oauth2/refresh_token?' . http_build_query([
            'appid'         => 'appid',
            'grant_type'    => 'refresh_token',
            'refresh_token' => $data['wechatToken']->getWebTokenRefresh()
        ]);

        $wechatTokenRepository->fetchToken($data['openid'])
                              ->shouldBeCalledTimes(1)
                              ->willReturn($data['wechatToken']);
        $httpClient->request('GET', $refreshUrl, Argument::any())
                   ->shouldBeCalledTimes(1)
                   ->willReturn(new Response(200, [], json_encode([
                        'errcode'   => 40029,
                        'errmsg'    => 'invalid code',
                   ])));

        $this->shouldThrow(new \Exception('刷新网页授权access_token失败: invalid code', 40029))
             ->duringCreateOrUpdateUserUsingWebToken('OPENID');
    }

    private function prepareData()
    {
        $openid = 'OPENID';
        $wechatToken = (new \Jihe\Entities\WechatToken())
            ->setOpenid('OPENID')
            ->setWebTokenAccess('ACCESS_TOKEN')
            ->setWebTokenExpireAt(new \DateTime(date('Y-m-d H:i:s', time() + 7200)))
            ->setWebTokenRefresh('REFRESH_TOKEN');
        $wechatUser = (new \Jihe\Entities\WechatUser())
            ->setOpenid($openid)
            ->setNickName('NICK_NAME')
            ->setGender(0)
            ->setCountry('china')
            ->setProvince('sichuan')
            ->setCity('chengdu')
            ->setHeadimgurl('http://domain/headimg')
            ->setUnionid('');

        return [
            'openid'    => $openid,
            'wechatToken'   => $wechatToken,
            'wechatUser'    => $wechatUser,
        ];
    }
}
