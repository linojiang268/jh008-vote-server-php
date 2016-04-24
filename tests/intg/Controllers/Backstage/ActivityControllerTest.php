<?php
namespace intg\Jihe\Controllers\Backstage;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use intg\Jihe\AuthDeviceCheck;
use intg\Jihe\MappedMimeTypeGuesser;
use intg\Jihe\RequestSignCheck;
use intg\Jihe\TestCase;
use Jihe\Entities\Activity as ActivityEntity;
use Jihe\Entities\ActivityAlbumImage as ActivityAlbumImageEntity;
use Jihe\Entities\Team as TeamEntity;
use Jihe\Models\Activity;
use Jihe\Models\ActivityApplicant;
use Jihe\Models\ActivityCheckInQRCode;
use Jihe\Models\ActivityMember;
use Jihe\Models\ActivityPlan;
use Jihe\Entities\ActivityPlan as ActivityPlanEntity;
use Jihe\Models\Admin\User as AdminUser;
use Jihe\Models\City;
use Jihe\Models\Team;
use Jihe\Models\User;
use Jihe\Services\ActivityApplicantService;
use Mockery;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesser;


class ActivityControllerTest extends TestCase
{
    use DatabaseTransactions;
    use RequestSignCheck;
    use AuthDeviceCheck;

    //=========================================
    //         backstageCreateActivity
    //=========================================
    public function testSuccessfulBackstageCreateActivity()
    {
        $response = $this->prepareTestDataAndRequestPOST('/community/activity/create', [
            'title'       => '登山',
            'begin_time'  => '2015-07-30 08:00:00',
            'end_time'    => '2015-07-30 18:00:00',
            'contact'     => 'Tom',
            'telephone'   => '13800138000',
            'cover_url'   => '/20150635/20152685.jpg',
            'images_url'  => '["/20150635/20152685.jpg"]',
            'update_step' => 2,
            'location'    => '[30.611096,104.005101]',
            'roadmap'     => '[[30.611096,104.005101],[30.583243,104.01315],[30.582746,104.122384]]',
        ]);

        $this->seeInDatabase('activities', [
            'id'         => $response->id,
            'begin_time' => '2015-07-30 08:00:00',
            'images_url' => '["\/20150635\/20152685.jpg"]',
        ]);
    }

    public function testThrowExceptionBackstageCreateActivity()
    {
        $this->prepareTestDataAndRequestPOST('/community/activity/create', [
            'contact'     => 'Tom',
            'telephone'   => '13800138000',
            'cover_url'   => '/20150635/20152685.jpg',
            'update_step' => 2,
        ], 10000, '活动名称未填写');
    }

    //=========================================
    //               UpdateActivity
    //=========================================
    public function testSuccessfulUpdateActivity()
    {
        $this->prepareTestDataAndRequestPOST('/community/activity/update', [
            'id'          => 1,
            'title'       => '登山123',
            'begin_time'  => '2015-07-30 08:00:00',
            'end_time'    => '2015-07-30 18:00:00',
            'contact'     => 'Tom',
            'telephone'   => '13800138000',
            'cover_url'   => '/20150635/20152685.jpg',
            'update_step' => 2,
            'location'    => '[30.611096,104.005101]',
            'roadmap'     => '[[30.611096,104.005101],[30.583243,104.01315],[30.582746,104.122384]]',
        ]);
        $activity = Activity::addSelectGeometryColumn()->findOrFail(1); //->toEntity();
        self::assertEquals(2, count($activity->location));
        self::assertEquals(30.611096, $activity->location[0]);
        self::assertEquals(104.005101, $activity->location[1]);
        self::assertEquals(30.583243, $activity->roadmap[1][0]);
        self::assertEquals(104.01315, $activity->roadmap[1][1]);
        self::assertEquals(4, count(json_decode($activity->images_url, true)));
    }

    public function testThrowExceptionUpdateActivity()
    {
        $this->prepareTestDataAndRequestPOST('/community/activity/update', [
            'begin_time'  => '2015-07-30 08:00:00',
            'end_time'    => '2015-07-30 18:00:00',
            'contact'     => 'Tom',
            'telephone'   => '13800138000',
            'cover_url'   => '/20150635/20152685.jpg',
            'update_step' => 2,
        ], 10000, '该活动不存在');
    }

    //=========================================
    //       publishActivity
    //=========================================
    public function testSuccessfulPublishActivity()
    {
        $this->mockJob(\Jihe\Jobs\ActivitySearchIndexRefreshJob::class, function ($job) {
            return $job->targetId == 1;
        });
        $this->mockJob(\Jihe\Jobs\SendMessageToTeamMemberJob::class, function ($job) {
            $team = $job->team;
            /* @var $team \Jihe\Entities\Team */
            return $team->getId() == 1 && $job->phones == null && $job->option['push'] == true;
        });
        $this->mockJob(\Jihe\Jobs\SendMessageToUserJob::class, function ($job) {
            return $job->phones[0] == '13801380000' && $job->option['push'] == true;
        });
        $this->mockJob(\Jihe\Jobs\ActivityPublishRemindToExceptTeamMemberJob::class, function ($job) {
            return $job->team == $job->activity->getTeam()->getId();
        });
        $this->mockStorageService('http://jhla-test.oss-cn-qingdao.aliyuncs.com/20150808/20150808141705830951.png');
        $this->prepareTestDataAndRequestPOST('/community/activity/dopublish',
                                                ['activity' => 1]);
        $this->seeInDatabase('activity_enroll_incomes', ['activity_id' => 1]);
        $activity = Activity::addSelectGeometryColumn()->findOrFail(1);
        self::assertEquals(1, $activity->status);
     }

    //=========================================
    //       GetActivitiesByTeam
    //=========================================
    public function testSuccessfulGetActivitiesByTeam()
    {
        $response = $this->prepareTestDataAndRequestGET('/community/activity/listdata?team=1');
        self::assertDataStructure($response);
        self::assertDataListFields($response->activities);
        self::assertActivityListFields($response->activities[0]);
        self::assertEquals(3, $response->activities[0]->enrolled_num);
    }

    public function testThrowExceptionGetActivitiesByTeam()
    {
        $this->prepareTestDataAndRequestGET('/community/activity/listdata?team=1', 10000, '非法团长', 3);
    }
    //=========================================
    //        getTeamActivitiesByName
    //=========================================
    public function testSuccessfulGetTeamActivitiesByName()
    {
        $response = $this->prepareTestDataAndRequestGET('/community/activity/search/name?team=1&keyword=%');
        self::assertDataStructure($response);
        self::assertDataListFields($response->activities);
        self::assertActivityListFields($response->activities[0]);
    }


    public function testThrowExceptionGetTeamActivitiesByName()
    {
        $this->prepareTestDataAndRequestGET('/community/activity/search/name?keyword=%', 10000, '非法团长', 3);
    }


