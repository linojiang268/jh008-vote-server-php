<?php
namespace intg\Jihe\Controllers\Backstage;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use intg\Jihe\TestCase;
use intg\Jihe\RequestSignCheck;
use intg\Jihe\AuthDeviceCheck;

class ActivityApplicantControllerTest extends TestCase
{
    use DatabaseTransactions;
    use RequestSignCheck;
    use AuthDeviceCheck;

    //=========================================
    //      sendVerifyCodeForWebApplicant
    //=========================================
    public function testSendVerifyCodeForWebApplicant()
    {
        $this->expectsJobs(\Jihe\Jobs\SendMessageToUserJob::class);

        $url = 'community/web/activity/applicant/verifycode?' . http_build_query([
            'mobile' => '13800138000',
        ]);
        $this->ajaxGet($url)->seeJsonContains([ 'code' => 0 ]);
    }

    public function testSendVerifyCodeWithInvalidMobile()
    {
        $url = 'community/web/activity/applicant/verifycode?' . http_build_query([
            'mobile' => '13800',
        ]);

        $this->ajaxGet($url)->seeJsonContains([ 'code' => 10000 ]);
        $this->assertContains('\u624b\u673a\u53f7' /* 手机号 */, $this->response->getContent(),
                              'invalid mobile should be detected');
    }

    //=========================================
    //      applicantFromWeb
    //=========================================
    public function testApplicantFromWebSuccessfully_NoAuditNoPay()
    {
        factory(\Jihe\Models\Verification::class)->create([
            'mobile' => '13800138000', 
            'code'   => '8952', 
        ]);
        $data = $this->prepareApplicantData();

        $this->startSession();
        $this->actingAs($data->user)
             ->ajaxPost('community/web/activity/applicant', [
                '_token'        => csrf_token(),
                'code'          => '8952',
                'activity_id'   => 1,
                'attrs'         => json_encode([
                                    [
                                        'key'   => '手机号',
                                        'value' => '13800138000',
                                    ], [
                                        'key'   => '姓名',
                                        'value' => '张三',
                                    ]]),
            ])->seeJsonContains([
                'code'      => 0,
            ]);

        $response = json_decode($this->response->getContent());
        self::assertEquals(3, $response->info->status);

        $this->seeInDatabase('activity_applicants', [
            'user_id'       => 1,
            'activity_id'   => 1,
            'name'          => '张三',
            'mobile'        => '13800138000',
        ]);
    }

    public function testApplicantFromWebSuccessfully_NoAuditNeePay()
    {
        factory(\Jihe\Models\Verification::class)->create([
            'mobile' => '13800138000', 
            'code'   => '8952', 
        ]);
        $data = $this->prepareApplicantData([
            'enroll_fee_type'   => 3, 
        ]);

        $this->startSession();
        $this->actingAs($data->user)
             ->ajaxPost('community/web/activity/applicant', [
                '_token'        => csrf_token(),
                'code'          => '8952',
                'activity_id'   => 1,
                'attrs'         => json_encode([
                                    [
                                        'key'   => '手机号',
                                        'value' => '13800138000',
                                    ], [
                                        'key'   => '姓名',
                                        'value' => '张三',
                                    ]]),
            ])->seeJsonContains([
                'code'      => 0,
            ]);

        $response = json_decode($this->response->getContent());
        self::assertEquals(2, $response->info->status);

        $this->seeInDatabase('activity_applicants', [
            'user_id'       => 1,
            'activity_id'   => 1,
            'name'          => '张三',
            'mobile'        => '13800138000',
        ]);
    }

    private function prepareApplicantData(array $actConf = [], array $userConf = [])
    {
        $actConf = array_merge([
            'id'                => 1,
            'team_id'           => 1,
            'city_id'           => 1,
            'title'             => '测试web报名',
            'enroll_begin_time' => date('Y-m-d H:i:s', strtotime('-1 days')),
            'enroll_end_time'   => date('Y-m-d H:i:s', strtotime('+1 days')),
            'enroll_limit'      => 0,           // no limitation
            'enroll_type'       => 1,           // any one can applicant
            'auditing'          => 0,           // 0 -- not audit; 1 -- need audit
            'enroll_attrs'      => null,
            'enroll_fee_type'   => 1,           // 1 -- free; 2 -- AA; 3 -- need pay
            'status'            => 1,           // aready published
        ], $actConf);
        $userConf = array_merge([
            'id'    => 1,
            'mobile'    => '13800138000',    
        ], $userConf);
        $data = new \stdClass;
        $data->team = factory(\Jihe\Models\Team::class)->create(['id' => $actConf['team_id']]);
        $data->city =factory(\Jihe\Models\City::class)->create(['id' => $actConf['city_id']]);
        $data->activity = factory(\Jihe\Models\Activity::class)->create($actConf);
        $data->user = factory(\Jihe\Models\User::class)->create($userConf);

        return $data;
    }

