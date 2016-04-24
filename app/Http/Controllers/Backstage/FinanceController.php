<?php
namespace Jihe\Http\Controllers\Backstage;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Jihe\Http\Controllers\Controller;
use Jihe\Services\TeamFinanceService;
use Jihe\Services\ActivityService;
use Jihe\Entities\ActivityEnrollIncome;
use Jihe\Entities\ActivityEnrollPayment;
use Jihe\Entities\Activity;
use Auth;

class FinanceController extends Controller
{
    public function index()
    {
        return view('backstage.finance.index', [
                'key' => 'financeIndex'
            ]);
    }

    public function indexDetail(ActivityService $activityService, $activityId)
    {
        try {
            $activity = $activityService->findAllStatusActivitiesByIds(
                intval($activityId), Auth::user()->id);
            if ($activity == null) {
                return view('backstage.wap.team')->withErrors('该活动不存在');
            }
        } catch (\Exception $ex) {
            return view('backstage.wap.team')->withErrors('服务器异常，请稍后再试');
        }

        return view('backstage.finance.indexDetail', [
                'key' => 'financeIndexDetail',
                'activity'  => $this->activityToArray($activity),
            ]);
    }

    public function stream() 
    {
        return view('backstage.finance.stream', [
                'key' => 'stream'
            ]);
    }

    public function withdrawals() 
    {
        return view('backstage.finance.withdrawals', [
                'key' => 'withdrawals'
            ]);
    }

    public function withdrawalsList() 
    {
        return view('backstage.finance.withdrawalsList', [
                'key' => 'withdrawals'
            ]);
    }    

    public function refund() 
    {
        return view('backstage.finance.refund', [
                'key' => 'refund'
            ]);
    }

    /**
     * list all activity enroll income for team
     */
    public function listActivityEnrollIncomeForTeam(
        Request $request, 
        Guard $auth,
        TeamFinanceService $teamFinanceService
    ) {
        $this->validate($request, [
            'page'  => 'integer',
            'size'  => 'integer',
        ], [
            'page.integer'  => '分页page错误',
            'size.integer'  => '分页size错误',
        ]);
        list($page, $pageSize) = $this->sanePageAndSize($request->input('page'),
                                            $request->input('size'));

        try {
            list($total, $incomes) = $teamFinanceService->listActivityEnrollIncomeForTeam(
                                            $request->input('team')->getId(),
                                            $auth->user()->getAuthIdentifier(),
                                            $page,
                                            $pageSize);

            return $this->json(['total' => $total,
                                'incomes' => array_map([$this, 'incomeToArray'], $incomes)]);
        } catch (\Exception $ex) {
            return $this->json(['total' => 0, 'incomes' => []]);
        }
    }

    /**
     * list all activity enroll payment for activity
     */
    public function listPaymentsForActivity(
        Request $request,
        Guard $auth,
        TeamFinanceService $teamFinanceService
    ) {
        $this->validate($request, [
            'activity'  => 'required|integer',
            'page'      => 'integer',
            'size'      => 'integer',
        ], [
            'activity.required' => '活动未指定',
            'activity.integer'  => '活动格式错误',
            'page.integer'      => '分页page错误',
            'size.integer'      => '分页size错误',
        ]);
        list($page, $pageSize) = $this->sanePageAndSize($request->input('page'),
                                            $request->input('size'));

        try {
            list($total, $payments) = $teamFinanceService->listEnrollPaymentsForActivity(
                                            $request->input('activity'),
                                            $auth->user()->getAuthIdentifier(),
                                            $page,
                                            $pageSize);
            
            return $this->json(['total' => $total,
                                'payments' => array_map([$this, 'paymentToArray'], $payments)]);
        } catch (\Exception $ex) {
            return $this->json(['total' => 0, 'payments' => []]);
        }
    }

    private function incomeToArray(ActivityEnrollIncome $income)
    {
        $activity = $income->getActivity();
        return [
            'id'                        => $income->getId(),
            'team'                      => [
                                                'id'    => $income->getTeam()->getId(),
                                            ],
            'activity'                  => [
                                                'id'    => $activity->getId(),
                                                'title' => $activity->getTitle(),
                                                'begin_time' => $activity->getBeginTime(),
                                                'end_time' => $activity->getEndTime(),
                                            ],
            'total_fee'                 => $income->getTotalFee(),
            'financial_action_result'   => $income->getFinancialActionResult(),
        ];
    }

    private function paymentToArray(ActivityEnrollPayment $payment)
    {
        $user = $payment->getUser();
        $payedAt = $payment->getPayedAt() ? 
                    $payment->getPayedAt()->format('Y-m-d H:i:s') : '';
        return [
            'id'            => $payment->getId(),
            'activity'      => [
                                    'id' => $payment->getActivity()->getId(),
                                ],
            'fee'           => $payment->getFee(),
            'payed_at'      => $payedAt,
            'status'        => $payment->getStatus(),
            'channel'       => $payment->getChannel(),
            'order_no'      => $payment->getOrderNo(),
            'trade_no'      => $payment->getTradeNo(),
            'user'          => [
                                    'id'        => $user->getId(),
                                    'nick_name' => $user->getNickName(),
                                    'mobile'    => $user->getMobile(),
                                ],
        ];
    }

    private function activityToArray(Activity $activity)
    {
        return [
            'id'                => $activity->getId(),
            'title'             => $activity->getTitle(),
            'enroll_begin_time' => $activity->getEnrollBeginTime(),
            'enroll_end_time'   => $activity->getEnrollEndTime(),
            'begin_time'        => $activity->getBeginTime(),
            'end_time'          => $activity->getEndTime(),
        ];
    }
}