    //=========================================
    //        getActivityById
    //=========================================
    public function testSuccessfulGetActivityById()
    {
        $response = $this->prepareTestDataAndRequestGET('/community/activity/info?activity=2');
        self::assertDataStructure($response, ['activity']);
        self::assertDataListFields([$response->activity], 'detail');
        self::assertActivityDetailFields($response->activity);
    }

    public function testThrowExceptionGetActivityById()
    {
        $this->prepareTestDataAndRequestGET('/community/activity/info?activity=10', 10000, '非法操作：无操作权限');
    }

    //=========================================
    //               deleteActivityById
    //=========================================
    public function testSuccessfulDeleteActivityById()
    {
        $this->mockJob(\Jihe\Jobs\ActivitySearchIndexRefreshJob::class, function ($job) {
            return $job->targetId == 1;
        });
        $this->prepareTestData();
        $this->startSession();
        $user = factory(User::class)->create([
            'id' => 1,
        ]);

        $this->actingAs($user)
            ->ajaxPost('/community/activity/delete', [
                '_token'   => csrf_token(),
                'activity' => 1,
            ]);
        $this->seeJsonContains(['code' => 0]);
    }

    //=========================================
    //               restoreActivityById
    //=========================================
    public function testSuccessfulRestoreActivityById()
    {
        $this->mockJob(\Jihe\Jobs\ActivitySearchIndexRefreshJob::class, function ($job) {
            return $job->targetId == 1;
        });
        $this->prepareTestData();
        $this->startSession();
        $user = factory(AdminUser::class)->create();
        $this->actingAs($user, 'extended-eloquent-admin')
            ->ajaxPost('/admin/activity/restore', [
                '_token'   => csrf_token(),
                'activity' => 1,
            ]);
        $this->seeJsonContains(['code' => 0]);
    }


    //==================================================
    //         searchTeamActivitiesByActivityTime
    //==================================================
    public function testSuccessfulSearchTeamActivitiesByActivityTime()
    {
        $response = $this->prepareTestDataAndRequestPOST('/community/activity/search/time', [
            'start' => '2015-07-12 00:00:00',
            'end'   => '2015-08-12 00:00:00',
        ]);
        self::assertDataStructure($response);
        self::assertDataListFields($response->activities);
        self::assertActivityListFields($response->activities[0]);

    }

    public function testThrowExceptionSearchTeamActivitiesByActivityTime()
    {
        $this->prepareTestDataAndRequestPOST('/community/activity/search/time', [
            'start' => '2015-07-12 00:00:00',
            'end'   => '2015-08-12 00:00:00',
        ], 10000, '非法团长', 2);

    }

    //=========================================
    //       searchActivityTitleByTagsAndStatus
    //=========================================
    public function testSuccessfulSearchActivityTitleByTagsAndStatus()
    {
        $this->prepareTestData();
        $this->startSession();
        $adminUser = factory(AdminUser::class)->create();
        $this->actingAs($adminUser, 'extended-eloquent-admin')
            ->ajaxPost('/admin/activity/list', ['_token' => csrf_token(), 'keyword' => '越野跑'])
            ->seeJsonContains(['code' => 0]);
        $response = json_decode($this->response->getContent());
        self::assertDataStructure($response);
        self::assertDataListFields($response->activities);
        self::assertActivityListFields($response->activities[0]);
    }

    public function testSuccessfulSearchActivityTitleByTagsAndStatus_modify_params_no_tags()
    {
        $this->prepareTestData();
        $this->startSession();
        $adminUser = factory(AdminUser::class)->create();
        $this->actingAs($adminUser, 'extended-eloquent-admin')
            ->ajaxPost('/admin/activity/list', [
                '_token' => csrf_token(),
                'tags'   => 1,
            ]);
        $this->seeJsonContains(['code' => 0]);
        $response = json_decode($this->response->getContent());
        self::assertDataStructure($response);
        self::assertDataListFields($response->activities);
        self::assertActivityListFields($response->activities[0]);
    }

    public function testSuccessfulSearchActivityTitleByTagsAndStatus_modify_params_only_stop()
    {
        $this->prepareTestData();
        $this->startSession();
        $adminUser = factory(AdminUser::class)->create();
        $this->actingAs($adminUser, 'extended-eloquent-admin')
            ->ajaxPost('/admin/activity/list', [
                '_token' => csrf_token(),
                'status' => 1,
            ])
            ->seeJsonContains(['code' => 0]);
        $response = json_decode($this->response->getContent());
        self::assertDataStructure($response);
        self::assertDataListFields($response->activities);
        self::assertEquals(2, $response->activities[0]->id);
        self::assertActivityListFields($response->activities[0]);
    }

    //=========================================
    //       sendNotice
    //=========================================
    public function testSuccessfulSendNotice()
    {
        $this->expectsJobs(\Jihe\Jobs\SendMessageToActivityMemberJob::class);
        $this->prepareTestData();
        $this->startSession();
        $user = factory(User::class)->create([
            'id' => 1,
        ]);

        $this->actingAs($user)
            ->ajaxPost('/community/activity/send/notice', [
                '_token'   => csrf_token(),
                'activity' => 2,
                'to_all'   => true,
                'send_way' => 'push',
                'content'  => '是颠三倒四是的是的是是的速度速度是的',
            ]);
        $this->seeJsonContains(['code' => 0]);
    }

    //=========================================
    //       restSendNoticesTimes
    //=========================================
    public function testSuccessfulRestSendNoticesTimes()
    {
        factory(Activity::class)->create([
            'id'                => 11,
            'team_id'           => 1,
            'city_id'           => 1,
            'title'             => '已发布测试活动－－%越野跑',
            'status'            => ActivityEntity::STATUS_PUBLISHED,
            'begin_time'        => date('Y-m-d 08:00:00', strtotime('-1 day')),
            'end_time'          => date('Y-m-d 18:00:00', strtotime('+2 day')),
            'enroll_begin_time' => date('Y-m-d 08:00:00', strtotime('-3 day')),
            'enroll_end_time'   => date('Y-m-d 10:00:00', strtotime('-2 day')),
            'publish_time'      => date('Y-m-d 10:00:00', strtotime('-5 day')),
            'update_step'       => 5,
            'location'          => [16.00211, 20],
            'has_album'         => 1,
        ]);
        $response = $this->prepareTestDataAndRequestGET('/api/manage/act/notice/send/times?activity=11');
        self::assertEquals(\Jihe\Services\MessageService::ACTIVITY_SEND_NOTICES_MAX_TIMES, $response->rest_times);
    }

