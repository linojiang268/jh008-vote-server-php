<?php
namespace Jihe\Http\Controllers\Backstage;

use Jihe\Exceptions\Activity\UserNotActivityMemberException;
use Jihe\Services\UserService;
use Validator;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Guard;
use Jihe\Http\Controllers\Controller;
use Jihe\Services\ActivityCheckInQRCodeService;
use Jihe\Services\ActivityService;
use Jihe\Services\ActivityCheckInService;
use Jihe\Services\ActivityMemberService;

class ActivityCheckInController extends Controller {

    public function createCheckInQRCodes(Request $request, Guard $auth,
            ActivityCheckInQRCodeService $activityCheckInQRCodeService) {
        $requestParams = $request->only('activity_id', 'qrcodes');
        // validate request
        $validator = Validator::make($requestParams, [
                    'activity_id' => 'required|integer',
                    'qrcodes' => 'required|integer',
                        ], [
                    'activity_id.required' => '活动未标识',
                    'activity_id.integer' => '活动标识错误',
                    'qrcodes.required' => '二维码数量未标识',
                    'qrcodes.integer' => '二维码数量格式错误',
        ]);

        if ($validator->fails()) {
            return $this->jsonException($validator->errors()->first());
        }

        try {
            list($activityId, $totalQRCodes) = [$requestParams['activity_id'], $requestParams['qrcodes']];
            $qrcodes = $activityCheckInQRCodeService->createManyQRCodes($auth->user()->getAuthIdentifier(), $activityId, $totalQRCodes);
            return $this->json(['qrcodes' => $qrcodes]);
        } catch (\Exception $e) {
            return $this->jsonException($e);
        }
    }

