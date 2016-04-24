<?php
namespace Jihe\Http\Controllers\Backstage;

use Illuminate\Contracts\Auth\Guard;
use Jihe\Contracts\Repositories\UserRepository;
use Jihe\Entities\ActivityFile;
use Jihe\Entities\ActivityPlan;
use Jihe\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Jihe\Services\ActivityCheckInQRCodeService;
use Jihe\Services\ActivityCheckInService;
use Jihe\Services\ActivityMemberService;
use Jihe\Services\ActivityService;
use Jihe\Services\Excel\ExcelWriter;
use Jihe\Services\WechatService;
use Illuminate\Support\Facades\Auth;
use Jihe\Services\StorageService;
use Jihe\Entities\Activity;
use Jihe\Entities\ActivityAlbumImage;
use Jihe\Services\ActivityApplicantService;
use Jihe\Services\TeamMemberService;
use Jihe\Utils\StringUtil;
use Jihe\Services\TeamFinanceService;
use Jihe\Services\MessageService;
use Jihe\Utils\PaginationUtil;
use Jihe\Entities\Message;
use Symfony\Component\HttpFoundation\File\MimeType\ExtensionGuesser;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesser;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ActivityController extends Controller
{

    /**
     * 活动首页
     */
    public function index()
    {
        return view('backstage.activity.index', [
        ]);
    }

    public function publish(Request $request, ActivityService $activityService, $id = "")
    {
        $team = $request->input('team');
        $cityName = $team->getCity()->getName();

        try {
            if ($id = intval($id)) {
                $activity = $activityService->getActivityById($id);
                if ($activity == null) {
                    return view('backstage.wap.team')->withErrors('该活动不存在');
                }
                $activity = $this->getActivityDetail($activity);
            } else {
                $activity = null;
            }
        } catch (\Exception $ex) {
            $activity = null;
        }

        return view('backstage.activity.publish', [
            'key'        => 'publish',
            'activityId' => $id,
            'cityName'       => $cityName,
            'activity'   => $activity,
        ]);
    }

    public function actList()
    {
        return view('backstage.activity.actList', [
            'key' => 'activityList',
        ]);
    }

    public function newsPublish($news = "")
    {
        return view('backstage.activity.newsPublish', [
            'key'  => 'news',
            'news' => $news,
        ]);
    }

    public function newsList()
    {
        return view('backstage.activity.newsList', [
            'key' => 'news',
        ]);
    }

    public function newsDetail()
    {
        return view('backstage.activity.newsDetail', [
            'key' => 'news',
        ]);
    }

    /**
     * 活动管理首页
     */
    public function manage()
    {
        return view('backstage.activity.manage', [
        ]);
    }

    /**
     * 签到二维码页面
     *
     */
    public function managerQrcode(ActivityService $activityService,
                                  ActivityCheckInQRCodeService $activityCheckInQRCodeService,
                                  $id = "")
    {
        try {
            $activity = $activityService->getActivityById($id);
            if ($activity == null) {
                return view('backstage.activity.manageError', ['key' => 'manageCheck'])->withErrors('该活动不存在');
            }
            $activity = $this->getActivityDetail($activity);
            $qrCode = $activityCheckInQRCodeService->getQRCodes($id);
        } catch (\Exception $ex) {
            return view('backstage.activity.manageError', ['key' => 'manageCheck'])->withErrors('服务器异常，请稍后再试');
        }

        return view('backstage.activity.manageQrcode', [
            'key'            => 'manageQrcode',
            'activity_id'    => $id,
            'activity_title' => $activity['title'],
            'qr_code_id'     => empty($qrCode) ? 0 : $qrCode[0]['id'],
            'qr_code_url'    => empty($qrCode) ? null : $qrCode[0]['url'],
        ]);
    }

    public function manageShare(ActivityService $activityService, $id = "")
    {

        try {
            $activity = $activityService->getActivityById($id);
            if ($activity == null) {
                return view('backstage.activity.manageError', ['key' => 'manageCheck'])->withErrors('该活动不存在');
            }
            $activity = $this->getActivityDetail($activity);
        } catch (\Exception $ex) {
            return view('backstage.activity.manageError', ['key' => 'manageCheck'])->withErrors('服务器异常，请稍后再试');
        }

        return view('backstage.activity.manageShare', [
            'key'            => 'manageShare',
            'activity_id'    => $id,
            'activity_title' => $activity['title'],
        ]);
    }

    public function manageSign(ActivityService $activityService,
                               ActivityCheckInQRCodeService $activityCheckInQRCodeService,
                               $id = "")
    {
        try {
            $activity = $activityService->getActivityById($id);
            if ($activity == null) {
                return view('backstage.activity.manageError', ['key' => 'manageCheck'])->withErrors('该活动不存在');
            }
            $activity = $this->getActivityDetail($activity);
            $qrCode = $activityCheckInQRCodeService->getQRCodes($id);
        } catch (\Exception $ex) {
            return view('backstage.activity.manageError', ['key' => 'manageCheck'])->withErrors('服务器异常，请稍后再试');
        }
        return view('backstage.activity.manageSign', [
            'key'            => 'manageSign',
            'activity_id'    => $id,
            'activity_title' => $activity['title'],
            'qr_code_id'     => empty($qrCode) ? 0 : $qrCode[0]['id'],
            'qr_code_url'    => empty($qrCode) ? null : $qrCode[0]['url'],
        ]);
    }

    public function manageCheck(ActivityService $activityService, $id = "")
    {

        try {
            $activity = $activityService->getActivityById($id);
            if ($activity == null) {
                return view('backstage.activity.manageError', ['key' => 'manageCheck'])->withErrors('该活动不存在');
            }
            $activity = $this->getActivityDetail($activity);
        } catch (\Exception $ex) {
            return view('backstage.activity.manageError', ['key' => 'manageCheck'])->withErrors('服务器异常，请稍后再试');
        }

        return view('backstage.activity.manageCheck', [
            'key'            => 'manageCheck',
            'activity_id'    => $id,
            'activity_title' => $activity['title'],
        ]);
    }

    /**
     * 路线指引页面
     *
     */
    public function manageLine(ActivityService $activityService, $id = "")
    {

        try {
            $activity = $activityService->getActivityById($id);
            if ($activity == null) {
                return view('backstage.wap.team')->withErrors('该活动不存在');
            }
            $activity = $this->getActivityDetail($activity);
        } catch (\Exception $ex) {
            return view('backstage.wap.team')->withErrors('服务器异常，请稍后再试');
        }

        return view('backstage.activity.manageLine', [
            'key'            => 'manageCheck',
            'activity_id'    => $id,
            'activity_title' => $activity['title'],
        ]);
    }

    /**
     * 活动资料页面
     *
     */
    public function manageFiles(ActivityService $activityService, $id = "")
    {

        try {
            $activity = $activityService->getActivityById($id);
            if ($activity == null) {
                return view('backstage.wap.team')->withErrors('该活动不存在');
            }
            $activity = $this->getActivityDetail($activity);
        } catch (\Exception $ex) {
            return view('backstage.wap.team')->withErrors('服务器异常，请稍后再试');
        }

        return view('backstage.activity.manageFiles', [
            'key'            => 'manageCheck',
            'activity_id'    => $id,
            'activity_title' => $activity['title'],
        ]);
    }

    public function manageGroup(ActivityService $activityService, $id = "")
    {

        try {
            $activity = $activityService->getActivityById($id);
            if ($activity == null) {
                return view('backstage.wap.team')->withErrors('该活动不存在');
            }
            $activity = $this->getActivityDetail($activity);
        } catch (\Exception $ex) {
            return view('backstage.wap.team')->withErrors('服务器异常，请稍后再试');
        }

        return view('backstage.activity.manageGroup', [
            'key'            => 'manageGroup',
            'activity_id'    => $id,
            'activity_title' => $activity['title'],
        ]);
    }

    public function manageInform(ActivityService $activityService, $id = "")
    {

        try {
            $activity = $activityService->getActivityById($id);
            if ($activity == null) {
                return view('backstage.activity.manageError', ['key' => 'manageCheck'])->withErrors('该活动不存在');
            }
            $activity = $this->getActivityDetail($activity);
        } catch (\Exception $ex) {
            return view('backstage.activity.manageError', ['key' => 'manageCheck'])->withErrors('服务器异常，请稍后再试');
        }

        return view('backstage.activity.manageInform', [
            'key'            => 'manageInform',
            'activity_id'    => $id,
            'activity_title' => $activity['title'],
        ]);
    }

    public function managePhotoMaster(ActivityService $activityService, $id = "")
    {

        try {
            $activity = $activityService->getActivityById($id);
            if ($activity == null) {
                return view('backstage.activity.manageError', ['key' => 'manageCheck'])->withErrors('该活动不存在');
            }
            $activity = $this->getActivityDetail($activity);
        } catch (\Exception $ex) {
            return view('backstage.activity.manageError', ['key' => 'manageCheck'])->withErrors('服务器异常，请稍后再试');
        }

        return view('backstage.activity.managePhotoMaster', [
            'key'            => 'managePhotoMaster',
            'activity_id'    => $id,
            'activity_title' => $activity['title'],
        ]);
    }

    public function managePhotoUsers(ActivityService $activityService, $id = "")
    {

        try {
            $activity = $activityService->getActivityById($id);
            if ($activity == null) {
                return view('backstage.activity.manageError', ['key' => 'manageCheck'])->withErrors('该活动不存在');
            }
            $activity = $this->getActivityDetail($activity);
        } catch (\Exception $ex) {
            return view('backstage.activity.manageError', ['key' => 'manageCheck'])->withErrors('服务器异常，请稍后再试');
        }

        return view('backstage.activity.managePhotoUsers', [
            'key'            => 'managePhotoUsers',
            'activity_id'    => $id,
            'activity_title' => $activity['title'],
        ]);
    }

    public function managePhotoSingle($id = "")
    {
        return view('backstage.activity.managePhotoSingle', [
            'key' => 'managePhotoSingle',
        ]);
    }

    /**
     * render activity detail page
     */
    public function detail(Request $request, ActivityService $activityService, WechatService $wechatService)
    {
        // validate request
        $this->validate($request, [
            'activity_id' => 'required|integer',
        ], [
            'activity_id.required' => '活动未填写',
            'activity_id.integer'  => '活动格式错误',
        ]);

        try {
            $activity = $activityService->getActivityById($request->input('activity_id'));
            if ($activity) {
                $activity = $this->getActivityDetail($activity);
            }
        } catch (\Exception $ex) {
            $activity = null;
        }

        try {
            $url = url("wap/activity/detail?activity_id={$request->input('activity_id')}");
            $package = $wechatService->getJsSignPackage($url);
        } catch (\Exception $ex) {
            $package = null;
        }
        
        $dateString = '';
        $shareActDesc = '';
        if ($activity) {
            $dateString .= $this->getDateString($activity['begin_time']) . ' ';
            $dateString .= substr($activity['begin_time'], 11, 5) . '--';
            if (substr($activity['begin_time'], 0, 10) != substr($activity['end_time'], 0, 10)) {
                $dateString .= $this->getDateString($activity['end_time']) . ' ';
            }
            $dateString .= substr($activity['end_time'], 11, 5);

            $actText = str_replace('&nbsp', '', strip_tags($activity["detail"]));
            $shareActDesc = $dateString . '\n' . mb_substr($actText, 0, 50);

            return view('backstage.wap.activity', [
                'activity'      => $activity,
                'shareActDesc'  => $shareActDesc,
                'activityTimeZone' => $dateString,
                'sign_package'  => $package,
                'app_installed' => 1 == $request->input('isappinstalled'),
            ]);
        }

        return view('backstage.wap.activity', [
            'activity'      => ['id' => $request->input('activity_id') ],
            'sign_package'  => $package,
            'app_installed' => 1 == $request->input('isappinstalled'),
        ])->withErrors('活动不存在');
    }

    public function getDateString($date) 
    {
        $result = '';
        $result .= substr($date, 5, 2) . '月'; // month
        $result .= substr($date, 8, 2) . '日'; //day
        return $result;
    }

    /**
     * render wap activity user sign up state
     */
    public function wapActivityUserSignUpStatus(Request $request, ActivityService $activityService)
    {
        // validate request
        $this->validate($request, [
            'activity_id' => 'integer',
            'mobile'      => 'mobile',
            'status'      => 'required|integer',
            'key'         => 'string',
        ], [
            'activity_id.integer' => '活动格式错误',
            'mobile.mobile'       => '手机号格式错误',
            'status.required'     => '报名状态未传入',
            'status.integer'      => '报名状态格式不正确',
            'key'                 => '查询key格式不正确',
        ]);

        try {
            $activity = $activityService->getActivityById($request->input('activity_id'));
            $activity = $this->getActivityDetail($activity);
        } catch (\Exception $ex) {
            $activity = null;
        }

        return view('backstage.wap.activitySignUpState', [
            'activity' => $activity,
            'mobile'   => $request->input('mobile'),
            'status'   => $request->input('status'),
            'key'      => $request->input('key'),
        ]);

    }

    /**
     * render H5 activity pay page
     */
    public function wapActivityPay(
        Request $request,
        WechatService $wechatService,
        ActivityService $activityService,
        ActivityApplicantService $activityApplicantService
    )
    {

        $this->validate($request, [
            'order_no' => 'required|string|size:32',
            'openid'   => 'string|max:128',
        ], [
            'order_no.required' => '订单号未填写',
            'order_no.string'   => '订单号格式错误',
            'order_no.size'     => '订单号格式错误',
            'openid'            => '用户openid未填写',
            'openid.string'     => '用户openid格式错误',
            'openid.max'        => '用户openid格式错误',
        ]);
        $order_no = $request->input('order_no');
        $openid = $request->input('openid');

        try {
            // Get activity applicant info
            $applicant = $activityApplicantService
                ->getApplicantInfoForPayment($request->input('order_no'));
            $activityId = $applicant['activity_id'];
            $mobile = $applicant['mobile'];

            // build wx jssdk config if openid applied
            $wxJssdkConfig = null;
            if ($request->input('openid')) {
                $uri = "wap/activity/pay?" . http_build_query([
                        'activity_id' => $activityId,
                        'mobile'      => $mobile,
                        'order_no'    => $request->input('order_no'),
                        'openid'      => $request->input('openid'),
                    ]);
                $wxJssdkConfig = $wechatService->getJsSignPackage(url($uri));
            }

            $activity = $activityService->getActivityById($activityId);
            $activity = $this->getActivityDetail($activity);
        } catch (\Exception $ex) {
            $activity = null;
            $mobile   = null;
            $wxJssdkConfig = null;
        }

        return view('backstage.wap.activityPay', [
            'order_no'        => $order_no,
            'mobile'          => $mobile,
            'activity'        => $activity,
            'openid'          => $openid,
            'wx_jssdk_config' => $wxJssdkConfig,
        ]);
    }

    /**
     * render H5 activity sign page
     */
    public function wapActivityCheckin(Request $request, ActivityService $activityService)
    {
        // validate request
        $this->validate($request, [
            'activity_id' => 'required|integer',
        ], [
            'activity_id.required' => '活动未填写',
            'activity_id.integer'  => '活动格式错误',
        ]);

        try {
            $activity = $activityService->getActivityById($request->input('activity_id'));
            $activity = $this->getActivityDetail($activity);
        } catch (\Exception $ex) {
            $activity = null;
        }

        return view('backstage.wap.activitySign.captcha', [
            'activity' => $activity,
        ]);
    }

    /**
     * render H5 activity detail
     */
    public function wapActivityInfo(Request $request,
                                    ActivityService $activityService,
                                    ActivityMemberService $activityMemberService,
                                    ActivityCheckInService $activityCheckInService,
                                    UserRepository $users)
    {
        // validate request
        $this->validate($request, [
            'activity_id' => 'required|integer',
            'mobile'      => 'required|mobile',
        ], [
            'activity_id.required' => '活动未填写',
            'activity_id.integer'  => '活动格式错误',
            'mobile.required'      => '手机号未填写',
            'mobile.mobile'        => '手机号格式错误',
        ]);

        try {
            $userId = $users->findId($request->input('mobile'));
            if ($userId == null) {
                return $this->jsonException('您还未注册，请先注册');
            }
            $activityDb = $activityService->getActivityById($request->input('activity_id'));
            if ($activityDb == null) {
                return $this->jsonException('活动不存在');
            }
            $checkInList = $activityCheckInService->getCheckInList($userId, $activityDb->getId());
            if (empty($checkInList)) {
                return view('backstage.wap.activitySign.signFailed')->withErrors('');
            }
            $activityCheckInService->firstStepQuickCheckIn($request->input('mobile'), $activityDb->getId());
            list($count, $activityPlans) = $activityService->findActivityPlanByActivityId($activityDb->getId());
            $activity = $this->getActivityDetail($activityDb);
            $activity['activity_plans'] = $activityPlans;
            $activity['activity_members_count'] = $activityMemberService->totalMemberOf($activityDb->getId());
            $activity['activity_album_count'] = $activityService->countApprovedAlbumImagesOfActivity($activityDb);
            $activity['activity_file_count'] = $activityService->countFilesOfActivity($activityDb);

        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }

        $plans = [];
        if (is_array($activity['activity_plans'])) {
            foreach ($activity['activity_plans'] as $index => $plan) {
                $planArray = [];
                list($startDate, $startHour) = explode(' ', $plan->getBeginTime());
                list($endDate, $endHour) = explode(' ', $plan->getEndTime());
                if (empty($plans[$startDate])) {
                    $plans[$startDate] = [];
                }
                $plans[$startDate][] = ['start' => $startHour, 'end' => $endHour, 'content' => $plan->getPlanText()];
            }
        }

        $activity['activity_plans'] = $plans;

        return view('backstage.wap.activitySign.activityInfo', [
            'activity' => $activity,
        ]);
    }

    /**
     * render H5 sign failed
     */
    public function wapSignFailed()
    {
        return view('backstage.wap.activitySign.signFailed');
    }

    private function processActivityParams(Request $request)
    {
        // validate request
        $this->validate($request, [
            'id'                => 'integer',
            'title'             => 'string',
            'begin_time'        => 'date_format:Y-m-d H:i:s',
            'end_time'          => 'date_format:Y-m-d H:i:s',
            'contact'           => 'string',
            'telephone'         => 'phone',
            'cover_url'         => 'string',
            'images_url'        => 'string',
            'address'           => 'string',
            'brief_address'     => 'string',
            'location'          => 'string',
            'roadmap'           => 'string',
            'detail'            => 'string',
            'enroll_begin_time' => 'date_format:Y-m-d H:i:s',
            'enroll_end_time'   => 'date_format:Y-m-d H:i:s',
            'enroll_type'       => 'integer',
            'enroll_limit'      => 'integer',
            'enroll_fee_type'   => 'integer',
            'enroll_fee'        => 'numeric',
            'enroll_attrs'      => 'string',
            'auditing'          => 'integer',
            'organizers'        => 'string',
            'publish'           => 'integer',
        ], [
            'id.integer'              => '活动序号格式错误',
            'title.string'            => '活动名称格式错误',
            'update_step.required'    => '活动步骤未填写',
            'update_step.integer'     => '活动步骤格式错误',
            'begin_time.date'         => '活动报名开始时间格式错误',
            'end_time.date'           => '活动报名结束时间格式错误',
            'contact.string'          => '活动联系人格式错误',
            'telephone.phone'         => '活动联系电话格式错误',
            'cover_url.string'        => '活动封面格式错误',
            'images_url.string'       => '轮播图格式错误',
            'address.string'          => '活动地址格式错误',
            'brief_address.string'    => '简短活动地址格式错误',
            'location.string'         => '活动坐标格式错误',
            'roadmap.string'          => '活动线路格式错误',
            'detail.string'           => '活动内容格式错误',
            'enroll_begin_time.date'  => '活动报名开始时间格式错误',
            'enroll_end_time.date'    => '活动报名结束时间格式错误',
            'enroll_type.integer'     => '报名限制格式错误',
            'enroll_limit.integer'    => '报名人数限制格式错误',
            'enroll_fee_type.integer' => '收费限制格式错误',
            'enroll_fee.numeric'      => '收费金额格式错误',
            'enroll_attrs.string'     => '报名资料格式错误',
            'auditing.integer'        => '报名是否审核格式错误',
            'organizers.string'       => '主办方格式错误',
            'publish.integer'         => '发布信息格式错误',
        ]);

        $params = $request->only(
            'team',
            'id', 'title',
            'begin_time', 'end_time',
            'contact', 'telephone',
            'cover_url', 'images_url',
            'address', 'brief_address',
            'location', 'roadmap',
            'detail',
            'enroll_begin_time', 'enroll_end_time',
            'enroll_type', 'enroll_limit',
            'enroll_fee_type', 'enroll_fee',
            'enroll_attrs',
            'auditing',
            'update_step',
            'organizers',
            'publish'
        );

        return $params;
    }

    /**
     * create activity
     */
    public function create(Request $request,
                           ActivityService $activityService,
                           TeamFinanceService $teamFinanceService,
                           ActivityCheckInQRCodeService $activityCheckInQRCodeService)
    {
        $params = $this->processActivityParams($request);
        try {
            unset($params['id']);
            $params['city_id'] = $params['team']->getCity()->getId();;
            $params['team_id'] = $params['team']->getId();
            $creatorId = $params['team']->getCreator()->getId();
            unset($params['team']);
            $params['update_step'] = 5;
            $publish = intval(array_get($params, 'publish'));
            $params['enroll_fee'] = $params['enroll_fee'] * 100;
            if (!array_get($params, 'title')) {
                return $this->jsonException('活动名称未填写');
            }
            $params = array_filter($params);
            $params = $this->checkUpdateParams($params);
            $id = $activityService->createActivity($params);
            if ($id <= 0) {
                return $this->jsonException('活动创建失败!');
            }
            if ($publish) {
                $result = $activityService->publishing($id, Auth::user()->id);
                if ($result) {
                    $activityCheckInQRCodeService->createManyQRCodes($creatorId, $id, 1);
                    $activity = $activityService->getActivityById($id, Auth::user()->id);
                    $teamFinanceService->createIncomeAfterActivityBePublished($activity);
                }
            }

            return $this->json(['id' => $id]);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    /**
     * Update activity
     */
    public function update(Request $request,
                           ActivityService $activityService,
                           TeamFinanceService $teamFinanceService,
                           ActivityCheckInQRCodeService $activityCheckInQRCodeService)
    {
        $params = $this->processActivityParams($request);
        try {
            if (!$activityService->checkActivityExists($params['id'])) {
                return $this->jsonException('该活动不存在');
            }
            $params['enroll_fee'] = $params['enroll_fee'] * 100;
            $activityDB = $activityService->getActivityById($params['id'], Auth::user()->id);
            if ($activityDB->getStatus() == Activity::STATUS_PUBLISHED) {
                $this->checkPublishActivityUpdateParams($params, $activityDB);
            }
            $params['city_id'] = $params['team']->getCity()->getId();;
            $params['team_id'] = $params['team']->getId();
            $creatorId = $params['team']->getCreator()->getId();
            unset($params['team']);
            $publish = intval(array_get($params, 'publish'));
            unset($params['publish']);
            $params = array_filter($params);
            $params = $this->checkUpdateParams($params);
            if (array_key_exists('organizers', $params)) {
                $ret = $activityService->updateActivityOrganizers($params['id'],
                    Auth::user()->id,
                    ['organizers' => $params['organizers']]);
            } else {
                $ret = $activityService->updateActivity($params['id'], Auth::user()->id, $params);
            }
            if (!$ret) {
                return $this->jsonException('活动信息更新失败!');
            }
            if ($publish) {
                $result = $activityService->publishing($params['id'], Auth::user()->id);
                if ($result) {
                    $qrCode = $activityCheckInQRCodeService->getQRCodes($params['id']);
                    if ($qrCode == null) {
                        $activityCheckInQRCodeService->createManyQRCodes($creatorId, $params['id'], 1);
                    }
                    $activity = $activityService->getActivityById($params['id'], Auth::user()->id);
                    $teamFinanceService->createIncomeAfterActivityBePublished($activity);
                }
            }

            return $this->json(['result' => $ret]);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    private function checkPublishActivityUpdateParams($activity, Activity $activityDB)
    {
        if ($activity) {
            if (isset($activity['enroll_fee'])) {
                if ($activity['enroll_fee'] != $activityDB->getEnrollFee()) {
                    throw new \Exception('已发布活动报名费不可修改');
                }
            }
            if (isset($activity['enroll_fee_type'])) {
                if ($activity['enroll_fee_type'] != $activityDB->getEnrollFeeType()) {
                    throw new \Exception('已发布活动报名收费方式不可修改');
                }
            }
            if (isset($activity['enroll_type'])) {
                if ($activity['enroll_type'] != $activityDB->getEnrollType()) {
                    throw new \Exception('已发布活动报名是否审核不可修改');
                }
            }
            if (isset($activity['enroll_limit']) && isset($activity['auditing'])) {
                if ($activity['enroll_limit'] < $activityDB->getEnrollLimit() && $activity['auditing'] == Activity::NOT_AUDITING) {
                    throw new \Exception('已发布活动不审核报名人数');
                }
            }
            if (isset($params['end_time']) && isset($params['begin_time'])) {
                if ($params['end_time'] != $activityDB->getEndTime() || $params['begin_time'] != $activityDB->getBeginTime()) {
                    throw new \Exception('已发布活动开始结束时间不可修改');
                }
            }
        }
    }

    /**
     * publishing activity
     */
    public function doPublish(Request $request,
                              ActivityService $activityService,
                              TeamFinanceService $teamFinanceService)
    {
        // validate request
        $this->validate($request, [
            'activity' => 'required|integer',
        ], [
            'activity.required' => '活动未填写',
            'activity.integer'  => '活动格式错误',
        ]);
        try {
            $ret = $activityService->publishing($request->input('activity'), Auth::user()->id);
            if ($ret) {
                $activity = $activityService->getActivityById($request->input('activity'), Auth::user()->id);
                $teamFinanceService->createIncomeAfterActivityBePublished($activity);
            }

            return $this->json(['result' => $ret]);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    private function checkUpdateParams($params)
    {
        if ($params) {
            if (isset($params['location'])) {
                $params['location'] = StringUtil::safeJsonDecode($params['location']);
                if ($params['location'] === false) {
                    return $this->jsonException('活动地点坐标错误!');
                }
            }
            if (isset($params['roadmap'])) {
                $params['roadmap'] = StringUtil::safeJsonDecode($params['roadmap'], true);
                if ($params['roadmap'] === false) {
                    throw new \Exception('活动地点坐标错误');
                }
            }
            if (isset($params['images_url'])) {
                if (StringUtil::safeJsonDecode($params['images_url']) === false) {
                    throw new \Exception('轮播图错误');
                }
            }
            if (isset($params['enroll_attrs'])) {
                if (StringUtil::safeJsonDecode($params['enroll_attrs']) === false) {
                    throw new \Exception('报名申请资料错误');
                }
            }
            if (isset($params['organizers'])) {
                if (StringUtil::safeJsonDecode($params['organizers']) === false) {
                    throw new \Exception('主办方错误');
                }
            }
            if (isset($params['enroll_end_time']) && isset($params['enroll_begin_time'])) {
                if ($params['enroll_end_time'] <= $params['enroll_begin_time']) {
                    throw new \Exception('报名时间错误');
                }
            }
            if (isset($params['end_time']) && isset($params['begin_time'])) {
                if ($params['end_time'] <= $params['begin_time']) {
                    throw new \Exception('活动时间错误');
                }
            }
            if (isset($params['begin_time']) && isset($params['enroll_end_time'])) {
                if ($params['begin_time'] <= $params['enroll_end_time']) {
                    throw new \Exception('活动时间与报名时间冲突');
                }
            }
        }
        return $params;
    }

    /**
     * find published activities by team
     */
    public function getMangeActivitiesByTeam(Request $request,
                                             ActivityService $activityService,
                                             ActivityApplicantService $activityApplicantService,
                                             TeamMemberService $teamMemberService)
    {
        // validate request
        $this->validate($request, [
            'page' => 'integer',
            'size' => 'integer',
        ], [
            'page.integer' => '分页page错误',
            'size.integer' => '分页size错误',
        ]);

        list($page, $size) = $this->sanePageAndSize($request->input('page'), $request->input('size'));

        try {
            $params = $request->only('team', 'page', 'size');
            $params['team_id'] = $params['team']->getId();
            unset($params['team']);
            list($total, $activities) = $activityService->getMangeActivityListByTeam(
                $params['team_id'],
                Auth::user()->id,
                $page,
                $size);
            $activities = $this->assembleData($activities, $activityApplicantService, $teamMemberService);
            return $this->json([
                'total_num'  => $total,
                'activities' => $activities,
            ]);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    /**
     * find activities by team
     */
    public function getActivitiesByTeam(Request $request,
                                        ActivityService $activityService,
                                        ActivityApplicantService $activityApplicantService,
                                        TeamMemberService $teamMemberService)
    {
        // validate request
        $this->validate($request, [
            'page' => 'integer',
            'size' => 'integer',
        ], [
            'page.integer' => '分页page错误',
            'size.integer' => '分页size错误',
        ]);

        list($page, $size) = $this->sanePageAndSize($request->input('page'), $request->input('size'));

        try {
            $params = $request->only('team', 'page', 'size');
            $params['team_id'] = $params['team']->getId();
            unset($params['team']);
            list($total, $activities) = $activityService->getActivityListByTeam(
                $params['team_id'],
                Auth::user()->id,
                $page,
                $size);
            $activities = $this->assembleData($activities, $activityApplicantService, $teamMemberService);
            return $this->json([
                'total_num'  => $total,
                'activities' => $activities,
            ]);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    /**
     * search activities via its name in some team
     */
    public function getTeamActivitiesByName(Request $request,
                                            ActivityService $activityService,
                                            ActivityApplicantService $activityApplicantService,
                                            TeamMemberService $teamMemberService)
    {
        // validate request
        $this->validate($request, [
            'keyword' => 'required|string',
            'page'    => 'integer',
            'size'    => 'integer',
        ], [
            'keyword.required' => '城市未填写',
            'keyword.string'   => '城市格式错误',
            'page.integer'     => '分页page错误',
            'size.integer'     => '分页size错误',
        ]);
        list($page, $size) = $this->sanePageAndSize($request->input('page'), $request->input('size'));
        try {
            list($total, $activities) = $activityService->getActivitiesByTeamAndName(
                $request->input('keyword'),
                Auth::user()->id,
                $request->input('team')->getId(),
                $page,
                $size);
            $activities = $this->assembleData($activities, $activityApplicantService, $teamMemberService);
            return $this->json([
                'total_num'  => $total,
                'activities' => $activities,
            ]);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    /*
     * search team activity by activity time
     */
    public function searchTeamActivitiesByActivityTime(Request $request,
                                                       ActivityService $activityService,
                                                       ActivityApplicantService $activityApplicantService,
                                                       TeamMemberService $teamMemberService)
    {
        // validate request
        $this->validate($request, [
            'start' => 'required|date_format:Y-m-d H:i:s',
            'end'   => 'required|date_format:Y-m-d H:i:s',
            'page'  => 'integer',
            'size'  => 'integer',
        ], [
            'start.required' => '起始时间未填写',
            'start.datetime' => '起始时间格式错误',
            'end.required'   => '结束时间未填写',
            'end.datetime'   => '结束时间格式错误',
            'page.integer'   => '分页page错误',
            'size.integer'   => '分页size错误',
        ]);
        list($page, $size) = $this->sanePageAndSize($request->input('page'), $request->input('size'));

        try {
            list($total, $activities) = $activityService->searchTeamActivitiesByActivityTime(
                Auth::user()->id,
                $request->input('start'),
                $request->input('end'),
                $request->input('team')->getId(),
                $page,
                $size);
            $activities = $this->assembleData($activities, $activityApplicantService, $teamMemberService);
            return $this->json([
                'total_num'  => $total,
                'activities' => $activities,
            ]);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    /**
     * get activity detail by id
     */
    public function getActivityById(Request $request, ActivityService $activityService)
    {
        // validate request
        $this->validate($request, [
            'activity' => 'required|integer',
        ], [
            'activity.required' => '活动未填写',
            'activity.integer'  => '活动格式错误',
        ]);

        try {
            $param = $request->only('activity');
            $activity = $activityService->getActivityById($param['activity'], Auth::user()->id);
            return $this->json([
                'activity' => $this->getActivityDetail($activity),
            ]);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    /**
     * delete activity by id
     */
    public function deleteActivityById(Request $request, ActivityService $activityService)
    {
        // validate reques
        $this->validate($request, [
            'activity' => 'required|integer',
        ], [
            'activity.required' => '活动序号未填写',
            'activity.integer'  => '活动序号格式错误',
        ]);

        try {
            $param = $request->only('activity');
            if (!$activityService->checkActivityExists($param['activity'])) {
                return $this->jsonException('该活动不存在!');
            }

            $ret = $activityService->activityDelete($param['activity'], Auth::user()->id);

            return $this->json(['result' => $ret]);

        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    public function getActivityMemberPhone(Request $request,
                                           ActivityService $activityService,
                                           ActivityMemberService $activityMemberService)
    {
        // validate reques
        $this->validate($request, [
            'activity' => 'required|integer',
        ], [
            'activity.required' => '活动序号未填写',
            'activity.integer'  => '活动序号格式错误',
        ]);

        try {
            $param = $request->only('activity');
            if (!$activityService->checkActivityExists($param['activity'])) {
                return $this->jsonException('该活动不存在!');
            }
            $members = $activityMemberService->allMemberOf($param['activity']);
            $mobiles = array_map(function ($member) {
                return $member['mobile'];
            }, $members);

            return $this->json(['mobiles' => $mobiles]);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    /*
     * assemble list data
     */
    private function assembleData($activities,
                                  ActivityApplicantService $activityApplicantService,
                                  TeamMemberService $teamMemberService)
    {
        $assembledActivities = array_map(function (Activity $activity) {
            return $this->getActivityDetailInList($activity);
        },
            $activities);
        $assembledActivities = $this->setRelatedData($assembledActivities, $activityApplicantService, $teamMemberService);

        return $assembledActivities;
    }

    /*
     * get activity detail to array
     */
    private function getActivityDetail(Activity $activity)
    {
        if (empty($activity)) {
            return [];
        }
        $tmp = $this->getActivityDetailInList($activity);
        $item = [
            'contact'           => $activity->getContact(),
            'telephone'         => $activity->getTelephone(),
            'detail'            => $activity->getDetail(),
            'auditing'          => $activity->getAuditing(),
            'images_url'        => $activity->getImagesUrl(),
            'enroll_begin_time' => $activity->getEnrollBeginTime(),
            'enroll_end_time'   => $activity->getEnrollEndTime(),
            'enroll_type'       => $activity->getEnrollType(),
            'enroll_limit'      => $activity->getEnrollLimit(),
            'roadmap'           => $activity->getRoadmap(),
            'update_step'       => $activity->getUpdateStep(),
            'status'            => $activity->getStatus(),
            'organizers'        => $activity->getOrganizers(),
        ];

        return array_merge($tmp, $item);
    }

    private function replaceAddress($address)
    {
        $str = '四川省';
        $pos = mb_strpos($address, $str);
        if ($pos === false) {
            return $str . $address;
        }
        return $address;
    }

    /**
     * list activity fields data to array
     */
    private function getActivityDetailInList(Activity $activity)
    {
        $tmp = $this->getActivitySimpleDetailInList($activity);


        $Item = [
            'team_id'         => $activity->getTeam()->getId(),
            'sub_status'      => $activity->getSubStatus(),
            'cover_url'       => $activity->getCoverUrl(),
            'qr_code_url'     => $activity->getQrCodeUrl(),
            'address'         => $this->replaceAddress($activity->getaddress()),
            'brief_address'   => $activity->getBriefAddress(),
            'enroll_fee_type' => $activity->getEnrollFeeType(),
            'enroll_fee'      => number_format($activity->getEnrollFee() / 100, 2),
            'auditing'        => $activity->getAuditing(),
            'essence'         => $activity->getEssence(),
            'city'            => [
                'id'   => $activity->getCity()->getId(),
                'name' => $activity->getCity()->getName(),
            ],
            'team'            => [
                'id'           => $activity->getTeam()->getId(),
                'name'         => $activity->getTeam()->getName(),
                'logo_url'     => $activity->getTeam()->getLogoUrl(),
                'introduction' => $activity->getTeam()->getIntroduction(),
            ],
            'location'        => $activity->getLocation(),
            'status'          => $activity->getStatus(),
            'enrolled_team'   => false,
            'enrolled_num'    => 0,
            'enroll_attrs'      => $activity->getEnrollAttrs(),
        ];
        return array_merge($tmp, $Item);
    }

    /**
     * list simple activity fields data to array
     */
    private function getActivitySimpleDetailInList(Activity $activity)
    {
        if (empty($activity)) {
            return [];
        }
        if (null == $activity->getTeam()) {
            throw new \Exception('非法社团');
        }
        if (null == $activity->getCity()) {
            throw new \Exception('非法地域信息');
        }
        return [
            'id'           => $activity->getId(),
            'title'        => $activity->getTitle(),
            'publish_time' => $activity->getPublishTime(),
            'begin_time'   => $activity->getBeginTime(),
            'end_time'     => $activity->getEndTime(),
        ];
    }

    /**
     * list albums of activity
     */
    public function listAlbumImages(Request $request, ActivityService $activityService)
    {
        $this->validate($request, [
            'activity'        => 'required|integer',
            'creator_type'    => 'required|integer',
            'page'            => 'integer|min:1',
            'size'            => 'integer|min:1',
            'last_id'         => 'integer',
            'last_created_at' => 'date',
        ], [
            'activity.required'     => '活动未指定',
            'activity.integer'      => '活动错误',
            'creator_type.required' => '创建者类型未指定',
            'creator_type.integer'  => '创建者类型错误',
            'page.integer'          => '分页page错误',
            'page.min'              => '分页page错误',
            'size.integer'          => '分页size错误',
            'size.min'              => '分页size错误',
            'last_id.integer'       => 'last_id错误',
            'last_created_at.date'  => 'last_created_at错误',
        ]);

        list($pageIndex, $pageSize) = $this->sanePageAndSize(
            $request->input('page'),
            $request->input('size'));

        try {
            // check activity
            $activity = $activityService->getPublishedActivityById($request->input('activity'));
            if ($activity == null || $activity->getTeam()->getId() != $request->input('team')->getId()) {
                return $this->jsonException('活动非法');
            }

            list($pages, $images) = (ActivityAlbumImage::SPONSOR == $request->input('creator_type')) ?
                $activityService->getAlbumImagesOfSponsor(
                    $activity,
                    $pageIndex,
                    $pageSize,
                    $request->input('last_created_at'),
                    $request->input('last_id'))
                :
                $activityService->getPendingAlbumImages(
                    $activity,
                    $pageIndex,
                    $pageSize,
                    $request->input('last_created_at'),
                    $request->input('last_id'));

            return $this->json(
                [
                    'pages'  => $pages,
                    'images' => array_map(function (ActivityAlbumImage $image) {
                        return [
                            'id'         => $image->getId(),
                            'image_url'  => $image->getImageUrl(),
                            'created_at' => $image->getCreatedAt(),
                        ];
                    }, $images),
                ]
            );
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    /**
     *  add album image of activity
     */
    public function addAlbumImage(Request $request, Guard $auth, ActivityService $activityService, StorageService $storageService)
    {
        $this->validate($request, [
            'activity' => 'required|integer',
            'image'    => 'required|mimes:jpeg,png,jpg',
        ], [
            'activity.required' => '活动未设置',
            'activity.integer'  => '活动格式错误',
            'image.required'    => 'image未设置',
            'image.mimes'       => 'image错误',
        ]);

        /* @var $image \Symfony\Component\HttpFoundation\File\UploadedFile */
        $image = $request->file('image');
        $imageId = null;

        try {
            // check activity
            $activity = $activityService->getPublishedActivityById($request->input('activity'));
            if ($activity == null || $activity->getTeam()->getId() != $request->input('team')->getId()) {
                return $this->jsonException('活动非法');
            }

            // store the image file
            $imageId = $storageService->storeAsImage($image);
            $imageEntity = $activityService->addAlbumImage([
                'activity'     => $activity,
                'creator_type' => ActivityAlbumImage::SPONSOR,
                'creator'      => $auth->user()->getAuthIdentifier(),
                'image_id'     => $imageId,
            ]);

            @unlink($image);

            return $this->json(
                [
                    'id'         => $imageEntity->getId(),
                    'image_url'  => $imageEntity->getImageUrl(),
                    'created_at' => $imageEntity->getCreatedAt(),
                ]
            );
        } catch (\Exception $ex) {
            @unlink($image);
            return $this->jsonException($ex);
        }
    }

    /**
     * approve album images of activity
     */
    public function approveAlbumImages(Request $request, ActivityService $activityService)
    {
        $this->validate($request, [
            'activity' => 'required|integer',
            'images'   => 'required|array',
        ], [
            'activity.required' => '活动未指定',
            'activity.integer'  => '活动错误',
            'images.required'   => '相册未指定',
            'images.array'      => '相册错误',
        ]);

        if (!$this->validateAlbumImages($request->input('images'))) {
            return $this->jsonException('相册格式错误');;
        }

        try {
            // check activity
            $activity = $activityService->getPublishedActivityById($request->input('activity'));
            if ($activity == null || $activity->getTeam()->getId() != $request->input('team')->getId()) {
                return $this->jsonException('活动非法');
            }

            $activityService->approveAlbumImages($activity, $request->input('images'));
            return $this->json('审批相册成功');
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    /**
     * remove user album images of activity
     */
    public function removeAlbumImages(Request $request, ActivityService $activityService)
    {
        $this->validate($request, [
            'activity' => 'required|integer',
            'images'   => 'required|array',
        ], [
            'activity.required' => '活动未指定',
            'activity.integer'  => '活动错误',
            'images.required'   => '相册未指定',
            'images.array'      => '相册错误',
        ]);

        if (!$this->validateAlbumImages($request->input('images'))) {
            return $this->jsonException('相册格式错误');;
        }

        try {
            // check activity
            $activity = $activityService->getPublishedActivityById($request->input('activity'));
            if ($activity == null || $activity->getTeam()->getId() != $request->input('team')->getId()) {
                return $this->jsonException('活动非法');
            }

            $activityService->removeAlbumImages($activity, $request->input('images'));
            return $this->json('删除相册成功');
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    /**
     *
     * @param array $images ids of album images
     */
    private function validateAlbumImages(array $images)
    {
        foreach ($images as $image) {
            if (filter_var($image, FILTER_VALIDATE_INT) === false) {
                return false;
            }
        }
        return true;
    }

    /**
     *  set related data
     *
     * @param                          $activities
     * @param ActivityApplicantService $activityApplicantService
     * @param TeamMemberService        $teamMemberService
     *
     * @return array
     */
    public function setRelatedData($activities,
                                   ActivityApplicantService $activityApplicantService)
    {
        if (empty($activities)) {
            return $activities;
        }
        $teams = [];
        $activityIds = [];
        foreach ($activities as $index => $activity) {
            if (isset($activity['team_id'])) {
                $teams[] = $activity['team_id'];
            }
            if (isset($activity['id'])) {
                $activityIds[] = $activity['id'];
            }
        }
        $activities = $this->setEnrolledTotal($activities, $activityIds, $activityApplicantService);

        return $activities;
    }

    /**
     * count added to activity user
     *
     * @param array $activities  get activities
     * @param array $activityIds activity id array
     *
     * @return array
     */

    private function setEnrolledTotal($activities, $activityIds, ActivityApplicantService $activityApplicantService)
    {
        $enrolledCounts = $activityApplicantService->getActivityApplicantsCount($activityIds);
        if ($enrolledCounts) {
            foreach ($activities as $index => $activity) {
                if (null == $activityId = array_get($activity, 'id')) {
                    continue;
                }
                if (isset($enrolledCounts[$activityId])) {
                    $activities[$index]['enrolled_num'] = $enrolledCounts[$activityId];
                }
            }
        }

        return $activities;
    }

    /**
     * send activity notice
     */
    public function sendNotice(Request $request, ActivityService $activityService,
                               MessageService $messageService)
    {
        $this->validate($request, [
            'activity' => 'required|integer',
            'to_all'   => 'required|boolean',
            'phones'   => 'array',
            'send_way' => 'required|min:3|max:4',
            'content'  => 'required|min:1|max:128',
        ], [
            'activity.required' => '活动序号未填写',
            'activity.integer'  => '活动序号格式错误',
            'to_all.required'   => '群发设置未指定',
            'to_all.boolean'    => '群发设置错误',
            'phones.array'      => '成员设置错误',
            'send_way.required' => '发送方式未指定',
            'send_way.min'      => '发送方式错误',
            'send_way.max'      => '发送方式错误',
            'content.required'  => '内容未指定',
            'content.min'       => '内容错误',
            'content.max'       => '内容错误',
        ]);

        try {
            $activity = $activityService->getActivityById($request->input('activity'), Auth::user()->id);
            if (!$activity) {
                return $this->jsonException('该活动不存在或无操作权限!');
            }

            if ($request->input('to_all') && 'sms' == $request->input('send_way') &&
                $messageService->restActivityNoticesToMass($activity) <= 0) {
                return $this->jsonException('您的群发次数已经用完了');
            }

            $activityService->pushActivityNotice($activity,
                $request->input('content'),
                $request->input('to_all'),
                $request->input('phones'),
                $request->input('send_way'));

            return $this->json('发送成功');
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    /**
     * send activity notice of sms for mass
     */
    public function sendNoticeOfSmsForMass(Request $request, ActivityService $activityService,
                                           MessageService $messageService)
    {
        $this->validate($request, [
            'activity' => 'required|integer',
            'content'  => 'required|min:1|max:128',
        ], [
            'activity.required' => '活动序号未填写',
            'activity.integer'  => '活动序号格式错误',
            'content.required'  => '内容未指定',
            'content.min'       => '内容错误',
            'content.max'       => '内容错误',
        ]);

        try {
            $activity = $activityService->getActivityById($request->input('activity'), Auth::user()->id);
            if (!$activity) {
                return $this->jsonException('该活动不存在或无操作权限!');
            }
            if($activity->getSendStatus() == Activity::MANAGE_SEND_ENROLL_NOT_BEGIN){
                return $this->jsonException('活动报名尚未开始，此功能不可使用');
            }
            if($activity->getSendStatus() == Activity::MANAGE_SEND_ACTIVITY_END){
                return $this->jsonException('活动已结束超过'.Activity::MANAGE_SEND_ACTIVITY_DELAY.'天，此功能不可使用');
            }
            if ($messageService->restActivityNoticesToMass($activity) <= 0) {
                return $this->jsonException('免费短信通知已经用完', 1);
            }
            $activityService->pushActivityNotice($activity,
                                                 $request->input('content'),
                                                 true,
                                                 null,
                                                 'sms');

            return $this->json('发送成功');
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    /**
     * send activity notice of sms for mass
     */
    public function restSendNoticesTimes(Request $request, ActivityService $activityService,
                                         MessageService $messageService)
    {
        $this->validate($request, [
            'activity' => 'required|integer',
        ], [
            'activity.required' => '活动序号未填写',
            'activity.integer'  => '活动序号格式错误',
        ]);

        try {
            $activity = $activityService->getActivityById($request->input('activity'), Auth::user()->id);
            if (!$activity) {
                return $this->jsonException('该活动不存在或无操作权限!');
            }
            if($activity->getSendStatus() == Activity::MANAGE_SEND_ENROLL_NOT_BEGIN){
                return $this->jsonException('活动报名尚未开始，此功能不可使用');
            }
            if($activity->getSendStatus() == Activity::MANAGE_SEND_ACTIVITY_END){
                return $this->jsonException('活动已结束超过'.Activity::MANAGE_SEND_ACTIVITY_DELAY.'天，此功能不可使用');
            }
            $times = $messageService->restActivityNoticesToMass($activity);
            if ($times <= 0) {
                return $this->jsonException('免费短信通知已经用完', 1);
            }

            return $this->json(['rest_times' => $times]);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    /**
     * list activity notices
     */
    public function listNotices(Request $request, ActivityService $activityService, MessageService $messageService)
    {
        $this->validate($request, [
            'activity' => 'required|integer',
            'page'     => 'integer',
            'size'     => 'integer',
        ], [
            'activity.required' => '活动未指定',
            'activity.integer'  => '活动错误',
            'page.integer'      => '分页page错误',
            'size.integer'      => '分页size错误',
        ]);

        try {
            $activity = $activityService->getPublishedActivityById($request->input('activity'));
            if (is_null($activity)) {
                throw new \Exception('活动不存在');
            }

            list($page, $size) = $this->sanePageAndSize(
                $request->input('page'),
                $request->input('size'));

            list($total, $messages) = $messageService->getActivityNotices(
                $activity, $page, $size);

            return $this->json([
                'pages'    => PaginationUtil::count2Pages($total, $size),
                'messages' => array_map(function (Message $message) {
                    return [
                        'id'            => $message->getId(),
                        'content'       => $message->getContent(),
                        'notified_type' => $message->getNotifiedType(),
                        'created_at'    => $message->getCreatedAt(),
                    ];
                }, $messages),
            ]);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    /**
     *  add file of activity
     */
    public function addFile(Request $request, Guard $auth, ActivityService $activityService, StorageService $storageService)
    {
        $this->validate($request, [
            'activity' => 'required|integer',
            'file'     => 'required|max:10485760', // 10M = 10485760
            'name'     => 'string|min:1|max:128',
            'memo'     => 'string|min:1|max:255',
        ], [
            'activity.required' => '活动未设置',
            'activity.integer'  => '活动格式错误',
            'file.required'     => 'file未设置',
            'name.string'       => '指定文件名错误',
            'name.min'          => '指定文件名错误',
            'name.max'          => '指定文件名错误',
            'memo.string'       => '指定文件备注错误',
            'memo.min'          => '指定文件备注错误',
            'memo.max'          => '指定文件备注错误',
        ]);

        /* @var $file \Symfony\Component\HttpFoundation\File\UploadedFile */
        $file = $request->file('file');
        $fileId = null;

        try {
            // check activity
            $activity = $activityService->getPublishedActivityById($request->input('activity'));
            if ($activity == null || $activity->getTeam()->getId() != $request->input('team')->getId()) {
                return $this->jsonException('活动非法');
            }

            // store the file
            $fileId = $storageService->storeAsFile($file);

            $fileAttributes = [];
            $fileAttributes['name'] = $file->getClientOriginalName();
            $fileAttributes['size'] = $file->getClientSize();
            $fileAttributes['extension'] = $file->getClientOriginalExtension();
            $fileAttributes['file_id'] = $fileId;

            if ($request->has('name')) {
                $fileAttributes['name'] = $request->input('name');
            }
            if ($request->has('memo')) {
                $fileAttributes['memo'] = $request->input('memo');
            }

            $fileEntity = $activityService->addFile($activity, $fileAttributes);

            @unlink($file);

            return $this->json(
                [
                    'id'         => $fileEntity->getId(),
                    'name'       => $fileEntity->getName(),
                    'memo'       => $fileEntity->getMemo(),
                    'size'       => $fileEntity->getSize(),
                    'extension'  => $fileEntity->getExtension(),
                    'url'        => $fileEntity->getUrl(),
                    'created_at' => $fileEntity->getCreatedAt(),
                ]
            );
        } catch (\Exception $ex) {
            @unlink($file);
            return $this->jsonException($ex);
        }
    }

    /**
     * list files of activity
     */
    public function listFiles(Request $request, ActivityService $activityService)
    {
        $this->validate($request, [
            'activity' => 'required|integer',
            'page'     => 'integer|min:1',
            'size'     => 'integer|min:1',
        ], [
            'activity.required' => '活动未指定',
            'activity.integer'  => '活动错误',
            'page.integer'      => '分页page错误',
            'page.min'          => '分页page错误',
            'size.integer'      => '分页size错误',
            'size.min'          => '分页size错误',
        ]);

        list($page, $size) = $this->sanePageAndSize(
            $request->input('page'),
            $request->input('size'));

        try {
            // check activity
            $activity = $activityService->getPublishedActivityById($request->input('activity'));
            if ($activity == null || $activity->getTeam()->getId() != $request->input('team')->getId()) {
                return $this->jsonException('活动非法');
            }

            list($total, $files) = $activityService->listFiles($activity, $page, $size);

            return $this->json(
                [
                    'pages' => PaginationUtil::count2Pages($total, $size),
                    'files' => array_map(function (ActivityFile $file) {
                        return [
                            'id'         => $file->getId(),
                            'name'       => $file->getName(),
                            'memo'       => $file->getMemo(),
                            'size'       => $file->getSize(),
                            'extension'  => $file->getExtension(),
                            'url'        => $file->getUrl(),
                            'created_at' => $file->getCreatedAt(),
                        ];
                    }, $files),
                ]
            );
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    /**
     * remove files of activity
     */
    public function removeFiles(Request $request, ActivityService $activityService)
    {
        $this->validate($request, [
            'activity' => 'required|integer',
            'files'    => 'required|array',
        ], [
            'activity.required' => '活动未指定',
            'activity.integer'  => '活动错误',
            'files.required'    => '文件未指定',
            'files.array'       => '文件错误',
        ]);

        if (!$this->validateFiles($request->input('files'))) {
            return $this->jsonException('文件格式错误');;
        }

        try {
            // check activity
            $activity = $activityService->getPublishedActivityById($request->input('activity'));
            if ($activity == null || $activity->getTeam()->getId() != $request->input('team')->getId()) {
                return $this->jsonException('活动非法');
            }

            $activityService->removeFiles($activity, $request->input('files'));
            return $this->json('删除文件成功');
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    /**
     *
     * @param array $files ids of files
     */
    private function validateFiles(array $files)
    {
        foreach ($files as $file) {
            if (filter_var($file, FILTER_VALIDATE_INT) === false) {
                return false;
            }
        }
        return true;
    }

    /*
     * get activity plans
     */
    public function getActivityPlans(Request $request, ActivityService $activityService)
    {
        $this->validate($request, [
            'activity' => 'required|integer',
        ], [
            'activity.required' => '活动未指定',
            'activity.integer'  => '活动错误',
        ]);
        try {
            // check activity
            $activity = $activityService->getPublishedActivityById($request->input('activity'));
            if ($activity == null) {
                return $this->jsonException('活动非法');
            }
            list($count, $activityPlans) = $activityService->findActivityPlanByActivityId($activity->getId());
            if ($activityPlans) {
                foreach ($activityPlans as $index => $activityPlan) {
                    $activityPlans[$index] = $this->getActivityPlanDetailInList($activityPlan);
                }
            }

            return $this->json(['total_num'        => $count,
                                'activities_plans' => $activityPlans]);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    /*
     * create activity plans
     */
    public function createActivityPlans(Request $request, ActivityService $activityService)
    {
        $this->validate($request, [
            'activity_plans' => 'required|string',
            'activity'       => 'required|integer',
        ], [
            'activity_plans.required' => '活动计划未填写',
            'activity_plans.string'   => '活动计划结构错误',
            'activity.required'       => '活动未指定',
            'activity.integer'        => '活动错误',
        ]);

        try {
            $activityPlans = StringUtil::safeJsonDecode($request->input('activity_plans'));
            if (!$activityPlans) {
                return $this->jsonException('活动计划结构非法');
            }
            // check activity
            $activity = $activityService->getPublishedActivityById($request->input('activity'));
            if ($activity == null) {
                return $this->jsonException('活动不存在');
            }
            // check param
            $activityPlans = $this->checkActivityPlansData($activityPlans, $activity->getId());
            $activityService->createActivityPlan($activityPlans,
                $activity->getId(),
                Auth::user()->id,
                $activity->getTeam()->getId());

            return $this->json(['result' => true]);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }

    }

    /**
     * @param array $activityPlans
     * @param int   $activityId
     *
     * @return bool
     * @throws \Exception
     */
    private function checkActivityPlansData($activityPlans, $activityId)
    {
        if ($activityPlans) {
            foreach ($activityPlans as $key => $activityPlan) {
                foreach ($activityPlan as $field => $value) {
                    if (in_array($field, ['begin_time', 'end_time', 'plan_text'])) {
                        if ($field == 'begin_time') {
                            if (!strtotime($activityPlan['begin_time'])) {
                                throw new \Exception('提交数据非法');
                            }
                        } elseif ($field == 'end_time') {
                            if (!strtotime($activityPlan['end_time'])) {
                                throw new \Exception('提交数据非法');
                            }
                        }
                    } else {
                        throw new \Exception('提交数据非法');
                    }
                    if (strtotime($activityPlan['begin_time']) >= strtotime($activityPlan['end_time'])) {
                        throw new \Exception('提交数据非法');
                    }
                    $activityPlans[$key]['activity_id'] = $activityId;
                }
            }
        }

        return $activityPlans;
    }

    /**
     * list activity plan fields data to array
     */
    private function getActivityPlanDetailInList(ActivityPlan $activityPlan)
    {
        return [
            'id'         => $activityPlan->getId(),
            'plan_text'  => $activityPlan->getPlanText(),
            'begin_time' => $activityPlan->getBeginTime(),
            'end_time'   => $activityPlan->getEndTime(),
        ];
    }

    /*
     * output import activity members
     */
    public function importActivityMembers(Request $request,
                                                  ActivityService $activityService)
    {
        // client not issuing an AJAX request, but requires JSON response
        // for simplicity, add AJAX request header ourselves
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        // validate the request
        $this->validate($request, [
            'activity'       => 'required|integer',
            'member_list' => 'required'
        ], [
            'member_list.required' => '未上传白名单文件',
            'activity.required'       => '活动未指定',
            'activity.integer'        => '活动错误',
        ]);
        // by default, Laravel (or underlying Symfony) cannot give the right mime type
        // of old version of Excel files, so we have to do it by ourselves
        $memberList = $request->file('member_list');
        if (!$this->excelUploaded($memberList)) {
            unlink(strval($memberList));
            return $this->jsonException('非法的活动报名文件');
        }
        $memberList = strval($memberList);  // get uploaded file's path
        try {
            // check activity
            $activity = $activityService->getActivityById($request->input('activity'), Auth::user()->id);
            $failed = $activityService->importActivityMembers($memberList, $activity);
            unlink($memberList);
            return $this->json(['failed' => $failed]);
        } catch (\Exception $ex) {
            unlink($memberList);
            return $this->jsonException($ex);
        }
    }

    /*
     * output import activity members template
     */
    public function importActivityMembersTemplate(Request $request,
                                                  ActivityService $activityService)
    {
        $this->validate($request, [
            'activity'       => 'required|integer',
        ], [
            'activity.required'       => '活动未指定',
            'activity.integer'        => '活动错误',
        ]);
        try {
            // check activity
            $activity = $activityService->getActivityById($request->input('activity'), Auth::user()->id);
        } catch (\Exception $ex) {
            return view('backstage.activity.manageError', ['key' => 'manageCheck'])->withErrors('非法操作');
        }
        ob_start();
        $headers = $activity->getEnrollAttrs();
        $writer = ExcelWriter::fromScratch();
        $writer->writeHeader($headers);
        $writer->save();
        return response()->make(ob_get_clean())
            ->header('Content-Type',        'application/vnd.ms-excel')
            ->header('Content-disposition', 'attachment; filename= members.xls');
    }

    private function excelUploaded(UploadedFile $file)
    {
        // symfony fails to given the correct mime-type to old versions of excel files
        // it simply gives 'application/vnd.ms-office', but what we want is
        // 'application/vnd.ms-excel' indeed
        $extensionGuesser = ExtensionGuesser::getInstance();
        $mimeGuesser = MimeTypeGuesser::getInstance();
        $mime = $mimeGuesser->guess(strval($file));
        $extension = $extensionGuesser->guess($mime);
        if ($extension != null) {
            return in_array($extension, ['xls', 'xlsx']);
        }
        if (false === stripos($mime, 'office')) { // guessed mime should have 'office' in it
            // typically, it is 'application/vnd.ms-office'
            return false;
        }
        // cannot guess extension, and now try to guess it from client info
        $clientMime = $file->getClientMimeType();
        if (false === stripos($clientMime, 'xls') &&   // 'application/wps-office.xls'
            false === stripos($clientMime, 'excel')) { // 'application/vnd.ms-excel'
            return false;
        }
        // client's extension should be either 'xls' or 'xlsx'
        return in_array($file->getClientOriginalExtension(), ['xls', 'xlsx']);
    }



}
