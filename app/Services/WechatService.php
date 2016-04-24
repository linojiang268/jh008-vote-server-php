<?php
namespace Jihe\Services;

use Cache;
use Jihe\Entities\WechatToken;
use Storage;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;
use Jihe\Contracts\Repositories\WechatTokenRepository;
use Jihe\Contracts\Repositories\WechatUserRepository;
use Jihe\Events\WechatOauthDoneEvent;

class WechatService
{
    const OAUTH_SCOPE_BASE = 'snsapi_base';
    const OAUTH_SCOPE_USERINFO = 'snsapi_userinfo';
    const PREFIX_KEY = 'wechat_';
    const TICKET = 'ticket';
    const TOKEN = 'access_token';

    const URL_FOR_GET_COMPANY_TICKET = "https://qyapi.weixin.qq.com/cgi-bin/get_jsapi_ticket?access_token=%s";
    const URL_FOR_GET_TICKET = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=%s";

    const URL_FOR_GET_COMPANY_TOKEN = "https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid=%s&corpsecret=%s";
    const URL_FOR_GET_TOKEN = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=%s&secret=%s";

    const URL_FOR_OAUTH_GET_CODE = 'https://open.weixin.qq.com/connect/oauth2/authorize';
    const URL_FOR_OAUTH_GET_TOKEN = 'https://api.weixin.qq.com/sns/oauth2/access_token';
    const URL_FOR_REFRESH_WEB_ACCESS_TOKEN = 'https://api.weixin.qq.com/sns/oauth2/refresh_token';

    const URL_FOR_FETCH_USERINFO_FROM_WEB = 'https://api.weixin.qq.com/sns/userinfo';

    private $appId;
    private $appSecret;
    private $company;

    /**
     * Http client
     *
     * @var \GuzzleHttp\ClientInterface
     */
    private $httpClient;

    /**
     * @var \Jihe\Contracts\Repositories\WechatTokenRepository
     */
    private $wechatTokenRepository;

    /**
     * @var \Jihe\Contracts\Repositories\WechatUserRepository
     */
    private $wechatUserRepository;

    /**
     * @param ClientInterface $httpClient
     * @param Jihe\Contracts\Repositories\WechatTokenRepository $wechatTokenRepository
     * @param Jihe\Contracts\Repositories\WechatUserRepository $wechatUserRepository
     * @param array           $option      keys taken:
     *                                      - app_id     (string)required
     *                                      - app_secret (string)required
     *                                      - company    (bool)required
     */
    public function __construct(ClientInterface $httpClient,
                                WechatTokenRepository $wechatTokenRepository,
                                WechatUserRepository $wechatUserRepository,
                                array $option
    ) {
        $this->httpClient = $httpClient;
        $this->wechatTokenRepository = $wechatTokenRepository;
        $this->wechatUserRepository = $wechatUserRepository;
        $this->appId      = array_get($option, 'app_id');
        $this->appSecret  = array_get($option, 'app_secret');
        $this->company    = array_get($option, 'company');
    }

    /**
     * get sign package of wx js
     *
     * @param $url    注意 URL 一定要动态获取，不能 hardcode.
     * @return array  keys taken:
     *                 - appId
     *                 - nonceStr
     *                 - timestamp
     *                 - url
     *                 - signature
     *                 - rawString
     */
    public function getJsSignPackage($url)
    {
        $jsapiTicket = $this->getJsTicket();

        $timestamp = time();
        $nonceStr = $this->createNonceStr();

        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";

        $signature = sha1($string);

        $signPackage = array(
            "appId"     => $this->appId,
            "nonceStr"  => $nonceStr,
            "timestamp" => $timestamp,
            "url"       => $url,
            "signature" => $signature,
            "rawString" => $string
        );

        return $signPackage;
    }

