<?php
namespace Jihe\Http\Controllers\Api;

use Illuminate\Contracts\Auth\Guard;
use Jihe\Entities\TeamMember;
use Jihe\Entities\TeamMemberEnrollmentRequest;
use Jihe\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Jihe\Services\TeamMemberService;
use Jihe\Services\TeamService;
use Jihe\Utils\StringUtil;

class TeamMemberController extends Controller
{
    /**
     * a (registered) user sends request for enrollment. The request can be
     * rejected (if requirements not met), accepted (if all requirements met)
     * or queued (waiting for someone's auditing sometimes later).
     */
    public function requestForEnrollment(Request $request, Guard $auth,
                                         TeamService $teamService, TeamMemberService $memberService)
    {
        $this->validate($request, [
            'team'   => 'required|integer',
            'memo'   => 'max:64',
            'name'   => 'max:32',
        ], [
            'team.required'  => '社团未指定',
            'team.integer'   => '社团指定错误',
            'memo.max'       => '备注过长',
            'name.max'       => '群昵称过长'
        ]);

        // check requirements, which is supposed to be a JSON
        $requirements = $request->input('requirements');
        if ($requirements) {
            if (false === $requirements = StringUtil::safeJsonDecode($requirements)) {
                return $this->jsonException('非法的加入条件');
            }
        }

        // check team
        $team = $teamService->getTeam($request->input('team'));
        if ($team == null) {
            return $this->jsonException('社团非法');
        }

        //sane requirements - if there is no requirements required, simply set $requirements to null
        $requirements = empty($team->getRequirements()) ? null : $requirements;

        try {
            list ($code, $message) = $memberService->requestForEnrollment($auth->user()->toEntity(), $team, [
                'name'         => $request->input('name'),
                'memo'         => $request->input('memo'),
                'requirements' => $requirements,
            ]);

            return $this->json([
                'result'  => $code,   // result code
                'message' => $message,
            ]);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    /**
     * a team member quits that team
     */
    public function quitTeam(Request $request, Guard $auth,
                             TeamMemberService $memberService, TeamService $teamService)
    {
        $this->validate($request, [
            'team'   => 'required|integer',
        ], [
            'team.required'     => '社团未指定',
            'team.integer'      => '社团指定错误',
        ]);

        try {
            $team = $teamService->getTeam($request->input('team'));
            if ($team && $team->getCreator()->getId() == $auth->user()->getAuthIdentifier()) {
                throw new \Exception('团长不能退出自己的社团');
            }
            
            $memberService->quitTeam($request->input('team'),
                                     $auth->user()->getAuthIdentifier());
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }

        return $this->json('退出成功');
    }


    /**
     * list all enrollment request for team
     */
    public function listPendingEnrollmentRequestForUser(Guard $auth, TeamMemberService $memberService)
    {
        $requests = $memberService->getPendingEnrollmentRequestsOfInitiator($auth->user()->getAuthIdentifier()) ?: [];
        return $this->json(['requests' => array_map(function (TeamMemberEnrollmentRequest $request) {
            return $this->morphEnrollmentRequest($request);
        }, $requests)]);
    }

    private function morphEnrollmentRequest(TeamMemberEnrollmentRequest $request)
    {
        $morphed = [
            'id'   => $request->getId(),
            'team' => [
                'id'   => $request->getTeam()->getId(),
                'name' => $request->getTeam()->getName(),
            ],
        ];

        /*if (!empty($request->getRequirements())) {
            $morphed['requirements'] = array_map(function (TeamMemberEnrollmentRequirement $requirement) {
                return [
                    'requirement' => $requirement->getRequirement()->getId(),
                    'value'       => $requirement->getValue(),
                ];
            }, $request->getRequirements());
        }*/

        return $morphed;
    }

    /**
     * list members in given team
     */
    public function listMembers(Request $request, Guard $auth,
                                TeamService $teamService, TeamMemberService $memberService)
    {
        $this->validate($request, [
            'team'  => 'required|integer',
            'page'  => 'integer',
            'size'  => 'integer',
        ], [
            'team.required'     => '社团未指定',
            'team.integer'      => '社团指定错误',
            'page.integer'      => '错误的页码',
            'size.integer'      => '错误的分页尺寸',
        ]);

        list($page, $size) = $this->sanePageAndSize($request->input('page'), $request->input('size'));
        if (null == ($team = $teamService->getTeam($request->input('team')))) {
            return $this->jsonException('非法的社团');
        }

        // if current user is in the team, show all members
        $all = $memberService->enrolled($auth->user()->getAuthIdentifier(), $team->getId());

        try {
            list($pages, $members) = $memberService->listMembers($team->getId(), $page, $size, [
                'visibility' => $all ? null : TeamMember::VISIBILITY_ALL,
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

    private function morphMember(TeamMember $member)
    {
        $morphed = [
            'id'     => $member->getUser()->getId(),
            'name'   => $member->getName() ?: $member->getUser()->getNickName(),
            'gender' => $member->getUser()->getGender(),
            'avatar' => $member->getUser()->getAvatarUrlOfThumbnail(),
        ];

        if ($member->getGroup() != null) {
            $morphed['group'] = [
                'id'   => $member->getGroup()->getId(),
                'name' => $member->getGroup()->getName(),
            ];
        }

        return $morphed;
    }

    /**
     * update team members's visibility
     */
    public function update(Request $request, Guard $auth, TeamMemberService $memberService)
    {
        $this->validate($request, [
            'team'       => 'required|integer',
            'name'       => 'max:32',
            'visibility' => 'required'
        ], [
            'team.required'       => '社团未指定',
            'team.integer'        => '社团指定错误',
            'visibility.required' => '隐私未指定',
        ]);

        if (!TeamMember::isValidVisibility($request->input('visibility'))) {
            return $this->jsonException('bad visibility');
        }

        try {
            $memberService->update($auth->user()->getAuthIdentifier(),
                                   $request->input('team'), [
                                       'visibility' => $request->input('visibility'),
                                       'name'       => $request->input('name'),
                                   ]);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }

        return $this->json('修改成功');
    }
}