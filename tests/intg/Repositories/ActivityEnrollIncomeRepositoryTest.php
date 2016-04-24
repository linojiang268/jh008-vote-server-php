<?php
namespace intg\Jihe\Repositories;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use intg\Jihe\TestCase;

class ActivityEnrollIncomeRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    //===============================================
    //          add
    //===============================================
    public function testAddSuccessfully()
    {
        $id = $this->getRepository()->add([
            'team_id'           => 1,
            'activity_id'       => 2,
            'total_fee'         => 0,
            'transfered_fee'    => 0,
            'enroll_end_time'   => '2015-08-20 10:20:00',
            'status'            => 1,
        ]);

        $this->assertInternalType('integer', $id);

        $this->seeInDatabase('activity_enroll_incomes', [
            'activity_id'   => 2,
            'total_fee'     => 0,
            'status'        => 1,
        ]);
    }

    //===============================================
    //          findAllIncomesByTeam
    //===============================================
    public function testFindAllIncomesByTeamFound()
    {
        factory(\Jihe\Models\Team::class)->create([
            'id'    => 1,
        ]);
        $activity = factory(\Jihe\Models\Activity::class)->create([
            'id'        => 1,
            'team_id'   => 1,
        ]);
        $opTimestamp = time();
        factory(\Jihe\Models\ActivityEnrollIncome::class)->create([
            'team_id' => 1,
            'activity_id' => $activity->id,
            'financial_action_result' => [
                    [$opTimestamp, 20, 'http://domain/res1.jpg'],
            ],
        ]);
        list($total, $incomes) = $this->getRepository()->findAllIncomesByTeam(1, 1, 1);
        self::assertEquals(1, $total);
        self::assertEquals(1, $incomes[0]->getTeam()->getId());
        self::assertEquals(1, $incomes[0]->getActivity()->getId());
        $financialActionResult = $incomes[0]->getFinancialActionResult();
        self::assertEquals($opTimestamp, $financialActionResult[0][0]);
        self::assertEquals('20', $financialActionResult[0][1]);
        self::assertEquals('http://domain/res1.jpg', $financialActionResult[0][2]);
    }

    public function testFindAllIncomesByTeamWrongActionResultFormat()
    {
        factory(\Jihe\Models\Team::class)->create([
            'id'    => 1,
        ]);
        $activity = factory(\Jihe\Models\Activity::class)->create([
            'id'        => 1,
            'team_id'   => 1,
        ]);
        try {
            factory(\Jihe\Models\ActivityEnrollIncome::class)->create([
                'team_id' => 1,
                'activity_id' => $activity->id,
                'financial_action_result' => [
                        [12345, 10, 'http://domain/res1.jpg'],
                ],
            ]);
            list($total, $incomes) = $this->getRepository()->findAllIncomesByTeam(1, 1, 1);
        } catch (\Exception $ex) {
            self::assertEquals('field financial_action_result format error', $ex->getMessage());
        }
    }

    public function testFindAllIncomesByTeamNotFound()
    {
        list($total, $incomes) = $this->getRepository()->findAllIncomesByTeam(1, 1, 1);
        self::assertEquals(0, $total);
        self::assertEmpty(0, $incomes);
    }

    //===============================================
    //          increaseTotalFeeForPaymentSuccess
    //===============================================
    public function testIncreaseTotalFeeForPaymentSuccess()
    {
        factory(\Jihe\Models\Team::class)->create([
            'id'    => 1,
        ]);
        $activity = factory(\Jihe\Models\Activity::class)->create([
            'id'        => 1,
            'team_id'   => 1,
        ]);
        factory(\Jihe\Models\ActivityEnrollIncome::class)->create([
            'team_id'       => 1,
            'activity_id'   => 1,
            'total_fee'     => 100,
        ]);

        $this->getRepository()->increaseTotalFeeForPaymentSuccess(1, 50);

        $this->seeInDatabase('activity_enroll_incomes', [
            'team_id'       => 1,
            'activity_id'   => 1,
            'total_fee'     => 150,     // changed
        ]);
    }

    //===============================================
    //          findOneByActivityId
    //===============================================
    public function  testFindOneByActivityIdFound()
    {
        factory(\Jihe\Models\Team::class)->create([
            'id'    => 1,
        ]);
        $activity = factory(\Jihe\Models\Activity::class)->create([
            'id'        => 1,
            'team_id'   => 1,
        ]);
        factory(\Jihe\Models\ActivityEnrollIncome::class)->create([
            'team_id'       => 1,
            'activity_id'   => 1,
            'total_fee'     => 100,
        ]);

        $income = $this->getRepository()->findOneByActivityId(1);
        self::assertEquals(1, $income->getActivity()->getId());
        self::assertEquals(1, $income->getTeam()->getId());
        self::assertEquals(100, $income->getTotalFee());
    }

    public function testFindOneByActivityIdNotFound()
    {
        $income = $this->getRepository()->findOneByActivityId(1);
        self::assertEquals(null, $income);
    }

    //===============================================
    //          findAllIncomes
    //===============================================
    public function testFindAllIncomesFound()
    {
        factory(\Jihe\Models\Team::class)->create([
            'id'    => 1,
        ]);
        $activity = factory(\Jihe\Models\Activity::class)->create([
            'id' => 1,
            'enroll_end_time'   => '2015-07-28 10:00:00',
        ]);
        $opTimestamp = time();
        $status = \Jihe\Entities\ActivityEnrollIncome::STATUS_TRANSFERING;
        factory(\Jihe\Models\ActivityEnrollIncome::class)->create([
            'team_id'                   => 1,
            'activity_id'               => $activity->id,
            'financial_action_result'   => [
                    [$opTimestamp, 20, 'http://domain/res1.jpg'],
            ],
            'transfered_fee'            => 20,
            'enroll_end_time'           => $activity->enroll_end_time,
            'status'                    => $status,
        ]);
        list($total, $incomes) = $this->getRepository()
            ->findAllIncomes($status, '2015-07-28', '2015-07-29', 1, 1);
        self::assertEquals(1, $total);
        self::assertEquals(1, $incomes[0]->getTeam()->getId());
        self::assertEquals(1, $incomes[0]->getActivity()->getId());
        $financialActionResult = $incomes[0]->getFinancialActionResult();
        self::assertEquals($opTimestamp, $financialActionResult[0][0]);
        self::assertEquals('20', $financialActionResult[0][1]);
        self::assertEquals('http://domain/res1.jpg', $financialActionResult[0][2]);
    }

    public function testFindAllIncomesNotFound()
    {
        $activity = factory(\Jihe\Models\Activity::class)->create([
            'id' => 1,
            'enroll_end_time'   => '2015-07-28 10:00:00',
        ]);
        $opTimestamp = time();
        $status = \Jihe\Entities\ActivityEnrollIncome::STATUS_TRANSFERING;
        factory(\Jihe\Models\ActivityEnrollIncome::class)->create([
            'team_id'                   => 1,
            'activity_id'               => $activity->id,
            'financial_action_result'   => [
                    [$opTimestamp, 20, 'http://domain/res1.jpg'],
            ],
            'transfered_fee'            => 20,
            'enroll_end_time'           => $activity->enroll_end_time,
            'status'                    => $status,
        ]);
        list($total, $incomes) = $this->getRepository()
            ->findAllIncomes($status, '2015-07-29', '2015-07-29', 1, 1);
        self::assertEquals(0, $total);
        self::assertEmpty($incomes);
    }

    //===============================================
    //          updateConfirm
    //===============================================
    public function testUpdateConfirmSuccessfully()
    {
        $activity = factory(\Jihe\Models\Activity::class)->create([
            'id' => 1,
            'enroll_end_time'   => '2015-07-28 10:00:00',
        ]);
        $status = \Jihe\Entities\ActivityEnrollIncome::STATUS_TRANSFERING;
        factory(\Jihe\Models\ActivityEnrollIncome::class)->create([
            'id'                        => 1,
            'team_id'                   => 1,
            'activity_id'               => $activity->id,
            'financial_action_result'   => [
                    [strtotime('2015-08-01 10:00:00'), 20, 'http://domain/res1.jpg'],
            ],
            'total_fee'                 => 100,
            'transfered_fee'            => 20,
            'enroll_end_time'           => $activity->enroll_end_time,
            'status'                    => $status,
        ]);

        $res = $this->getRepository()->updateConfirm(1, 40, 'http://domain/evidence.jpg');
        self::assertEquals(true, $res);

        $income = $this->getRepository()->findOneById(1);
        self::assertEquals(60, $income->getTransferedFee());
        $financialActionResult = $income->getFinancialActionResult();
        self::assertCount(2, $financialActionResult);
        self::assertEquals(40, $financialActionResult[1][1]);
        self::assertEquals('http://domain/evidence.jpg', $financialActionResult[1][2]);
    }

    public function testUpdateConfirmIncomeNotExists()
    {
        $res = $this->getRepository()->updateConfirm(1, 40, 'http://domain/evidence.jpg');
        self::assertEquals(false, $res);
    }

    //===============================================
    //          countActivitiesAmount
    //===============================================
    public function testCountActivitiesAmount()
    {
        factory(\Jihe\Models\Activity::class)->create(['id' => 1]);
        factory(\Jihe\Models\Activity::class)->create(['id' => 2]);
        factory(\Jihe\Models\Activity::class)->create(['id' => 3]);
        factory(\Jihe\Models\Activity::class)->create(['id' => 4]);

        factory(\Jihe\Models\ActivityEnrollIncome::class)->create([
            'id'                        => 1,
            'activity_id'               => 1,
            'total_fee'                 => 1000,
        ]);
        factory(\Jihe\Models\ActivityEnrollIncome::class)->create([
            'id'                        => 2,
            'activity_id'               => 2,
            'total_fee'                 => 800,
        ]);
        factory(\Jihe\Models\ActivityEnrollIncome::class)->create([
            'id'                        => 3,
            'activity_id'               => 3,
            'total_fee'                 => 10000,
        ]);
        factory(\Jihe\Models\ActivityEnrollIncome::class)->create([
            'id'                        => 4,
            'activity_id'               => 4,
            'total_fee'                 => 0,
        ]);

        $res = $this->getRepository()->countActivitiesAmount([1,2,3,4]);
        self::assertEquals(1000, $res[1]);
        self::assertEquals(800, $res[2]);
        self::assertEquals(10000, $res[3]);
        self::assertEquals(0, $res[4]);
    }

    /**
     * @return \Jihe\Contracts\Repositories\ActivityEnrollIncomeRepository
     */
    private function getRepository()
    {
        return $this->app[\Jihe\Contracts\Repositories\ActivityEnrollIncomeRepository::class];
    }
}