    public function testSuccessfulRestSendNoticesTimes_End()
    {
        factory(Activity::class)->create([
            'id'                => 11,
            'team_id'           => 1,
            'city_id'           => 1,
            'title'             => '已发布测试活动－－%越野跑',
            'status'            => ActivityEntity::STATUS_PUBLISHED,
            'begin_time'        => date('Y-m-d 08:00:00', strtotime('-2 day')),
            'end_time'          => date('Y-m-d 18:00:00', strtotime('-1 day')),
            'enroll_begin_time' => date('Y-m-d 08:00:00', strtotime('-4 day')),
            'enroll_end_time'   => date('Y-m-d 10:00:00', strtotime('-3 day')),
            'publish_time'      => date('Y-m-d 10:00:00', strtotime('-6 day')),
            'update_step'       => 5,
            'location'          => [16.00211, 20],
            'has_album'         => 1,
        ]);

        $response = $this->prepareTestDataAndRequestGET('/api/manage/act/notice/send/times?activity=11');
        self::assertEquals(\Jihe\Services\MessageService::ACTIVITY_SEND_NOTICES_MAX_TIMES, $response->rest_times);
    }

    public function testSuccessfulRestSendNoticesTimes_ReallyEnd()
    {
        factory(Activity::class)->create([
            'id'                => 11,
            'team_id'           => 1,
            'city_id'           => 1,
            'title'             => '已发布测试活动－－%越野跑',
            'status'            => ActivityEntity::STATUS_PUBLISHED,
            'begin_time'        => date('Y-m-d 08:00:00', strtotime('-12 day')),
            'end_time'          => date('Y-m-d 18:00:00', strtotime('-11 day')),
            'enroll_begin_time' => date('Y-m-d 08:00:00', strtotime('-14 day')),
            'enroll_end_time'   => date('Y-m-d 10:00:00', strtotime('-13 day')),
            'publish_time'      => date('Y-m-d 10:00:00', strtotime('-16 day')),
            'update_step'       => 5,
            'location'          => [16.00211, 20],
            'has_album'         => 1,
        ]);
        $this->prepareTestDataAndRequestGET('/api/manage/act/notice/send/times?activity=11',
            10000,
            '活动已结束超过'.ActivityEntity::MANAGE_SEND_ACTIVITY_DELAY.'天，此功能不可使用');
    }

    public function testSuccessfulRestSendNoticesTimes_NotEnrollEnd()
    {
        factory(Activity::class)->create([
            'id'                => 11,
            'team_id'           => 1,
            'city_id'           => 1,
            'title'             => '已发布测试活动－－%越野跑',
            'status'            => ActivityEntity::STATUS_PUBLISHED,
            'begin_time'        => date('Y-m-d 08:00:00', strtotime('+12 day')),
            'end_time'          => date('Y-m-d 18:00:00', strtotime('+23 day')),
            'enroll_begin_time' => date('Y-m-d 08:00:00', strtotime('+5 day')),
            'enroll_end_time'   => date('Y-m-d 10:00:00', strtotime('+11 day')),
            'publish_time'      => date('Y-m-d 10:00:00', strtotime('-5 day')),
            'update_step'       => 5,
            'location'          => [16.00211, 20],
            'has_album'         => 1,
        ]);
        $this->prepareTestDataAndRequestGET('/api/manage/act/notice/send/times?activity=11&123',
            10000,
            '活动报名尚未开始，此功能不可使用');
    }

    //=========================================
    //       sendNoticeOfSmsForMass
    //=========================================
    public function testFailSendNoticeOfSmsForMass()
    {
        $this->expectsJobs(\Jihe\Jobs\SendMessageToActivityMemberJob::class);
        $this->prepareTestData();
        $user = factory(User::class)->create([
            'id' => 1,
        ]);
        factory(Activity::class)->create([
            'id'                => 11,
            'team_id'           => 1,
            'city_id'           => 1,
            'title'             => '已发布测试活动－－%越野跑',
            'status'            => ActivityEntity::STATUS_PUBLISHED,
            'begin_time'        => date('Y-m-d 08:00:00', strtotime('-1 day')),
            'end_time'          => date('Y-m-d 18:00:00', strtotime('+2 day')),
            'enroll_begin_time' => date('Y-m-d 08:00:00', strtotime('-3 day')),
            'enroll_end_time'   => date('Y-m-d 10:00:00', strtotime('-2 day')),
            'publish_time'      => date('Y-m-d 10:00:00', strtotime('-5 day')),
            'update_step'       => 5,
            'location'          => [16.00211, 20],
            'has_album'         => 1,
        ]);

        $this->startSession();
        $this->actingAs($user)
            ->ajaxPost('/api/manage/act/notice/send', [
                'activity' => 11,
                'content'  => '是颠三倒四是的是的是是的速度速度是的',
            ]);
        $this->seeJsonContains(['code' => 0]);
    }

    public function testFailSendNoticeOfSmsForMass_Exception()
    {
        $this->prepareTestData();
        $user = factory(User::class)->create([
            'id' => 1,
        ]);

        $this->startSession();
        $this->actingAs($user)
            ->ajaxPost('/api/manage/act/notice/send', [
                'activity' => 2,
                'content'  => '是颠三倒四是的是的是是的速度速度是的',
            ]);
        $this->seeJsonContains(['code' => 10000]);
    }

    //=========================================
    //       getActivityPlans
    //=========================================
    public function testSuccessfulGetActivityPlans()
    {
        $response = $this->prepareTestDataAndRequestGET('/community/activity/plan/list?activity=2');
        self::assertEquals(2, $response->total_num);
        self::assertLessThan($response->activities_plans[1]->begin_time, $response->activities_plans[0]->begin_time);
        self::assertLessThan($response->activities_plans[1]->end_time, $response->activities_plans[0]->end_time);
        self::assertLessThan($response->activities_plans[1]->begin_time, $response->activities_plans[0]->end_time);

    }

    //=========================================
    //       createActivityPlans
    //=========================================
    public function testSuccessfulCreateActivityPlans()
    {
        $this->prepareTestData();
        $this->startSession();
        $user = factory(User::class)->create([
            'id' => 1,
        ]);

        $this->actingAs($user)
            ->ajaxPost('/community/activity/plan/add', [
                '_token'   => csrf_token(),
                'activity' => 2,
                'activity_plans'   => json_encode([
                    [
                        'begin_time'  => date('Y-m-d H:i:s', strtotime('3600 seconds')),
                        'end_time'    => date('Y-m-d H:i:s', strtotime('7200 seconds')),
                        'plan_text'   => '随便吃',
                    ],
                ]),
            ]);
        $this->seeJsonContains(['code' => 0]);
        self::seeInDatabase('activity_plan', ['plan_text' => '随便吃', 'activity_id' => 2]);
    }

    //=========================================
    //       getActivityMemberPhone
    //=========================================
    public function testSuccessfulGetActivityMemberPhone()
    {
        $response = $this->prepareTestDataAndRequestGET('/api/manage/act/member/mobile?activity=1');
        self::assertCount(4, $response->mobiles);
    }

    //=========================================
    //       importActivityMembersTemplate
    //=========================================
    public function testSuccessfulImportActivityMembersTemplate()
    {
        $this->prepareTestData();
        $user = factory(User::class)->create(['id' => 1]);
        $this->actingAs($user)->get('/community/activity/import/members/template?activity=2');
        self::assertStringStartsWith('attachment', $this->response->headers->get('Content-Disposition'));
    }

