<?php
namespace Jihe\Services;

use Jihe\Services\TeamService;
use Jihe\Services\ActivityService;
use Jihe\Contracts\Repositories\ActivityEnrollPaymentRepository;
use Jihe\Contracts\Repositories\ActivityEnrollIncomeRepository;
use Jihe\Entities\Activity;
use Jihe\Entities\ActivityEnrollIncome;

/**
 * Team finance related service
 */
class TeamFinanceService
{
    /**
     * @var \Jihe\Services\TeamService
     */
    private $teamService;

    /**
     * @var \Jihe\Services\ActivityService
     */
    private $activityService;

    /**
     * @var \Jihe\Contracts\Repositories\ActivityEnrollPaymentRepository
     */
    private $activityEnrollPaymentRepository;

    /**
     * @var \Jihe\Contracts\Repositories\ActivityEnrollIncomeRepository
     */
    private $activityEnrollIncomeRepository;

    public function __construct(
        TeamService $teamService,
        ActivityService $activityService,
        ActivityEnrollPaymentRepository $activityEnrollPaymentRepository,
        ActivityEnrollIncomeRepository $activityEnrollIncomeRepository
    )
    {
        $this->teamService = $teamService;
        $this->activityService = $activityService;
        $this->activityEnrollPaymentRepository = $activityEnrollPaymentRepository;
        $this->activityEnrollIncomeRepository = $activityEnrollIncomeRepository;
    }

    /**
     * Get team activity income list
     *
     * @param int $teamId   activities in the team id
     * @param int $userId
     * @param int $page     the current page number
     * @param int $pageSize the number of data per page
     *
     * @return array
     */
    public function listActivityEnrollIncomeForTeam($teamId, $userId, $page, $pageSize)
    {
        if ( ! $this->teamService->canManipulate($userId, $teamId)) {
            return [0, []];
        }

        return $this->activityEnrollIncomeRepository
                    ->findAllIncomesByTeam($teamId, $page, $pageSize);
    }

    /**
     * Get activity income list for admin
     */
    public function listActivityEnrollIncome($page, $pageSize, $status = null,
                                            $beginTime = null, $endTime = null)
    {
        if ( ! $endTime) {
            $endTime = date('Y-m-d H:i:s');
        }
        return $this->activityEnrollIncomeRepository
                    ->findAllIncomes($status, $beginTime, $endTime, $page, $pageSize);
    }

    /**
     * Get activity enroll payment details
     *
     * @param int $activityId   payment record in activity id
     * @param int $userId
     * @param int $page         the current page number
     * @param int $pageSize     the number of data per page
     *
     * @return array
     */
    public function listEnrollPaymentsForActivity($activityId, $userId, $page, $pageSize)
    {
        if ( ! $this->activityService->checkActivityOwner($activityId, $userId)) {
            return [0, []];
        }

        return $this->activityEnrollPaymentRepository
                    ->findAllEnrollPaymentsByActivity($activityId, $page, $pageSize);
    }

    /**
     * create a activity enroll income record after a activity be published
     *
     * @param \Jihe\Entities\Activity $activity
     *
     * @return int  income insert id
     */
    public function createIncomeAfterActivityBePublished(Activity $activity)
    {
        return $this->activityEnrollIncomeRepository
                    ->add([
                        'team_id'           => $activity->getTeam()->getId(),
                        'activity_id'       => $activity->getId(),
                        'total_fee'         => 0,
                        'transfered_fee'    => 0,
                        'enroll_end_time'   => $activity->getEnrollEndTime(),
                        'status'            => ActivityEnrollIncome::STATUS_WAIT,
                    ]);
    }

    /**
     * Set income status to tranferring
     *
     * @param int $income   income id
     *
     * @return boolean      indicate whether update option successfully
     */
    public function doIncomeTransfer($income)
    {
        $income = $this->activityEnrollIncomeRepository->findOneById($income);
        if ( ! $income || $income->getStatus() != ActivityEnrollIncome::STATUS_WAIT)
        {
            return false;
        }

        return $this->activityEnrollIncomeRepository
                    ->updateStatus($income->getId(),
                                   ActivityEnrollIncome::STATUS_TRANSFERING);
    }

    /**
     * confirm a transfer operation
     *
     * @param int $income           income id
     * @param int $fee              transfered fee, unit: fen
     * @param string $evidence      url point to the uploaded evidence file
     */
    public function confirmIncomeTransfer($income, $fee, $evidence)
    {
        $income = $this->activityEnrollIncomeRepository->findOneById($income);
        $validStatus = [
            ActivityEnrollIncome::STATUS_WAIT,
            ActivityEnrollIncome::STATUS_TRANSFERING,
        ];
        if ( ! $income || ! in_array($income->getStatus(), $validStatus))
        {
            throw new \Exception('您不能执行确认转账操作');
        }

        $validFee = $income->getTotalFee() - $income->getTransferedFee();
        if ($validFee < $fee) {
            throw new \Exception('您当前转款的金额超出了允许的额度，还剩' .
                                $validFee / 100 . '元可转');
        }

        $status = $this->activityEnrollIncomeRepository
                    ->updateConfirm($income->getId(), $fee, $evidence);
        if ( ! $status) {
            throw new \Exception('执行确认转账操作失败');
        }
    }

    /**
     * Set income status to finished
     *
     * @param int $income   income id
     *
     * @return boolean      indicate whether update option successfully
     *
     */
    public function finishIncomeTransfer($income)
    {
        $income = $this->activityEnrollIncomeRepository->findOneById($income);
        if ( ! $income || $income->getStatus() != ActivityEnrollIncome::STATUS_TRANSFERING)
        {
            return false;
        }

        return $this->activityEnrollIncomeRepository
                    ->updateStatus($income->getId(),
                                   ActivityEnrollIncome::STATUS_FINISHED);
    }
}
