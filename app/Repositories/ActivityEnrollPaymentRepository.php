<?php
namespace Jihe\Repositories;

use Jihe\Contracts\Repositories\ActivityEnrollPaymentRepository as ActivityEnrollPaymentRepositoryContract;
use Jihe\Models\ActivityEnrollPayment;
use Jihe\Entities\ActivityEnrollPayment as ActivityEnrollPaymentEntity;
use Jihe\Utils\PaginationUtil;

class ActivityEnrollPaymentRepository implements ActivityEnrollPaymentRepositoryContract
{
    /**
     * (non-PHPdoc)
     *
     * @see \Jihe\Contracts\Repositories\ActivityEnrollPaymentRepository::findAllEnrollPaymentsByActivity()
     */
    public function findAllEnrollPaymentsByActivity($activityId, $page, $pageSize)
    {
        $query = ActivityEnrollPayment::with('user', 'activity')
                    ->where('activity_id', $activityId)
                    ->where('status', ActivityEnrollPaymentEntity::STATUS_SUCCESS)
                    ->orderBy('created_at', 'desc');
        $count = $query->count();
        $page = PaginationUtil::genValidPage($page, $count, $pageSize);
        $payments = $query->forPage($page, $pageSize)->get()->all();
        $payments = array_map([ $this, 'convertToEntity' ], $payments);

        return [$count, $payments];
    }

    /**
     * (non-PHPdoc)
     *
     * @see \Jihe\Contracts\Repositories\ActivityEnrollPaymentRepository::add()
     */
    public function add(array $activityEnrollPayment)
    {
        $activityEnrollPayment['status'] = ActivityEnrollPaymentEntity::STATUS_WAIT;
        return ActivityEnrollPayment::create($activityEnrollPayment)->id;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \Jihe\Contracts\Repositories\ActivityEnrollPaymentRepository::findOneByOrderNo()
     */
    public function findOneByOrderNo($orderNo, $lock = false)
    {
        $query = ActivityEnrollPayment::where('order_no', $orderNo);
        if ($lock) {
            $query->lockForUpdate();
        }
        $payment = $query->get()->first();
        return $this->convertToEntity($payment);
    }

    /**
     * (non-PHPdoc)
     *
     * @see \Jihe\Contracts\Repositories\ActivityEnrollPaymentRepository::updateStatus()
     */
    public function updateAfterPayed($orderNo, $status, $tradeNo, $payedAt)
    {
        return ActivityEnrollPayment::where('order_no', $orderNo)
                                    ->update([
                                        'status'    => $status,
                                        'trade_no'  => $tradeNo,
                                        'payed_at'  => $payedAt,
                                    ]);
    }

    /**
     * (non-PHPdoc)
     *
     * @see \Jihe\Contracts\Repositories\ActivityEnrollPaymentRepository::updateById()
     */
    public function updateById($payment, array $updates)
    {
        return ActivityEnrollPayment::where('id', $payment)->update($updates);
    }

    /**
     * (non-PHPdoc)
     *
     * @return \Jihe\Entities\ActivityEnrollPayment|null
     */
    private function convertToEntity($activityEnrollPayment)
    {
        return $activityEnrollPayment ? $activityEnrollPayment->toEntity() : null;
    }


}
