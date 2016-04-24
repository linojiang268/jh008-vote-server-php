<?php
namespace Jihe\Contracts\Repositories;

interface WechatTokenRepository
{
    /**
     * Save web access token, if records related the openid aready
     * exists, update it
     *
     * @param string $openid
     * @param string $accessToken
     * @param integer $tokenExpiresIn   after tokenExpiresIn seconds, accessToken
     *                                  will expired
     * @param string $refreshToken      has a long lifetime, be used for fetch
     *                                  access token after it expired
     *
     * @return \Jihe\Entities\WechatToken
     */
    public function saveWebAccessToken($openid,
                                       $accessToken,
                                       $tokenExpiresIn,
                                       $refreshToken
    );

    /**
     * Fetch user wechat token by openid
     *
     * @param string $openid
     *
     * @return \Jihe\Entities\WechatToken|null
     */
    public function fetchToken($openid);
}
