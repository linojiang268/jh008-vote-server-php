<?php
namespace intg\Jihe\Repositories;

use intg\Jihe\TestCase;
use Jihe\Entities\Activity as ActivityEntity;
use Jihe\Entities\ActivityApplicant as ActivityApplicantEntity;
use Jihe\Models\Activity;
use Jihe\Models\ActivityApplicant;
use \PHPUnit_Framework_Assert as Assert;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ActivityApplicantRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    //==================================
    //      saveApplicantInfo
    //==================================
    public function testSaveApplicantInfoSuccessfully()
    {
        $data = $this->prepareApplicantSaveData();
        $applicant = $this->getRepository()->saveApplicantInfo($data);
        Assert::assertInstanceOf(\Jihe\Entities\ActivityApplicant::class, $applicant);
        Assert::assertEquals('张三', $applicant->getName());
        Assert::assertCount(1, $applicant->getAttrs());
        Assert::assertEquals('身高', $applicant->getAttrs()[0]['key']);
        Assert::assertEquals('170cm', $applicant->getAttrs()[0]['value']);
        Assert::assertEquals(32, strlen($applicant->getOrderNo()));

        $this->seeInDatabase('activity_applicants', [
            'name'    => '张三',
            'mobile'  => '13800138000',
            'user_id' => 1,
            'attrs'   => json_encode([[
                'key'   => '身高',
                'value' => '170cm',]]),
        ]);
    }

    public function testSaveApplicantInfoSuccessfully_InvalidOldApplicant()
    {
        factory(\Jihe\Models\ActivityApplicant::class)->create([
            'activity_id' => 1,
            'user_id'     => 1,
            'name'        => '李四',
            'status'      => 1,
        ]);
        $data = $this->prepareApplicantSaveData();
        $applicant = $this->getRepository()->saveApplicantInfo($data);

        Assert::assertEquals('张三', $applicant->getName());
        Assert::assertCount(1, $applicant->getAttrs());
        Assert::assertEquals('身高', $applicant->getAttrs()[0]['key']);
        Assert::assertEquals('170cm', $applicant->getAttrs()[0]['value']);

        $this->seeInDatabase('activity_applicants', [
            'name'    => '张三',
            'mobile'  => '13800138000',
            'user_id' => 1,
            'status'  => 2,
        ]);
        $this->seeInDatabase('activity_applicants', [
            'name'   => '李四',
            'status' => -1,              // old records aready be invalided
        ]);
    }

    private function prepareApplicantSaveData()
    {
        return [
            'name'        => '张三',
            'mobile'      => '13800138000',
            'attrs'       => json_encode([[
                'key'   => '身高',
                'value' => '170cm']]),
            'activity_id' => 1,
            'user_id'     => 1,
            'channel'     => 1,
            'status'      => 2,
        ];
    }

    //==================================
    //      getUserSuccessfulApplicant
    //==================================
    public function testGetUserSuccessfulApplicant()
    {
        factory(\Jihe\Models\ActivityApplicant::class)->create([
            'user_id'     => 1,
            'activity_id' => 1,
            'status'      => 3,       // stand for applicant successful
        ]);

        $applicant = $this->getRepository()->getUserSuccessfulApplicant(1, 1);
        Assert::assertNotEmpty($applicant);
        Assert::assertEquals(1, $applicant['user_id']);
        Assert::assertEquals(1, $applicant['activity_id']);
    }

    public function testGetUserSuccessfulApplicant_NotFound()
    {
        factory(\Jihe\Models\ActivityApplicant::class)->create([
            'user_id'     => 1,
            'activity_id' => 1,
            'status'      => 1,
        ]);

        $applicant = $this->getRepository()->getUserSuccessfulApplicant(1, 1);
        Assert::assertNull($applicant);
    }

    //======================================
    //      updateStatusAfterPaymentSuccess
    //======================================
    public function testUpdateStatusAfterPaymentSuccess()
    {
        factory(\Jihe\Models\ActivityApplicant::class)->create([
            'order_no'    => 'abcefghi',
            'user_id'     => 1,
            'activity_id' => 1,
            'status'      => 2,       // stand for STATUS_PAY
        ]);

        $result = $this->getRepository()->updateStatusAfterPaymentSuccess('abcefghi');
        Assert::assertEquals(true, $result);
    }

    public function testUpdateStatusAfterPaymentSuccess_NotFound()
    {
        factory(\Jihe\Models\ActivityApplicant::class)->create([
            'order_no'    => 'abcefghi',
            'user_id'     => 1,
            'activity_id' => 1,
            'status'      => 1,       // stand for STATUS_PAY
        ]);

        $result = $this->getRepository()->updateStatusAfterPaymentSuccess('abcefghi');
        Assert::assertEquals(false, $result);
    }

    //======================================
    //      recycleActivityApplicant
    //======================================
    public function testRecycleActivityApplicant()
    {
        factory(\Jihe\Models\ActivityApplicant::class)->create([
            'id'          => 1,
            'order_no'    => 'abcefghi',
            'user_id'     => 1,
            'activity_id' => 1,
            'status'      => 1,       // stand for STATUS_PAY
        ]);

        $result = $this->getRepository()->recycleActivityApplicant(1);
        Assert::assertEquals(true, $result);

        $this->seeInDatabase('activity_applicants', [
            'id'     => 1,
            'status' => -1,      // -1 stands for applicant aready recycled
        ]);
    }

    public function testRecycleActivityApplicant_StatusInvalid()
    {
        factory(\Jihe\Models\ActivityApplicant::class)->create([
            'id'          => 1,
            'order_no'    => 'abcefghi',
            'user_id'     => 1,
            'activity_id' => 1,
            'status'      => 3,       // stand for STATUS_SUCCESS
        ]);

        $result = $this->getRepository()->recycleActivityApplicant(1);
        Assert::assertEquals(false, $result);

        $this->seeInDatabase('activity_applicants', [
            'id'     => 1,
            'status' => 3,       // status not changed
        ]);

    }

    //======================================
    //      countActivityApplicant
    //======================================
    public function testCountActivityApplicant()
    {
        factory(\Jihe\Models\ActivityApplicant::class)->create([
            'id'          => 1,
            'order_no'    => 'abcefghi1',
            'user_id'     => 1,
            'activity_id' => 1,
            'status'      => ActivityApplicant::STATUS_INVALID,
        ]);
        factory(\Jihe\Models\ActivityApplicant::class)->create([
            'id'          => 2,
            'order_no'    => 'abcefghi2',
            'user_id'     => 1,
            'activity_id' => 1,
            'status'      => ActivityApplicant::STATUS_NORMAL,
        ]);
        factory(\Jihe\Models\ActivityApplicant::class)->create([
            'id'          => 3,
            'order_no'    => 'abcefghi3',
            'user_id'     => 2,
            'activity_id' => 1,
            'status'      => ActivityApplicant::STATUS_NORMAL,
        ]);
        factory(\Jihe\Models\ActivityApplicant::class)->create([
            'id'          => 4,
            'order_no'    => 'abcefghi4',
            'user_id'     => 1,
            'activity_id' => 2,
            'status'      => ActivityApplicant::STATUS_NORMAL,
        ]);
        factory(\Jihe\Models\ActivityApplicant::class)->create([
            'id'          => 5,
            'order_no'    => 'abcefghi5',
            'user_id'     => 2,
            'activity_id' => 2,
            'status'      => ActivityApplicant::STATUS_NORMAL,
        ]);
        factory(Activity::class)->create([
            'id'      => 1,
            'team_id' => 1,
            'city_id' => 1,
            'title'   => '已发布测试活动－－_\%皮划艇1',
            'status'  => ActivityEntity::STATUS_PUBLISHED,
        ]);
        factory(Activity::class)->create([
            'id'      => 2,
            'team_id' => 1,
            'city_id' => 1,
            'title'   => '已发布测试活动－－_\%皮划艇2',
            'status'  => ActivityEntity::STATUS_PUBLISHED,
        ]);
        $result = $this->getRepository()->countActivityApplicant([1, 2]);
        Assert::assertEquals(2, $result[1]);
        Assert::assertEquals(2, $result[2]);
    }

    //=======================================
    //     getTeamsOfRequestedActivities
    //=======================================
    public function testGetTeamsOfRequestedActivities()
    {
        factory(\Jihe\Models\Team::class)->create(['id' => 1, 'status' => \Jihe\Entities\Team::STATUS_NORMAL,]);
        factory(\Jihe\Models\Team::class)->create(['id' => 2, 'status' => \Jihe\Entities\Team::STATUS_NORMAL,]);

        factory(\Jihe\Models\Activity::class)->create([
            'id'           => 1,
            'team_id'      => 1,
            'city_id'      => 2,
            'title'        => '已发布测试活动－－_\%跑步',
            'status'       => \Jihe\Entities\Activity::STATUS_PUBLISHED,
            'end_time'     => date('Y-m-d 00:00:00', strtotime('+2 days')),
            'begin_time'   => date('Y-m-d 00:00:01', strtotime('+2 days')),
            'publish_time' => date('Y-m-d 10:11:11', strtotime('-10 days')),
        ]);

        factory(\Jihe\Models\Activity::class)->create([
            'id'           => 2,
            'team_id'      => 2,
            'city_id'      => 2,
            'title'        => '已发布测试活动－－_\%跑步',
            'status'       => \Jihe\Entities\Activity::STATUS_PUBLISHED,
            'end_time'     => date('Y-m-d 00:00:00', strtotime('+2 days')),
            'begin_time'   => date('Y-m-d 00:00:01', strtotime('+2 days')),
            'publish_time' => date('Y-m-d 10:11:11', strtotime('-10 days')),
        ]);

        factory(\Jihe\Models\Activity::class)->create([
            'id'           => 3,
            'team_id'      => 2,
            'city_id'      => 2,
            'title'        => '已发布测试活动－－_\%跑步',
            'status'       => \Jihe\Entities\Activity::STATUS_PUBLISHED,
            'end_time'     => date('Y-m-d 00:00:00', strtotime('+2 days')),
            'begin_time'   => date('Y-m-d 00:00:01', strtotime('+2 days')),
            'publish_time' => date('Y-m-d 10:11:11', strtotime('-10 days')),
        ]);

        factory(\Jihe\Models\ActivityApplicant::class)->create([
            'id'          => 1,
            'activity_id' => 1,
            'user_id'     => 1,
        ]);

        factory(\Jihe\Models\ActivityApplicant::class)->create([
            'id'          => 2,
            'activity_id' => 2,
            'user_id'     => 1,
        ]);

        factory(\Jihe\Models\ActivityApplicant::class)->create([
            'id'          => 3,
            'activity_id' => 3,
            'user_id'     => 1,
        ]);

        Assert::assertEquals([1, 2], $this->getRepository()->findTeamsOfRequestedActivities(1));
    }

    //======================================
    //      getActivityApplicantsPageById
    //======================================
    public function testGetActivityApplicantsPageById_FetchNextOrderAsc()
    {
        $this->prepareActivityApplicantsData(5);

        list(
            $count, $preId, $nextId, $applicants

            ) = $this->getRepository()->getActivityApplicantsPageById(
            1, 1, \Jihe\Entities\ActivityApplicant::STATUS_SUCCESS, 3, false, false
        );

        self::assertEquals(5, $count);
        self::assertEquals(2, $preId);
        self::assertEquals(4, $nextId);
        self::assertCount(3, $applicants);
    }

    public function testGetActivityApplicantsPageById_FetchNextOrderAsc_Empty()
    {
        $this->prepareActivityApplicantsData(5);

        list(
            $count, $preId, $nextId, $applicants

            ) = $this->getRepository()->getActivityApplicantsPageById(
            1, 5, \Jihe\Entities\ActivityApplicant::STATUS_SUCCESS, 3, false, false
        );

        self::assertEquals(5, $count);
        self::assertEquals(6, $preId);
        self::assertEquals(6, $nextId);
        self::assertCount(0, $applicants);
    }

    public function testGetActivityApplicantsPageById_FetchNextOrderAsc_WithoutId()
    {
        $this->prepareActivityApplicantsData(5);

        list(
            $count, $preId, $nextId, $applicants

            ) = $this->getRepository()->getActivityApplicantsPageById(
            1, null, \Jihe\Entities\ActivityApplicant::STATUS_SUCCESS, 3, false, false
        );

        self::assertEquals(5, $count);
        self::assertEquals(1, $preId);
        self::assertEquals(3, $nextId);
        self::assertCount(3, $applicants);
    }

    public function testGetActivityApplicantsPageById_FetchNextOrderDesc()
    {
        $this->prepareActivityApplicantsData(5);

        list(
            $count, $preId, $nextId, $applicants

            ) = $this->getRepository()->getActivityApplicantsPageById(
            1, 5, \Jihe\Entities\ActivityApplicant::STATUS_SUCCESS, 3, true, false
        );

        self::assertEquals(5, $count);
        self::assertEquals(4, $preId);
        self::assertEquals(2, $nextId);
        self::assertCount(3, $applicants);
    }

    public function testGetActivityApplicantsPageById_FetchNextOrderDesc_WithoutId()
    {
        $this->prepareActivityApplicantsData(5);

        list(
            $count, $preId, $nextId, $applicants

            ) = $this->getRepository()->getActivityApplicantsPageById(
            1, null, \Jihe\Entities\ActivityApplicant::STATUS_SUCCESS, 3, true, false
        );

        self::assertEquals(5, $count);
        self::assertEquals(5, $preId);
        self::assertEquals(3, $nextId);
        self::assertCount(3, $applicants);
    }

    public function testGetActivityApplicantsPageById_FetchNextOrderDesc_Empty()
    {
        $this->prepareActivityApplicantsData(5);

        list(
            $count, $preId, $nextId, $applicants

            ) = $this->getRepository()->getActivityApplicantsPageById(
            1, 0, \Jihe\Entities\ActivityApplicant::STATUS_SUCCESS, 3, true, false
        );

        self::assertEquals(5, $count);
        self::assertEquals(0, $preId);
        self::assertEquals(0, $nextId);
        self::assertCount(0, $applicants);
    }

    public function testGetActivityApplicantsPageById_FetchPreOrderAsc()
    {
        $this->prepareActivityApplicantsData(5);
        list(
            $count, $preId, $nextId, $applicants

            ) = $this->getRepository()->getActivityApplicantsPageById(
            1, 5, \Jihe\Entities\ActivityApplicant::STATUS_SUCCESS, 3, false, true
        );

        self::assertEquals(5, $count);
        self::assertEquals(2, $preId);
        self::assertEquals(4, $nextId);
        self::assertCount(3, $applicants);
    }

    public function testGetActivityApplicantsPageById_FetchPreOrderAsc_WithoutId()
    {
        $this->prepareActivityApplicantsData(5);
        list(
            $count, $preId, $nextId, $applicants

            ) = $this->getRepository()->getActivityApplicantsPageById(
            1, null, \Jihe\Entities\ActivityApplicant::STATUS_SUCCESS, 3, false, true
        );

        self::assertEquals(5, $count);
        self::assertEquals(null, $preId);
        self::assertEquals(null, $nextId);
        self::assertCount(0, $applicants);
    }

    public function testGetActivityApplicantsPageById_FetchPreOrderAsc_Empty()
    {
        $this->prepareActivityApplicantsData(5);
        list(
            $count, $preId, $nextId, $applicants

            ) = $this->getRepository()->getActivityApplicantsPageById(
            1, 1, \Jihe\Entities\ActivityApplicant::STATUS_SUCCESS, 3, false, true
        );

        self::assertEquals(5, $count);
        self::assertEquals(0, $preId);
        self::assertEquals(0, $nextId);
        self::assertCount(0, $applicants);
    }

    public function testGetActivityApplicantsPageById_FetchPreOrderDesc()
    {
        $this->prepareActivityApplicantsData(5);

        list(
            $count, $preId, $nextId, $applicants

            ) = $this->getRepository()->getActivityApplicantsPageById(
            1, 0, \Jihe\Entities\ActivityApplicant::STATUS_SUCCESS, 3, true, true
        );

        self::assertEquals(5, $count);
        self::assertEquals(3, $preId);
        self::assertEquals(1, $nextId);
        self::assertCount(3, $applicants);
    }

    public function testGetActivityApplicantsPageById_FetchPreOrderDesc_WithoutId()
    {
        $this->prepareActivityApplicantsData(5);

        list(
            $count, $preId, $nextId, $applicants

            ) = $this->getRepository()->getActivityApplicantsPageById(
            1, null, \Jihe\Entities\ActivityApplicant::STATUS_SUCCESS, 3, true, true
        );

        self::assertEquals(5, $count);
        self::assertEquals(null, $preId);
        self::assertEquals(null, $nextId);
        self::assertCount(0, $applicants);
    }

    public function testGetActivityApplicantsPageById_FetchPreOrderDesc_Empty()
    {
        $this->prepareActivityApplicantsData(5);

        list(
            $count, $preId, $nextId, $applicants

            ) = $this->getRepository()->getActivityApplicantsPageById(
            1, 5, \Jihe\Entities\ActivityApplicant::STATUS_SUCCESS, 3, true, true
        );

        self::assertEquals(5, $count);
        self::assertEquals(6, $preId);
        self::assertEquals(6, $nextId);
        self::assertCount(0, $applicants);
    }

    //===========================================
    //        getAuditApplicants
    //===========================================
    public function testGetAuditApplicantsFound()
    {
        factory(\Jihe\Models\Activity::class)->create([
            'id'         => 1,
            'enroll_fee' => 500,
        ]);
        factory(\Jihe\Models\User::class)->create([
            'id'     => 1,
            'mobile' => '13800138000',
        ]);
        factory(\Jihe\Models\ActivityApplicant::class)->create([
            'id'          => 1,
            'activity_id' => 1,
            'status'      => ActivityApplicantEntity::STATUS_AUDITING,
        ]);
        factory(\Jihe\Models\ActivityApplicant::class)->create([
            'id'          => 2,
            'activity_id' => 1,
            'status'      => ActivityApplicantEntity::STATUS_AUDITING,
        ]);
        factory(\Jihe\Models\ActivityApplicant::class)->create([
            'id'          => 3,
            'activity_id' => 2,
            'status'      => ActivityApplicantEntity::STATUS_AUDITING,
        ]);
        factory(\Jihe\Models\ActivityApplicant::class)->create([
            'id'          => 4,
            'activity_id' => 1,
            'status'      => ActivityApplicantEntity::STATUS_PAY,
        ]);

        $applicants = $this->getRepository()->getAuditApplicants(1, [1, 2, 3]);
        self::assertCount(2, $applicants);
        self::assertEquals(1, $applicants[0]->getId());
        self::assertEquals(2, $applicants[1]->getId());
        self::assertEquals('13800138000', $applicants[0]->getUser()->getMobile());
        self::assertEquals(500, $applicants[0]->getActivity()->getEnrollFee());
    }

    //===========================================
    //        approveToPay
    //===========================================
    public function testApproveToPay()
    {
        factory(\Jihe\Models\ActivityApplicant::class)->create([
            'id'     => 1,
            'status' => ActivityApplicantEntity::STATUS_AUDITING,
        ]);
        factory(\Jihe\Models\ActivityApplicant::class)->create([
            'id'     => 2,
            'status' => ActivityApplicantEntity::STATUS_AUDITING,
        ]);
        factory(\Jihe\Models\ActivityApplicant::class)->create([
            'id'     => 3,
            'status' => ActivityApplicantEntity::STATUS_PAY,
        ]);

        $affectedRows = $this->getRepository()->approveToPay(
            [1, 2, 3], new \DateTime(date('Y-m-d H:i:s', time() + 1800))
        );
        self::assertEquals(2, $affectedRows);

        $this->seeInDatabase('activity_applicants', [
            'id'     => 1,
            'status' => ActivityApplicantEntity::STATUS_PAY,
        ]);
        $this->seeInDatabase('activity_applicants', [
            'id'     => 2,
            'status' => ActivityApplicantEntity::STATUS_PAY,
        ]);
    }

    //===========================================
    //        refuse
    //===========================================
    public function testRefuse()
    {
        factory(\Jihe\Models\ActivityApplicant::class)->create([
            'id'     => 1,
            'status' => ActivityApplicantEntity::STATUS_AUDITING,
        ]);
        factory(\Jihe\Models\ActivityApplicant::class)->create([
            'id'     => 2,
            'status' => ActivityApplicantEntity::STATUS_AUDITING,
        ]);
        factory(\Jihe\Models\ActivityApplicant::class)->create([
            'id'     => 3,
            'status' => ActivityApplicantEntity::STATUS_PAY,
        ]);

        $affectedRows = $this->getRepository()->refuse([1, 2, 3]);
        self::assertEquals(2, $affectedRows);

        $this->seeInDatabase('activity_applicants', [
            'id'     => 1,
            'status' => ActivityApplicantEntity::STATUS_INVALID,
        ]);
        $this->seeInDatabase('activity_applicants', [
            'id'     => 2,
            'status' => ActivityApplicantEntity::STATUS_INVALID,
        ]);

    }

    //===========================================
    //        remark
    //===========================================
    public function testRemark()
    {
        factory(\Jihe\Models\ActivityApplicant::class)->create([
            'id'          => 1,
            'activity_id' => 1,
            'status'      => ActivityApplicantEntity::STATUS_SUCCESS,
        ]);

        $affectedRows = $this->getRepository()->remark(1, 1, '临时取消');
        self::assertEquals(1, $affectedRows);
        $this->seeInDatabase('activity_applicants', [
            'id'     => 1,
            'remark' => '临时取消',
        ]);
    }

    private function prepareActivityApplicantsData($number)
    {
        factory(\Jihe\Models\Activity::class)->create([
            'id' => 1,
        ]);
        factory(\Jihe\Models\User::class)->create([
            'id' => 1,
        ]);
        foreach (range(1, $number) as $id) {
            factory(\Jihe\Models\ActivityApplicant::class)->create([
                'id'          => $id,
                'activity_id' => 1,
                'user_id'     => 1,
                'status'      => \Jihe\Entities\ActivityApplicant::STATUS_SUCCESS,
            ]);
        }
    }

    //===========================================
    //        getActivityApplicantsByMobiles
    //===========================================
    public function testGetActivityApplicantsByMobiles()
    {
        factory(\Jihe\Models\ActivityApplicant::class)->create([
            'id'          => 1,
            'activity_id' => 22,
            'user_id'     => 1,
            'mobile'      => '13800138001',
            'status'      => ActivityApplicantEntity::STATUS_NORMAL,
        ]);
        factory(\Jihe\Models\ActivityApplicant::class)->create([
            'id'          => 2,
            'activity_id' => 22,
            'user_id'     => 2,
            'mobile'      => '13800138002',
            'status'      => ActivityApplicantEntity::STATUS_AUDITING,
        ]);
        factory(\Jihe\Models\ActivityApplicant::class)->create([
            'id'          => 3,
            'activity_id' => 22,
            'user_id'     => 3,
            'mobile'      => '13800138003',
            'status'      => ActivityApplicantEntity::STATUS_PAY,
        ]);
        factory(\Jihe\Models\ActivityApplicant::class)->create([
            'id'          => 4,
            'activity_id' => 22,
            'user_id'     => 4,
            'mobile'      => '13800138004',
            'status'      => ActivityApplicantEntity::STATUS_INVALID,
        ]);
        factory(\Jihe\Models\ActivityApplicant::class)->create([
            'id'          => 5,
            'activity_id' => 22,
            'user_id'     => 5,
            'mobile'      => '13800138005',
            'status'      => ActivityApplicantEntity::STATUS_SUCCESS,
        ]);

        $result = $this->getRepository()->getActivityApplicantsByMobiles(22, [
            '13800138001',
            '13800138002',
            '13800138003',
            '13800138004',
            '13800138005',
        ]);
        self::assertEquals('13800138004', array_search(null, $result));
    }


    /**
     * @return \Jihe\Contracts\Repositories\ActivityApplicantRepository
     */
    private function getRepository()
    {
        return $this->app[\Jihe\Contracts\Repositories\ActivityApplicantRepository::class];
    }
}
