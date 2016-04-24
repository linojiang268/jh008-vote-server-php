<?php
namespace Jihe\Contracts\Repositories;

interface ActivityEnrollPaymentRepository
{
    /**
     * Get all enroll payments of a activity
     *
     * @param int $activityId
     * @param int $page         the current page number
     * @param int $pageSize     the number of data per page
     *
     * @return array
     */
    public function findAllEnrollPaymentsByActivity($activityId, $page, $pageSize);

    /**
     * add activityEnrollPayment
     *
     * @param array $activityEnrollPayment  According to the structure of the
     *                                      activity_enroll_payments table, the
     *                                      data is assigned to the corresponding
     *                                      field
     *                                      Example:
     *                                      $activityEnrollPayment = [
     *                                                  'activity_id'   => 1,
     *                                                  'user_id'       => 1,
     *                                                  'order_no'      => 1,
     *                                                  'fee'           => 10,
     *                                                  'channel'       => 1,
     *                                      ]
     *
     * @return int  insert id
     */
    public function add(array $activityEnrollPayment);

    /**
     * find one payment and lock by order#
     *
     * @param string $orderNo
     * @param boolean   $lock   payment will be locked if $lock is true
     *
     * @return \Jihe\Entities\ActivityEnrollPayment|null
     */
    public function findOneByOrderNo($orderNo, $lock = false);

    /**
     * update payment status by orderNo
     *
     * @param string $orderNo
     * @param int $status
     * @param string $tradeNo           payment trade#
     * @param string|DateTime $payedAt  payment time
     *
     * @return int              affected nums
     */
    public function updateAfterPayed($orderNo, $status, $tradeNo, $payedAt);

    /**
     * update payment by id
     *
     * @param integer $payment      payment id
     * @param array   $updates      datas will be update
     *
     * @return boolean
     */
    public function updateById($payment, array $updates);
}
