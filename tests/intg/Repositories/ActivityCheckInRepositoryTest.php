<?php
namespace intg\Jihe\Repositories;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use intg\Jihe\TestCase;
use Jihe\Entities\Activity as ActivityEntity;
use Jihe\Models\Activity;

class ActivityCheckInRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    //===============================================
    //          getAllByActivityAndStep
    //===============================================
    public function testGetAllByActivityAndStepSuccessfully()
    {
        $this->prepareUserData();
        $this->prepareCheckInData();

        list($total, $checkIns) = $this->getRepository()
                ->getAllByActivityAndStep(1, 1, 1, 5);
        self::assertEquals(2, $total);
        self::assertCount(2, $checkIns);
        self::assertArrayHasKey('nick_name', $checkIns[0]);
        self::assertArrayHasKey('mobile', $checkIns[0]);
        self::assertArrayHasKey('user_id', $checkIns[0]);
        self::assertArrayHasKey('step', $checkIns[0]);
        self::assertInstanceOf(\DateTime::class, $checkIns[0]['created_at']);
    }

    public function testGetAllByActivityAndStep_NotFound()
    {
        list($total, $checkIns) = $this->getRepository()
                ->getAllByActivityAndStep(1, 1, 1, 5);
        self::assertEquals(0, $total);
        self::assertCount(0, $checkIns);
    }

    private function prepareUserData()
    {
        factory(\Jihe\Models\User::class)->create([
            'id'            => 1,
            'mobile'        => '13800138001',
            'nick_name'     => 'zhangsan',
            'created_at'    => date('Y-m-d H:i:s', time('-1 day')),
        ]);
        factory(\Jihe\Models\User::class)->create([
            'id'        => 2,
            'mobile'    => '13800138002',
            'nick_name' => 'lisi',
        ]);
    }

    private function prepareCheckInData()
    {
        factory(\Jihe\Models\ActivityCheckIn::class)->create([
            'activity_id'   => 1,
            'user_id'       => 1,
            'step'          => 1,
        ]);
        factory(\Jihe\Models\ActivityCheckIn::class)->create([
            'activity_id'   => 1,
            'user_id'       => 2,
            'step'          => 1,
        ]);
        factory(\Jihe\Models\ActivityCheckIn::class)->create([
            'activity_id'   => 1,
            'user_id'       => 1,
            'step'          => 2,
        ]);
    }
    //===============================================
    //          countActivityCheckIn
    //===============================================
    public function testCountActivityCheckIn()
    {
        factory(\Jihe\Models\ActivityCheckIn::class)->create([
            'activity_id'   => 1,
            'user_id'       => 1,
            'step'          => 1,
        ]);
        factory(\Jihe\Models\ActivityCheckIn::class)->create([
            'activity_id'   => 1,
            'user_id'       => 2,
            'step'          => 1,
        ]);
        factory(\Jihe\Models\ActivityCheckIn::class)->create([
            'activity_id'   => 1,
            'user_id'       => 3,
            'step'          => 2,
        ]);
        factory(\Jihe\Models\ActivityCheckIn::class)->create([
            'activity_id'   => 2,
            'user_id'       => 1,
            'step'          => 1,
        ]);
        factory(\Jihe\Models\ActivityCheckIn::class)->create([
            'activity_id'   => 3,
            'user_id'       => 2,
            'step'          => 1,
        ]);
        factory(\Jihe\Models\ActivityCheckIn::class)->create([
            'activity_id'   => 3,
            'user_id'       => 3,
            'step'          => 2,
        ]);
        factory(Activity::class)->create([
            'id'       => 1,
            'team_id'  => 1,
            'city_id'  => 1,
            'title'    => '已发布测试活动－－_\%皮划艇1',
            'status'   => ActivityEntity::STATUS_PUBLISHED,
        ]);
        factory(Activity::class)->create([
            'id'       => 2,
            'team_id'  => 1,
            'city_id'  => 1,
            'title'    => '已发布测试活动－－_\%皮划艇2',
            'status'   => ActivityEntity::STATUS_PUBLISHED,
        ]);
        factory(Activity::class)->create([
            'id'       => 3,
            'team_id'  => 1,
            'city_id'  => 1,
            'title'    => '已发布测试活动－－_\%皮划艇3',
            'status'   => ActivityEntity::STATUS_PUBLISHED,
        ]);
        $ret = $this->getRepository()->countActivityCheckIn([1,2,3]);
        self::assertEquals(2, $ret[1]);
        self::assertEquals(1, $ret[2]);
        self::assertEquals(1, $ret[3]);
    }


    /**
     * @return \Jihe\Contracts\Repositories\ActivityEnrollIncomeRepository
     */
    private function getRepository()
    {
        return $this->app[\Jihe\Contracts\Repositories\ActivityCheckInRepository::class];
    }
}
