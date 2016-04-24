<?php
namespace Jihe\Http\Controllers\Backstage;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Jihe\Http\Controllers\Controller;
use Jihe\Services\Photo\PhotoService;
use Jihe\Services\TeamService;
use Jihe\Services\TeamMemberService;
use Jihe\Services\StorageService;
use Jihe\Services\WechatService;
use Jihe\Services\CityService;
use Jihe\Services\TeamGroupService;
use Jihe\Entities\TeamRequest;
use Jihe\Entities\TeamCertification;
use Jihe\Entities\Team;
use Jihe\Entities\Message;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Jihe\Dispatches\DispatchesMessage;
use Jihe\Services\MessageService;
use Jihe\Utils\PaginationUtil;
use Jihe\Services\ActivityService;

class TeamController extends Controller
{
    use DispatchesJobs, DispatchesMessage;
    
    /**
     *  accept user's team enrollment request
     */
    public function requestForEnrollment(Request $request, Guard $auth, TeamService $teamService,
                                         StorageService $storageService, PhotoService $photoService)
    {
        $this->validate($request, [
            'city'          => 'required|integer',
            'name'          => 'required|max:32',
            'email'         => 'required|email',
            'logo_id'       => 'max:255',
            'address'       => 'max:255',
            'contact_phone' => 'phone',
            'contact'       => 'max:16',
            'contact_hidden' => 'boolean',
            'introduction'  => 'max:128',
            'crop_start_x'  => 'integer',
            'crop_start_y'  => 'integer',
            'crop_end_x'    => 'integer',
            'crop_end_y'    => 'integer',
        ], [
            'city.required'        => '城市未填写',
            'city.integer'         => '城市格式错误',
            'name.required'        => '社团名称未填写',
            'name.max'             => '社团名称错误',
            'email.required'       => '邮箱未填写',
            'email.email'          => '邮箱格式错误',
            'logo_id.max'          => 'logo错误',
            'address.max'          => '地址错误',
            'contact_phone.phone'  => '联系方式格式错误',
            'contact.max'          => '联系人错误',
            'contact_hidden.boolean' => '联系人是否隐藏设置错误',
            'introduction.max'     => '简介错误',
            'crop_start_x.integer'  => '裁剪起点x坐标',
            'crop_start_y.integer'  => '裁剪起点y坐标',
            'crop_end_x.integer'    => '裁剪结束点x坐标',
            'crop_end_y.integer'    => '裁剪结束点y坐标',
        ]);

        $enrollmentRequest = $request->only('city', 'name', 'email', 'logo_id', 'address',
                                            'contact_phone', 'contact', 'introduction',
                                            'crop_start_x', 'crop_start_y', 'crop_end_x', 'crop_end_y');
        $enrollmentRequest['contact_hidden'] = $request->input('contact_hidden', 0) ? 1 : 0;
        $enrollmentRequest['initiator'] = $auth->user()->toEntity();

        try {
            if (empty($enrollmentRequest['logo_id'])) {
                $enrollmentRequest['logo_id'] = $teamService->getDefaultLogo();
            } else {
                $ext = (0 == strcasecmp('.png', substr($enrollmentRequest['logo_id'], -4))) ? 'png' : 'jpg';

                $dest = sys_get_temp_dir() . '/' . time() . '.' . $ext;
                $photoService->crop($enrollmentRequest['logo_id'], [
                    'format'  => 'png' == $ext ? 'png' : 'jpeg',
                    'x'       => $enrollmentRequest['crop_start_x'],
                    'y'       => $enrollmentRequest['crop_start_y'],
                    'width'   => $enrollmentRequest['crop_end_x'] - $enrollmentRequest['crop_start_x'],
                    'height'  => $enrollmentRequest['crop_end_y'] - $enrollmentRequest['crop_start_y'],
                    'save_as' => $dest,
                ]);

                $enrollmentRequest['logo_id'] = $storageService->storeAsImage($dest);
            }

            $teamService->requestForEnrollment($enrollmentRequest);

            isset($dest) && $this->clean($dest);
        } catch (\Exception $ex) {
            isset($dest) && $this->clean($dest);
            return $this->jsonException($ex);
        }

        return $this->json('创建社团申请提交成功');
    }

