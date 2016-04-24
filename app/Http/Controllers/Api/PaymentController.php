<?php

namespace Jihe\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Guard;
use Jihe\Http\Controllers\Controller;
use Jihe\Services\PaymentService;
use Jihe\Services\UserService;
use Jihe\Exceptions\User\UserNotExistsException;
use Log;

class PaymentController extends Controller
{
    //==================================
    //          Wxpay
    //==================================
    /**
     * app payment prepare trade.
     */
    public function wxpayAppPrepareTrade(Request $request, Guard $auth,
                                        PaymentService $paymentService)
    {
        $this->validate($request, [
            'order_no' => 'required|string|size:32',
        ], [
            'order_no.required'  => '订单号未填写',
            'order_no.string'   => '订单号格式错误',
            'order_no.size'     => '订单号格式错误',
        ]);

        try {
            $payParams = $paymentService->prepareWxAppTrade(
                                                $auth->user()->getAuthIdentifier(),
                                                $request->input('order_no'),
                                                $request->ip());
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }

        return $this->json([
            'appid'     => $payParams->appid,
            'mchid'     => $payParams->mchid,
            'prepay_id' => $payParams->prepayId,
            'nonce_str' => $payParams->nonceStr,
        ]);
    }

    public function wxpayWebPrepareTrade(Request $request,
                                         UserService $userService,
                                         PaymentService $paymentService
    ) {
        $this->validate($request, [
            'order_no'  => 'required|string|size:32',
            'mobile'    => 'required|mobile',
            'openid'    => 'required|string|max:128',
        ], [
            'order_no.required' => '订单号未填写',
            'order_no.string'   => '订单号格式错误',
            'order_no.size'     => '订单号格式错误',
            'mobile.required'    => '手机号未填写',
            'mobile.mobile'      => '手机号格式错误',
            'openid'            => '用户openid未填写',
            'openid.string'     => '用户openid格式错误',
            'openid.max'       => '用户openid格式错误',
        ]);

        try {
            $user = $userService->findUserByMobile($request->input('mobile'));
            if ( ! $user) {
                throw new UserNotExistsException($request->input('mobile'));
            }

            $payParams = $paymentService->prepareWxWebTrade(
                                $user->getId(),
                                $request->input('openid'),
                                $request->input('order_no'),
                                $request->ip());
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }

        return $this->json([
            'appId'     => $payParams->appId,
            'timeStamp' => (string) $payParams->timeStamp,      // timeStamp must be string in wechat jssdk
            'nonceStr'  => $payParams->nonceStr,
            'package'   => $payParams->package,
            'signType'  => $payParams->signType,
            'paySign'   => $payParams->paySign,
        ]);
    }

    public function wxpayNotify(Request $request, PaymentService $paymentService)
    {
        $requestBody = (string) $request->getContent();
        $notification = $this->xmlText2Array($requestBody);

        $result = $paymentService->notifyWxpayment($notification);

        $content = $this->array2Xml([
            'return_code' => $result,
        ]);

        return response($content)
                        ->header('Content-Type', 'text/xml; charset=UTF-8');
    }

    public function wxpayWebNotify(Request $request, PaymentService $paymentService)
    {
        $requestBody = (string) $request->getContent();
        $notification = $this->xmlText2Array($requestBody);

        $result = $paymentService->notifyWxWebpayment($notification);

        $content = $this->array2Xml([
            'return_code' => $result,
        ]);

        return response($content)
                        ->header('Content-Type', 'text/xml; charset=UTF-8');
    }

    //==================================
    //          Alipay
    //==================================
    /**
     * app payment prepare trade.
     */
    public function alipayAppPrepareTrade(Request $request, Guard $auth,
                                          PaymentService $paymentService)
    {
        $this->validate($request, [
            'order_no'      => 'required|string|size:32',
        ], [
            'order_no.required'     => '订单号未填写',
            'order_no.string'       => '订单号格式错误',
            'order_no.size'         => '订单号格式错误',
        ]);

        try {
            $paymentData = $paymentService->prepareAliAppTrade(
                                                    $auth->user()->getAuthIdentifier(),
                                                    $request->input('order_no'));
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }

        return $this->json([
            'payment_data' => $paymentData,
        ]);
    }

    public function alipayNotify(Request $request, PaymentService $paymentService)
    {
        $notification = $request->input();

        $result = $paymentService->notifyAlipayment($notification);

        return response($result);
    }

    private function xmlText2Array($xmlText)
    {
        try {
            $xml = simplexml_load_string($xmlText, 'SimpleXMLElement', LIBXML_NOCDATA);
        } catch (\Exception $ex) {
            Log::error('Wxpay notify xml parse error.', [
                'raw_xml_text' => $xmlText,
            ]);
            return [];
        }

        return json_decode(json_encode($xml), true) ?: [];
    }

    private function array2Xml(array $data)
    {
        $xml = '<xml>';
        foreach ($data as $k => $v) {
            if (is_numeric($v)) {
                $xml .= '<'.$k.'>'.$v.'</'.$k.'>';
            } else {
                $xml .= '<'.$k.'><![CDATA['.$v.']]></'.$k.'>';
            }
        }
        $xml .= '</xml>';

        return $xml;
    }
}
