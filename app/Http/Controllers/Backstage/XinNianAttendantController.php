<?php
namespace Jihe\Http\Controllers\Backstage;

use Jihe\Models\YoungSingle;
use Jihe\Services\StorageService;
use Jihe\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Jihe\Models\XinNianVote as Vote;
use Jihe\Repositories\YoungSingleRepository as AttendantRepository;
use Jihe\Repositories\XinNianVoteRepository as VoteRepository;
use Jihe\Services\WechatService;
use Validator;
use Log;
use Jihe\Utils\PaginationUtil;
use Illuminate\Contracts\Auth\Guard;
use Cache;

class XinNianAttendantController extends Controller
{

    /**
     * wap home page of attendant
     */
    public function attendant(Request $request, Guard $auth, WechatService $wechatService)
    {
        $wechat = $request->session()->get('wechat');

        try {
            $url = url($request->fullUrl());
            $package = $wechatService->getJsSignPackage($url);
        } catch (\Exception $ex) {
            $package = null;
        }

        $data = [
            'mobile'         => $auth->guest() ? null : $auth->user()->mobile,
            'wechat_openid'  => array_get($wechat, 'openid', null),
            'wechat_success' => intval(array_get($wechat, 'success')),
            'wechat_session' => intval($request->session()->has('wechat')),
            'sign_package'   => $package,
            'prefix'         => app('config')['storage']['alioss']['public_path'],
            'isDue'          => (time() > strtotime('2016-01-19 16:30:00')),
        ];

        if (!$data['wechat_success'] || $data['wechat_success'] == null) {
            $request->session()->forget('wechat');
        }

        return view('backstage.wap.xinnian.index', $data);
    }

