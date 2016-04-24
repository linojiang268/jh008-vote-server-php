<?php
namespace Jihe\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Jihe\Http\Controllers\Controller;
use Jihe\Services\TeamService;
use Jihe\Entities\TeamRequest;
use Jihe\Entities\Team;
use Jihe\Entities\TeamCertification;

class TeamController extends Controller
{
    /**
     * list pending requests
     */
    public function listPendingRequests(Request $request, TeamService $teamService)
    {
        $this->validate($request, [
            'page' => 'required|min:1',
            'size' => 'required|min:1',
        ], [
            'page.required' => '分页page未指定',
            'page.min'      => '分页page错误',
            'size.required' => '分页size未指定',
            'size.min'      => '分页size错误',
        ]);
    
        list($page, $size) = $this->sanePageAndSize(
                                    $request->input('page'), 
                                    $request->input('size'));
        try {
            list($pages, $requests) = $teamService->getPendingRequests($page, $size);
            
            return $this->json([
                    'pages'    => $pages,
                    'requests' => array_map(
                                            [
                                                $this, 
                                                'morphToRequestAttributes'
                                            ], 
                                            $requests),
            ]);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    private function morphToRequestAttributes(TeamRequest $request)
    {
        return [
                'id'            => $request->getId(),
                'city_name'     => $request->getCity()->getName(),
                'name'          => $request->getName(),
                'email'         => $request->getEmail(),
                'logo_url'      => $request->getLogoUrl(),
                'address'       => $request->getAddress(),
                'contact_phone' => $request->getContactPhone(),
                'contact'       => $request->getContact(),
                'introduction'  => $request->getIntroduction(),
                'status'        => $request->getStatus(),
                'memo'          => $request->getMemo(),
                'is_created'    => null == $request->getTeam(),
        ];
    }
    
    /**
     * approve pending enrollment request
     */
    public function approvePendingEnrollmentRequest(Request $request, TeamService $teamService)
    {
        $this->validate($request, [
            'request' => 'required|min:1',
            'memo'    => 'max:128'
        ], [
            'request.required' => '申请未指定',
            'request.min'      => '申请错误',
            'memo.max'         => '备注错误',
        ]);
    
        try {
            $teamService->approveEnrollmentRequest($request->input('request'), $request->input('memo'));
            return $this->json('处理成功');
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }
    
    /**
     * reject pending enrollment request
     */
    public function rejectPendingEnrollmentRequest(Request $request, TeamService $teamService)
    {
        $this->validate($request, [
            'request' => 'required|min:1',
            'memo'    => 'max:128'
        ], [
            'request.required' => '申请未指定',
            'request.min'      => '申请错误',
            'memo.max'         => '备注错误',
        ]);
    
        try {
            $teamService->rejectEnrollmentRequest($request->input('request'), $request->input('memo'));
            return $this->json('处理成功');
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }
    
    /**
     * approve pending update request
     */
    public function approvePendingUpdateRequest(Request $request, TeamService $teamService)
    {
        $this->validate($request, [
                'request' => 'required|min:1',
                'memo'    => 'max:128'
        ], [
                'request.required' => '申请未指定',
                'request.min'      => '申请错误',
                'memo.max'         => '备注错误',
        ]);
    
        try {
            $teamService->approveUpdateRequest($request->input('request'), $request->input('memo'));
            return $this->json('处理成功');
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }
    
    /**
     * reject pending update request
     */
    public function rejectPendingUpdateRequest(Request $request, TeamService $teamService)
    {
        $this->validate($request, [
                'request' => 'required|min:1',
                'memo'    => 'max:128'
        ], [
                'request.required' => '申请未指定',
                'request.min'      => '申请错误',
                'memo.max'         => '备注错误',
        ]);
    
        try {
            $teamService->rejectUpdateRequest($request->input('request'), $request->input('memo'));
            return $this->json('处理成功');
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }
    
    /**
     * list pending teams for certification
     */
    public function listPendingTeamsForCertification(Request $request, TeamService $teamService)
    {
        $this->validate($request, [
            'page' => 'required|min:1',
            'size' => 'required|min:1',
        ], [
            'page.required' => '分页page未指定',
            'page.min'      => '分页page错误',
            'size.required' => '分页size未指定',
            'size.min'      => '分页size错误',
        ]);
    
        list($page, $size) = $this->sanePageAndSize(
                                    $request->input('page'),
                                    $request->input('size'));
        try {
            list($pages, $teams) = $teamService->getPendingTeamsForCertification($page, $size);
    
            return $this->json([
                    'pages'    => $pages,
                    'teams' => array_map(
                            [
                                $this,
                                'morphToTeamAttributes'
                            ],
                            $teams),
            ]);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }
    
    /**
     * list certifications
     */
    public function listCertifications(Request $request, TeamService $teamService)
    {
        $this->validate($request, [
            'team' => 'required|min:1',
        ], [
            'team.required' => '社团未指定',
            'team.min'      => '社团错误',
        ]);
        
        try {
            $team = $teamService->getTeam($request->input('team'));
            if (is_null($team)) {
                throw new \Exception('社团不存在');
            }
            
            $certifications = $teamService->getTeamCertifications($team->getId());
            
            $ret = [];
            foreach ($certifications as $certification) {
                if (TeamCertification::TYPE_ID_CARD_FRONT == $certification->getType()) {
                    $ret['id_card_front'][] = $this->morphToCertificationAttributes($certification);
                } elseif (TeamCertification::TYPE_ID_CARD_BACK == $certification->getType()) {
                    $ret['id_card_back'][] = $this->morphToCertificationAttributes($certification);
                } else {
                    $ret['bussiness_certificates'][] = $this->morphToCertificationAttributes($certification);
                }
            }
            
            return $this->json($ret);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }
    
    private function morphToCertificationAttributes(TeamCertification $certification)
    {
        return [
                'id'                => $certification->getId(),
                'type'              => $certification->getType(),
                'certification_url' => $certification->getCertificationUrl(),
        ];
    }
    
    /**
     * approve pending team for certification
     */
    public function approvePendingTeamForCertification(Request $request, TeamService $teamService)
    {
        $this->validate($request, [
            'team' => 'required|min:1',
        ], [
            'team.required' => '社团未指定',
            'team.min'      => '社团错误',
        ]);
    
        try {
            $teamService->approveTeamCertification($request->input('team'));
            return $this->json('处理成功');
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }
    
    /**
     * reject pending team for certification
     */
    public function rejectPendingTeamForCertification(Request $request, TeamService $teamService)
    {
        $this->validate($request, [
            'team' => 'required|min:1',
        ], [
            'team.required' => '社团未指定',
            'team.min'      => '社团错误',
        ]);
    
        try {
            $teamService->rejectTeamCertification($request->input('team'));
            return $this->json('处理成功');
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }
    
    /**
     * list teams
     */
    public function listTeams(Request $request, TeamService $teamService)
    {
        $this->validate($request, [
            'name'      => 'max:32',
            'tagged'    => 'integer',
            'forbidden' => 'integer',
            'page'      => 'required|min:1',
            'size'      => 'required|min:1',
        ], [
            'name.max'           => '名称错误',
            'tagged.integer'     => '标记错误',
            'forbidden.integer'  => '封停错误',
            'page.required'      => '分页page未指定',
            'page.min'           => '分页page错误',
            'size.required'      => '分页size未指定',
            'size.min'           => '分页size错误',
        ]);
        
        list($page, $size) = $this->sanePageAndSize(
                                                    $request->input('page'),
                                                    $request->input('size'));

        $tagged = null;
        if ($request->has('tagged')) {
            if (0 == $request->input('tagged')) {
                $tagged = false;
            } elseif (1 == $request->input('tagged')) {
                $tagged = true;
            }
        }
        
        $forbidden = null;
        if ($request->has('forbidden')) {
            if (0 == $request->input('forbidden')) {
                $forbidden = false;
            } elseif (1 == $request->input('forbidden')) {
                $forbidden = true;
            }
        }
        
        try {
            list($pages, $teams) = $teamService->getTeams($page, $size, 
                                                            ['tagged' => $tagged, 'forbidden' => $forbidden, 
                                                             'name' => $request->input('name')]);
    
            return $this->json([
                    'pages' => $pages,
                    'teams' => array_map(
                                        [
                                            $this,
                                            'morphToTeamAttributes'
                                        ],
                                        $teams),
            ]);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }
    
    private function morphToTeamAttributes(Team $team)
    {
        return [
                'id'            => $team->getId(),
                'name'          => $team->getName(),
                'email'         => $team->getEmail(),
                'logo_url'      => $team->getLogoUrl(),
                'address'       => $team->getAddress(),
                'contact_phone' => $team->getContactPhone(),
                'contact'       => $team->getContact(),
                'introduction'  => $team->getIntroduction(),
                'certification' => $team->getCertification(),
                'qr_code_url'   => $team->getQrCodeUrl(),
                'status'        => $team->getStatus(),
                'city_name'     => $team->getCity()->getName(),
                'tags'          => $team->getTags(),
        ];
    }
    
    /**
     * freeze team
     */
    public function freezeTeam(Request $request, TeamService $teamService)
    {
        $this->validate($request, [
            'team' => 'required|min:1',
        ], [
            'team.required' => '社团未指定',
            'team.min'      => '社团错误',
        ]);
    
        try {
            $teamService->updateProperties($request->input('team'), ['status' => Team::STATUS_FREEZE]);
            return $this->json('处理成功');
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }
    
    /**
     * cancel freeze team
     */
    public function cancelFreezeTeam(Request $request, TeamService $teamService)
    {
        $this->validate($request, [
            'team' => 'required|min:1',
        ], [
            'team.required' => '社团未指定',
            'team.min'      => '社团错误',
        ]);
    
        try {
            $teamService->updateProperties($request->input('team'), ['status' => Team::STATUS_NORMAL]);
            return $this->json('处理成功');
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }
    
    /**
     * forbidden team
     */
    public function forbiddenTeam(Request $request, TeamService $teamService)
    {
        $this->validate($request, [
            'team' => 'required|min:1',
        ], [
            'team.required' => '社团未指定',
            'team.min'      => '社团错误',
        ]);
    
        try {
            $teamService->updateProperties($request->input('team'), ['status' => Team::STATUS_FORBIDDEN]);
            return $this->json('处理成功');
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }
    
    /**
     * cancel forbidden team
     */
    public function cancelForbiddenTeam(Request $request, TeamService $teamService)
    {
        $this->validate($request, [
            'team' => 'required|min:1',
        ], [
            'team.required' => '社团未指定',
            'team.min'      => '社团错误',
        ]);
    
        try {
            $teamService->updateProperties($request->input('team'), ['status' => Team::STATUS_NORMAL]);
            return $this->json('处理成功');
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }
    
    /**
     * tag team
     */
    public function tagTeam(Request $request, TeamService $teamService)
    {
        $this->validate($request, [
            'team' => 'required|min:1',
            'tags' => 'array'
        ], [
            'team.required' => '社团未指定',
            'team.min'      => '社团错误',
            'tags.array'    => '标签错误',
        ]);
    
        try {
            $tags = null;
            if ($request->has('tags')) {
                $tags = $request->input('tags');
            }
            
            $teamService->updateProperties($request->input('team'), ['tags' => $tags]);
            
            return $this->json('处理成功');
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }
}