    private function clean($file)
    {
        file_exists($file) && unlink($file);
    }

    /**
     * accept user's update request
     */
    public function requestForUpdate(Request $request, Guard $auth, TeamService $teamService,
                                     StorageService $storageService, PhotoService $photoService)
    {
        $this->validate($request, [
            'name'          => 'required|max:32',
            'email'         => 'email',
            'logo_id'       => 'max:255',
            'address'       => 'max:255',
            'contact_phone' => 'phone',
            'contact'       => 'max:32',
            'contact_hidden' => 'boolean',
            'introduction'  => 'max:128',
            'logo_crop'     => 'required|boolean',
            'crop_start_x'  => 'integer',
            'crop_start_y'  => 'integer',
            'crop_end_x'    => 'integer',
            'crop_end_y'    => 'integer',
        ], [
            'name.required'        => '社团名称未填写',
            'name.max'             => '社团名称错误',
            'email.email'          => '邮箱格式错误',
            'logo_id.max'          => 'logo错误',
            'address.max'          => '地址错误',
            'contact_phone.phone'  => '联系方式格式错误',
            'contact.max'          => '联系人错误',
            'contact_hidden.boolean' => '联系人是否隐藏设置错误',
            'introduction.max'     => '简介错误',
            'logo_crop.required'   => 'logo是否需要裁剪未指定',
            'logo_crop.boolean'    => 'logo是否需要裁剪',
            'crop_start_x.integer'  => '裁剪起点x坐标',
            'crop_start_y.integer'  => '裁剪起点y坐标',
            'crop_end_x.integer'    => '裁剪结束点x坐标',
            'crop_end_y.integer'    => '裁剪结束点y坐标',
        ]);

        $updateRequest = $request->only('name', 'email', 'logo_id', 'address',
                                        'contact_phone', 'contact', 'introduction');
        $updateRequest['contact_hidden'] = $request->input('contact_hidden', 0) ? 1 : 0;
        $updateRequest['team']      = $request->input('team')->getId();
        $updateRequest['initiator'] =  $auth->user()->toEntity();

        try {
            if ($request->input('logo_crop', 0)) {
                $ext = (0 == strcasecmp('.png', substr($updateRequest['logo_id'], -4))) ? 'png' : 'jpg';

                $dest = sys_get_temp_dir() . '/' . time() . '.' . $ext;
                $photoService->crop($updateRequest['logo_id'], [
                    'format'  => 'png' == $ext ? 'png' : 'jpeg',
                    'x'       => $request->input('crop_start_x'),
                    'y'       => $request->input('crop_start_y'),
                    'width'   => $request->input('crop_end_x') - $request->input('crop_start_x'),
                    'height'  => $request->input('crop_end_y') - $request->input('crop_start_y'),
                    'save_as' => $dest,
                ]);

                $updateRequest['logo_id'] = $storageService->storeAsImage($dest);
            } else {
                if ($storageService->isTmp($updateRequest['logo_id'])) {
                    // store logo by copy url from tmp
                    $updateRequest['logo_id'] = $storageService->storeAsImage($updateRequest['logo_id']);
                }
            }

            // request for team update
            $teamService->requestForUpdate($updateRequest);

            isset($dest) && $this->clean($dest);
        } catch (\Exception $ex) {
            isset($dest) && $this->clean($dest);
            return $this->jsonException($ex);
        }

        return $this->json('修改社团资料申请提交成功');
    }

