<?php
namespace intg\Jihe\Controllers\Backstage;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use intg\Jihe\TestCase;
use intg\Jihe\RequestSignCheck;
use intg\Jihe\AuthDeviceCheck;

class FinanceControllerTest extends TestCase
{
    use DatabaseTransactions;
    use RequestSignCheck;
    use AuthDeviceCheck;

    //=========================================
    //      listActivityEnrollIncomeForTeam
    //=========================================
    public function testListActivityEnrollIncomeForTeam_Empty()
    {
        factory(\Jihe\Models\Team::class)->create([
            'id'         => 1,
            'city_id'    => 1,
            'creator_id' => 1,
        ]);
        factory(\Jihe\Models\Activity::class)->create([
            'id'        => 1,
            'city_id'   => 1,
            'team_id'   => 1,
        ]);
        $user = factory(\Jihe\Models\User::class)->create([
            'id'        => 1,
            'mobile'    => '13800138000'
        ]);
        factory(\Jihe\Models\City::class)->create([
            'id'        => 1,
        ]);
        $this->startSession();

        $this->actingAs($user)->ajaxGet('/community/finance/enrollment/income');

        $response = json_decode($this->response->getContent());
        self::assertEquals(0, $response->code);
        self::assertEquals(0, $response->total);
        self::assertEmpty($response->incomes);
    }

    public function testListActivityEnrollIncomeForTeam_NotEmpty()
    {
        factory(\Jihe\Models\Team::class)->create([
            'id'         => 1,
            'city_id'    => 1,
            'creator_id' => 1,
        ]);
        factory(\Jihe\Models\Activity::class)->create([
            'id'            => 1,
            'city_id'       => 1,
            'team_id'       => 1,
            'title'         => '山花节跑步',
            'begin_time'    => '2015-07-20 10:00:00',
            'end_time'      => '2015-07-20 15:30:00',
        ]);
        $user = factory(\Jihe\Models\User::class)->create([
            'id'        => 1,
            'mobile'    => '13800138000'
        ]);
        factory(\Jihe\Models\ActivityEnrollIncome::class)->create([
            'team_id'                   => 1,
            'activity_id'               => 1,
            'financial_action_result'   => [
                                [ time(), 10, 'http://domain/res1.jpg'],
                            ],
            'status'                    => \Jihe\Entities\ActivityEnrollIncome::STATUS_TRANSFERING,
        ]);
        factory(\Jihe\Models\City::class)->create([
            'id'        => 1,
        ]);
        $this->startSession();

        $this->actingAs($user)->ajaxGet('/community/finance/enrollment/income?team=1');

        $response = json_decode($this->response->getContent());
        self::assertEquals(0, $response->code);
        self::assertEquals(1, $response->total);
        self::assertCount(1, $response->incomes);

        $income = $response->incomes[0];
        self::assertObjectHasAttribute('id', $income);
        self::assertObjectHasAttribute('activity', $income);
        self::assertObjectHasAttribute('financial_action_result', $income);
        self::assertEquals(1, $income->team->id);
        self::assertEquals(1, $income->activity->id);
        self::assertEquals('山花节跑步', $income->activity->title);
        self::assertEquals('2015-07-20 10:00:00', $income->activity->begin_time);
        self::assertEquals('2015-07-20 15:30:00', $income->activity->end_time);
        self::assertEquals('http://domain/res1.jpg', $income->financial_action_result[0][2]);
    }

    //=========================================
    //      listPaymentsForActivity
    //=========================================
    public function testListPaymentsForActivity_Empty()
    {
        factory(\Jihe\Models\Team::class)->create([
            'id'         => 1,
            'city_id'    => 1,
            'creator_id' => 1,
        ]);
        factory(\Jihe\Models\Activity::class)->create([
            'id'            => 1,
            'city_id'   => 1,
            'team_id'       => 1,
            'title'         => '山花节跑步',
        ]);
        $user = factory(\Jihe\Models\User::class)->create([
            'id'        => 1,
            'mobile'    => '13800138000',
        ]);
        factory(\Jihe\Models\City::class)->create([
            'id'        => 1,
        ]);
        
        $this->startSession();

        $this->actingAs($user)->ajaxGet('/community/finance/enrollment/payment?activity=1');

        $response = json_decode($this->response->getContent());
        self::assertEquals(0, $response->code);
        self::assertEquals(0, $response->total);
        self::assertEmpty($response->payments);
    }

    public function testListPaymentsForActivity_NotEmpty()
    {
        factory(\Jihe\Models\Team::class)->create([
            'id'         => 1,
            'city_id'    => 1,
            'creator_id' => 1,
        ]);
        factory(\Jihe\Models\Activity::class)->create([
            'id'        => 1,
            'team_id'   => 1,
            'city_id'   => 1,
            'title'     => '山花节跑步',
        ]);
        $user = factory(\Jihe\Models\User::class)->create([
            'id'        => 1,
            'mobile'    => '13800138000',
        ]);
        factory(\Jihe\Models\User::class)->create([
            'id'        => 2,
            'mobile'    => '13500135000',
        ]);
        factory(\Jihe\Models\ActivityEnrollPayment::class)->create([
            'activity_id'   => 1,
            'user_id'       => 2,
            'fee'           => 100,
            'payed_at'      => '2015-07-23 09:00:00',
            'channel'       => 1,
            'order_no'      => '1234567890',
            'trade_no'      => 'ali2015xxxxxx',
        ]);
        factory(\Jihe\Models\City::class)->create([
            'id'        => 1,
        ]);
        
        $this->startSession();

        $this->actingAs($user)->ajaxGet('/community/finance/enrollment/payment?activity=1');

        $response = json_decode($this->response->getContent());
        self::assertEquals(0, $response->code);
        self::assertEquals(1, $response->total);
        self::assertCount(1, $response->payments);

        $payment = $response->payments[0];
        self::assertObjectHasAttribute('id', $payment);
        self::assertEquals(1, $payment->activity->id);
        self::assertEquals(100, $payment->fee);
        self::assertEquals('2015-07-23 09:00:00', $payment->payed_at);
        self::assertEquals(2, $payment->user->id);
        self::assertEquals('13500135000', $payment->user->mobile);
        self::assertEquals(1, $payment->channel);
        self::assertEquals('1234567890', $payment->order_no);
        self::assertEquals('ali2015xxxxxx', $payment->trade_no);
    }
}