    //=========================================
    //         importActivityMembers
    //=========================================
    public function testImportActivityMembers()
    {
        $this->prepareTestData();
        factory(Activity::class)->create([
            'id'                => 22,
            'team_id'           => 1,
            'city_id'           => 1,
            'title'             => '已发布测试活动－－%越野跑',
            'status'            => ActivityEntity::STATUS_PUBLISHED,
            'begin_time'        => '2015-07-30 08:00:00',
            'end_time'          => '2015-07-30 18:00:00',
            'enroll_begin_time' => '2015-07-20 08:00:00',
            'enroll_end_time'   => '2015-07-25 08:00:00',
            'publish_time'      => '2015-07-18 08:00:00',
            'update_step'       => 5,
            'tags'              => '["跑步", "游泳"]',
            'enroll_attrs'      => '["手机号", "姓名", "性别", "年龄"]',
        ]);

        $this->mockExcelForUploading(__DIR__ . '/test-data/Activity/Member/importMembers.xls');
        $this->startSession();
        $user = factory(User::class)->create(['id' => 1]);
        $this->actingAs($user)->ajaxCall('POST', '/community/activity/import/members', [
            'activity'   => 22,
            '_token' => csrf_token(),
        ], [], [
            'member_list' => $this->makeUploadFile($this->getMockedExcelPath('importMembers.xls')),
        ]);
        $response = json_decode($this->response->getContent());
        self::assertEquals(0, $response->code);
        self::assertEmpty($response->failed);
    }

    public function testImportActivityMembers_HasFailed()
    {
        $this->prepareTestData();
        factory(Activity::class)->create([
            'id'                => 22,
            'team_id'           => 1,
            'city_id'           => 1,
            'title'             => '已发布测试活动－－%越野跑',
            'status'            => ActivityEntity::STATUS_PUBLISHED,
            'begin_time'        => '2015-07-30 08:00:00',
            'end_time'          => '2015-07-30 18:00:00',
            'enroll_begin_time' => '2015-07-20 08:00:00',
            'enroll_end_time'   => '2015-07-25 08:00:00',
            'publish_time'      => '2015-07-18 08:00:00',
            'update_step'       => 5,
            'tags'              => '["跑步", "游泳"]',
            'enroll_attrs'      => '["手机号", "姓名", "性别", "年龄"]',
        ]);

        $this->mockExcelForUploading(__DIR__ . '/test-data/Activity/Member/importMembers_hasFailed.xls');
        $this->startSession();
        $user = factory(User::class)->create(['id' => 1]);
        $this->actingAs($user)->ajaxCall('POST', '/community/activity/import/members', [
            'activity'   => 22,
            '_token' => csrf_token(),
        ], [], [
            'member_list' => $this->makeUploadFile($this->getMockedExcelPath('importMembers_hasFailed.xls')),
        ]);
        $response = json_decode($this->response->getContent());
        self::assertEquals(0, $response->code);
        self::assertCount(7, $response->failed);
        self::assertEquals(30, $response->failed[6]->row);
        self::assertEquals(ActivityApplicantService::ATTR_MOBILE.'格式错误', $response->failed[6]->message);
    }

    /**
     * http method GET request API uri
     *
     * @param string $uri     API uri
     * @param int    $code    API return code
     * @param string $message throw exception message
     * @param int    $userId  user id
     */
    private function prepareTestDataAndRequestGET($uri, $code = 0, $message = null, $userId = 1)
    {
        $this->prepareTestData();
        $user = factory(User::class)->create(['id' => $userId]);
        $this->actingAs($user)->ajaxGet($uri);
        $this->seeJsonContains(['code' => $code]);
        $response = json_decode($this->response->getContent());
        if ($code != 0) {
            self::assertEquals($message, $response->message);
            return null;
        }

        return $response;
    }

    /**
     * @param array $response API return json to array
     * @param array $fields   API return json root index
     */
    private static function assertDataStructure($response, $fields = ['total_num', 'activities'])
    {
        if ($fields) {
            foreach ($fields as $field) {
                self::assertObjectHasAttribute($field, $response);
            }
        }
    }

    /**
     * @param array  $activities API return activity array
     * @param string $type       Api return list or detail
     */
    private static function assertDataListFields($activities, $type = 'list')
    {
        self::assertNotEquals(0, count($activities));
        for ($i = 0; $i < count($activities); $i++) {
            if ($type == 'list') {
                self::assertAnElementInList($activities[$i]);
            } else {
                self::assertElementInInfo($activities[$i]);
                self::assertAnElementInList($activities[$i]);
            }
        }
    }

    private static function assertActivityListFields($activity)
    {
        self::assertEquals(2, $activity->id);
        self::assertEquals('已发布测试活动－－%越野跑', $activity->title);
        self::assertEquals('http://dev.image.com.cn/default/activity1.png', $activity->cover_url);
        self::assertEquals('四川省成都市高新区萃华路xxx号', $activity->address);
        self::assertEquals('花样年：香年广场', $activity->brief_address);
        self::assertEquals(3, $activity->enroll_fee_type);
        self::assertEquals(9.98, $activity->enroll_fee);
        self::assertEquals(1, $activity->team->id);
        self::assertEquals(1, $activity->city->id);
        self::assertEquals('2015-07-30 08:00:00', $activity->begin_time);
        self::assertEquals('2015-07-30 18:00:00', $activity->end_time);
        self::assertEquals('2015-07-18 08:00:00', $activity->publish_time);
        self::assertEquals(0, $activity->essence);
    }

    private static function assertActivityDetailFields($activity)
    {
        self::assertEquals(2, $activity->id);
        self::assertEquals('已发布测试活动－－%越野跑', $activity->title);
        self::assertEquals('http://dev.image.com.cn/default/activity1.png', $activity->cover_url);
        self::assertEquals('四川省成都市高新区萃华路xxx号', $activity->address);
        self::assertEquals('花样年：香年广场', $activity->brief_address);
        self::assertEquals(3, $activity->enroll_fee_type);
        self::assertEquals(9.98, $activity->enroll_fee);
        self::assertEquals(1, $activity->team->id);
        self::assertEquals(1, $activity->city->id);
        self::assertEquals('13801380000', $activity->telephone);
        self::assertEquals('不认识', $activity->contact);
        self::assertEquals('我不知道写什么 …… ……', $activity->detail);
        self::assertEquals('2015-07-30 08:00:00', $activity->begin_time);
        self::assertEquals('2015-07-30 18:00:00', $activity->end_time);
        self::assertEquals('2015-07-20 08:00:00', $activity->enroll_begin_time);
        self::assertEquals('2015-07-25 08:00:00', $activity->enroll_end_time);
        self::assertEquals(1, $activity->enroll_type);
        self::assertEquals(1000, $activity->enroll_limit);
        self::assertEquals(3, count($activity->enroll_attrs));
        self::assertEquals('手机号', $activity->enroll_attrs[0]);
        self::assertEquals(1, $activity->status);
        self::assertEquals(2, count($activity->location));
        self::assertEquals(12.5, $activity->location[0]);
        self::assertEquals(34.3434, $activity->location[1]);
        self::assertEquals(49.4535434, $activity->roadmap[1][0]);
        self::assertEquals(88, $activity->roadmap[1][1]);
        self::assertEquals('2015-07-18 08:00:00', $activity->publish_time);
        self::assertEquals(5, $activity->update_step);
        self::assertEquals(0, $activity->essence);
        self::assertEquals(4, count($activity->images_url));
    }

