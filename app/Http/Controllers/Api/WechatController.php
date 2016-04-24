<?php

namespace Jihe\Http\Controllers\Api;

use Illuminate\Http\Request;
use Jihe\Http\Controllers\Controller;
use Jihe\Services\WechatService;

class WechatController extends Controller
{
    /**
     * Go to wechat oauth, after oauth finished, page will be redirect to
     * redirect url (go to oauth parameter) with user openid, for example:
     *      redirect url is: http://domain/logic, after oauth finished,
     *      page will be redirect to: http://domain/logic?openid=xxxxx
     */
    public function goToOauth(Request $request, WechatService $wechatService)
    {
        $this->validate($request, [
            'redirect_url'          => 'required|url|max:128',
            'is_scope_userinfo'     => 'string|boolean',
        ], [
            'redirect_url.required'     => '授权回跳页面未填写',
            'redirect_url.url'          => '授权回跳页面格式错误',
            'redirect_url.max'          => '授权回跳页面格式错误',
            'is_scope_userinfo.string'  => '授权类型格式错误',
            'is_scope_userinfo.boolean' => '授权类型格式错误',
        ]);

        $isScopeUserInfo = (bool) $request->input('is_scope_userinfo');
        $doOauthUrl = url('wap/wechat/oauth');
        $url = $wechatService->getOauthUrl($doOauthUrl,
                                           $request->input('redirect_url'),
                                           $isScopeUserInfo);

        return redirect($url);
    }

    /**
     * Invode by Wechat with oauth code, page will be redirect to redirect url set by
     * oauthUrl controller after oauth finished
     */
    public function doOauth(Request $request, WechatService $wechatService)
    {
        $this->validate($request, [
            'code'     => 'required|string|max:128',
            'state'    => 'required|string|max:128',        // redirect_url after oauth
        ], [
            'code.required'         => '授权code未填写',
            'code.string'           => '授权code格式错误',
            'code.max'              => '授权code格式错误',
            'state.required'        => 'state未填写',
            'state.string'          => 'state格式错误',
            'state.max'             => 'state格式错误',
        ]);

        try {
            $wechatToken = $wechatService->getWebOauthInfo($request->input('code'));
        } catch (\Exception $ex) {
            $wechatToken = null;
        }
        $openid = $wechatToken ? $wechatToken->getOpenid() : 'null';

        // store openid in session
        $request->session()->put('wechat', [
            'success'   => $openid ? true : false,
            'openid'    => $openid,
            'oauthTime' => time(),
        ]);

        $redirectUrl = $this->assembleOauthRedirectUrl(
            $request->input('state'), ['openid' => $openid]
        );
        return redirect($redirectUrl);
    }

    private function assembleOauthRedirectUrl($redirectUrl, array $params)
    {
        $urlElements = parse_url($redirectUrl);

        // put params into query string
        $queryArrays = [];
        if (isset($urlElements['query'])) {
            parse_str($urlElements['query'], $queryArrays);
            unset($queryArrays['openid']);
        }
        $queryString = http_build_query(array_merge($queryArrays, $params));

        $url = (isset($urlElements['scheme']) ? $urlElements['scheme'] : 'http') . '://' .
               (isset($urlElements['host']) ? $urlElements['host'] : '') . 
               (isset($urlElements['port']) ? ':' . $urlElements['port'] : '') .
               (isset($urlElements['path']) ? $urlElements['path'] : '/') .
               ($queryString ? ('?' . $queryString) : '') .
               (isset($urlElements['fragment']) ? ('#' . $urlElements['fragment']) : '');

        return $url;
    }
}
