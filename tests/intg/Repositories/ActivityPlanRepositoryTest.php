<?php
namespace intg\Jihe\Repositories;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use intg\Jihe\TestCase;
use Jihe\Models\ActivityPlan;

class ActivityPlanRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    //===========================================
    //            add
    //===========================================
    public function testAdd_ActivityPlanExists()
    {
        $activityPlan = [
            'activity_id' => 1,
            'begin_time'  => date('Y-m-d H:i:s', strtotime('240 seconds')),
            'end_time'    => date('Y-m-d H:i:s', strtotime('1440 seconds')),
            'plan_text'   => '东方热哦陪外婆玩儿玩儿问',
        ];
        $id = $this->getRepository()->add($activityPlan);
        self::seeInDatabase('activity_plan', ['plan_text'   => '东方热哦陪外婆玩儿玩儿问', 'id' => $id]);
    }

    //===========================================
    //            updateOnce
    //===========================================
    public function testUpdateOnce_ActivityPlanExists()
    {
        factory(ActivityPlan::class)->create([
            'id'          => 1,
            'activity_id' => 1,
            'begin_time'  => date('Y-m-d H:i:s', strtotime('240 seconds')),
            'end_time'    => date('Y-m-d H:i:s', strtotime('1440 seconds')),
            'plan_text'   => '东方热哦陪外婆玩儿玩儿问',
        ]);

        $this->getRepository()->updateOnce(1, ['activity_id' => 2, 'plan_text' => '夜色']);
        self::seeInDatabase('activity_plan', ['plan_text' => '夜色', 'activity_id' => 2]);
    }

    //===========================================
    //            updateMultiple
    //===========================================
    public function testUpdateMultiple_ActivityPlanExists()
    {
        factory(ActivityPlan::class)->create([
            'id'          => 1,
            'activity_id' => 1,
            'begin_time'  => date('Y-m-d H:i:s', strtotime('240 seconds')),
            'end_time'    => date('Y-m-d H:i:s', strtotime('1440 seconds')),
            'plan_text'   => '东方热哦陪外婆玩儿玩儿问',
        ]);
        factory(ActivityPlan::class)->create([
            'id'          => 2,
            'activity_id' => 1,
            'begin_time'  => date('Y-m-d H:i:s', strtotime('480 seconds')),
            'end_time'    => date('Y-m-d H:i:s', strtotime('2880 seconds')),
            'plan_text'   => '东方',
        ]);

        $this->getRepository()->updateMultiple(['activity_id' => ['=', 1]], ['plan_text' => '夜色']);
        self::seeInDatabase('activity_plan', ['id' => 1, 'plan_text' => '夜色', 'activity_id' => 1]);
        self::seeInDatabase('activity_plan', ['id' => 2, 'plan_text' => '夜色', 'activity_id' => 1]);
    }

    //===========================================
    //            deleteActivityPlanById
    //===========================================
    public function testDeleteActivityPlanById_ActivityPlanExists()
    {
        factory(ActivityPlan::class)->create([
            'id'          => 1,
            'activity_id' => 1,
            'begin_time'  => date('Y-m-d H:i:s', strtotime('240 seconds')),
            'end_time'    => date('Y-m-d H:i:s', strtotime('1440 seconds')),
            'plan_text'   => '东方热哦陪外婆玩儿玩儿问',
        ]);

        $this->getRepository()->deleteActivityPlanById(1);
        $ret = $this->getRepository()->findActivityPlanById(1);

        self::assertEquals(0, count($ret));
    }
    //===========================================
    //       deleteActivityPlanByActivityId
    //===========================================
    public function testDeleteActivityPlanByActivityId_ActivityPlanExists()
    {
        factory(ActivityPlan::class)->create([
            'id'          => 1,
            'activity_id' => 1,
            'begin_time'  => date('Y-m-d H:i:s', strtotime('240 seconds')),
            'end_time'    => date('Y-m-d H:i:s', strtotime('1440 seconds')),
            'plan_text'   => '东方热哦陪外婆玩儿玩儿问',
        ]);
        factory(ActivityPlan::class)->create([
            'id'          => 2,
            'activity_id' => 1,
            'begin_time'  => date('Y-m-d H:i:s', strtotime('480 seconds')),
            'end_time'    => date('Y-m-d H:i:s', strtotime('2880 seconds')),
            'plan_text'   => '东方',
        ]);

        $this->getRepository()->deleteActivityPlanByActivityId(1);
        $ret = $this->getRepository()->findActivityPlanById(1);
        self::assertEquals(0, count($ret));
        $ret = $this->getRepository()->findActivityPlanById(2);
        self::assertEquals(0, count($ret));
    }

    //===========================================
    //       findActivityPlanById
    //===========================================
    public function testFindActivityPlanById_ActivityPlanExists()
    {
        factory(ActivityPlan::class)->create([
            'id'          => 1,
            'activity_id' => 1,
            'begin_time'  => date('Y-m-d H:i:s', strtotime('240 seconds')),
            'end_time'    => date('Y-m-d H:i:s', strtotime('1440 seconds')),
            'plan_text'   => '东方热哦陪外婆玩儿玩儿问',
        ]);
        $ret = $this->getRepository()->findActivityPlanById(1);
        self::assertEquals(1, count($ret));
    }

    //===========================================
    //       findActivityPlanByActivityId
    //===========================================
    public function testFindActivityPlanByActivityId_ActivityPlanExists()
    {
        factory(ActivityPlan::class)->create([
            'id'          => 1,
            'activity_id' => 1,
            'begin_time'  => date('Y-m-d H:i:s', strtotime('240 seconds')),
            'end_time'    => date('Y-m-d H:i:s', strtotime('1440 seconds')),
            'plan_text'   => '东方热哦陪外婆玩儿玩儿问',
        ]);
        factory(ActivityPlan::class)->create([
            'id'          => 2,
            'activity_id' => 1,
            'begin_time'  => date('Y-m-d H:i:s', strtotime('480 seconds')),
            'end_time'    => date('Y-m-d H:i:s', strtotime('2880 seconds')),
            'plan_text'   => '东方',
        ]);
        $ret = $this->getRepository()->findActivityPlanByActivityId(1);
        self::assertEquals(2, count($ret));
    }


    /**
     * @return \Jihe\Contracts\Repositories\ActivityPlanRepository
     */
    private function getRepository()
    {
        return $this->app[\Jihe\Contracts\Repositories\ActivityPlanRepository::class];
    }

}