    //=================================
    //          Oauth
    //=================================
    /**
     * Get Oauth url, user will redirect to this url, then wechat server will
     * redirect to the url specifed in Oauth url with oauth code
     *
     * @param string $doOauthUrl        wechat will redirect to this url with oauth code
     * @param string $redirectUrl       redirect url after oauth finished
     * @param boolean $isScopeUserInfo  use snsapi_userinfo scope if true, or snsapi_base
     *
     * @return string                   oauth url
     */
    public function getOauthUrl($doOauthUrl, $redirectUrl, $isScopeUserInfo)
    {
        $scope = $isScopeUserInfo ? self::OAUTH_SCOPE_USERINFO
                                  : self::OAUTH_SCOPE_BASE;

        return self::URL_FOR_OAUTH_GET_CODE . '?' . http_build_query([
            'appid'         => $this->appId,
            'redirect_uri'  => $doOauthUrl,
            'response_type' => 'code',
            'scope'         => $scope,
            'state'         => $redirectUrl,
        ]) . '#wechat_redirect';
    }

    /**
     * Get Web oauth info by code
     *
     * @param string $code      code from wx
     *
     * @return \Jihe\Entities\WechatToken|null
     */
    public function getWebOauthInfo($code)
    {
        $url = self::URL_FOR_OAUTH_GET_TOKEN . '?' . http_build_query([
            'appid'         => $this->appId,
            'secret'        => $this->appSecret,
            'code'          => $code,
            'grant_type'    => 'authorization_code',
        ]);

        $res = json_decode($this->httpGet($url));
        if ( ! $res ||isset($res->errcode)) {
            return null;
        }


        if ($res->scope == self::OAUTH_SCOPE_USERINFO) {
            // Save oauth token info, only scope is snsapi_userinfo
            // we can fetch user info using web access token
            $wechatToken = $this->wechatTokenRepository->saveWebAccessToken(
                $res->openid, $res->access_token,
                $res->expires_in, $res->refresh_token
            );

            // fire oauth done event
            event(new WechatOauthDoneEvent($res->openid));
        } else {
            $wechatToken = (new WechatToken)->setOpenid($res->openid);
        }

        return $wechatToken;
    }

    /**
     * Judge whether aready finished oauth or not
     *
     * @param string $openid
     *
     * @return boolean
     */
    public function isUserOauthed($openid)
    {
        return $this->wechatTokenRepository->fetchToken($openid) != null;
    }

    public function getUser($openid)
    {
        return $this->wechatUserRepository->findOne($openid);
    }

    /**
     * create a user which get from wechat using web token, or
     * update existance user
     *
     * @param string $openid
     *
     * @return \Jihe\Entities\WechatUser
     */
    public function createOrUpdateUserUsingWebToken($openid)
    {
        $userInfo = $this->getUserInfoUsingWebTokenAndParse($openid);
        return $this->wechatUserRepository->saveUser($userInfo);
    }

    /**
     * Get user info from wechat
     *
     * @param string $openid
     *
     * @return array            user info
     *
     * @throw \Exception if error happend
     */
    private function getUserInfoUsingWebTokenAndParse($openid)
    {
        $wechatToken = $this->wechatTokenRepository->fetchToken($openid);
        if ( ! $wechatToken || ! $wechatToken->getWebTokenAccess()) {
            throw new \Exception('用户未授权');
        }
        if ($wechatToken->getWebTokenExpireAt()->getTimestamp() < time()) {
            $wechatToken = $this->refreshWebToken($wechatToken->getWebTokenRefresh());
        }

        // call wechat interface and get userinfo
        $url = self::URL_FOR_FETCH_USERINFO_FROM_WEB . '?' . http_build_query([
            'access_token'  => $wechatToken->getWebTokenAccess(),
            'openid'        => $openid,
            'lang'          => 'zh_CN',
        ]);
        $res = json_decode($this->httpGet($url));
        if ( ! $res || isset($res->errcode)) {
            throw new \Exception(sprintf(
                '获取微信用户信息失败: %s', $res->errmsg
            ), $res->errcode);
        }

        return [
            'openid'        => $res->openid,
            'nick_name'     => $res->nickname,
            'gender'        => (int) $res->sex,
            'country'       => $res->country,
            'province'      => $res->province,
            'city'          => $res->city,
            'headimgurl'    => $res->headimgurl,
            'unionid'       => isset($res->unionid) ? $res->unionid : null,
        ];
    }