    /**
     * accept user's update request
     */
    public function update(Request $request, TeamService $teamService)
    {
        $this->validate($request, [
            'email'         => 'email',
            'contact_phone' => 'phone',
            'contact'       => 'max:32',
            'contact_hidden' => 'boolean',
        ], [
            'email.email'          => '邮箱格式错误',
            'contact_phone.phone'  => '联系方式格式错误',
            'contact.max'          => '联系人错误',
            'contact_hidden.boolean' => '联系人是否隐藏设置错误',
        ]);

        $updateTeam = $request->only('email', 'contact_phone', 'contact', 'contact_hidden');
        $updateTeam['contact_hidden'] = $request->input('contact_hidden', 0) ? 1 : 0;
        $updateTeam['team'] = $request->input('team')->getId();

        try {
            $teamService->update($updateTeam);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }

        return $this->json('修改成功');
    }
    
    /**
     * inspect the handled request by request id param
     */
    public function inspectRequest(Request $request, TeamService $teamService)
    {
        // validate request
        $this->validate($request, [
            'request'          => 'required|integer',
        ], [
            'request.required' => '申请未指定',
            'request.integer'  => '申请错误',
        ]);

        try { // inspect the request
            $teamService->inspectRequest($request->input('request'));
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }

        return $this->json('设置成功');
    }
    
