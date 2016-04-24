<?php

namespace Jihe\Http\Controllers\Api;

use Jihe\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Jihe\Services\UserService;
use Jihe\Services\ActivityApplicantService;
use Illuminate\Contracts\Auth\Guard;
use Jihe\Dispatches\DispatchesMessage;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Jihe\Models\ActivityApplicant;
use Jihe\Utils\SmsTemplate;
use Jihe\Entities\ActivityApplicant as ActivityApplicantEntity;
use Validator;

class ActivityApplicantController extends Controller
{
    use DispatchesJobs, DispatchesMessage;

    /**
     * User applicant activity from app
     */
    public function applicantActivity(
        Request $request,
        Guard $auth,
        ActivityApplicantService $activityApplicantService
    ) {
        $this->validate($request, [
            'activity_id' => 'required|integer',
            'attrs' => 'required|string',
        ], [
            'activity_id.required' => '活动未标识',
            'activity_id.integer' => '活动标识错误',
            'attrs.required' => '报名信息未提交',
            'attrs.string' => '报名信息错误',
        ]);

        try {
            $applicant = $activityApplicantService->doApplicant(
                $auth->user()->getAuthIdentifier(),
                $request->input('activity_id'),
                $request->input('attrs'),
                ActivityApplicantEntity::CHANNEL_APP
            );

            return $this->json([
                'info'  => $this->assembleActivityApplicant($applicant),
            ]);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    public function getApplicantInfoForPayment(Request $request, Guard $auth, ActivityApplicantService $activityApplicantService)
    {
        $requestParams = $request->only('activity_id');
        // validate request
        $validator = Validator::make($requestParams, [
            'activity_id' => 'required|integer',
        ], [
            'activity_id.required' => '活动未标识',
            'activity_id.integer' => '活动标识错误',
        ]);

        if ($validator->fails()) {
            return $this->jsonException($validator->errors()->first());
        }
        try {
            $info = $activityApplicantService->getApplicantInfoForPaymentByActivityId($auth->user()->getAuthIdentifier(), $requestParams['activity_id']);
            return $this->json(['info' => $info]);
        } catch (\Exception $e) {
            return $this->jsonException($e);
        }
    }

    public function searchActivityApplicantFromWeb(
        Request $request,
        ActivityApplicantService $activityApplicantService
    ) {
        $this->validate($request, [
            'mobile'        => 'required|mobile',
            'activity_id'   => 'required|integer',
        ], [
            'mobile.required'       => '手机号未填写',
            'mobile.mobile'         => '手机号格式错误',
            'activity_id.required'  => '活动未填写',
            'activity_id.integer'   => '活动格式错误',
        ]);

        $latestApplicant = $activityApplicantService->getUserLatestApplicantInfo(
            $request->input('activity_id'), $request->input('mobile')
        );

        return $this->json([
            'mobile'        => $request->input('mobile'),
            'applicant'     => $latestApplicant ? [
                                    'status' => (int) $latestApplicant['status'],
                                    'order_no'  => $latestApplicant['order_no'],
                                ] : null,
        ]);
    }

    private function assembleActivityApplicant(ActivityApplicantEntity $applicant)
    {
        return [
            'id'                => $applicant->getId(),
            'activity_id'       => $applicant->getActivity()->getId(),
            'order_no'          => $applicant->getOrderNo(),
            'expire_at'         => $applicant->getExpireAt() ? $applicant->getExpireAt()->format('Y-m-d H:i:s') : null,
            'status'            => $applicant->getStatus(),
        ];
    }
}
