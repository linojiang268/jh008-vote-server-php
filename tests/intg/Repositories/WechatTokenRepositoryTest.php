<?php
namespace intg\Jihe\Repositories;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use intg\Jihe\TestCase;

class WechatTokenRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    //===============================================
    //          saveWebAccessToken
    //===============================================
    public function testSaveWebAccessTokenSuccessfully()
    {
        $openid = 'o6_bmjrPTlm6_2sgVt7hMZOPfL2M';
        $accessToken = 'ACCESS_TOKEN';
        $tokenExpiresIn = 7200;
        $refreshToken = 'REFRESH_TOKEN';
        $user = $this->getRepository()->saveWebAccessToken(
            $openid, $accessToken, $tokenExpiresIn, $refreshToken
        );

        self::assertInstanceOf(\Jihe\Entities\WechatToken::class, $user);
        self::assertEquals($openid, $user->getOpenid());
        self::assertEquals($accessToken, $user->getWebTokenAccess());
        self::assertEquals($refreshToken, $user->getWebTokenRefresh());

        $this->seeInDatabase('wechat_tokens', [
            'openid'                => $openid,
            'web_token_access'      => $accessToken,
            'web_token_refresh'     => $refreshToken,
        ]);
    }

    public function testSaveWebAccessTokenSuccessfully_UpdateToken()
    {
        $openid = 'o6_bmjrPTlm6_2sgVt7hMZOPfL2M';
        $accessToken = 'ACCESS_TOKEN';
        $tokenExpiresIn = 7200;
        $refreshToken = 'REFRESH_TOKEN';

        factory(\Jihe\Models\WechatToken::class)->create([
            'openid'            => $openid,
            'web_token_access'  => 'OLD_ACCESS_TOKEN',
            'web_token_refresh' => 'OLD_REFRESH_TOKEN',
        ]);

        $user = $this->getRepository()->saveWebAccessToken(
            $openid, $accessToken, $tokenExpiresIn, $refreshToken
        );

        self::assertInstanceOf(\Jihe\Entities\WechatToken::class, $user);
        self::assertEquals($openid, $user->getOpenid());
        self::assertEquals($accessToken, $user->getWebTokenAccess());
        self::assertEquals($refreshToken, $user->getWebTokenRefresh());

        $this->seeInDatabase('wechat_tokens', [
            'openid'                => $openid,
            'web_token_access'      => 'ACCESS_TOKEN',  // aready changed
            'web_token_refresh'     => 'REFRESH_TOKEN', // aready changed
        ]);

    }

    /**
     * @return \Jihe\Contracts\Repositories\WechatTokenRepository
     */
    private function getRepository()
    {
        return $this->app[\Jihe\Contracts\Repositories\WechatTokenRepository::class];
    }
}