    /**
     * update requirements of team
     */
    public function updateRequirements(Request $request, TeamService $teamService)
    {
        // validate request
        $this->validate($request, [
            'join_type'    => 'required|integer',
            'requirements' => 'array',
        ], [
            'join_type.required'    => '社团加入条件未设置',
            'join_type.integer'     => '社团加入条件错误',
            'requirements.array'    => '社团加入条件要求错误',
        ]);

        if (!$this->validateRequirements($request->input('requirements', []))) {
            return $this->jsonException('社团加入条件要求格式错误');
        }
       
        try {
            // update requirements of team
            $teamService->updateTeamRequirements(
                            $request->input('team')->getId(), 
                            $request->input('join_type'),
                            $request->input('requirements', []));
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    
        return $this->json('社团加入条件修改成功');
    }
    
    /**
     * requirements array validator
     *
     * @param array     $requirements
     * @return boolean  true if requirements validated successfully, otherwise false
     */
    private function validateRequirements(array $requirements)
    {
        foreach ($requirements as $requirement) {
            if (is_null(array_get($requirement, 'requirement'))) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * team request certification and update team certification data
     */
    public function requestCertifications(Request $request, TeamService $teamService, StorageService $storageService)
    {
        $this->validate($request, [
            'certifications' => 'required|array'
        ], [
            'certifications.required' => '社团认证资料未设置',
            'certifications.array'    => '社团认证资料错误',
        ]);

        $certifications = $request->input('certifications');
        if (!$this->validateCertifications($certifications)) {
            return $this->jsonException('社团认证资料格式错误');;
        }
        
        try {
            foreach ($certifications as $certification) {
                if ($storageService->isTmp($certification['certification_id'])) {
                    // store certification file by copy url from tmp
                    $certification['certification_id'] = $storageService->storeAsFile($certification['certification_id']);
                }
            }
            
            // request and update certifications of team
            $teamService->requestTeamCertifications(
                                                    $request->input('team')->getId(),
                                                    $certifications);
            return $this->json('社团认证申请提交成功');
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }
    
    /**
     * certifications array validator
     *
     * @param array     $certifications
     * @return boolean  true if certifications validated successfully, otherwise false
     */
    private function validateCertifications(array $certifications)
    {
        // available types
        $types = [
            TeamCertification::TYPE_ID_CARD_FRONT,
            TeamCertification::TYPE_ID_CARD_BACK,
            TeamCertification::TYPE_BUSSINESS_CERTIFICATES,
        ];
        
        $requestCertificationTypes = [];
        foreach ($certifications as $certification) {
            // rule#1: must has key 'certification_id'
            if (is_null(array_get($certification, 'certification_id'))) {
                return false;
            }
            
            // rule#2: must has key 'type', and type value is avaliable
            $type = (array_get($certification, 'type'));
            if (is_null($type) || !in_array($type, $types)) {
                return false;
            }
            $requestCertificationTypes[] = $type;
        }
        
        // rule#3: request certification must include all available types
        if (!empty(array_diff($types, $requestCertificationTypes))) {
            return false;
        }
        
        return true;
    }
    
    /**
     * download team qrcode
     */
    public function downloadQrcode(Request $request, TeamService $teamService)
    {
        $this->validate($request, [
            'size' => 'required|integer|between:1,500',
        ], [
            'size.required' => 'size未指定',
            'size.integer'  => 'size错误',
            'size.between'  => 'size错误',
        ]);
    
        $qrcode = $teamService->generateTeamQrcode($request->input('team'));
        
        return response()->make($qrcode)
            ->header('Content-Type',        'image/png')
            ->header('Content-disposition', 'attachment; filename=qrcode.png');
    }
    
    /**
     * send team notice
     */
    public function sendNotice(Request $request)
    {
        $this->validate($request, [
            'to_all'   => 'required|boolean',
            'phones'   => 'array',
            'send_way' => 'required|min:3|max:4',
            'content'  => 'required|min:1|max:128',
        ], [
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
        
        /* @var $team \Jihe\Entities\Team */
        $team = $request->input('team');
        
        try {
            $phones = null;
            if (!$request->input('to_all')) {
                if (empty($request->input('phones'))) {
                    throw new \Exception('成员未指定');
                }
                $phones = $request->input('phones');
            }
            
            $message = [];
            $message['content'] = $request->input('content');
            $message['type'] = Message::TYPE_TEAM;
            $message['attributes'] = ['team_id' => $team->getId()];
            
            $option = [];
            $option['record'] = true;
            $option['record_attributes'] = ['team' => $team->getId()];
            if ('sms' == $request->input('send_way')) {
                $option['sms'] = true;
            } else {
                $option['push'] = true;
            }
            
            $this->sendToTeamMembers($team, $phones, $message, $option);
            return $this->json('发送成功');
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }
    
    /**
     * list team notices
     */
    public function listNotices(Request $request, MessageService $messageService)
    {
        $this->validate($request, [
            'page' => 'integer',
            'size' => 'integer',
        ], [
            'page.integer'  => '分页page错误',
            'size.integer'  => '分页size错误',
        ]);
        
        try {
            /* @var $team \Jihe\Entities\Team */
            $team = $request->input('team');
            
            list($page, $size) = $this->sanePageAndSize(
                                        $request->input('page'), 
                                        $request->input('size'));
            
            list($total, $messages) = $messageService->getTeamNotices(
                                                       $team, $page, $size);
            
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

    /*
     * 创建社团页面
     */
     public function create()
     {
        return view('backstage.team.create', [

        ]);
     }

    /*
      * 消息通知页面
      */
      public function message()
      {
         return view('backstage.team.message', [

         ]);
      }

    /*
      * 资料页面
      */
      public function info()
      {
         return view('backstage.team.info', [

         ]);
      }

    /*
      * 资料修改页面
      */
      public function infoEdit()
      {
         return view('backstage.team.infoEdit', [

         ]);
      }

    /**
     *  show team profile
     */
    public function profile(TeamService $teamService, Guard $auth, CityService $cityService)
    {
        $user = $auth->user()->getAuthIdentifier();
        
        /**
         * team of auth user
         */
        $team = null;
        
        $teams = $teamService->getTeamsByCreator($user);
        $team = empty($teams) ? null : $teams[0];
        $createdTeam = !is_null($team);
        $updateStatus = $team ? $teamService->canRequestForUpdate($team) : true;
        /**
         * uninspected requests that include both pending requests(default uninspected) and handled requests but uninspected
         */
        $requests = $teamService->getUninspectedRequests($user);
        if (!empty($requests)) {
            foreach ($requests as $request) {
                if ($request->getStatus() == TeamRequest::STATUS_PENDING) {
                    $team = $this->createTeamFromPendingRequest($request);
                    $updateStatus = false;
                    break;
                }

            }
        }

        $cities = $cityService->getAvailableCities();
       
        return view('backstage.team.profile', [
            'key'          => 'profile',
            'created_team' => $createdTeam,
            'team'         => $team,
            'teamLogoUrl'  => is_null($team) ? null : $team->getLogoUrl(),
            'requests'     => $requests,
            'updateStatus' => $updateStatus,
            'cities'       => $cities,
        ]);
    }

    // profileByWap
    public function profileByWap(TeamService $teamService, Guard $auth, CityService $cityService)
    {   
        if ( $auth->guest() ) {
            return view('backstage.wap.teamCreate')->withErrors('用户未登录，不能创建社团。');
        }
        $user = $auth->user()->getAuthIdentifier();
        
        /**
         * team of auth user
         */
        $team = null;
        
        $teams = $teamService->getTeamsByCreator($user);
        $team = empty($teams) ? null : $teams[0];
        $createdTeam = !is_null($team);
        $logo_modified = is_null($team) ? false : ($teamService->getDefaultLogo() != $team->getLogoUrl());
        $updateStatus = $team ? $teamService->canRequestForUpdate($team) : true;
        /**
         * uninspected requests that include both pending requests(default uninspected) and handled requests but uninspected
         */
       $requests = $teamService->getUninspectedRequests($user);
        if (!empty($requests)) {
            foreach ($requests as $request) {
                if ($request->getStatus() == TeamRequest::STATUS_PENDING) {
                    $team = $this->createTeamFromPendingRequest($request);
                    $updateStatus = false;
                    break;
                }

            }
        }

        $cities = $cityService->getAvailableCities();
       
        return view('backstage.wap.teamCreate', [
            'key'          => 'createTeam',
            'logo_modified' => $logo_modified,
            'created_team' => $createdTeam,
            'team'         => $team,
            'teamLogoUrl'  => is_null($team) ? null : $team->getLogoUrl(),
            'requests'     => $requests,
            'updateStatus' => $updateStatus,
            'cities'       => $cities,
        ]);
    }

    // test by pheart
    public function verifyForEnrollment(Request $request, TeamService $teamService) {
        $this->validate($request, [
            'id'          => 'required|integer',
        ], [
            'id.required'     => 'id非法',
        ]);

        if ($teamService->approveEnrollmentRequest($request->input('id'))) {
            return $this->json('审核成功');
        } else {
            return $this->json('审核拒绝');
        }
    }

    // test by pheart
    public function verifyForUpdate(Request $request, TeamService $teamService) {
        $this->validate($request, [
            'id'          => 'required|integer',
        ], [
            'id.required'     => 'id非法',
        ]);


        if ($teamService->approveUpdateRequest($request->input('id'))) {
            return $this->json('审核成功');
        } else {
            return $this->json('审核拒绝');
        }
    }

    private function createTeamFromPendingRequest($request)
    {
        return $request;
    }
    
    public function authentication(Request $request, TeamService $teamService)
    {
        $team = $request->input('team');
        
        // status of team certification
        $status = $team->getCertification();
        
        // data of team has committed for request certification
        $data = null;
        
        $cardFront = [];
        $cardBack  = [];
        $businessCertificates = [];
        
        if (Team::CERTIFICATION != $status) {
            $data = $teamService->getTeamCertifications($team->getId());
            
            foreach ($data as $certification) {
                if (TeamCertification::TYPE_ID_CARD_FRONT == $certification->getType()) {
                    array_push($cardFront, $certification);
                } elseif (TeamCertification::TYPE_ID_CARD_BACK == $certification->getType()) {
                    array_push($cardBack, $certification);
                } else {
                    array_push($businessCertificates, $certification);
                }
            }
        }
        
        return view('backstage.team.authentication', [
            'key'    => 'authentication',
            'status' => $status,
            'cardFront' => $cardFront,
            'cardBack' => $cardBack,
            'businessCertificates' => $businessCertificates,
        ]);
    }

    public function condition(Request $request, TeamService $teamService)
    {
        /**
         * @var array  joinType and requirements
         */
        $joinCondition = $teamService->getTeamRequirements($request->input('team')->getId());
        
        return view('backstage.team.condition', [
            'key' => 'condition',
            'joinCondition' => $joinCondition,
        ]);
    }

    public function passwd()
    {
        return view('backstage.team.passwd', [
            'key' => 'passwd'
        ]);
    }

    public function bind()
    {
        return view('backstage.team.bind', [
            'key' => 'bind'
        ]);
    }

    public function verifyPend()
    {
        return view('backstage.team.verifyPend', [
            'key' => 'verifyPend'
        ]);
    }

    public function verifyRefuse()
    {
        return view('backstage.team.verifyRefuse', [
            'key' => 'verifyRefuse'
        ]);
    }

    public function verifyBlacklist()
    {
        return view('backstage.team.verifyBlacklist', [
            'key' => 'verify'
        ]);
    }

    public function verifyWhitelist(Request $request)
    {
        $team = $request->input('team');
        
        // status of team certification
        $status = $team->getCertification();
        return view('backstage.team.verifyWhitelist', [
            'key' => 'verify',
            'status' => $status
        ]);
    }

    public function manager(Request $request, TeamGroupService $groupService)
    {
        $team = $request->input('team');
        return view('backstage.team.manager', [
            'key'    => 'managerMember',
            'groups' => $groupService->getGroupsOf($team),
        ]);
    }

    public function managerGroup()
    {
        return view('backstage.team.managerGroup', [
            'key' => 'manager'
        ]);
    }

    public function notice(Request $request)
    {
        $team = $request->input('team');
        return view('backstage.team.notice', [
            'key' => 'notice',
            'team'=> $team
        ]);
    }

    public function noticeList()
    {
        return view('backstage.team.noticeList', [
            'key' => 'notice'
        ]);
    }

    public function statistics(Request $request)
    {
        return view('backstage.team.statistics', [
            'key' => 'statistics'
        ]);
    }

    public function qrcode(Request $request)
    {
        $team = $request->input('team');
        $qrCodeUrl = $team->getQrCodeUrl();
        return view('backstage.team.qrcode', [
            'key' => 'qrcode',
            'qrCodeUrl' => $qrCodeUrl
        ]);
    }

    /**
     * render team detail page
     */
    public function detail(Request $request, TeamService $teamService, TeamMemberService $teamMemberService, ActivityService $activityService, WechatService $wechatService)
    {
        $this->validate($request, [
            'team_id' => 'required|integer',
        ], [
            'team_id.required' => 'id非法',
            'team_id.integer' => 'id非法',
        ]);

        $team = $teamService->getTeam($request->input('team_id'));
        if (empty($team)) {
            return view('backstage.wap.team', [
                'app_installed' => 1 == $request->input('isappinstalled'),
            ])->withErrors('社团不存在');
        }

        try {
            $url = url("wap/team/detail?team_id={$request->input('team_id')}");
            $package = $wechatService->getJsSignPackage($url);
        } catch (\Exception $ex) {
            $package = null;
        }

        $memberNum = $teamMemberService->countMembers($team->getId());
        $activityNum = $activityService->getTeamActivitiesCount($team->getId());
        $albumImageNum = $activityService->countApprovedAlbumImagesOfTeam($team);
        $activities    = $activityService->getPublishedActivityListByTeam($request->input('team_id'), 1, 3);

        return view('backstage.wap.team', [
            'team' => $team,
            'member_num' => $memberNum,
            'activity_num' => $activityNum,
            'album_image_num' => $albumImageNum,
            'activities'      => $activities[1],
            'sign_package' => $package,
            'app_installed' => 1 == $request->input('isappinstalled'),
        ]);
    }

    /**
     * download app page
     *
     */
    public function download()
    {
        return view('backstage.wap.download');
    }
}