    //=========================================
    //      approveApplicant
    //=========================================
    public function testApproveApplicantSuccessful_ApproveToPay()
    {
        $city = factory(\Jihe\Models\City::class)->create([]);
        $user = factory(\Jihe\Models\User::class)->create([]);
        $team = factory(\Jihe\Models\Team::class)->create([
            'city_id'       => $city->id,
            'creator_id'    => $user->id,
        ]);
        $activity = factory(\Jihe\Models\Activity::class)->create([
            'city_id'           => $city->id,
            'team_id'           => $team->id,
            'enroll_fee_type'   => 3,      // need pay
            'auditing'          => 1,       // need auditing
        ]);
        $applicant = factory(\Jihe\Models\ActivityApplicant::class)->create([
            'activity_id'   => $activity->id,
            'status'    => 1,       // 1 stands for auding
        ]);

        $this->startSession();
        $this->actingAs($user)->ajaxPost('community/activity/applicant/approve', [
            'activity_id'   => $activity->id,
            'applicant_id'  => $applicant->id,
            '_token'        => csrf_token(),
        ])->seeJsonContains([ 'code' => 0 ]);

        $this->seeInDatabase('activity_applicants', [
            'id'        => $applicant->id,
            'status'    => 2,           // stands for wait pay
        ]);
    }

    //=========================================
    //      getApplicantsListForClientManage
    //=========================================
    public function testGetApplicantsListForClientManage_NextAsc()
    {
        $user = $this->prepareActivityApplicantsData(5);
        $url = 'api/manage/act/applicant/list?' . http_build_query([
            'activity'   => 1,
            'status'        => 1,
            'size'          => 3,
        ]);
        $this->startSession();
        $this->actingAs($user)->ajaxGet($url)->seeJsonContains(['code' => 0]);
        $response = json_decode($this->response->getContent());
        self::assertNotNull($response);
        self::assertEquals(5, $response->total);
        self::assertEquals(1, $response->pre_id);
        self::assertEquals(3, $response->next_id);
        self::assertCount(3, $response->applicants);
        self::assertEquals(1, $response->applicants[0]->id);
        self::assertEquals('test', $response->applicants[0]->remark);
        self::assertEquals(100, $response->applicants[0]->enroll_fee);
    }

    public function testGetApplicantsListForClientManage_NextAsc_Withid()
    {
        $user = $this->prepareActivityApplicantsData(5);
        $url = 'api/manage/act/applicant/list?' . http_build_query([
            'activity'   => 1,
            'id'            => 2,
            'status'        => 1,
            'sort'          => 0,
            'size'          => 3,
        ]);
        $this->startSession();
        $this->actingAs($user)->ajaxGet($url)->seeJsonContains(['code' => 0]);
        $response = json_decode($this->response->getContent());
        self::assertNotNull($response);
        self::assertEquals(5, $response->total);
        self::assertEquals(3, $response->pre_id);
        self::assertEquals(5, $response->next_id);
        self::assertCount(3, $response->applicants);
        self::assertEquals(3, $response->applicants[0]->id);
    }

    public function testGetApplicantsListForClientManage_NextAsc_Empty()
    {
        $user = $this->prepareActivityApplicantsData(5);
        $url = 'api/manage/act/applicant/list?' . http_build_query([
            'activity'   => 1,
            'id'            => 6,
            'status'        => 1,
            'sort'          => 0,
            'size'          => 3,
        ]);
        $this->startSession();
        $this->actingAs($user)->ajaxGet($url)->seeJsonContains(['code' => 0]);
        $response = json_decode($this->response->getContent());
        self::assertNotNull($response);
        self::assertEquals(5, $response->total);
        self::assertEquals(7, $response->pre_id);
        self::assertEquals(7, $response->next_id);
        self::assertCount(0, $response->applicants);
    }