    /**
     * http method POST request API uri
     *
     * @param string $uri     API uri
     * @param int    $code    API return code
     * @param string $message throw exception message
     * @param int    $user    user id
     *
     * @return array
     */
    private function prepareTestDataAndRequestPOST($uri, $postData, $code = 0, $message = null, $user = 1)
    {
        $this->prepareTestData();
        $this->startSession();
        $user = factory(User::class)->create(['id' => $user]);
        $postData['_token'] = csrf_token();
        $this->actingAs($user)
            ->ajaxPost($uri, $postData);
        $this->seeJsonContains(['code' => $code]);
        $response = json_decode($this->response->getContent());
        if ($code != 0) {
            self::assertEquals($message, $response->message);
        }

        return $response;
    }

    private function prepareTestData()
    {
        factory(ActivityPlan::class)->create([
            'id'          => 1,
            'activity_id' => 2,
            'begin_time'  => date('Y-m-d H:i:s', strtotime('240 seconds')),
            'end_time'    => date('Y-m-d H:i:s', strtotime('1440 seconds')),
            'plan_text'   => '东方热哦陪外婆玩儿玩儿问',
        ]);

        factory(ActivityPlan::class)->create([
            'id'          => 2,
            'activity_id' => 2,
            'begin_time'  => date('Y-m-d H:i:s', strtotime('1600 seconds')),
            'end_time'    => date('Y-m-d H:i:s', strtotime('2880 seconds')),
            'plan_text'   => '东方',
        ]);

        factory(City::class)->create([
            'id'   => 1,
            'name' => '成都',
        ]);

        factory(ActivityCheckInQRCode::class)->create([
            'activity_id'   => 2,
        ]);

        factory(AdminUser::class)->create([
            'id' => 1,
        ]);

        factory(ActivityApplicant::class)->create([
            'user_id'     => 1,
            'activity_id' => 2,
        ]);

        factory(ActivityApplicant::class)->create([
            'user_id'     => 2,
            'activity_id' => 2,
        ]);

        factory(ActivityApplicant::class)->create([
            'user_id'     => 3,
            'activity_id' => 2,
        ]);

        factory(ActivityMember::class)->create([
            'user_id'     => 1,
            'activity_id' => 1,
        ]);

        factory(ActivityMember::class)->create([
            'user_id'     => 2,
            'activity_id' => 1,
        ]);

        factory(ActivityMember::class)->create([
            'user_id'     => 3,
            'activity_id' => 1,
        ]);

        factory(ActivityMember::class)->create([
            'user_id'     => 4,
            'activity_id' => 1,
        ]);

        factory(Team::class)->create([
            'id'         => 1,
            'city_id'    => 1,
            'creator_id' => 1,
            'email'      => 'email@email.com',
            'logo_url'   => '20150635/20152685.jpg',
        ]);

        factory(Activity::class)->create([
            'id'         => 1,
            'team_id'    => 1,
            'city_id'    => 1,
            'title'      => '未发布测试活动－－%游泳',
            'status'     => ActivityEntity::STATUS_NOT_PUBLISHED,
            'begin_time' => '2015-07-28 08:00:00',
            'end_time'   => '2015-07-28 18:00:00',
        ]);

        factory(Activity::class)->create([
            'id'                => 2,
            'team_id'           => 1,
            'city_id'           => 1,
            'title'             => '已发布测试活动－－%越野跑',
            'status'            => ActivityEntity::STATUS_PUBLISHED,
            'begin_time'        => '2015-07-30 08:00:00',
            'end_time'          => '2015-07-30 18:00:00',
            'enroll_begin_time' => '2015-07-20 08:00:00',
            'enroll_end_time'   => '2015-07-25 08:00:00',
            'publish_time'      => '2015-07-18 08:00:00',
            'update_step'       => 5,
            'tags'              => '["跑步", "游泳"]',
        ]);

        factory(Activity::class)->create([
            'id'                => 3,
            'team_id'           => 1,
            'city_id'           => 1,
            'title'             => '已发布测试活动－－%越野跑',
            'status'            => ActivityEntity::STATUS_DELETE,
            'begin_time'        => '2015-07-30 08:00:00',
            'end_time'          => '2015-07-30 18:00:00',
            'enroll_begin_time' => '2015-07-20 08:00:00',
            'enroll_end_time'   => '2015-07-25 08:00:00',
            'publish_time'      => '2015-07-18 08:00:00',
            'update_step'       => 5,
        ]);
    }

    /**
     * assert common activities list by element
     *
     * @param $activity
     */
    private static function assertAnElementInList($activity)
    {
        self::assertObjectHasAttribute('id', $activity);
        self::assertObjectHasAttribute('title', $activity);
        self::assertObjectHasAttribute('sub_status', $activity);
        self::assertObjectHasAttribute('cover_url', $activity);
        self::assertObjectHasAttribute('address', $activity);
        self::assertObjectHasAttribute('brief_address', $activity);
        self::assertObjectHasAttribute('enroll_fee_type', $activity);
        self::assertObjectHasAttribute('enroll_fee', $activity);
    }

    /**
     * assert activity detail by element
     *
     * @param $activity
     */
    private static function assertElementInInfo($activity)
    {
        self::assertObjectHasAttribute('begin_time', $activity);
        self::assertObjectHasAttribute('end_time', $activity);
        self::assertObjectHasAttribute('enroll_begin_time', $activity);
        self::assertObjectHasAttribute('enroll_end_time', $activity);
        self::assertObjectHasAttribute('enroll_type', $activity);
        self::assertObjectHasAttribute('enroll_limit', $activity);
        self::assertObjectHasAttribute('enroll_attrs', $activity);
        self::assertObjectHasAttribute('roadmap', $activity);
        self::assertObjectHasAttribute('location', $activity);
        self::assertObjectHasAttribute('logo_url', $activity->team);
        self::assertObjectHasAttribute('introduction', $activity->team);
        self::assertEquals(2, count($activity->location));
    }

