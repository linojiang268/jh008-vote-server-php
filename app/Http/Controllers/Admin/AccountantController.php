<?php
namespace Jihe\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Jihe\Http\Controllers\Controller;
use Jihe\Services\TeamFinanceService;
use Jihe\Entities\ActivityEnrollIncome;
use Jihe\Contracts\Services\Storage\StorageService;

class AccountantController extends Controller
{
    public function index()
    {
        return view('admin.accountant.list', [
            'key' => 'accountant'
        ]);
    }

    /**
     * list activity incomes
     */
    public function listIncome(Request $request, TeamFinanceService $teamFinanceService)
    {
        $this->validate($request, [
            'status'        => 'integer',
            'begin_time'    => 'date_format:Y-m-d H:i:s',
            'end_time'      => 'date_format:Y-m-d H:i:s',
            'page'          => 'integer',
            'size'          => 'integer',
        ], [
            'status'                    => '转账状态格式错误',
            'begin_time.date_format'    => '查询开始时间格式错误，示例：2015-08-01 10:00:00',
            'end_time.date_format'      => '查询结束时间格式错误，示例：2015-08-01 16:00:00',
            'page.integer'              => '分页page错误',
            'size.integer'              => '分页size错误',
        ]);
        $beginTime = $request->input('begin_time');
        $endTime = $request->input('end_time');
        $currTime = date('Y-m-d H:i:s');
        if ($beginTime && $beginTime > $currTime) {
            return $this->jsonException('查询开始时间不能大于当前时间');
        }
        if ($endTime && $endTime > $currTime) {
            return $this->jsonException('查询结束时间不能大于当前时间');
        }
        if ($beginTime && $endTime && $beginTime > $endTime) {
            return $this->jsonException('查询开始时间不能大于查询结束时间');
        }
        list($page, $pageSize) = $this->sanePageAndSize($request->input('page'),
                                            $request->input('size'));

        list($total, $incomes) = $teamFinanceService->listActivityEnrollIncome(
                                            $page, $pageSize,
                                            $request->input('status'),
                                            $request->input('begin_time'),
                                            $request->input('end_time'));

        return $this->json(['total' => $total,
                            'incomes' => array_map([$this, 'incomeToArray'], $incomes)]);
    }

    /**
     * Set activity income status to transfer
     */
    public function doIncomeTransfer(Request $request, TeamFinanceService $teamFinanceService)
    {
        $this->validate($request, [
            'id'    => 'required|integer',
        ], [
            'id.required'   => '活动收入编号未填写',
            'id.integer'    => '活动收入编号格式错误',
        ]);

        $updateStatus = $teamFinanceService->doIncomeTransfer($request->input('id'));

        return $updateStatus ? $this->json('操作成功') : $this->jsonException('操作失败');
    }

    /**
     * confirm a activity income behavior, include transaction evidence
     */
    public function confirmIncomeTransfer(Request $request,
                                          TeamFinanceService $teamFinanceService
    ) {
        $this->validate($request, [
            'id'        => 'required|integer',
            'fee'       => 'required|integer|min:1',
            'evidence'  => 'required|string',
        ], [
            'id.required'       => '活动收入编号未填写',
            'id.integer'        => '活动收入编号格式错误',
            'fee.required'      => '金额未填写',
            'fee.integer'       => '金额格式错误',
            'fee.min'           => '金额必须为大于0的数字',
            'evidence.required' => '未上传凭证地址',
        ]);

        try {
            $teamFinanceService->confirmIncomeTransfer(
                $request->input('id'), (int) $request->input('fee'), $request->input('evidence'));
        } catch (\Exception $ex) {
            return $this->jsonException($ex->getMessage());
        }

        return $this->json('操作成功');
    }

    /**
     * Set activity income status to finished
     */
    public function finishIncomeTransfer(Request $request, TeamFinanceService $teamFinanceService)
    {
        $this->validate($request, [
            'id'    => 'required|integer',
        ], [
            'id.required'   => '活动收入编号未填写',
            'id.integer'    => '活动收入编号格式错误',
        ]);

        $updateStatus = $teamFinanceService->finishIncomeTransfer($request->input('id'));

        return $updateStatus ? $this->json('操作成功') : $this->jsonException('操作失败');
    }

    private function incomeToArray(ActivityEnrollIncome $income)
    {
        $activity = $income->getActivity();
        return [
            'id'                    => $income->getId(),
            'activity_title'        => $activity->getTitle(),
            'activity_begin_time'   => $activity->getEnrollBeginTime(),
            'activity_end_time'     => $activity->getEnrollEndTime(),
            'team_name'             => $income->getTeam()->getName(),
            'total_fee'             => $income->getTotalFee(),
            'transfered_fee'        => $income->getTransferedFee(),
            'status'                => $income->getStatus(),
            'status_desc'           => $income->getStatusDesc(),
            'detail'                => array_map(function($item) {
                list($opTime, $fee, $evidenceUrl) = $item;
                return [
                    'op_time'       => date('Y-m-d H:i:s', $opTime),
                    'fee'           => $fee,
                    'evidence_url'  => $evidenceUrl,
                ];
            }, $income->getFinancialActionResult()),
        ];
    }
}
