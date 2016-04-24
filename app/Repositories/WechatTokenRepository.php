<?php
namespace Jihe\Repositories;

use Jihe\Contracts\Repositories\WechatTokenRepository as WechatTokenRepositoryContract;
use Jihe\Models\WechatToken;

class WechatTokenRepository implements WechatTokenRepositoryContract
{
    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\WechatTokenRepository::saveWebAccessToken()
     */
    public function saveWebAccessToken($openid,
                                       $accessToken,
                                       $tokenExpiresIn,
                                       $refreshToken
    ) {
        // expireBuffer be used to ensure fetching new access token
        // before exists access token really expired
        $expireBuffer = 60;
        $tokenExpireAt = time() + $tokenExpiresIn - $expireBuffer;
        $wechatToken = WechatToken::where('openid', $openid)->first();
        if ($wechatToken) {
            $wechatToken->web_token_access = $accessToken;
            $wechatToken->web_token_expire_at = $tokenExpireAt;
            $wechatToken->web_token_refresh = $refreshToken;
            $wechatToken->save();
        } else {
            $wechatToken = WechatToken::create([
                'openid'    => $openid,
                'web_token_access'  => $accessToken,
                'web_token_expire_at'   => $tokenExpireAt,
                'web_token_refresh' => $refreshToken,
            ]);
        }

        return $this->convertToEntity($wechatToken);
    }

    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\WechatTokenRepository::fetchToken()
     */
    public function fetchToken($openid)
    {
        return $this->convertToEntity(
            WechatToken::where('openid', $openid)->first()
        );
    }

    /**
     * convert modle to entity
     *
     * @return \Jihe\Entities\WechatToken|null
     */
    private function convertToEntity($wechatToken)
    {
        return $wechatToken ? $wechatToken->toEntity() : null;
    }
}
