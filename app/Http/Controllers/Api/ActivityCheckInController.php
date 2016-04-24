<?php

namespace Jihe\Http\Controllers\Api;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Jihe\Http\Controllers\Controller;
use Jihe\Utils\URLUtil;
use Validator;
use Jihe\Services\ActivityService;
use Jihe\Services\ActivityCheckInService;
use Jihe\Services\ActivityMemberService;
use Jihe\Services\VerificationService;

class ActivityCheckInController extends Controller
{
    public function sendVerifyCodeForCheckIn(
        Request $request,
        VerificationService $verificationService
    )
    {
        $this->validate($request, [
            'mobile'      => 'required|size:11',
            'activity_id' => 'required|integer',
        ], [
            'mobile.required'      => '手机号未填写',
            'mobile.size'          => '手机号格式错误',
            'activity_id.required' => '活动id未填写',
            'activity_id.integer'  => '活动id格式错误',
        ]);

        try {
            list($code, $sendInterval) = $verificationService->sendForActivityCheckIn(
                $request->input('mobile'), $request->input('activity_id'));
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }

        return $this->json([
            'send_interval' => $sendInterval,
            'message'       => '发送成功',
        ]);
    }

    public function getCheckInList(
        Request $request,
        Guard $auth,
        ActivityService $activityService,
        ActivityCheckInService $activityCheckInService,
        ActivityMemberService $activityMemberService
    )
    {
        /**
         * We get qrcode_url, not parameters only, cause the qrcode_url may be related to
         * a html page which will be show when user scan the qrcode without our app scanner.
         */
        $requestParams = $request->only('qrcode_url');
        $validator = Validator::make($requestParams, [
            'qrcode_url' => 'required',
        ], [
            'qrcode_url.required' => '二维码错误',
        ]);

        if ($validator->fails()) {
            return $this->jsonException($validator->errors()->first());
        }
        try {
            $QRParams = URLUtil::parseURLParamsToArray($requestParams['qrcode_url']);

            if (!array_key_exists('activity_id', $QRParams)) {
                throw new \Exception('无法识别活动');
            }

            if (!array_key_exists('step', $QRParams)) {
                throw new \Exception('无法识别步骤');
            }

            if (!array_key_exists('ver', $QRParams)) {
                throw new \Exception('无法识别二维码版本');
            }

            list($userId, $activityId, $step, $qrcodeVersion) = [$auth->user()->getAuthIdentifier(), $QRParams['activity_id'], $QRParams['step'], $QRParams['ver']];
            // get Activity info
            $activity = $activityService->getPublishedActivityById($activityId);
            if (!$activity) {
                return $this->jsonException('该活动不存在');
            }

            $checkInList = $activityCheckInService->getCheckInList($userId, $activityId);
            if (empty($checkInList)) {
                return $this->jsonException('该活动不需要签到');
            }

            if (!$activityMemberService->isActivityMember($userId, $activityId)) {
                return $this->jsonException('您没有报名，无法签到');
            }

            return $this->json([
                'activity_id'    => $activityId,
                'activity_title' => $activity->getTitle(),
                'step'           => $step,
                'ver'            => $qrcodeVersion,
                'check_list'     => $checkInList,
            ]);
        } catch (\Exception $e) {
            return $this->jsonException($e);
        }
    }

    public function checkIn(Request $request, Guard $auth, ActivityCheckInService $activityCheckInService)
    {
        $requestParams = $request->only('activity_id', 'step');
        $validator = Validator::make($requestParams, [
            'activity_id' => 'required|integer',
            'step'        => 'required|integer',
            'ver'         => 'integer',
        ], [
            'activity_id.required' => '活动未标识',
            'activity_id.integer'  => '活动标识错误',
            'step.required'        => '签到步骤未标识',
            'step.integer'         => '签到步骤格式错误',
        ]);

        if ($validator->fails()) {
            return $this->jsonException($validator->errors()->first());
        }
        try {
            list($userId, $activityId, $step) = [
                $auth->user()->getAuthIdentifier(),
                $requestParams['activity_id'],
                $requestParams['step'],
            ];
            $checkInResult = $activityCheckInService->checkIn($userId, $activityId, $step);

            return $this->json([
                'message' => $checkInResult['message'],
                'step' => $step,
                'check_list' => $checkInResult['check_list']
            ]);
        } catch (\Exception $e) {
            return $this->jsonException($e);
        }
    }

    public function firstStepQuickCheckIn(
        Request $request,
        ActivityCheckInService $activityCheckInService
    )
    {
        $this->validate($request, [
            'mobile'   => 'required|mobile',
            'activity' => 'required|integer',
            'captcha'  => 'required|captcha',
        ], [
            'mobile.required'   => '手机号未填写',
            'mobile.mobile'     => '手机号格式错误',
            'captcha.required'  => '验证码未填写',
            'captcha.captcha'   => '验证码错误',
            'activity.required' => '活动id未填写',
            'activity.integer'  => '活动id格式错误',
        ]);

        try {
            // check in
            $activityCheckInService->firstStepQuickCheckIn($request->input('mobile'),
                $request->input('activity'));
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }

        return $this->json('签到成功');
    }
}
