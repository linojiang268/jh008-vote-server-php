<?php
namespace intg\Jihe\Repositories;

use \PHPUnit_Framework_Assert as Assert;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use intg\Jihe\TestCase;
use Jihe\Entities\ActivityEnrollPayment;

class ActivityEnrollPaymentRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    //==============================================
    //          findAllEnrollPaymentsByActivity
    //==============================================
    public function testFindAllEnrollPaymentsByActivityFound()
    {
        $activity = factory(\Jihe\Models\Activity::class)->create([
            'id' => 1,
        ]);
        $user = factory(\Jihe\Models\User::class)->create([
            'id' => 1,
            'mobile' => '13800138000'
        ]);
        factory(\Jihe\Models\ActivityEnrollPayment::class)->create([
            'activity_id'   => $activity->id,
            'user_id'       => $user->id,
        ]);
        list($total, $payments) = $this->getRepository()->findAllEnrollPaymentsByActivity(1, 1, 1);
        self::assertEquals(1, $total);
        self::assertEquals(1, $payments[0]->getActivity()->getId());
        self::assertEquals('13800138000', $payments[0]->getUser()->getMobile());
        self::assertEquals(2, $payments[0]->getStatus());
    }

    public function testFindAllEnrollPaymentsByActivityNotFound()
    {
        list($total, $payments) = $this->getRepository()->findAllEnrollPaymentsByActivity(1, 1, 1);
        self::assertEquals(0, $total);
        self::assertEmpty(0, $payments);
    }

    public function testFindAllEnrollPaymentsByActivityNotFoundSuccessRecords()
    {
        factory(\Jihe\Models\ActivityEnrollPayment::class)->create([
            'activity_id' => 1,
            'status' => 0,
        ]);
        list($total, $payments) = $this->getRepository()->findAllEnrollPaymentsByActivity(1, 1, 1);
        self::assertEquals(0, $total);
        self::assertEmpty(0, $payments);
    }

    //====================================================
    //          add
    //====================================================
    public function testAdd()
    {
        $orderNo = str_random(32);
        Assert::assertInternalType("int", $this->getRepository()->add([
            'activity_id'   => 1,
            'user_id'       => 1,
            'fee'           => 10,
            'order_no'      => $orderNo,
            'channel'       => 2,
        ]));
        $this->seeInDatabase('activity_enroll_payments', [
            'user_id'       => 1,
            'activity_id'   => 1,
            'fee'           => 10,
            'channel'       => 2,
            'order_no'      => $orderNo,
            'status'        => ActivityEnrollPayment::STATUS_WAIT,
        ]);
    }

    //======================================================
    //          findOneByOrderNo
    //======================================================
    public function testFindOneByOrderNoWithoutLockFound()
    {
        $activity = factory(\Jihe\Models\Activity::class)->create([
            'id' => 1,
        ]);
        $user = factory(\Jihe\Models\User::class)->create([
            'id' => 1,
            'mobile' => '13800138000'
        ]);
        $payment = factory(\Jihe\Models\ActivityEnrollPayment::class)->create([
            'activity_id'   => $activity->id,
            'user_id'       => $user->id,
            'status'        => ActivityEnrollPayment::STATUS_WAIT,
        ]);
        $orderNo = $payment->order_no;
        $payment = $this->getRepository()->findOneByOrderNo($orderNo);
        self::assertEquals('1', $payment->getActivity()->getId());
        self::assertEquals('1', $payment->getUser()->getId());
        self::assertEquals(0, $payment->getStatus());
    }

    public function testFindOneByOrderNoWithLockFound()
    {
        $activity = factory(\Jihe\Models\Activity::class)->create([
            'id' => 1,
        ]);
        $user = factory(\Jihe\Models\User::class)->create([
            'id' => 1,
            'mobile' => '13800138000'
        ]);
        $payment = factory(\Jihe\Models\ActivityEnrollPayment::class)->create([
            'activity_id'   => $activity->id,
            'user_id'       => $user->id,
            'status'        => ActivityEnrollPayment::STATUS_WAIT,
        ]);
        $orderNo = $payment->order_no;
        $payment = $this->getRepository()->findOneByOrderNo($orderNo, true);
        self::assertEquals('1', $payment->getActivity()->getId());
        self::assertEquals('1', $payment->getUser()->getId());
        self::assertEquals(0, $payment->getStatus());
    }

    //==========================================================
    //              updateAfterPayed
    //==========================================================
    public function testUpdateAfterPayed()
    {
        $orderNo = 'T8986DC81A8CAAAAEEB9D219B68D6ZIP';
        factory(\Jihe\Models\ActivityEnrollPayment::class)->create([
            'activity_id'   => 1,
            'user_id'       => 1,
            'order_no'      => $orderNo,
            'trade_no'      => '',
            'payed_at'      => null,
            'status'        => ActivityEnrollPayment::STATUS_WAIT,
        ]);

        $affectedNums = $this->getRepository()
                            ->updateAfterPayed($orderNo, ActivityEnrollPayment::STATUS_SUCCESS,
                                                '1217752501201407033233368018',
                                                '20150728100256');
        self::assertEquals(1, $affectedNums);

        $this->seeInDatabase('activity_enroll_payments', [
            'activity_id'   => 1,
            'user_id'       => 1,
            'order_no'      => $orderNo,
            'trade_no'      => '1217752501201407033233368018',          // changed
            'payed_at'      => '2015-07-28 10:02:56',                   // changed
            'status'        => ActivityEnrollPayment::STATUS_SUCCESS,   // changed
        ]);
    }

    /**
     * @return \Jihe\Contracts\Repositories\ActivityEnrollPaymentRepository
     */
    private function getRepository()
    {
        return $this->app[\Jihe\Contracts\Repositories\ActivityEnrollPaymentRepository::class];
    }
}