    //=========================================
    //            list album images
    //=========================================
    public function testSuccessfulListSponsorAlbumImages()
    {
        $user = factory(\Jihe\Models\User::class)->create(['id' => 1]);
        factory(\Jihe\Models\City::class)->create([
            'id' => 1,
        ]);

        factory(Team::class)->create([
            'id'         => 1,
            'city_id'    => 1,
            'creator_id' => 1,
            'status'     => TeamEntity::STATUS_NORMAL,
        ]);

        factory(Activity::class)->create([
            'id'        => 1,
            'team_id'   => 1,
            'city_id'   => 1,
            'title'     => '活动1',
            'status'    => ActivityEntity::STATUS_PUBLISHED,
            'location'  => [15.99716, 20],
            'has_album' => 1,
        ]);

        factory(\Jihe\Models\ActivityAlbumImage::class)->create([
            'activity_id'  => 1,
            'creator_type' => ActivityAlbumImageEntity::SPONSOR,
        ]);

        $this->startSession();

        $this->actingAs($user)
            ->ajaxGet('/community/activity/album/image/list?activity=1&creator_type=0&page=1&size=5');

        $this->seeJsonContains(['code' => 0]);

        $result = json_decode($this->response->getContent());

        self::assertObjectHasAttribute('pages', $result);
        self::assertObjectHasAttribute('images', $result);

        $image = $result->images[0];
        self::assertObjectHasAttribute('id', $image);
        self::assertObjectHasAttribute('image_url', $image);
        self::assertObjectHasAttribute('created_at', $image);
    }

    public function testSuccessfulListSponsorAlbumImages_UserTime()
    {
        $user = factory(\Jihe\Models\User::class)->create(['id' => 1]);
        factory(\Jihe\Models\City::class)->create([
            'id' => 1,
        ]);

        factory(Team::class)->create([
            'id'         => 1,
            'city_id'    => 1,
            'creator_id' => 1,
            'status'     => TeamEntity::STATUS_NORMAL,
        ]);

        factory(Activity::class)->create([
            'id'        => 1,
            'team_id'   => 1,
            'city_id'   => 1,
            'title'     => '活动1',
            'status'    => ActivityEntity::STATUS_PUBLISHED,
            'location'  => [15.99716, 20],
            'has_album' => 1,
        ]);

        factory(\Jihe\Models\ActivityAlbumImage::class)->create([
            'id'           => 1,
            'activity_id'  => 1,
            'creator_type' => ActivityAlbumImageEntity::SPONSOR,
            'created_at'   => '2015-07-01 00:00:00',
        ]);

        $this->startSession();

        $this->actingAs($user)
            ->ajaxGet('/community/activity/album/image/list?activity=1&creator_type=0&last_created_at=2015-08-01 00:00:00&last_id=5');

        $this->seeJsonContains(['code' => 0]);

        $result = json_decode($this->response->getContent());

        self::assertObjectHasAttribute('pages', $result);
        self::assertObjectHasAttribute('images', $result);

        $image = $result->images[0];
        self::assertObjectHasAttribute('id', $image);
        self::assertObjectHasAttribute('image_url', $image);
        self::assertObjectHasAttribute('created_at', $image);
    }

    public function testSuccessfulListPendingUserAlbumImages()
    {
        $user = factory(\Jihe\Models\User::class)->create(['id' => 1]);
        factory(\Jihe\Models\City::class)->create([
            'id' => 1,
        ]);

        factory(Team::class)->create([
            'id'         => 1,
            'city_id'    => 1,
            'creator_id' => 1,
            'status'     => TeamEntity::STATUS_NORMAL,
        ]);

        factory(Activity::class)->create([
            'id'        => 1,
            'team_id'   => 1,
            'city_id'   => 1,
            'title'     => '活动1',
            'status'    => ActivityEntity::STATUS_PUBLISHED,
            'location'  => [15.99716, 20],
            'has_album' => 1,
        ]);

        factory(\Jihe\Models\ActivityAlbumImage::class)->create([
            'activity_id'  => 1,
            'creator_type' => ActivityAlbumImageEntity::USER,
        ]);

        $this->startSession();

        $this->actingAs($user)
            ->ajaxGet('/community/activity/album/image/list?activity=1&creator_type=1&page=1&size=5');

        $this->seeJsonContains(['code' => 0]);

        $result = json_decode($this->response->getContent());

        self::assertObjectHasAttribute('pages', $result);
        self::assertObjectHasAttribute('images', $result);

        $image = $result->images[0];
        self::assertObjectHasAttribute('id', $image);
        self::assertObjectHasAttribute('image_url', $image);
        self::assertObjectHasAttribute('created_at', $image);
    }

    //=========================================
    //            add album image
    //=========================================
    public function testSuccessfulAddAlbumImage()
    {
        $user = factory(\Jihe\Models\User::class)->create(['id' => 1]);
        factory(\Jihe\Models\City::class)->create([
            'id' => 1,
        ]);

        factory(Team::class)->create([
            'id'         => 1,
            'city_id'    => 1,
            'creator_id' => 1,
            'status'     => TeamEntity::STATUS_NORMAL,
        ]);

        factory(Activity::class)->create([
            'id'        => 1,
            'team_id'   => 1,
            'city_id'   => 1,
            'title'     => '活动1',
            'status'    => ActivityEntity::STATUS_PUBLISHED,
            'location'  => [15.99716, 20],
            'has_album' => 1,
        ]);

        $this->mockStorageService();
        $this->mockImageForUploading(__DIR__ . '/test-data/panda.jpg');
        $this->startSession();

        $this->actingAs($user)->call('POST', '/community/activity/album/image/add', [
            '_token'   => csrf_token(),
            'activity' => 1,
        ], [], [
            'image' => $this->makeUploadFile($this->getMockedImagePath('panda.jpg')),
        ]);

        $this->seeJsonContains(['code' => 0]);

        $result = json_decode($this->response->getContent());
        self::assertObjectHasAttribute('id', $result);
        self::assertObjectHasAttribute('image_url', $result);
        self::assertObjectHasAttribute('created_at', $result);
    }

    //=========================================
    //           approve album images
    //=========================================
    public function testSuccessfulApproveAlbumImages()
    {
        $user = factory(\Jihe\Models\User::class)->create(['id' => 1]);
        factory(\Jihe\Models\City::class)->create([
            'id' => 1,
        ]);

        factory(Team::class)->create([
            'id'         => 1,
            'city_id'    => 1,
            'creator_id' => 1,
            'status'     => TeamEntity::STATUS_NORMAL,
        ]);

        factory(Activity::class)->create([
            'id'        => 1,
            'team_id'   => 1,
            'city_id'   => 1,
            'title'     => '活动1',
            'status'    => ActivityEntity::STATUS_PUBLISHED,
            'location'  => [15.99716, 20],
            'has_album' => 1,
        ]);

        factory(\Jihe\Models\ActivityAlbumImage::class)->create([
            'id'           => 1,
            'activity_id'  => 1,
            'creator_type' => ActivityAlbumImageEntity::USER,
            'status'       => ActivityAlbumImageEntity::STATUS_PENDING,
        ]);

        factory(\Jihe\Models\ActivityAlbumImage::class)->create([
            'id'           => 2,
            'activity_id'  => 1,
            'creator_type' => ActivityAlbumImageEntity::USER,
            'status'       => ActivityAlbumImageEntity::STATUS_PENDING,
        ]);

        $this->mockStorageService();
        $this->mockImageForUploading(__DIR__ . '/test-data/panda.jpg');
        $this->startSession();

        $this->actingAs($user)->ajaxPost('/community/activity/album/image/approve', [
            '_token'   => csrf_token(),
            'activity' => 1,
            'images'   => [1, 2],
        ]);

        $this->seeJsonContains(['code' => 0]);
    }