    public function testGetApplicantsListForClientManage_PreAsc()
    {
        $user = $this->prepareActivityApplicantsData(5);
        $url = 'api/manage/act/applicant/list?' . http_build_query([
            'activity'   => 1,
            'id'            => 5,
            'status'        => 1,
            'sort'          => 0,
            'is_pre'        => 1,       // means to fetch records pre specified id
            'size'          => 3,
        ]);
        $this->startSession();
        $this->actingAs($user)->ajaxGet($url)->seeJsonContains(['code' => 0]);
        $response = json_decode($this->response->getContent());
        self::assertNotNull($response);
        self::assertEquals(5, $response->total);
        self::assertEquals(2, $response->pre_id);
        self::assertEquals(4, $response->next_id);
        self::assertCount(3, $response->applicants);
        self::assertEquals(2, $response->applicants[0]->id);
    }

    //=========================================
    //      batchApproveApplicant
    //=========================================
    public function testBatchApproveApplicant_StatusToPay()
    {
        $this->expectsJobs(\Jihe\Jobs\SendMessageToUserJob::class);
        $user = $this->prepareActivityApplicantsData(5);

        $this->startSession();
        $this->actingAs($user)->ajaxPost('api/manage/act/applicant/approve', [
            'activity'      => 1,
            'applicants'    => [1, 2, 3],
        ])->seeJsonContains(['code' => 0]);

        $this->seeInDatabaseForApplicants(
            [1, 2, 3], \Jihe\Entities\ActivityApplicant::STATUS_PAY
        );
    }

    public function testBatchApproveApplicant_NotFound()
    {
        $user = $this->prepareActivityApplicantsData(0);

        $this->startSession();
        $this->actingAs($user)->ajaxPost('api/manage/act/applicant/approve', [
            'activity'      => 1,
            'applicants'    => [9, 10],
        ])->seeJsonContains(['code' => 0]);
    }

    public function testBatchApproveApplicant_StatusToStatus()
    {
        $this->expectsJobs(\Jihe\Jobs\SendMessageToUserJob::class);

        $user = $this->prepareActivityApplicantsData(5);
        \Jihe\Models\Activity::where('id', 1)->update([
            'enroll_fee_type'   => \Jihe\Entities\Activity::ENROLL_FEE_TYPE_FREE,
        ]);

        $this->startSession();
        $this->actingAs($user)->ajaxPost('api/manage/act/applicant/approve', [
            'activity'      => 1,
            'applicants'    => [1, 2, 3],
        ])->seeJsonContains(['code' => 0]);

        $this->seeInDatabaseForApplicants(
            [1, 2, 3], \Jihe\Entities\ActivityApplicant::STATUS_SUCCESS
        );
    }

    //=========================================
    //      batchRefuseApplicant
    //=========================================
    public function testBatchRefuseApplicant()
    {
        $this->expectsJobs(\Jihe\Jobs\SendMessageToUserJob::class);

        $user = $this->prepareActivityApplicantsData(5);
        \Jihe\Models\Activity::where('id', 1)->update([
            'enroll_fee_type'   => \Jihe\Entities\Activity::ENROLL_FEE_TYPE_FREE,
        ]);

        $this->startSession();
        $this->actingAs($user)->ajaxPost('api/manage/act/applicant/refuse', [
            'activity'      => 1,
            'applicants'    => [1, 2, 3],
        ])->seeJsonContains(['code' => 0]);

        $this->seeInDatabaseForApplicants(
            [1, 2, 3], \Jihe\Entities\ActivityApplicant::STATUS_INVALID
        );
    }

    public function testBatchRefuseApplicant_NotFound()
    {
        $user = $this->prepareActivityApplicantsData(0);

        $this->startSession();
        $this->actingAs($user)->ajaxPost('api/manage/act/applicant/refuse', [
            'activity'      => 1,
            'applicants'    => [9, 10],
        ])->seeJsonContains(['code' => 0]);
    }

