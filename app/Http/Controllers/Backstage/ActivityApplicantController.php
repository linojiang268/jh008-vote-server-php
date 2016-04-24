<?php

namespace Jihe\Http\Controllers\Backstage;


use Jihe\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Jihe\Models\ActivityApplicant;
use Jihe\Services\VerificationService;
use Jihe\Services\ActivityApplicantService;
use Jihe\Services\UserService;
use Illuminate\Contracts\Auth\Guard;
use Jihe\Utils\SmsTemplate;
use Jihe\Dispatches\DispatchesMessage;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Jihe\Entities\ActivityApplicant as ActivityApplicantEntity;
use Validator;
use Log;
class ActivityApplicantController extends Controller
{
    use DispatchesJobs, DispatchesMessage;

    public function getApplicantsList(Request $request, Guard $auth, ActivityApplicantService $activityApplicantService)
    {
        $requestParams = $request->only('activity_id', 'page', 'size', 'status', 'sort');
        $validator = Validator::make($requestParams, [
            'activity_id' => 'required|integer',
            'page' => 'required|integer',
            'size' => 'integer',
            'status' => 'required|integer',
            'sort' => 'integer'
        ], [
            'activity_id.required' => '活动未标识',
            'activity_id.integer' => '活动标识错误',
            'page.required' => '分页未标识',
            'page.integer' => '分页格式错误',
            'size.integer' => '分页大小格式错误',
            'status.required' => '报名状态未标识',
            'status.integer' => '报名状态格式错误',
        ]);

        if ($validator->fails()) {
            return $this->jsonException($validator->errors()->first());
        }

        try {
            $sort = array_get($requestParams, 'sort', 0);
            $size = array_get($requestParams, 'size', 20);
            if ($size <= 0) {
                $size = 20;
            }

            list($activityId, $page, $status) = [$requestParams['activity_id'], $requestParams['page'], $requestParams['status']];

            list($count, $list) = $activityApplicantService->getApplicantsListByStatus($activityId, [$status], $page, $size, $sort == 0 ? 'ASC' : 'DESC');

            $pages = intval(ceil($count / $size));

            return $this->json(['pages' => $pages, 'list' => $list]);
        } catch (\Exception $e) {
            return $this->jsonException($e);
        }
    }

    /**
     * Get applicants, use applicant id as pagination identifier
     */
    public function getApplicantsListForClientManage(
        Request $request,
        Guard $auth,
        ActivityApplicantService $applicantService
    ) {
        $this->validate($request, [
            'activity'      => 'required|integer',
            'status'        => 'required|integer',
            'id'            => 'integer',
            'size'          => 'integer|min:1',
            'sort'          => 'in:0,1',
            'is_pre'        => 'in:0,1',
        ], [
            'activity.required' => '活动id未填写',
            'activity.integer'  => '活动id格式错误',
            'id.integer'        => '分页id格式错误',
            'id.min'            => '分页id不能小于1',
            'status.required'   => '报名状态未填写',
            'status.integer'    => '报名状态格式错误',
            'size.integer'      => '分页size格式错误',
            'size.min'          => '分页size不能小于1',
            'sort'              => '排序类型格式错误',
            'is_pre.in'         => '向前翻页格式错误',
        ]);

        $applicantId = (int) $request->input('id', 0) > 0 ?
            (int) $request->input('id') : 0;
        try {
            list(
                $total, $preId, $nextId, $applicants
            ) = $applicantService->getApplicantsListById(
                $request->input('activity'),
                $applicantId,
                $request->input('status'),
                (int) $request->input('size', 15),
                $request->input('sort') ? true : false,
                $request->input('is_pre') ? true : false
            );

            return $this->json([
                'total'         => $total,
                'applicants'    => $applicants->map(function($item, $key) {
                    return $this->assembleActivityApplicantForClient($item);
                }),
                'pre_id'        => $preId,
                'next_id'       => $nextId,
            ]);
        } catch (\Exception $e) {
            return $this->jsonException($e);
        }
    }

