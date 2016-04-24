<?php
namespace Jihe\Http\Controllers\Backstage;

use Illuminate\Support\Facades\Auth;
use Jihe\Services\StorageService;
use Jihe\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Jihe\Models\PlaneModelVote as Vote;
use Jihe\Repositories\PlaneModelAttendantRepository as AttendantRepository;
use Jihe\Repositories\PlaneModelVoteRepository as VoteRepository;
use Jihe\Services\WechatService;
use Validator;
use Log;
use Jihe\Utils\PaginationUtil;
use Illuminate\Contracts\Auth\Guard;
use Cache;

class PlaneModelController extends Controller
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
        ];

        if (!$data['wechat_success'] || $data['wechat_success'] == null) {
            $request->session()->forget('wechat');
        }

        return view('backstage.wap.planeModel.index', $data);
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

            list($total, $attendants) = $attendantRepository->listApprovedAttendants($page, $size, false);

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

            list($total, $attendants) = $attendantRepository->listPendingAttendants($page, $size);

            return $this->json([
                'pages'      => PaginationUtil::count2Pages($total, $size),
                'attendants' => array_map([$this, 'morphAttendant'], $attendants),
            ]);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    private function morphAttendant($stdAttendant)
    {
        return [
            'id'         => $stdAttendant->id,
            'name'       => $stdAttendant->name,
            'height'     => $stdAttendant->height,
            'weight'     => $stdAttendant->weight,
            'bwh'        => $stdAttendant->bwh,
            'shoe_size'  => $stdAttendant->shoe_size,
            'mobile'     => $stdAttendant->mobile,
            'cover_url'  => $this->getThumbnailOfCover($stdAttendant->cover_url),
            'images_url' => $this->getThumbnailImages(json_decode($stdAttendant->images_url)),
            'intro'      => $stdAttendant->intro,
        ];
    }

    private function getThumbnailOfCover($coverUrl)
    {
        return $coverUrl . '@250w_90Q_1pr.jpg';
    }

    private function getThumbnailImages(array $images)
    {
        return array_map(function ($imageUrl) {
            return $imageUrl . '@720w_1pr.jpg';
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
            $size = AttendantRepository::ATTENDANT_LIST_SIZE;

            list($total, $attendants) = $attendantRepository->listApprovedAttendants($page, $size, true);

            return $this->json([
                'pages'      => PaginationUtil::count2Pages($total, $size),
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
                'name'       => $attendant->name,
                'cover_url'  => $this->getThumbnailOfCover($attendant->cover_url),
                'vote_count' => $votes[$attendant->id],
            ];
        }, $attendants);
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
            if (is_null($attendant) || 0 == $attendant->status) {
                return $this->jsonException('选手不存在');
            }

            return $this->json($this->morphAttendantOfDetail($attendant, $voteRepository));
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    private function morphAttendantOfDetail($stdAttendant, VoteRepository $voteRepository)
    {
        $detail = $this->morphAttendant($stdAttendant);
        $detail['vote_count'] = $voteRepository->findUserVoteCount([$stdAttendant->id])[$stdAttendant->id];
        $detail['vote_sort'] = $voteRepository->findUserSort($stdAttendant->id);
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
     * remove attendant
     */
    public function remove(Request $request, AttendantRepository $attendantRepository)
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

            if ($attendantRepository->remove($request->input('attendant'))) {
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
            'name'       => 'required|max:32',
            'height'     => 'required|integer',
            'weight'     => 'required|integer',
            'bwh'        => 'required|max:32',
            'shoe_size'  => 'required|numeric',
            'mobile'     => 'required|mobile',
            'images_url' => 'required|array',
            'intro'      => 'required|max:120',
        ], [
            'name.required'       => '姓名未填写',
            'name.max'            => '姓名错误',
            'height.required'     => '身高未填写',
            'height.integer'      => '身高错误',
            'weight.required'     => '体重未填写',
            'weight.max'          => '体重错误',
            'bwh.required'        => '三围未填写',
            'bwh.max'             => '三围错误',
            'shoe_size.required'  => '鞋码未填写',
            'shoe_size.max'       => '鞋码错误',
            'mobile.required'     => '手机未填写',
            'mobile.max'          => '手机错误',
            'images_url.required' => '照片未填写',
            'images_url.array'    => '照片错误',
            'intro.required'      => '简介未填写',
            'intro.max'           => '简介错误',
        ]);

        try {
            $imagesUrl = [];
            foreach ($request->input('images_url') as $tmp) {
                if ($storageService->isTmp($tmp)) {
                    // store image by copy url from tmp
                    array_push($imagesUrl, $storageService->storeAsImage($tmp));
                    continue;
                }
                array_push($imagesUrl, $tmp);
            }
            $attendant = [
                'name'       => $request->input('name'),
                'height'     => $request->input('height'),
                'weight'     => $request->input('weight'),
                'bwh'        => $request->input('bwh'),
                'shoe_size'  => $request->input('shoe_size'),
                'mobile'     => $request->input('mobile'),
                'images_url' => $imagesUrl,
                'intro'      => $request->input('intro'),
            ];

            if (count($attendant['images_url']) > 5) {
                return $this->jsonException('上传照片超过5张');
            }

            if (count($attendant['images_url']) < 5) {
                return $this->jsonException('上传照片不足5张');
            }

            if ($attendantRepository->findIdByMobile($request->input('mobile'))) {
                return $this->jsonException('手机号已存在');
            }

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
            'type'  => 'required|integer|in:' . implode(',', [Vote::TYPE_APP, Vote::TYPE_WX]),
            'user'  => 'required|integer',
        ], [
            'voter.required' => '投票人未指定',
            'voter.string'   => '投票人错误',
            'type.required'  => '投票渠道未指定',
            'type.integer'   => '投票渠道错误',
            'user.required'  => '选手未指定',
            'user.integer'   => '选手错误',
        ]);
        try {
            $attendant = $request->input('user');
            $voter = $request->input('voter');
            $type = $request->input('type');
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
                if ($type == Vote::TYPE_APP) {
                    return $this->jsonException('对不起，您今日集合app投票次数已满，请尝试微信投票。', 1);
                }
                if ($type == Vote::TYPE_WX) {
                    return $this->jsonException('对不起，您今日微信投票次数已满，请尝试集合app投票。', 2);
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
        $key = md5('pane_model_attendant' . '_' . $attendant);
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
        if ($type == Vote::TYPE_APP && !Auth::user()) {
            throw new \Exception('用户非法操作!');
        }
        if ($type == Vote::TYPE_APP && Auth::user()->mobile != $voter) {
            throw new  \Exception('用户非法操作!');
        }
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
        if ($type == Vote::TYPE_APP && $todayVote < VoteRepository::APP_VOTE_NUM) {
            return true;
        }
        if ($type == Vote::TYPE_WX && $todayVote < VoteRepository::WX_VOTE_NUM) {
            return true;
        }
        return false;
    }
}