    //=========================================
    //      remark
    //=========================================
    public function testRemarkSuccessfully()
    {
        $user = $this->prepareActivityApplicantsData(
            1, \Jihe\Entities\ActivityApplicant::STATUS_SUCCESS
        );

        $this->startSession();
        $this->actingAs($user)->ajaxPost('api/manage/act/applicant/remark', [
            'activity'  => 1,
            'applicant' => 1,
            'content'   => '临时取消',
        ])->seeJsonContains(['code' => 0]);

        $res = \Jihe\Models\ActivityApplicant::find(1);

        $this->seeInDatabase('activity_applicants', [
            'id'        => 1,
            'remark'    => '临时取消'
        ]);
    }

    public function testRemarkFailed_NoPermission()
    {
        $this->prepareActivityApplicantsData(
            1, \Jihe\Entities\ActivityApplicant::STATUS_SUCCESS
        );
        $user = factory(\Jihe\Models\User::class)->create([
            'id' => 100,
        ]);

        $this->startSession();
        $this->actingAs($user)->ajaxPost('api/manage/act/applicant/remark', [
            'activity'  => 1,
            'applicant' => 1,
            'content'   => '临时取消',
        ])->seeJsonContains([
            'code' => 10000,
            'message' => '非法团长',
        ]);

    }

    //=========================================
    //      addSingleVip
    //=========================================
    public function testAddSingleVipSuccessful()
    {
        $user = $this->prepareActivityApplicantsData(0);
        $this->startSession();
        $this->actingAs($user)->ajaxPost('api/manage/act/applicant/vip/add', [
            'activity'  => 1,
            'attrs'     => json_encode([
                            ['key' => '手机号', 'value' => '13800138000'],
                            ['key' => '姓名', 'value' => '张三'],
                            ['key' => '身高', 'value' => '170cm'],
                            ]),
        ])->seeJsonContains([
            'code' => 0, 'message' => '添加成功',
        ]);
        $this->seeInDatabase('activity_applicants', [
            'mobile'    => '13800138000',
            'name'      => '张三',
            'attrs'     => json_encode([[
                            'key' => '身高', 'value' => '170cm',
                            ]]),
            'activity_id'   => 1,
        ]);
    }

    public function testAddSingleVipFailed_HasPendingRecord()
    {
        $user = $this->prepareActivityApplicantsData(1);
        $this->startSession();
        $this->actingAs($user)->ajaxPost('api/manage/act/applicant/vip/add', [
            'activity'  => 1,
            'attrs'     => json_encode([
                            ['key' => '手机号', 'value' => '13800138000'],
                            ['key' => '姓名', 'value' => '张三'],
                            ['key' => '身高', 'value' => '170cm'],
                            ]),
        ])->seeJsonContains([
            'code' => 10000,
            'message' => '您的报名申请在审核中，请耐心等待',
        ]);
    }

    private function seeInDatabaseForApplicants(array $ids, $status)
    {
        foreach ($ids as $id) {
            $this->seeInDatabase('activity_applicants', [
                'id'        => $id,
                'status'    => $status,
            ]);
        }
    }

    private function prepareActivityApplicantsData(
        $number, $status = \Jihe\Entities\ActivityApplicant::STATUS_AUDITING
    ) {
        factory(\Jihe\Models\City::class)->create([
            'id'    => 1,
        ]);
        factory(\Jihe\Models\Team::class)->create([
            'id'            => 1,
            'creator_id'    => 1,
            'city_id'       => 1,
        ]);
        factory(\Jihe\Models\Activity::class)->create([
            'id'                => 1,
            'team_id'           => 1,
            'city_id'           => 1,
            'enroll_fee_type'   => \Jihe\Entities\Activity::ENROLL_FEE_TYPE_PAY,
            'enroll_fee'        => 100,
            'enroll_attrs'      => json_encode(['手机号', '姓名', '身高']),
        ]);
        $user = factory(\Jihe\Models\User::class)->create([
            'id'        => 1,
            'mobile'    => '13800138000',
        ]);
        $range = $number ? range(1, $number) : [];
        foreach($range as $id) {
            factory(\Jihe\Models\ActivityApplicant::class)->create([
                'id'            => $id,
                'activity_id'   => 1,
                'user_id'       => 1,
                'name'          => 'test' . $id,
                'status'        => $status,
                'remark'        => 'test',
            ]);
        }

        return $user;
    }
}
