<?php
namespace Jihe\Http\Controllers\Backstage;

use Illuminate\Http\Request;
use Jihe\Contracts\Repositories\UserRepository;
use Jihe\Entities\TeamGroup;
use Jihe\Entities\TeamMember;
use Jihe\Entities\TeamMemberEnrollmentRequest;
use Jihe\Entities\TeamMemberEnrollmentRequirement;
use Jihe\Entities\TeamMemberRequirement;
use Jihe\Http\Controllers\Controller;
use Jihe\Services\TeamGroupService;
use Jihe\Services\TeamMemberService;
use Symfony\Component\HttpFoundation\File\MimeType\ExtensionGuesser;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesser;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class TeamMemberController extends Controller
{
    /**
     * list pending enrollment request for team
     */
    public function listPendingEnrollmentRequestForTeam(Request $request,
                                                        TeamMemberService $memberService)
    {
        list($page, $size) = $this->sanePageAndSize($request->input('page'), $request->input('size'));
        $team = $request->input('team');

        list($pages, $requests) = $memberService->getPendingEnrollmentRequestsOfTeam($team->getId(), $page, $size)
                                  ?: [0, []];
        return $this->json(['pages' => $pages, 'requests' => array_map(function (TeamMemberEnrollmentRequest $request) {
            return $this->morphEnrollmentRequest($request);
        }, $requests)]);
    }

    /**
     * list rejected enrollment request for team
     */
    public function listRejectedEnrollmentRequestForTeam(Request $request,
                                                         TeamMemberService $memberService)
    {
        $team = $request->input('team');
        list($page, $size) = $this->sanePageAndSize($request->input('page'), $request->input('size'));

        list($pages, $requests) = $memberService->getRejectedEnrollmentRequestsOfTeam($team->getId(), $page, $size)
                                  ?: [0, []];
        return $this->json(['pages' => $pages, 'requests' => array_map(function (TeamMemberEnrollmentRequest $request) {
            return $this->morphEnrollmentRequest($request);
        }, $requests)]);
    }

    private function morphEnrollmentRequest(TeamMemberEnrollmentRequest $request)
    {
        $morphed = [
            'id'   => $request->getId(),
            'name' => $request->getName(),
            'memo' => $request->getMemo(),
            'reason' => $request->getReason(),
            'initiator' => [
                'id'     => $request->getInitiator()->getId(),
                'mobile' => $request->getInitiator()->getMobile(),
            ],
        ];

        if (!empty($request->getRequirements())) {
            $morphed['requirements'] = array_map(function (TeamMemberEnrollmentRequirement $requirement) {
                return [
                    'requirement' => $requirement->getRequirement()->getId(),
                    'value'       => $requirement->getValue(),
                ];
            }, $request->getRequirements());
        }

        return $morphed;
    }

    /**
     * update enrollment request (no matter what status it has)
     */
    public function updateEnrollmentRequest(Request $request, TeamMemberService $memberService)
    {
        $this->validate($request, [
            'request' => 'required|integer',
            'memo'    => 'max:64',
        ], [
            'request.required' => '请求参数未指定',
            'request.integer'  => '请求参数错误',
            'memo.max'         => '备注过长',
        ]);
        $enrollmentRequest = $memberService->getEnrollmentRequest($request->input('request'),
                                                                  $request->input('team'));
        if ($enrollmentRequest == null) {
            return $this->jsonException('非法请求');
        }

        try {
            $memberService->updateEnrollmentRequest($enrollmentRequest->getId(), [
                'memo' => $request->input('memo'),
            ]);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }

        return $this->json('修改成功');
    }


    /**
     * reject enrollment request
     */
    public function rejectEnrollmentRequest(Request $request, TeamMemberService $memberService)
    {
        $this->validate($request, [
            'request'   => 'required|integer',
            'reason'    => 'max:64',
        ], [
            'request.required'     => '请求参数未指定',
            'request.integer'      => '请求参数错误',
            'reason.max'           => '拒绝理由过长',
        ]);

        $enrollmentRequest = $memberService->getPendingEnrollmentRequest($request->input('request'),
                                                                         $request->input('team'));
        if (!$enrollmentRequest) {
            return $this->jsonException('非法请求');
        }

        try {
            $memberService->rejectEnrollmentRequest($enrollmentRequest, $request->input('reason'));
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }

        return $this->json('拒绝入团申请成功');
    }

    /**
     * reject enrollment requests
     */
    public function rejectEnrollmentRequests(Request $request, TeamMemberService $memberService)
    {
        $this->validate($request, [
            'requests'  => 'required|array',
            'reason'    => 'max:64',
        ], [
            'requests.required'   => '请求参数未指定',
            'requests.array'      => '请求参数错误',
            'reason.max'          => '拒绝理由过长',
        ]);

        $enrollmentRequests = $memberService->getPendingEnrollmentRequest($request->input('requests'),
                                                                          $request->input('team'));
        if (count($enrollmentRequests) != count($request->input('requests'))) {
            return $this->jsonException('非法请求');
        }

        try {
            $memberService->rejectEnrollmentRequests($enrollmentRequests, $request->input('reason'));
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }

        return $this->json('拒绝入团申请成功');
    }

    /**
     * approve enrollment request
     */
    public function approveEnrollmentRequest(Request $request,
                                             TeamMemberService $memberService, TeamGroupService $groupService)
    {
        // validate request
        $this->validate($request, [
            'request'   => 'required|integer',
            'memo'      => 'max:64',
        ], [
            'request.required' => '请求参数未指定',
            'request.integer'  => '请求参数错误',
            'memo.max'         => '用户备注过长',
        ]);

        $enrollmentRequest = $memberService->getPendingEnrollmentRequest($request->input('request'),
                                                                         $request->input('team'));
        if (!$enrollmentRequest) {
            return $this->jsonException('非法请求');
        }

        $group = null;
        if ($request->has('group') && (null == ($group = $groupService->getGroup($request->input('group'))))) {
            return $this->jsonException('非法的用户分组');
        }
        $enrollmentRequest->setGroup($group);
        $enrollmentRequest->setMemo($request->input('memo'));

        try {
            $memberService->approveEnrollmentRequest($enrollmentRequest);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }

        return $this->json('批准入团申请成功');
    }

    /**
     *  update team member's group
     */
    public function changeMemberGroup(Request $request,
                                      TeamGroupService $groupService, TeamMemberService $memberService)
    {
        $this->validate($request, [
            'to'     => 'integer',
            'member' => 'required',
        ], [
            'to.integer'      => '目标分组指定错误',
            'member.required' => '成员指定错误',
        ]);

        $team = $request->input('team');
        $toGroup = TeamGroup::UNGROUPED;
        if ($request->has('to')) {
            $toGroup = $request->input('to');
            if (null == $group = $groupService->getGroup($toGroup)) {
                return $this->jsonException('错误的目标分组');
            } else {
                if ($group->getTeam()->getId() != $team->getId()) {
                    return $this->jsonException('错误的社团分组');
                }
            }
        }

        $members = (array)$request->input('member');
        try {
            $memberService->changeMemberGroup($team->getId(), $members, $toGroup);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }

        return $this->json('修改分组成功');
    }

    /**
     * update team members's basic info (by team leader)
     */
    public function update(Request $request, TeamMemberService $memberService)
    {
        $this->validate($request, [
            'memo'   => 'max:64',
            'member' => 'required',
        ], [
            'memo.required'   => '备注未指定',
            'memo.max'        => '备注过长',
            'member.required' => '成员指定错误',
        ]);

        try {
            $memberService->update($request->input('member'), $request->input('team')->getId(), [
                'memo' => $request->input('memo'),
            ]);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }

        return $this->json('修改成功');
    }

    /**
     * list members 
     */
    public function listMembers(Request $request, TeamMemberService $memberService)
    {
        $this->validate($request, [
            'page'    => 'integer',
            'size'    => 'integer',
            'group'   => 'integer',
            'keyword' => 'min:2',
        ], [
            'page.integer'      => '错误的页码',
            'size.integer'      => '错误的分页尺寸',
        ]);

        list($page, $size) = $this->sanePageAndSize($request->input('page'), $request->input('size'));
        $team = $request->input('team');
        try {
            list($pages, $members) = $memberService->listMembers($team->getId(), $page, $size, [
                'group'      => $request->input('group'),
                'keyword'    => $request->input('keyword'),
            ]);
            return $this->json([
                'pages'   => $pages,
                'members' => array_map(function (TeamMember $member) {
                    return $this->morphMember($member);
                }, $members),
            ]);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    /**
     * export members
     */
    public function exportMembers(Request $request, TeamMemberService $memberService)
    {
        $this->validate($request, [
            'group'   => 'integer',
            'keyword' => 'min:2',
        ]);

        $team = $request->input('team');
        ob_start();
        $memberService->exportMembers($team->getId(), [
            'group'   => $request->input('group'),
            'keyword' => $request->input('keyword'),
        ]);

        return response()->make(ob_get_clean())
            ->header('Content-Type',        'application/vnd.ms-excel')
            ->header('Content-disposition', 'attachment; filename= members.xls');
    }

    private function morphMember(TeamMember $member)
    {
        $morphed = [
            'id'      => $member->getUser()->getId(),
            'name'    => $member->getName() ?: $member->getUser()->getNickName(),
            'gender'  => $member->getUser()->getGender(),
            'mobile'  => $member->getUser()->getMobile(),
            'entrytime' => $member->getEntryTime(),
            'memo'   => $member->getMemo(),
        ];

        if (!empty($member->getRequirements())) {
            $morphed['requirements'] = array_map(function (TeamMemberRequirement $requirement) {
                return [
                    'requirement' => $requirement->getRequirement()->getId(),
                    'value'       => $requirement->getValue(),
                ];
            }, $member->getRequirements());
        }

        if ($member->getGroup() != null) {
            $morphed['group'] = [
                'id'   => $member->getGroup()->getId(),
                'name' => $member->getGroup()->getName(),
            ];
        }

        return $morphed;
    }

    /**
     * add an entry to enrollment whitelist
     */
    public function addEnrollmentWhitelist(Request $request, TeamMemberService $memberService)
    {
        $this->validate($request, [
            'mobile'  => 'required|mobile',
            'name'    => 'max:32',
            'memo'    => 'max:64'
        ], [
            'mobile.required' => '手机号未填写',
            'mobile.mobile'   => '非法的手机号',
            'name.max'        => '用户群名片过长',
            'memo.max'        => '用户备注过长',
        ]);

        $team = $request->input('team');

        try {
            if ($permission = $memberService->findEnrollmentPermission($request->input('mobile'), $team->getId())) {
                return $this->jsonException('用户已经在' . ($permission->prohibited() ? '黑名单' : '白名单') . '中');
            }

            $memberService->addEnrollmentPermission([
                'mobile' => $request->input('mobile'),
                'name' => $request->input('name'),
                'memo' => $request->input('memo'),
                'team' => $team->getId(),
            ], $team, true);

            return $this->json('加入白名单成功');
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    /**
     * import whitelist for team member enrollment
     */
    public function importEnrollmentWhitelist(Request $request,
                                              TeamMemberService $memberService)
    {
        // client not issuing an AJAX request, but requires JSON response
        // for simplicity, add AJAX request header ourselves
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        // validate the request
        $this->validate($request, [
            'whitelist' => 'required'
        ], [
            'whitelist.required' => '未上传白名单文件',
        ]);

        // by default, Laravel (or underlying Symfony) cannot give the right mime type
        // of old version of Excel files, so we have to do it by ourselves
        $whitelist = $request->file('whitelist');
        if (!$this->excelUploaded($whitelist)) {
            unlink(strval($whitelist));
            return $this->jsonException('非法的白名单文件');
        }

        $whitelist = strval($whitelist);  // get uploaded file's path
        $team = $request->input('team');
        try {
            $failed = $memberService->importEnrollmentPermissions($whitelist, $team);
            unlink($whitelist);
            if (!empty($failed)) {
                return $this->json(['failed' => $failed]);
            }
        } catch (\Exception $ex) {
            unlink($whitelist);
            return $this->jsonException($ex);
        }

        return $this->json('导入白名单数据成功');
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
        return in_array($file->getClientOriginalExtension(), ['xls', 'xlsx']);;
    }

    /**
     * blacklist someone so that he/she cannot request for enrollment later
     */
    public function blacklistEnrollmentRequest(Request $request,
                                               TeamMemberService $memberService,
                                               UserRepository $userService)
    {
        $this->validate($request, [
            'request' => 'required|integer',
            'memo'    => 'max:32'
        ], [
            'request.required' => '请求未指定',
            'request.integer'  => '请求指定错误',
            'memo.max'         => '备注过长',
        ]);

        $team = $request->input('team');

        $enrollmentRequest = $memberService->getPendingEnrollmentRequest($request->input('request'), $team);
        if (!$enrollmentRequest) {
            return $this->jsonException('非法请求');
        }

        try {
            $memberService->blacklistEnrollmentRequest($enrollmentRequest, $team,
                                                       $request->input('name') ?: '',
                                                       $request->input('memo'));
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }

        return $this->json('加入黑名单成功');
    }

    /**
     * white a blacklisted enrollment request
     */
    public function whiteBlacklistedEnrollmentRequest(Request $request,
                                                      TeamMemberService $memberService,
                                                      UserRepository $userService)
    {
        $this->validate($request, [
            'mobile'   => 'required|mobile',
        ], [
            'mobile.required' => '请求未指定',
            'mobile.mobile'   => '请求指定错误',
        ]);

        $team = $request->input('team');
        try {
            $memberService->whiteBlacklistedEnrollmentRequest($request->input('mobile'), $team);

            return $this->json('从黑名单中移除成功');
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    public function deleteEnrollmentPermission(Request $request,
                                               TeamMemberService $memberService) 
    {
        $this->validate($request, [
            'mobile'   => 'required|mobile',
        ], [
            'mobile.required' => '请求未指定',
            'mobile.mobile'   => '请求指定错误',
        ]);

        $team = $request->input('team');
        try {
            $permission = $memberService->findEnrollmentPermission($request->input('mobile'), $team->getId());
            if ($permission != null) {
                $memberService->deleteEnrollmentPermission($permission->getId());
            }

            return $this->json('移除成功');
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    /**
     * update white/black list
     */
    public function updateEnrollmentPermission(Request $request, TeamMemberService $memberService)
    {
        $this->validate($request, [
            'memo'   => 'max:64',
            'mobile' => 'required|mobile',
        ], [
            'memo.required'   => '备注未指定',
            'memo.max'        => '备注过长',
            'mobile.required' => '成员指定错误',
            'mobile.mobile'   => '成员指定格式错误',
        ]);

        try {
            $permission = $memberService->findEnrollmentPermission($request->input('mobile'),
                                                                   $request->input('team')->getId());
            if ($permission == null) {
                return $this->jsonException('非法请求');
            }

            $memberService->updateEnrollmentPermission($permission->getId(), [
                'memo' => $request->input('memo'),
            ]);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }

        return $this->json('修改成功');
    }

    /**
     * list whiltelist in a team
     */
    public function showEnrollmentWhitelist(Request $request, TeamMemberService $memberService)
    {
        $team = $request->input('team');
        list($page, $size) = $this->sanePageAndSize($request->input('page'), $request->input('size'));

        try {
            list($pages, $whitelist) = $memberService->getPermittedEnrollmentPermissionsFor($team->getId(), $page, $size)
                                       ?: [0, []];

            return $this->json([
                'pages' => $pages,
                'whitelist' => array_map([$this, 'morphPermission'], $whitelist),
            ]);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    /**
     * list blacklist in a team
     */
    public function showEnrollmentBlacklist(Request $request, TeamMemberService $memberService)
    {
        $team = $request->input('team');
        list($page, $size) = $this->sanePageAndSize($request->input('page'), $request->input('size'));

        try {
            list($pages, $blacklist) = $memberService->getProhibitedEnrollmentPermissionsFor($team->getId(), $page, $size)
                                       ?: [0, []];

            return $this->json([
                'pages' => $pages,
                'blacklist' => array_map([$this, 'morphPermission'], $blacklist),
            ]);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    private function morphPermission(\Jihe\Entities\TeamMemberEnrollmentPermission $permission)
    {
        return [
            'memo'   => $permission->getMemo(),
            'mobile' => $permission->getMobile(),
            'name'   => $permission->getName(),
        ];
    }
}