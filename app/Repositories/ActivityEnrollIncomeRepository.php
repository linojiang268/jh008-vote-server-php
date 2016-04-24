<?php
namespace Jihe\Repositories;

use Jihe\Contracts\Repositories\ActivityEnrollIncomeRepository as ActivityEnrollIncomeRepositoryContract;
use Jihe\Models\ActivityEnrollIncome;
use Jihe\Entities\ActivityEnrollIncome as ActivityEnrollIncomeEntity;
use Jihe\Utils\PaginationUtil;
use Jihe\Entities\Activity;
use DB;

class ActivityEnrollIncomeRepository implements ActivityEnrollIncomeRepositoryContract
{
    /**
     * (non-PHPdoc)
     *
     * @see \Jihe\Contracts\Repositories\ActivityEnrollIncomeRepository::findAllIncomesByTeam()
     */
    public function findAllIncomesByTeam($teamId, $page, $pageSize)
    {
        $where = [
            'team'  => $teamId,
        ];

        return $this->findAll($where, $page, $pageSize);
    }

    /**
     * (non-PHPdoc)
     *
     * @see \Jihe\Contracts\Repositories\ActivityEnrollIncomeRepository::findAllIncomes()
     */
    public function findAllIncomes($status, $beginTime, $endTime, $page, $pageSize)
    {
        $where = [
            'status'        => $status,
            'beginEnrollTime'     => $beginTime,
            'endEnrollTime'       => $endTime,
        ];

        return $this->findAll($where, $page, $pageSize);
    }

    /**
     * find all records by conditions passed in
     *
     * @param array $where      condition for seach records from database
     * @param int $page         the current page number
     * @param int $pageSize     the number of data per page
     */
    private function findAll($where, $page, $pageSize)
    {
        $query = ActivityEnrollIncome::with('activity', 'team');
        if (isset($where['status']) && $where['status'] !== null) {
            $query->where('status', $where['status']);
        }
        if ( ! empty($where['beginEnrollTime'])) {
            $query->where('enroll_end_time', '>=', $where['beginEnrollTime']);
        }
        if ( ! empty($where['endEnrollTime'])) {
            $query->where('enroll_end_time', '<=', $where['endEnrollTime']);
        }
        if ( ! empty($where['team'])) {
            $query->where('team_id', $where['team']);
        }

        $count = $query->count();
        $page = PaginationUtil::genValidPage($page, $count, $pageSize);
        $incomes = $query->orderBy('enroll_end_time', 'desc')
                         ->forPage($page, $pageSize)
                         ->get()
                         ->all();
        $incomes = array_map([ $this, 'convertToEntity' ], $incomes);

        return [$count, $incomes];
    }

    /**
     * (non-PHPdoc)
     *
     * @see \Jihe\Contracts\Repositories\ActivityEnrollIncomeRepository::increaseTotalFeeForPaymentSuccess()
     */
    public function increaseTotalFeeForPaymentSuccess($activityId, $fee)
    {
        ActivityEnrollIncome::where('activity_id', $activityId)
            ->increment('total_fee', $fee);
    }

    public function add(array $income)
    {
        return ActivityEnrollIncome::create($income)->id;
    }

    public function findOneByActivityId($activityId)
    {
        $income = ActivityEnrollIncome::where('activity_id', $activityId)
            ->get()
            ->first();

        return $this->convertToEntity($income);
    }

    /**
     * (non-PHPdoc)
     *
     * @see \Jihe\Contracts\Repositories\ActivityEnrollIncomeRepository::findOneById()
     */
    public function findOneById($id)
    {
        $income = ActivityEnrollIncome::find($id);

        return $this->convertToEntity($income);
    }

    /**
     * (non-PHPdoc)
     *
     * @see \Jihe\Contracts\Repositories\ActivityEnrollIncomeRepository::updateStatus()
     */
    public function updateStatus($income, $status)
    {
        return $this->update($income, ['status' => $status]);
    }

    /**
     * (non-PHPdoc)
     *
     * @see \Jihe\Contracts\Repositories\ActivityEnrollIncomeRepository::updateConfirm()
     */
    public function updateConfirm($income, $fee, $evidence)
    {
        $income = ActivityEnrollIncome::find($income);
        if ( ! $income) {
            return false;
        }
        $income->addFinancialActionResult([time(), $fee, $evidence]);
        $income->transfered_fee += $fee;
        $income->status = ActivityEnrollIncomeEntity::STATUS_TRANSFERING;
        return $income->save();
    }

    /**
     * (non-PHPdoc)
     *
     * @see \Jihe\Contracts\Repositories\ActivityEnrollIncomeRepository::update()
     */
    public function update($income, array $updates)
    {
        return 1 == ActivityEnrollIncome::where('id', $income)
                            ->update($updates);
    }

    /**
     * (non-PHPdoc)
     *
     * @return \Jihe\Entities\ActivityEnrollIncome|null
     */
    private function convertToEntity($activityEnrollIncome)
    {
        return $activityEnrollIncome ? $activityEnrollIncome->toEntity() : null;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \Jihe\Contracts\Repositories\ActivityEnrollIncomeRepository::countActivitiesAmount()
     */
    public function countActivitiesAmount($activities)
    {
        $activitiesAmount = DB::table('activity_enroll_incomes')
            ->leftJoin('activities', 'activities.id', '=', 'activity_enroll_incomes.activity_id')
            ->where('activities.status', Activity::STATUS_PUBLISHED)
            ->whereIn('activity_enroll_incomes.activity_id', $activities)
            ->groupBy('activity_enroll_incomes.activity_id')
            ->select(DB::raw('activity_enroll_incomes.total_fee, activity_enroll_incomes.activity_id'))
            ->get();
        $result = [];
        if($activitiesAmount){
            foreach ($activitiesAmount as $activityAmount) {
                $result[$activityAmount->activity_id] = $activityAmount->total_fee;
            }
        }

        return $result;
    }

}