    /**
     * Fetch new web access token if which expired
     *
     * @param string $refreshToken
     *
     * @return \Jihe\Entities\WechatToken
     *
     * @throw \Exception if refresh token failed
     */
    private function refreshWebToken($refreshToken)
    {
        $url = self::URL_FOR_REFRESH_WEB_ACCESS_TOKEN . '?' . http_build_query([
            'appid'         => $this->appId,
            'grant_type'    => 'refresh_token',
            'refresh_token' => $refreshToken,
        ]);

        $res = json_decode($this->httpGet($url));
        if ( ! $res) {
            throw new \Exception('刷新网页授权access_token失败');
        }

        if (isset($res->errcode)) {
            throw new \Exception(sprintf(
                '刷新网页授权access_token失败: %s', $res->errmsg
            ), $res->errcode);
        }

        // Save new token info
        return $this->wechatTokenRepository->saveWebAccessToken(
            $res->openid, $res->access_token,
            $res->expires_in, $res->refresh_token
        );
    }

    private function createNonceStr($length = 16)
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    private function getJsTicket()
    {
        $key = self::TICKET;

        $ticket = $this->getParamFromLocal($key);

        if (!$this->checkParamExpireTime($ticket)) {
            $token = $this->getJsToken();

            if ($this->company) {
                $ticket = $this->getParamFromWechat(sprintf(self::URL_FOR_GET_COMPANY_TICKET,
                                                            $token),
                                                    $key);
            } else {
                $ticket = $this->getParamFromWechat(sprintf(self::URL_FOR_GET_TICKET,
                                                            $token),
                                                    $key);
            }

            if (!$this->checkParamExpireTime($ticket)) {
                throw new \Exception('get ticket fail');
            }

            $this->saveParamToLocal($key, $ticket);
        }

        return $ticket->$key;
    }

    private function getJsToken()
    {
        $key = self::TOKEN;

        $token = $this->getParamFromLocal($key);

        if (!$this->checkParamExpireTime($token)) {
            if ($this->company) {
                $token = $this->getParamFromWechat(sprintf(self::URL_FOR_GET_COMPANY_TOKEN,
                                                           $this->appId,
                                                           $this->appSecret),
                                                   $key);
            } else {
                $token = $this->getParamFromWechat(sprintf(self::URL_FOR_GET_TOKEN,
                                                           $this->appId,
                                                           $this->appSecret),
                                                   $key);
            }

            if (!$this->checkParamExpireTime($token)) {
                throw new \Exception('get access_token fail');
            }

            $this->saveParamToLocal($key, $token);
        }

        return $token->$key;
    }

    private function checkParamExpireTime($value)
    {
        if (is_null($value) || $value->app_id != $this->appId || $value->expire_time < time()) {
            return false;
        }

        return true;
    }

    /**
     * get param from local (from cache or from storage file)
     *
     * @param $name  name of param
     * @return       std value of param
     */
    private function getParamFromLocal($name)
    {
        $key = self::PREFIX_KEY . $name;
        if (Cache::has($key)) {
            if (!empty($value = Cache::get($key))) {
                return json_decode($value);
            }
        }

        if (Storage::exists($key)) {
            if (!empty($value = Storage::get($key))) {
                $value = json_decode($value);

                $this->saveParamToLocal($name, $value);
                return $value;
            }
        }

        return null;
    }

    /**
     * get param from wechat by network
     *
     * @param $requestUrl  url for get the param value
     * @param $name        name of param
     * @return             std value of param
     */
    private function getParamFromWechat($requestUrl, $name)
    {
        $res = json_decode($this->httpGet($requestUrl));

        $ret = new \stdClass();
        $ret->app_id = $this->appId;

        if (property_exists($res, $name)) {
            $ret->$name       = $res->$name;
            $ret->expire_time = time() + $res->expires_in - 200;
        } else {
            $ret->$name       = '';
            $ret->expire_time = 0;
        }

        return $ret;
    }

    private function httpGet($url) {
        $options = [
            RequestOptions::TIMEOUT => 500,
            RequestOptions::VERIFY  => false,
        ];

        $response = $this->httpClient->request('GET', $url, $options);
        if ($response->getStatusCode() != 200) {
            throw new \Exception('bad response from wxjs: ' . (string)$response->getBody());
        }

        return (string) $response->getBody();
    }

    /**
     * save param to local (in cache and storage file)
     *
     * @param $name   name of param
     * @param $value  std value of param
     */
    private function saveParamToLocal($name, $value)
    {
        $key = self::PREFIX_KEY . $name;
        Cache::forever($key, json_encode($value));

        Storage::put($key, json_encode($value));
    }
}