    public function approveApplicant(Request $request, Guard $auth, ActivityApplicantService $activityApplicantService, UserService $userService)
    {
        $requestParams = $request->only('activity_id', 'applicant_id');
        $validator = Validator::make($requestParams, [
            'activity_id' => 'required|integer',
            'applicant_id' => 'required|integer',
        ], [
            'activity_id.required' => '活动未标识',
            'activity_id.integer' => '活动标识错误',
            'applicant_id.required' => '报名号未标识',
            'applicant_id.integer' => '报名号格式错误',
        ]);

        if ($validator->fails()) {
            return $this->jsonException($validator->errors()->first());
        }

        try {
            list($activityId, $applicantId) = [$requestParams['activity_id'], $requestParams['applicant_id']];
            $result = $activityApplicantService->approveApplicant($auth->user()->getAuthIdentifier(), $applicantId, $activityId);
            if (!$result) {
                throw new \Exception("失败");
            }

            $message = '';
            if ($result['status'] == ActivityApplicant::STATUS_SUCCESS) {
                if($result['channel'] == ActivityApplicantService::CHANNEL_APP){
                    $message = SmsTemplate::generalMessage(SmsTemplate::ACTIVITY_MEMBER_ENROLLMENT_REQUEST_APPROVED_AND_ENROLLED_SUCCESSFUL, $result['activity_title']);
                }elseif($result['channel'] == ActivityApplicantService::CHANNEL_WEB){
                    $message = SmsTemplate::generalMessage(SmsTemplate::ACTIVITY_MEMBER_ENROLLMENT_REQUEST_APPROVED_AND_ENROLLED_SUCCESSFUL_FORM_WEB, $result['activity_title']);
                }
            } elseif ($result['status'] == ActivityApplicant::STATUS_PAY) {
                if($result['channel'] == ActivityApplicantService::CHANNEL_APP){
                    $message = SmsTemplate::generalMessage(SmsTemplate::ACTIVITY_MEMBER_ENROLLED_SUCCESSFUL_AND_WAITINT_FOR_PAYMENT, $result['activity_title']);
                }elseif($result['channel'] == ActivityApplicantService::CHANNEL_WEB){
                    $message = SmsTemplate::generalMessage(SmsTemplate::ACTIVITY_MEMBER_ENROLLED_SUCCESSFUL_AND_WAITINT_FOR_PAYMENT_FORM_WEB, $result['activity_title']);
                }
            }
            if (!empty($message)) {
                //TODO: send message
                try {
                    $user = $userService->findUserById($result['user_id']);
                    if (!empty($user)) {
                        $mobile = $user->getMobile();
                        if ($result['mobile'] == $mobile) {
                            $this->sendToUsers([$mobile], ['content' => $message], ['sms' => true]);
                        } elseif (!empty($result['mobile'])) {
                            $this->sendToUsers([$mobile, $result['mobile']], ['content' => $message], ['sms' => true]);
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("sms send to uid[{$result['user_id']}] failed!");
                }
            }

            return $this->json('成功');
        } catch (\Exception $e) {
            return $this->jsonException($e);
        }
    }

    public function batchApproveApplicant(
        Request $request,
        Guard $auth,
        ActivityApplicantService $activityApplicantService
    ) {
        $this->validate($request, [
            'activity'      => 'required|integer',
            'applicants'    => 'required|array',
        ], [
            'activity.required'      => '活动id未填写',
            'activity.integer'       => '活动id格式错误',
            'applicants.required'    => '报名id未填写',
            'applicants.array'       => '报名id格式错误',
        ]);

        try {
            $activityApplicantService->approveApplicants(
                $auth->user()->getAuthIdentifier(),
                $request->input('activity'),
                $request->input('applicants')
            );
            return $this->json();

        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    public function refuseApplicant(Request $request, Guard $auth, ActivityApplicantService $activityApplicantService, UserService $userService)
    {
        $requestParams = $request->only('activity_id', 'applicant_id');
        $validator = Validator::make($requestParams, [
            'activity_id' => 'required|integer',
            'applicant_id' => 'required|integer',
        ], [
            'activity_id.required' => '活动未标识',
            'activity_id.integer' => '活动标识错误',
            'applicant_id.required' => '报名号未标识',
            'applicant_id.integer' => '报名号格式错误',
        ]);

        if ($validator->fails()) {
            return $this->jsonException($validator->errors()->first());
        }

        try {
            list($activityId, $applicantId) = [$requestParams['activity_id'], $requestParams['applicant_id']];
            $result = $activityApplicantService->refuseApplicant($auth->user()->getAuthIdentifier(), $applicantId, $activityId);
            if (!$result) {
                throw new \Exception("失败");
            }
            $message = SmsTemplate::generalMessage(SmsTemplate::ACTIVITY_MEMBER_ENROLLEDMENT_REQUEST_REJECTED, $result['activity_title']);
            //TODO: send message
            try {
                $user = $userService->findUserById($auth->user()->getAuthIdentifier());
                if (!empty($user)) {
                    $mobile = $user->getMobile();
                    if ($result['mobile'] == $mobile) {
                        $this->sendToUsers([$mobile], ['content' => $message], ['sms' => true]);
                    } elseif (!empty($result['mobile'])) {
                        $this->sendToUsers([$result['mobile']], ['content' => $message], ['sms' => true]);
                    }
                }
            } catch (\Exception $e) {
            }
            return $this->json('成功');
        } catch (\Exception $e) {
            return $this->jsonException($e);
        }
    }

    /**
     * Refuse a number of activity applicants once a time
     */
    public function batchRefuseApplicant(
        Request $request,
        Guard $auth,
        ActivityApplicantService $activityApplicantService
    ) {
        $this->validate($request, [
            'activity'      => 'required|integer',
            'applicants'    => 'required|array',
        ], [
            'activity.required'      => '活动id未填写',
            'activity.integer'       => '活动id格式错误',
            'applicants.required'    => '报名id未填写',
            'applicants.array'       => '报名id格式错误',
        ]);

        try {
            $activityApplicantService->refuseApplicants(
                $auth->user()->getAuthIdentifier(),
                $request->input('activity'),
                $request->input('applicants')
            );
            return $this->json();

        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    public function enforcePayment(Request $request, Guard $auth, ActivityApplicantService $activityApplicantService)
    {
        $requestParams = $request->only('activity_id', 'applicant_ids');
        $validator = Validator::make($requestParams, [
            'activity_id' => 'required|integer',
            'applicant_ids' => 'required',
        ], [
            'activity_id.required' => '活动未标识',
            'activity_id.integer' => '活动标识错误',
            'applicant_ids.required' => '报名号未标识',
        ]);

        if ($validator->fails()) {
            return $this->jsonException($validator->errors()->first());
        }
        try {
            throw new \Exception("已弃用的接口");


            $applicantIds = $requestParams['applicant_ids'];
            if (!is_array($applicantIds)) {
                throw new \Exception("报名号格式错误");
            }

            if (!$activityApplicantService->setPaymentExpireTime($auth->user()->getAuthIdentifier(), $applicantIds, $requestParams['activity_id'])) {
                throw new \Exception("失败");
            }
            return $this->json('成功');
        } catch (\Exception $e) {
            return $this->jsonException($e);
        }
    }

    /**
     * Send mobile verification to user for activity web applicant
     */
    public function sendVerifyCodeForWebApplicant(
        Request $request,
        VerificationService $verificationService
    ) {
        $this->validate($request, [
            'mobile' => 'required|mobile',
        ], [
            'mobile.required'   => '手机号未填写',
            'mobile.mobile'     => '手机号格式错误',
        ]);

        try {
            list($code, $sendInterval) = $verificationService
                    ->sendForActivityWebApplicant($request->input('mobile'));
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }

        return $this->json([
            'send_interval' => $sendInterval,
            'message'       => '发送成功',
        ]);
    }

    public function applicantFromWeb(
        Request $request,
        ActivityApplicantService $activityApplicantService,
        VerificationService $verificationService
    ) {
        $this->validate($request, [
            'activity_id' => 'required|integer',
            'code' => 'required|string|size:4',
            'attrs' => 'required|string',
        ], [
            'activity_id.required' => '活动未标识',
            'activity_id.integer' => '活动标识错误',
            'code.required' => '验证码未填写',
            'code.size' => '验证码错误',
            'attrs.required' => '报名信息未提交',
            'attrs.string' => '报名信息错误',
        ]);

        try {
            $applicant = $activityApplicantService->doApplicantFromWeb(
                $request->input('activity_id'),
                $request->input('attrs'),
                $request->input('code')
            );

            return $this->json([
                'info'  => $this->assembleApplicantFromWebResponse($applicant),
            ]);

        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    public function remark(
        Request $request,
        Guard $auth,
        ActivityApplicantService $activityApplicantService
    ) {
        $this->validate($request, [
            'activity'  => 'required|integer',
            'applicant' => 'required|integer',
            'content'   => 'string|max:10',
        ], [
            'activity.required'     => '活动id未填写',
            'activity.integer'      => '活动id格式错误',
            'applicant.required'    => '报名id未填写',
            'applicant.integer'     => '报名id格式错误',
            'content.string'        => '备注格式错误',
            'content.max'           => '备注不超过10个字符',
        ]);

        try {
            $activityApplicantService->remark(
                $auth->user()->getAuthIdentifier(),
                $request->input('activity'),
                $request->input('applicant'),
                $request->input('content')
            );

            return $this->json('备注成功');
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }


    /**
     * Team add vip user who will be treated a activity member
     */
    public function addSingleVip(
        Request $request,
        ActivityApplicantService $activityApplicantService
    ) {
        $this->validate($request, [
            'activity'  => 'required|integer',
            'attrs'     => 'required|string',
        ], [
            'activity.required' => '活动id未填写',
            'activity.integer'  => '活动id格式错误',
            'attrs.required'    => '报名信息未提交',
            'attrs.string'      => '报名信息格式错误',
        ]);

        try {
            $applicant = $activityApplicantService->doApplicant(
                null,
                $request->input('activity'),
                $request->input('attrs'),
                ActivityApplicantEntity::CHANNEL_SINGLE_VIP
            );

            return $this->json('添加成功');
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    private function assembleActivityApplicantForClient($applicant)
    {
        $fee = in_array($applicant->getChannel(), [
            ActivityApplicantEntity::CHANNEL_APP,
            ActivityApplicantEntity::CHANNEL_WEB,
            ]) ? $applicant->getActivity()->getEnrollFee() : 0;
        return [
            'id'                => $applicant->getId(),
            'activity_id'       => $applicant->getActivity()->getId(),
            'name'              => $applicant->getName(),
            'mobile'            => $applicant->getMobile(),
            'attrs'             => $applicant->getAttrs(),
            'status'            => $applicant->getStatus(),
            'remark'            => $applicant->getRemark(),
            'applicant_time'    => $applicant->getApplicantTime()->format('Y-m-d H:i:s'),
            'enroll_channel'    => $applicant->getChannel(),
            'enroll_fee'        => $fee,
        ];
    }

    private function assembleApplicantFromWebResponse($applicant)
    {
        return [
            'id'            => $applicant->getId(),
            'activity_id'   => $applicant->getActivity()->getId(),
            'order_no'      => $applicant->getOrderNo(),
            'expire_at'     => $applicant->getExpireAt() ? $applicant->getExpireAt()->format('Y-m-d H:i:s') : null,
            'status'        => $applicant->getStatus(),
        ];
    }
}