    public function getCheckInQRCodes(Request $request, ActivityCheckInQRCodeService $activityCheckInQRCodeService) {
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
            $qrcodes = $activityCheckInQRCodeService->getQRCodes($requestParams['activity_id']);
            return $this->json(['qrcodes' => $qrcodes]);
        } catch (\Exception $e) {
            return $this->jsonException($e);
        }
    }

    public function downloadCheckInQRCodes(Request $request, ActivityCheckInQRCodeService $activityCheckInQRCodeService) {
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
            $qrcodes = $activityCheckInQRCodeService->getQRCodes($requestParams['activity_id']);
            if (!empty($qrcodes)) {
                $filename = $qrcodes[0]['url']; // download only the first qrcode
                header("Cache-Control: max-age=0");
                header("Content-Description: File Transfer");
                header('Content-disposition: attachment; filename=' . basename($filename));
                header("Content-Transfer-Encoding: binary");
                @readfile($filename);
            } else {
                return $this->jsonException('没有签到二维码');
            }
        } catch (\Exception $e) {
            return $this->jsonException($e);
        }
    }

    /**
     * List all check in info in a activity, filter by step
     */
    public function listActivityCheckIn(
        Request $request,
        Guard $auth,
        ActivityService $activityService,
        ActivityCheckInService $activityCheckInService
    ) {
        $this->validate($request, [
            'activity_id'   => 'required|integer',
            'step'          => 'required|integer',
            'page'          => 'integer',
            'size'          => 'integer',
        ], [
            'activity_id.required'  => '活动id未填写',
            'activity_id.integer'   => '活动id格式错误',
            'step.required'         => '签到点编号未填写',
            'step.integer'          => '签到点编号格式错误',
            'page.integer'          => '分页page错误',
            'size.integer'          => '分页size错误',
        ]);

        list($page, $pageSize) = $this->sanePageAndSize(
            $request->input('page'), $request->input('size'));

        try {
            // check permission
            if ( ! $activityService->checkActivityOwner(
                $request->input('activity_id'),
                $auth->user()->getAuthIdentifier()
            )) {
                return $this->json(['total' => 0, 'check_ins' => []]);
            }
            list($total, $checkIns) = $activityCheckInService
                ->getActivityAllCheckInListByStep($request->input('activity_id'),
                                          $request->input('step'),
                                          $page, $pageSize);

            return $this->json(['total' => $total,
                                'check_ins' => array_map([$this, 'assembleActivityCheckInData'], $checkIns)]);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    /**
     * Get user checkin list for client manage
     *
     * type be used to indicate the type of checkin list, desc as below:
     *      0 - waiting list
     *      1 - done list
     */
    public function listForClientManage(
        Request $request,
        Guard $auth,
        ActivityCheckInService $activityCheckInService
    ) {
        $this->validate($request, [
            'activity'   => 'required|integer',
            'type'          => 'in:0,1',
            'page'          => 'integer',
            'size'          => 'integer',
        ], [
            'activity.required'  => '活动id未填写',
            'activity.integer'   => '活动id格式错误',
            'type'                  => '类型只能为0或1',
            'page.integer'          => '分页page错误',
            'size.integer'          => '分页size错误',
        ]);

        list($page, $size) = $this->sanePageAndSize(
            $request->input('page'), $request->input('size')
        );

        try {
            list(
                $total, $checkins
            ) = $activityCheckInService->getUserCheckInListForClientManage(
                $request->input('activity'),
                (int) $request->input('type', 0),
                $page, $size
            );

            return $this->json([
                'total'     => $total,
                'checkins'  => $checkins->map(function ($item, $key) {
                    return $this->assembleCheckInDataFromMember($item);
                }),
            ]);
        } catch (\Exception $ex) {
            return $this->json(['total' => 0, 'checkins' => []]);
        }
    }

    /**
     * Search user checkin info for client manage entance
     */
    public function searchInfo(
        Request $request,
        ActivityCheckInService $activityCheckInService
    ) {
        $this->validate($request, [
            'activity'      => 'required|integer',
            'search'        => 'string',
        ], [
            'activity.required' => '活动id未填写',
            'activity.integer'  => '活动id格式错误',
            'search.string'     => '查询内容格式错误',
        ]);

        $name = null;
        $mobile = null;
        if ($request->input('search')) {
            $validator = Validator::make($request->input(), [
                'search'    => 'mobile',
            ]);
            if ($validator->fails()) {
                $name = $request->input('search');
            } else {
                $mobile = $request->input('search');
            }
        }

        $checkins = $activityCheckInService->searchCheckinInfo(
            $request->input('activity'), $mobile, $name
        );

        return $this->json([
            'checkins'  => $checkins->map(function ($item, $key) {
                return $this->assembleCheckInDataFromMember($item);
            })->toArray(),
        ]);
    }

    public function manageCheckIn(Request $request,
                                  Guard $auth,
                                  ActivityCheckInService $activityCheckInService,
                                  ActivityMemberService $activityMemberService)
    {
        $requestParams = $request->only('activity_id', 'user', 'step');
        $validator = Validator::make($requestParams, [
            'activity_id' => 'required|integer',
            'step'        => 'required|integer',
            'user'        => 'required|integer',
        ], [
            'activity_id.required' => '活动未标识',
            'activity_id.integer'  => '活动标识错误',
            'step.required'        => '签到步骤未标识',
            'step.integer'         => '签到步骤格式错误',
            'user.required'        => '用户未标识',
            'user.integer'         => '用户格式错误',
        ]);
        if ($validator->fails()) {
            return $this->jsonException($validator->errors()->first());
        }
        try {
            list($userId, $activityId, $step, $processId) = [
                $requestParams['user'],
                $requestParams['activity_id'],
                $requestParams['step'],
                $auth->user()->getAuthIdentifier(),
            ];
            if(!$activityMemberService->isActivityMember($userId, $activityId)){
                return $this->jsonException('非法操作：无操作权限');
            }
            $checkInResult = $activityCheckInService->checkIn($userId, $activityId, $step, $processId);

            return $this->json(['message'    => $checkInResult['message'],
                                'step'       => $step,
                                'check_list' => $checkInResult['check_list'],
            ]);
        } catch (\Exception $e) {
            return $this->jsonException($e);
        }
    }

    public function qrcodeCheckin(Request $request,
                                  Guard $auth,
                                  ActivityCheckInService $activityCheckInService,
                                  ActivityMemberService $activityMemberService,
                                  UserService $userService)
    {
        $requestParams = $request->only('activity', 'identity', 'step');
        $validator = Validator::make($requestParams, [
            'activity' => 'required|integer',
            'step'     => 'required|integer',
            'identity' => 'required|string',
        ], [
            'activity.required' => '活动未标识',
            'activity.integer'  => '活动标识错误',
            'step.required'     => '签到步骤未标识',
            'step.integer'      => '签到步骤格式错误',
            'identity.required' => '用户未标识',
            'identity.string'   => '用户身份标识格式错误',
        ]);
        if ($validator->fails()) {
            return $this->jsonException($validator->errors()->first());
        }
        try {
            $user = $userService->getUserByIdentity($request->input('identity'));

            list($activity, $step, $processId) = [
                $requestParams['activity'],
                $requestParams['step'],
                $auth->user()->getAuthIdentifier(),
            ];

            if(!$activityMemberService->isActivityMember($user->getId(), $activity)){
                throw new UserNotActivityMemberException('未报名');
            }
            $checkInResult = $activityCheckInService->checkIn($user->getId(), $activity, $step, $processId);

            return $this->json(['message'    => $checkInResult['message'],
                                'step'       => $step,
                                'check_list' => $checkInResult['check_list'],
                               ]);
        } catch (\Exception $e) {
            return $this->jsonException($e);
        }
    }

    public function manageRemoveCheckIn(Request $request,
                                        Guard $auth,
                                        ActivityCheckInService $activityCheckInService,
                                        ActivityMemberService $activityMemberService)
    {
        $requestParams = $request->only('activity_id', 'user', 'step');
        $validator = Validator::make($requestParams, [
            'activity_id' => 'required|integer',
            'step'        => 'required|integer',
            'user'        => 'required|integer',
        ], [
            'activity_id.required' => '活动未标识',
            'activity_id.integer'  => '活动标识错误',
            'step.required'        => '签到步骤未标识',
            'step.integer'         => '签到步骤格式错误',
            'user.required'        => '用户未标识',
            'user.integer'         => '用户格式错误',
        ]);
        if ($validator->fails()) {
            return $this->jsonException($validator->errors()->first());
        }
        try {
            list($userId, $activityId, $step, $processId) = [
                $requestParams['user'],
                $requestParams['activity_id'],
                $requestParams['step'],
                $auth->user()->getAuthIdentifier(),
            ];
            if(!$activityMemberService->isActivityMember($userId, $activityId)){
                return $this->jsonException('非法操作：无操作权限');
            }
            $ret = $activityCheckInService->removeCheckIn($userId, $activityId, $step, $processId);

            return $this->json(['result' => $ret]);
        } catch (\Exception $e) {
            return $this->jsonException($e);
        }
    }

    private function assembleCheckInDataFromMember($checkin)
    {
        $check_by_user = $checkin->getCheckins()->count() &&
            $checkin->getCheckins()->first()->getProcessId() ? 0 : 1;
        return $checkin ? [
            'id'                => $checkin->getId(),
            'user_id'           => $checkin->getUser()->getId(),
            'mobile'            => $checkin->getMobile(),
            'name'              => $checkin->getName(),
            'status'            => $checkin->getCheckins()->count() ? 1 : 0, // 0 -- wating; 1 - done
            'check_by_user'     => $check_by_user,                          // 0 -- checkin by user self
                                                                            // 1 -- checkin by manager
        ] : null;
    }

    private function assembleActivityCheckInData($data)
    {
        return $data ? [
            'id'            => $data['id'],
            'user_id'       => $data['user_id'],
            'nick_name'     => $data['nick_name'],
            'mobile'        => $data['mobile'],
            'step'          => $data['step'],
            'created_at'    => $data['created_at']->format('Y-m-d H:i:s'),
        ] : null;
    }
}
