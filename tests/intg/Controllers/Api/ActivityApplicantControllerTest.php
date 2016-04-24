<?php
namespace intg\Jihe\Controllers\Api;

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
    //          applicantActivity 
    //=========================================
    public function testApplicantActivitySuccessful()
    {
        $this->expectsEvents(\Jihe\Events\UserApplicantActivityEvent::class);

        $activityId = 2;
        $mobile = '13800138000';
        $activityTitle = 'test';
        factory(\Jihe\Models\Team::class)->create([
            'id' => 2,
        ]);
        factory(\Jihe\Models\City::class)->create([
            'id' => 2,
        ]);
        factory(\Jihe\Models\Activity::class)->create([
            'id'                => $activityId,
            'team_id'           => 2,
            'city_id'           => 2,
            'title'             => $activityTitle,
            'enroll_begin_time' => date('Y-m-d H:i:s', strtotime('-1 days')),
            'enroll_end_time'   => date('Y-m-d H:i:s', strtotime('+1 days')),
            'enroll_limit'      => 0,       // no limitation
            'enroll_type'       => 1,       // any one can applicant
            'auditing'          => 0,       // not need audit
            'enroll_attrs'      => null,
            'enroll_fee_type'   => 3,       // need pay
            'status'            => 1,       // aready published
        ]);
        $user = factory(\Jihe\Models\User::class)->create([
            'id'        => 1,
            'mobile'    => $mobile,
        ]);
        
        $this->startSession();
        $this->actingAs($user)->ajaxPost('/api/activity/applicant/applicant', [
            'activity_id'   => $activityId,
            'attrs'         => json_encode([
                                [
                                    'key'   => '手机号',
                                    'value' => 13800138010
                                ], [
                                    'key'   => '姓名',
                                    'value' => '张三',
                                ]]),
        ])->seeJsonContains([
            'code'      => 0,
            'status'    => 2,
        ]);

        $response = json_decode($this->response->getContent());
        self::assertEquals(32, strlen($response->info->order_no));

        $this->seeInDatabase('activity_applicants', [
            'user_id'       => $user->id,
            'activity_id'   => $activityId,
            'name'          => '张三',
            'mobile'        => $mobile,         // mobile is user's mobile, not mobile passed in from attrs
            'status'        => 2,               // wait for pay
        ]);
    }

    //===========================================
    //           searchActivityApplicantFromWeb
    //===========================================
    public function testSearchActivityApplicantFromWebSuccessfully()
    {
        $activityId = 1;
        $mobile = '13800138000';
        $activityTitle = 'test';
        factory(\Jihe\Models\Team::class)->create([
            'id' => 1,
        ]);
        factory(\Jihe\Models\City::class)->create([
            'id' => 1,
        ]);
        factory(\Jihe\Models\Activity::class)->create([
            'id'                => $activityId,
            'team_id'           => 1,
            'city_id'           => 1,
            'title'             => $activityTitle,
            'enroll_begin_time' => date('Y-m-d H:i:s', strtotime('-1 days')),
            'enroll_end_time'   => date('Y-m-d H:i:s', strtotime('+1 days')),
            'enroll_limit'      => 0,       // no limitation
            'enroll_type'       => 1,       // any one can applicant
            'auditing'          => 0,       // not need audit
            'enroll_attrs'      => null,
            'enroll_fee_type'   => 3,       // need pay
            'status'            => 1,       // aready published
        ]);
        $user = factory(\Jihe\Models\User::class)->create([
            'id'        => 1,
            'mobile'    => $mobile,
        ]);

        factory(\Jihe\Models\ActivityApplicant::class)->create([
            'status'    => 3,
            'order_no'  => 'abcdefg',
        ]);

        $this->ajaxGet('/wap/activity/applicant/search?' . http_build_query([
            'activity_id'   => $activityId,
            'mobile'        => $mobile,
        ]))->seeJsonContains([
            'code'      => 0,
            'mobile'    => $mobile,
            'applicant' => [
                'status'    => 3,
                'order_no'  => 'abcdefg',
            ],
        ]);
    }

    public function testSearchActivityApplicantFromWeb_UserNotExists()
    {
        $activityId = 1;
        $mobile = '13800138000';
        $activityTitle = 'test';
        factory(\Jihe\Models\Team::class)->create([
            'id' => 1,
        ]);
        factory(\Jihe\Models\City::class)->create([
            'id' => 1,
        ]);
        factory(\Jihe\Models\Activity::class)->create([
            'id'                => $activityId,
            'team_id'           => 1,
            'city_id'           => 1,
            'title'             => $activityTitle,
            'enroll_begin_time' => date('Y-m-d H:i:s', strtotime('-1 days')),
            'enroll_end_time'   => date('Y-m-d H:i:s', strtotime('+1 days')),
            'enroll_limit'      => 0,       // no limitation
            'enroll_type'       => 1,       // any one can applicant
            'auditing'          => 0,       // not need audit
            'enroll_attrs'      => null,
            'enroll_fee_type'   => 3,       // need pay
            'status'            => 1,       // aready published
        ]);
        $user = factory(\Jihe\Models\User::class)->create([
            'id'        => 1,
            'mobile'    => $mobile,
        ]);

        factory(\Jihe\Models\ActivityApplicant::class)->create([
            'status'    => 3,
        ]);

        $this->ajaxGet('/wap/activity/applicant/search?' . http_build_query([
            'activity_id'   => $activityId,
            'mobile'        => '13800138111',
        ]))->seeJsonContains([
            'code'      => 0,
            'applicant' => null,
        ]);
    }

    public function testSearchActivityApplicantFromWeb_ApplicantNotExists()
    {
        $activityId = 1;
        $mobile = '13800138000';
        $activityTitle = 'test';
        factory(\Jihe\Models\Team::class)->create([
            'id' => 1,
        ]);
        factory(\Jihe\Models\City::class)->create([
            'id' => 1,
        ]);
        factory(\Jihe\Models\Activity::class)->create([
            'id'                => $activityId,
            'team_id'           => 1,
            'city_id'           => 1,
            'title'             => $activityTitle,
            'enroll_begin_time' => date('Y-m-d H:i:s', strtotime('-1 days')),
            'enroll_end_time'   => date('Y-m-d H:i:s', strtotime('+1 days')),
            'enroll_limit'      => 0,       // no limitation
            'enroll_type'       => 1,       // any one can applicant
            'auditing'          => 0,       // not need audit
            'enroll_attrs'      => null,
            'enroll_fee_type'   => 3,       // need pay
            'status'            => 1,       // aready published
        ]);
        $user = factory(\Jihe\Models\User::class)->create([
            'id'        => 1,
            'mobile'    => $mobile,
        ]);

        $this->ajaxGet('/wap/activity/applicant/search?' . http_build_query([
            'activity_id'   => $activityId,
            'mobile'        => $mobile,
        ]))->seeJsonContains([
            'code'      => 0,
            'applicant' => null,
        ]);
    }
}