    //=========================================
    //           remove album images
    //=========================================
    public function testSuccessfulRemoveAlbumImages()
    {
        $user = factory(\Jihe\Models\User::class)->create(['id' => 1]);
        factory(\Jihe\Models\City::class)->create([
            'id' => 1,
        ]);

        factory(Team::class)->create([
            'id'         => 1,
            'city_id'    => 1,
            'creator_id' => 1,
            'status'     => TeamEntity::STATUS_NORMAL,
        ]);

        factory(Activity::class)->create([
            'id'        => 1,
            'team_id'   => 1,
            'city_id'   => 1,
            'title'     => '活动1',
            'status'    => ActivityEntity::STATUS_PUBLISHED,
            'location'  => [15.99716, 20],
            'has_album' => 1,
        ]);

        factory(\Jihe\Models\ActivityAlbumImage::class)->create([
            'id'           => 1,
            'activity_id'  => 1,
            'creator_type' => ActivityAlbumImageEntity::USER,
            'status'       => ActivityAlbumImageEntity::STATUS_PENDING,
        ]);

        factory(\Jihe\Models\ActivityAlbumImage::class)->create([
            'id'           => 2,
            'activity_id'  => 1,
            'creator_type' => ActivityAlbumImageEntity::USER,
            'status'       => ActivityAlbumImageEntity::STATUS_PENDING,
        ]);

        $this->mockStorageService();
        $this->mockImageForUploading(__DIR__ . '/test-data/panda.jpg');
        $this->startSession();

        $this->actingAs($user)->ajaxPost('/community/activity/album/image/remove', [
            '_token'   => csrf_token(),
            'activity' => 1,
            'images'   => [1, 2],
        ]);

        $this->seeJsonContains(['code' => 0]);
    }

    //=========================================
    //               listNotices
    //=========================================
    public function testSuccessfullListNotices()
    {
        $user = factory(\Jihe\Models\User::class)->create(['id' => 1]);
        factory(\Jihe\Models\City::class)->create([
            'id' => 1,
        ]);

        factory(Team::class)->create([
            'id'         => 1,
            'city_id'    => 1,
            'creator_id' => 1,
            'status'     => TeamEntity::STATUS_NORMAL,
        ]);

        factory(Activity::class)->create([
            'id'        => 1,
            'team_id'   => 1,
            'city_id'   => 1,
            'title'     => '活动1',
            'status'    => ActivityEntity::STATUS_PUBLISHED,
            'location'  => [15.99716, 20],
            'has_album' => 1,
        ]);

        factory(\Jihe\Models\Message::class)->create([
            'team_id'     => 1,
            'activity_id' => 1,
            'user_id'     => null,
        ]);
        factory(\Jihe\Models\Message::class)->create([
            'team_id'     => 1,
            'activity_id' => 1,
            'user_id'     => 1,
        ]);
        factory(\Jihe\Models\Message::class)->create([
            'team_id'     => 1,
            'activity_id' => 1,
            'user_id'     => 2,
        ]);
        factory(\Jihe\Models\Message::class)->create([
            'team_id'     => 1,
            'activity_id' => null,
            'user_id'     => null,
        ]);
        factory(\Jihe\Models\Message::class)->create([
            'team_id'     => null,
            'activity_id' => null,
            'user_id'     => null,
        ]);

        $this->actingAs($user)
            ->ajaxGet("/community/activity/notice/list?page=1&size=10&activity=1");
        $this->seeJsonContains(['code' => 0]);

        $result = json_decode($this->response->getContent());

        $this->assertObjectHasAttribute('pages', $result);
        $this->assertObjectHasAttribute('messages', $result);
        $this->assertEquals(1, $result->pages);
        $this->assertCount(3, $result->messages);

        $message = $result->messages[0];
        $this->assertObjectHasAttribute('id', $message);
        $this->assertObjectHasAttribute('content', $message);
        $this->assertObjectHasAttribute('created_at', $message);
    }

    //=========================================
    //                add file
    //=========================================
    public function testSuccessfulAddFile()
    {
        $user = factory(\Jihe\Models\User::class)->create(['id' => 1]);
        factory(\Jihe\Models\City::class)->create([
            'id' => 1,
        ]);

        factory(Team::class)->create([
            'id'         => 1,
            'city_id'    => 1,
            'creator_id' => 1,
            'status'     => TeamEntity::STATUS_NORMAL,
        ]);

        factory(Activity::class)->create([
            'id'        => 1,
            'team_id'   => 1,
            'city_id'   => 1,
            'title'     => '活动1',
            'status'    => ActivityEntity::STATUS_PUBLISHED,
            'location'  => [15.99716, 20],
            'has_album' => 1,
        ]);

        $this->mockStorageService();
        $this->mockImageForUploading(__DIR__ . '/test-data/panda.jpg');
        $this->startSession();

        $this->actingAs($user)->call('POST', '/community/activity/file/add', [
            '_token'   => csrf_token(),
            'activity' => 1,
        ], [], [
            'file' => $this->makeUploadFile($this->getMockedImagePath('panda.jpg')),
        ]);

        $this->seeJsonContains(['code' => 0]);

        $result = json_decode($this->response->getContent());
        $this->assertObjectHasAttribute('id', $result);
        $this->assertObjectHasAttribute('name', $result);
        $this->assertObjectHasAttribute('memo', $result);
        $this->assertObjectHasAttribute('size', $result);
        $this->assertObjectHasAttribute('extension', $result);
        $this->assertObjectHasAttribute('url', $result);
        $this->assertObjectHasAttribute('created_at', $result);
    }

    //=========================================
    //              list files
    //=========================================
    public function testSuccessfulListFiles()
    {
        $user = factory(\Jihe\Models\User::class)->create(['id' => 1]);
        factory(\Jihe\Models\City::class)->create([
            'id' => 1,
        ]);

        factory(Team::class)->create([
            'id'         => 1,
            'creator_id' => 1,
            'city_id'    => 1,
            'status'     => TeamEntity::STATUS_NORMAL,
        ]);

        factory(Activity::class)->create([
            'id'        => 1,
            'team_id'   => 1,
            'city_id'   => 1,
            'title'     => '活动1',
            'status'    => ActivityEntity::STATUS_PUBLISHED,
            'location'  => [15.99716, 20],
            'has_album' => 1,
        ]);

        factory(\Jihe\Models\ActivityFile::class)->create([
            'activity_id'  => 1,
        ]);

        factory(\Jihe\Models\ActivityFile::class)->create([
            'activity_id'  => 1,
        ]);

        factory(\Jihe\Models\ActivityFile::class)->create([
            'activity_id'  => 2,
        ]);

        $this->startSession();

        $this->actingAs($user)
             ->ajaxGet('/community/activity/file/list?activity=1&page=1&size=1');

        $this->seeJsonContains(['code' => 0]);

        $result = json_decode($this->response->getContent());

        $this->assertObjectHasAttribute('pages', $result);
        $this->assertEquals(2, $result->pages);
        $this->assertObjectHasAttribute('files', $result);
        $this->assertCount(1, $result->files);

        $file = $result->files[0];
        $this->assertObjectHasAttribute('id', $file);
        $this->assertObjectHasAttribute('name', $file);
        $this->assertObjectHasAttribute('memo', $file);
        $this->assertObjectHasAttribute('size', $file);
        $this->assertObjectHasAttribute('extension', $file);
        $this->assertObjectHasAttribute('url', $file);
        $this->assertObjectHasAttribute('created_at', $file);
    }