    /**
     * list attendants
     */
    public function listApprovedAttendants(Request $request, AttendantRepository $attendantRepository)
    {
        $this->validate($request, [
            'page' => 'integer',
            'size' => 'integer',
        ], [
            'page.integer' => '分页page错误',
            'size.integer' => '分页size错误',
        ]);

        try {
            list($page, $size) = $this->sanePageAndSize(
                $request->input('page'),
                $request->input('size'));

            list($total, $attendants) = $attendantRepository->listApprovedYoungSingles($page, $size, false);

            return $this->json([
                'pages'      => PaginationUtil::count2Pages($total, $size),
                'attendants' => array_map([$this, 'morphAttendant'], $attendants),
            ]);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    /**
     * list pending attendants
     */
    public function listPendingAttendants(Request $request, AttendantRepository $attendantRepository)
    {
        $this->validate($request, [
            'page' => 'integer',
            'size' => 'integer',
        ], [
            'page.integer' => '分页page错误',
            'size.integer' => '分页size错误',
        ]);

        try {
            list($page, $size) = $this->sanePageAndSize(
                $request->input('page'),
                $request->input('size'));

            list($total, $attendants) = $attendantRepository->listPendingYoungSingles($page, $size);

            return $this->json([
                'pages'      => PaginationUtil::count2Pages($total, $size),
                'attendants' => array_map([$this, 'morphAttendant'], $attendants),
            ]);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    private function morphAttendant($model)
    {
        return [
            'id'                  => $model->id,
            'order_id'            => $model->order_id,
            'name'                => $model->name,
            'id_number'           => $model->id_number,
            'gender'              => $model->gender,
            'date_of_birth'       => $model->date_of_birth,
            'height'              => $model->height,
            'graduate_university' => $model->graduate_university,
            'degree'              => $model->degree,
            'yearly_salary'       => $model->yearly_salary,
            'work_unit'           => $model->work_unit,
            'mobile'              => $model->mobile,
            'cover_url'           => $this->getThumbnailOfCover($model->cover_url),
            'images_url'          => $this->getThumbnailImages(json_decode($model->images_url)),
            'source_images_url'   => json_decode($model->images_url),
            'talent'              => $model->talent,
            'mate_choice'         => $model->mate_choice,
        ];
    }

    private function getThumbnailOfCover($coverUrl)
    {
        return $coverUrl . '@250w_90Q_2o_1pr.jpg';
    }

    private function getThumbnailImages(array $images)
    {
        return array_map(function ($imageUrl) {
            //return $imageUrl . '@720w_2o_1pr.jpg';
            return $imageUrl . '@150h_2o_1pr.jpg';
        },
            $images);
    }

    /**
     * list approved attendants from wap
     */
    public function getAttendants(Request $request,
                                  AttendantRepository $attendantRepository, VoteRepository $voteRepository)
    {
        $this->validate($request, [
            'page' => 'integer',
        ], [
            'page.integer' => '分页page错误',
        ]);

        try {
            $page = $request->input('page', 1);
            $size = AttendantRepository::LIST_SIZE;

            list($total, $attendants) = $attendantRepository->listApprovedYoungSingles($page, $size, true);

            return $this->json([
                'pages'      => PaginationUtil::count2Pages($total, $size),
                'attendants' => $this->morphAttendants($attendants, $voteRepository),
            ]);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    /**
     * list approved sort attendants from wap
     */
    public function getSortAttendants(Request $request,
                                      AttendantRepository $attendantRepository, VoteRepository $voteRepository)
    {
        $this->validate($request, [
            'page' => 'integer',
        ], [
            'page.integer' => '分页page错误',
        ]);

        try {
            $pageNumber = 10;
            $page = $request->input('page', 1);
            if ($page <= 0 || $page > $pageNumber) {
                return $this->jsonException('分页page错误');
            }
            $size = AttendantRepository::LIST_SIZE;
            $attendants = [];
            list($total, $attendantIds) = $voteRepository->getAttendantIds($page, $size);
            if (!empty($attendantIds)) {
                $attendants = $attendantRepository->findByIds($attendantIds);
            }
            $allPage = PaginationUtil::count2Pages($total, $size);
            if ($allPage > $pageNumber) {
                $allPage = $pageNumber;
            }

            return $this->json([
                'pages'      => $allPage,
                'attendants' => $this->morphAttendants($attendants, $voteRepository),
            ]);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }


    private function morphAttendants(array $attendants, VoteRepository $voteRepository)
    {
        if (empty($attendants)) {
            return [];
        }

        $attendantIds = array_map(function ($attendant) {
            return $attendant->id;
        }, $attendants);

        $votes = $voteRepository->findUserVoteCount($attendantIds);

        return array_map(function ($attendant) use ($votes) {
            return [
                'id'         => $attendant->id,
                'order_id'   => $attendant->order_id,
                'name'       => $attendant->name,
                'cover_url'  => $this->getThumbnailOfCover($attendant->cover_url),
                'vote_count' => $votes[ $attendant->id ],
            ];
        }, $attendants);
    }

    /**
     * search attendant
     */
    public function search(Request $request,
                           AttendantRepository $attendantRepository, VoteRepository $voteRepository)
    {
        $this->validate($request, [
            'attendant' => 'required|integer',
        ], [
            'attendant.required' => '选手未指定',
            'attendant.integer'  => '选手错误',
        ]);

        try {
            $attendant = $attendantRepository->findByNumber($request->input('attendant'));
            if (is_null($attendant) || YoungSingle::STATUS_PENDING == $attendant->status) {
                return $this->jsonException('选手不存在');
            }

            return $this->json($this->morphAttendantOfDetail($attendant, $voteRepository));
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    /**
     * detail of attendant
     */
    public function detail(Request $request,
                           AttendantRepository $attendantRepository, VoteRepository $voteRepository)
    {
        $this->validate($request, [
            'attendant' => 'required|integer',
        ], [
            'attendant.required' => '选手未指定',
            'attendant.integer'  => '选手错误',
        ]);

        try {
            $attendant = $attendantRepository->find($request->input('attendant'));
            if (is_null($attendant) || YoungSingle::STATUS_PENDING == $attendant->status) {
                return $this->jsonException('选手不存在');
            }

            return $this->json($this->morphAttendantOfDetail($attendant, $voteRepository));
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    private function morphAttendantOfDetail(YoungSingle $model, VoteRepository $voteRepository)
    {
        $detail = $this->morphAttendant($model);
        $detail['vote_count'] = $voteRepository->findUserVoteCount([$model->id])[ $model->id ];
        $detail['vote_sort'] = $voteRepository->findUserSort($model->id);

        return $detail;
    }

    /**
     * approve attendant
     */
    public function approve(Request $request, AttendantRepository $attendantRepository)
    {
        $this->validate($request, [
            'attendant' => 'required|integer',
        ], [
            'attendant.required' => '选手未指定',
            'attendant.integer'  => '选手错误',
        ]);

        try {
            $attendant = $attendantRepository->find($request->input('attendant'));
            if (is_null($attendant)) {
                return $this->jsonException('选手不存在');
            }

            if (1 == $attendant->status) {
                return $this->jsonException('选手已通过审核');
            }

            if ($attendantRepository->approve($attendant)) {
                return $this->json('审核成功');
            }

            return $this->jsonException('审核失败');
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    /**
     * remove applicants
     */
    public function removeApplicants(Request $request, AttendantRepository $attendantRepository)
    {
        $this->validate($request, [
            'attendant' => 'required|integer',
        ], [
            'attendant.required' => '选手未指定',
            'attendant.integer'  => '选手错误',
        ]);

        try {
            $attendant = $attendantRepository->find($request->input('attendant'));
            if (is_null($attendant)) {
                return $this->jsonException('选手不存在');
            }

            if ($attendantRepository->removeApplicants($request->input('attendant'))) {
                return $this->json('删除成功');
            }

            return $this->jsonException('删除失败');
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    /**
     * remove attendants
     */
    public function removeAttendants(Request $request, AttendantRepository $attendantRepository)
    {
        $this->validate($request, [
            'attendant' => 'required|integer',
        ], [
            'attendant.required' => '选手未指定',
            'attendant.integer'  => '选手错误',
        ]);

        try {
            $attendant = $attendantRepository->find($request->input('attendant'));
            if (is_null($attendant)) {
                return $this->jsonException('选手不存在');
            }

            if ($attendantRepository->removeYoungSingles($request->input('attendant'))) {
                return $this->json('删除成功');
            }

            return $this->jsonException('删除失败');
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    /**
     * enrollment for attendant
     */
    public function enroll(Request $request, AttendantRepository $attendantRepository, StorageService $storageService)
    {
        $this->validate($request, [
            'name'                => 'required|max:32',
            'id_number'           => 'required|unique:young_singles|max:18',
            'gender'              => 'required|integer',
            'date_of_birth'       => 'required|max:16',
            'height'              => 'required|integer',
            'graduate_university' => 'required|max:128',
            'degree'              => 'required|integer',
            'yearly_salary'       => 'required|integer',
            'work_unit'           => 'required|max:128',
            'mobile'              => 'required|mobile',
            'images_url'          => 'required|array',
            'talent'              => 'max:128',
            'mate_choice'         => 'max:128',
        ], [
            'name.required'                => '姓名未填写',
            'name.max'                     => '姓名错误',
            'id_number.required'           => '身份证号未填写',
            'id_number.max'                => '身份证号错误',
            'id_number.unique'             => '该身份证号已注册',
            'gender.required'              => '性别未填写',
            'gender.integer'               => '性别错误',
            'date_of_birth.required'       => '出生年月未填写',
            'date_of_birth.integer'        => '出生年月错误',
            'height.required'              => '身高未填写',
            'height.integer'               => '身高错误',
            'graduate_university.required' => '毕业院校未填写',
            'graduate_university.max'      => '毕业院校错误',
            'degree.required'              => '学历未填写',
            'degree.integer'               => '学历错误',
            'yearly_salary.required'       => '年薪未填写',
            'yearly_salary.integer'        => '年薪错误',
            'work_unit.required'           => '工作单位未填写',
            'work_unit.max'                => '工作单位错误',
            'mobile.required'              => '手机未填写',
            'mobile.mobile'                => '手机错误',
            'images_url.required'          => '照片未填写',
            'images_url.array'             => '照片错误',
            'talent.required'              => '个人才艺未填写',
            'talent.max'                   => '个人才艺错误',
            'mate_choice.required'         => '择偶要求未填写',
            'mate_choice.max'              => '择偶要求错误',
        ]);

        if (time() > strtotime('2016-01-19 16:30:00')) {
            return $this->jsonException('报名已截止');
        }

        try {
            if (count($request->input('images_url')) > 5) {
                return $this->jsonException('上传照片超过5张');
            }

            if (count($request->input('images_url')) < 1) {
                return $this->jsonException('上传照片不足1张');
            }

            if ($attendantRepository->findByMobile($request->input('mobile'))) {
                return $this->jsonException('手机号已存在');
            }

            $imagesUrl = [];
            foreach ($request->input('images_url') as $tmp) {
                if ($storageService->isTmp($tmp)) {
                    // store image by copy url from tmp
                    array_push($imagesUrl, $storageService->storeAsImage($tmp));
                    continue;
                }
                array_push($imagesUrl, $tmp);
            }

            $attendant = new YoungSingle();
            $attendant->name = $request->input('name');
            $attendant->id_number = $request->input('id_number');
            $attendant->gender = $request->input('gender');
            $attendant->date_of_birth = $request->input('date_of_birth');
            $attendant->height = $request->input('height');
            $attendant->graduate_university = $request->input('graduate_university');
            $attendant->degree = $request->input('degree');
            $attendant->yearly_salary = $request->input('yearly_salary');
            $attendant->work_unit = $request->input('work_unit');
            $attendant->mobile = $request->input('mobile');
            $attendant->cover_url = $imagesUrl[0];
            $attendant->images_url = json_encode($imagesUrl);
            $attendant->talent = $request->input('talent');
            $attendant->mate_choice = $request->input('mate_choice');

            if ($attendantRepository->add($attendant)) {
                return $this->json('提交报名成功');
            }

            return $this->jsonException('提交报名失败');
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    /**
     * vote for attendant
     */
    public function vote(Request $request,
                         VoteRepository $voteRepository,
                         WechatService $wechatService,
                         AttendantRepository $attendantRepository)
    {
        $this->validate($request, [
            'voter' => 'required|string',
            'user'  => 'required|integer',
        ], [
            'voter.required' => '投票人未指定',
            'voter.string'   => '投票人错误',
            'type.required'  => '投票渠道未指定',
            'type.integer'   => '投票渠道错误',
            'user.required'  => '选手未指定',
            'user.integer'   => '选手错误',
        ]);

        if (time() > strtotime('2016-01-19 16:30:00')) {
            return $this->jsonException('投票已截止');
        }

        try {
            $attendant = $request->input('user');
            $voter = $request->input('voter');
            $type = Vote::TYPE_WX;
            $this->checkVotingRights($request, $voter, $type, $wechatService);
            $data = [
                'voter'   => $voter,
                'type'    => $type,
                'user_id' => $attendant,
            ];
            $attendantDB = $this->getAttendant($attendantRepository, $attendant);
            if ($attendantDB < 1) {
                return $this->jsonException('选手不存在');
            }
            $todayVote = $voteRepository->findTodayVoteCountByVoter($voter);
            if (!$this->checkVoteNumber($todayVote, $type)) {
                if ($type == Vote::TYPE_WX) {
                    return $this->jsonException('对不起，您今日微信投票次数已满。', 2);
                }
            }
            if ($voteRepository->add($data)) {
                return $this->json('投票成功');
            }

            return $this->jsonException('投票失败');
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    private function getAttendant(AttendantRepository $attendantRepository, $attendant)
    {
        $key = md5(VoteRepository::PREFIX . '_attendant' . '_' . $attendant);
        if (Cache::has($key)) {
            if (null !== ($value = Cache::get($key))) {
                return $value;
            }
        }
        $attendantDB = $attendantRepository->find($attendant);
        if ($attendantDB) {
            Cache::forever($key, $attendantDB->id);

            return $attendantDB->id;
        } else {
            Cache::put($key, 0, 60);

            return 0;
        }
    }

    private function checkVotingRights(Request $request, $voter, $type, WechatService $wechatService)
    {
        $openid = $request->session()->get('wechat.openid');
        if ($type == Vote::TYPE_WX && $openid == null) {
            throw new  \Exception('未授权的非法操作!');
        }
        if ($type == Vote::TYPE_WX && $openid != $voter) {
            throw new  \Exception('未授权的非法操作!');
        }

        return true;
    }

    private function checkVoteNumber($todayVote, $type)
    {
        if ($type == Vote::TYPE_WX && $todayVote < VoteRepository::WX_VOTE_NUM) {
            return true;
        }

        return false;
    }
}