    //=========================================
    //             remove files
    //=========================================
    public function testSuccessfulRemoveFiles()
    {
        $user = factory(\Jihe\Models\User::class)->create(['id' => 1]);
        factory(\Jihe\Models\City::class)->create([
            'id' => 1,
        ]);

        factory(Team::class)->create([
            'id'         => 1,
            'city_id'    => 1,
            'creator_id' => 1,
            'status'     => TeamEntity::STATUS_NORMAL,
        ]);

        factory(Activity::class)->create([
            'id'        => 1,
            'team_id'   => 1,
            'city_id'   => 1,
            'title'     => '活动1',
            'status'    => ActivityEntity::STATUS_PUBLISHED,
            'location'  => [15.99716, 20],
            'has_album' => 1,
        ]);

        factory(\Jihe\Models\ActivityFile::class)->create([
            'id'           => 1,
            'activity_id'  => 1,
        ]);

        factory(\Jihe\Models\ActivityFile::class)->create([
            'id'           => 2,
            'activity_id'  => 1,
        ]);

        factory(\Jihe\Models\ActivityFile::class)->create([
            'id'           => 3,
            'activity_id'  => 2,
        ]);

        $this->startSession();

        $this->actingAs($user)->ajaxPost(
            '/community/activity/file/remove',
            [
                '_token'   => csrf_token(),
                'activity' => 1,
                'files'   => [1, 2],
            ]
        );

        $this->seeJsonContains(['code' => 0]);

        $this->notSeeInDatabase('activity_files', [
            'id'          => 1,
            'activity_id' => 1,
            'deleted_at'  => null,
        ]);

        $this->notSeeInDatabase('activity_files', [
            'id'          => 2,
            'activity_id' => 1,
            'deleted_at'  => null,
        ]);

        $this->seeInDatabase('activity_files', [
            'id'          => 3,
            'activity_id' => 2,
            'deleted_at'  => null,
        ]);
    }

    /**
     * mock image file for uploading
     *
     * @param string $path file path to existing image file
     * @param string $name name in the mocked file system (virtual file system, exactly)
     */
    private function mockImageForUploading($path, $name = null)
    {
        // populate name if not given
        !$name && $name = pathinfo($path, PATHINFO_BASENAME);

        // put excel files in 'imageupload' directory - the root directory
        // NOTE: 'imageupload' is also used by getMockedExcelUrl() below
        $this->mockFiles('imageupload', [
            $name => file_get_contents($path),
        ]);
        // no need to register mime type guessers, as the default FileinfoMimeTypeGuesser
        // works on images
    }

    /**
     * get mocked image file's full path (including scheme and root directory)
     *
     * @param $path      path to mocked image file
     *
     * @return string    full path of the mocked image file
     */
    private function getMockedImagePath($path)
    {
        return $this->getMockedFilePath(sprintf('imageupload/%s', ltrim($path, '/')));
    }

    private function mockStorageService($return = 'http://domain/key.png')
    {
        $storageService = \Mockery::mock(\Jihe\Services\StorageService::class);
        $storageService->shouldReceive('isTmp')->withAnyArgs()->andReturn(true);
        $storageService->shouldReceive('storeAsFile')->withAnyArgs()->andReturn($return);
        $storageService->shouldReceive('storeAsImage')->withAnyArgs()->andReturn($return);
        $storageService->shouldReceive('remove')->withAnyArgs()->andReturn(false);
        $this->app [\Jihe\Services\StorageService::class] = $storageService;

        return $storageService;
    }

    /**
     * mock excel file for uploading (as whitelist)
     * @param string $path    file path to existing excel file
     * @param string $name    name in the mocked file system (virtual file system, exactly)
     */
    private function mockExcelForUploading($path, $name = null)
    {
        // populate name if not given
        !$name && $name = pathinfo($path, PATHINFO_BASENAME);
        // put excel files in 'activitymember' directory - the root directory
        // NOTE: 'activitymember' is also used by getMockedExcelUrl() below
        $this->mockFiles('activitymember', [
            $name => file_get_contents($path),
        ]);

        // mime_content_type() does not produce precise mime type sometimes,
        // for example, mime_content_type('/path/to/excel/file') may produce
        // 'application/vnd.ms-office', but we want is 'application/vnd.ms-excel'
        // so that ExtensionGuessers can give desired file extension and validation
        // against mime types (the mimes validator) can pass.
        // On the other hand, FileinfoMimeTypeGuesser cannot produce desired
        // mime type as well. For mocked excel files, it simply produces
        // 'application/octet-stream', which is definitely improper.
        MimeTypeGuesser::getInstance()->register(new MappedMimeTypeGuesser([
            $this->getMockedExcelPath($name) => 'application/vnd.ms-excel'
        ]));
    }

    /**
     * get mocked excel file's full path (including scheme and root directory)
     *
     * @param $path      path to mocked excel file
     * @return string    full path of the mocked excel file
     */
    private function getMockedExcelPath($path)
    {
        return $this->getMockedFilePath(sprintf('activitymember/%s', ltrim($path, '/')));
    }

    private function mockElasticSearchClient($return = 'key')
    {
//        $this->app->singleton(\Jihe\Contracts\Services\Search\SearchService::class, function ($app) {
//            $servers = $app['config']['search.servers'];
//            $elasticaClient = new \Elastica\Client($servers);
//
//            return new \Jihe\Services\Search\ElasticSearchService($elasticaClient,
//                $app[\Jihe\Contracts\Repositories\TeamRepository::class],
//                $app[\Jihe\Contracts\Repositories\ActivityRepository::class]);
//        });
//
//        $elasticaSearchClient = \Mockery::mock(\Jihe\Contracts\Services\Storage\StorageService::class);
//        $storageService->shouldReceive('storeTmp')->withAnyArgs()->andReturn($return);
//        $storageService->shouldReceive('store')->withAnyArgs()->andReturn($return);
//        $this->app[\Jihe\Contracts\Services\Storage\StorageService::class] = $storageService;
//
//        return $storageService;
    }
}
