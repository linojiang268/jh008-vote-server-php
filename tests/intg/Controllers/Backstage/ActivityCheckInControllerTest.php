<?php
namespace intg\Jihe\Controllers\Backstage;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use intg\Jihe\TestCase;
use intg\Jihe\RequestSignCheck;
use intg\Jihe\AuthDeviceCheck;
use Jihe\Exceptions\ExceptionCode;

class ActivityCheckInControllerTest extends TestCase
{
    use DatabaseTransactions;
    use RequestSignCheck;
    use AuthDeviceCheck;

    //=========================================
    //      listActivityCheckIn
    //=========================================
    public function testListActivityCheckInSuccessfully()
    {
        $user = $this->prepareTeamActivityAndFounder(1000);
        $this->prepareUserData();
        $this->prepareCheckInData();

        $this->startSession();
        $this->actingAs($user)->ajaxGet(
            '/community/activity/checkin/list/all?activity_id=1&step=1'
        )->seeJsonContains(['code' => 0]);
        $response = json_decode($this->response->getContent());
        self::assertEquals(0, $response->code);
        self::assertEquals(2, $response->total);
        self::assertObjectHasAttribute('nick_name', $response->check_ins[0]);
        self::assertObjectHasAttribute('mobile', $response->check_ins[0]);
        self::assertObjectHasAttribute('user_id', $response->check_ins[0]);
        self::assertObjectHasAttribute('step', $response->check_ins[0]);
        self::assertObjectHasAttribute('created_at', $response->check_ins[0]);
    }

    public function testListActivityCheckInSuccessfully_NoTeamPermission()
    {
        $user = factory(\Jihe\Models\User::class)->create([
            'id'     => 1000,
            'mobile' => '13800138000',
        ]);
        $this->prepareUserData();
        $this->prepareCheckInData();

        $this->startSession();
        $this->actingAs($user)->ajaxGet(
            '/community/activity/checkin/list/all?activity_id=1&step=1'
        )->seeJsonContains(['code' => 10000, 'message' => '非法团长']);
    }

    public function testListActivityCheckInSuccessfully_NoActivityPermission()
    {
        $user = $this->prepareTeamActivityAndFounder(1000);
        $this->prepareUserData();
        $this->prepareCheckInData(2);

        $this->startSession();
        $this->actingAs($user)->ajaxGet(
            '/community/activity/checkin/list/all?activity_id=2&step=1'
        )->seeJsonContains([
            'code'    => 10000,
            'message' => '非法操作：无操作权限',
        ]);
    }

    //=========================================
    //      listForClientManage
    //=========================================
    public function testListForClientManage_WaitingList()
    {
        $user = $this->prepareTeamActivityAndFounder(1);
        $this->prepareActivityMemberData(1, 5, ['checkin' => 0]);

        $url = 'api/manage/act/checkin/list?' . http_build_query([
                'activity' => 1,
                'type'     => 0,
                'page'     => 1,
                'size'     => 2,
            ]);

        $this->startSession();
        $this->actingAs($user)->ajaxGet($url)
            ->seeJsonContains(['code' => 0]);

        $response = json_decode($this->response->getContent());

        self::assertEquals(5, $response->total);
        self::assertCount(2, $response->checkins);
        self::assertEquals(1, $response->checkins[0]->id);
        self::assertEquals(0, $response->checkins[0]->status);
        self::assertEquals(1, $response->checkins[0]->check_by_user);
    }

    public function testListForClientManage_DoneList()
    {
        $user = $this->prepareTeamActivityAndFounder(1);
        $this->prepareActivityMemberData(1, 5, ['checkin' => 1]);

        $url = 'api/manage/act/checkin/list?' . http_build_query([
                'activity' => 1,
                'type'     => 1,
                'page'     => 1,
                'size'     => 2,
            ]);

        $this->startSession();
        $this->actingAs($user)->ajaxGet($url)
            ->seeJsonContains(['code' => 0]);

        $response = json_decode($this->response->getContent());

        self::assertEquals(5, $response->total);
        self::assertCount(2, $response->checkins);
        self::assertEquals(1, $response->checkins[0]->id);
        self::assertEquals(1, $response->checkins[0]->status);
        self::assertEquals(0, $response->checkins[0]->check_by_user);
    }

    //=========================================
    //      searchInfo
    //=========================================
    public function testSearchInfoSuccessfully_UseName()
    {
        $user = $this->prepareTeamActivityAndFounder(1);
        $this->prepareActivityMemberData(1, 1, [
            'user_id'   => 10,
            'mobile'    => '13800138000',
            'name'      => '签到阿三',
            'checkin'   => 1,
        ]);

        $url = 'api/manage/act/checkin/search?' . http_build_query([
                'activity'  => 1,
                'search'    => '阿三',
        ]);

        $this->startSession();
        $this->actingAs($user)->ajaxGet($url)->seeJsonContains(['code' => 0]);

        $response = json_decode($this->response->getContent());
        self::assertCount(1, $response->checkins);
        self::assertEquals(1, $response->checkins[0]->id);
        self::assertEquals(10, $response->checkins[0]->user_id);
        self::assertEquals('13800138000', $response->checkins[0]->mobile);
        self::assertEquals(1, $response->checkins[0]->status);
        self::assertEquals(0, $response->checkins[0]->check_by_user);
    }

    public function testSearchInfoSuccessfully_UseMobile()
    {
        $user = $this->prepareTeamActivityAndFounder(1);
        $this->prepareActivityMemberData(1, 1, [
            'user_id'   => 10,
            'mobile'    => '13800138000',
            'name'      => '签到阿三',
            'checkin'   => 1,
        ]);

        $url = 'api/manage/act/checkin/search?' . http_build_query([
                'activity'  => 1,
                'search'    => '13800138000',
        ]);

        $this->startSession();
        $this->actingAs($user)->ajaxGet($url)->seeJsonContains(['code' => 0]);

        $response = json_decode($this->response->getContent());
        self::assertCount(1, $response->checkins);
        self::assertEquals(1, $response->checkins[0]->id);
        self::assertEquals(10, $response->checkins[0]->user_id);
        self::assertEquals('13800138000', $response->checkins[0]->mobile);
        self::assertEquals(1, $response->checkins[0]->status);
        self::assertEquals(0, $response->checkins[0]->check_by_user);
    }

    //=============================================
    //          manageCheckIn
    //=============================================
    public function testManageCheckInSuccessfully()
    {
        $user = factory(\Jihe\Models\User::class)->create([
            'id'     => 2,
            'mobile' => '13800138001',
        ]);
        $this->createCheckInRelatedData();
        $this->startSession();
        $this->actingAs($user)->ajaxPost('api/manage/act/checkin', [
            'user'        => 1,
            'step'        => 1,
            'activity_id' => 2,

        ])->seeJsonContains(['code' => 0]);

        $this->seeInDatabase('activity_check_in', [
            'user_id'     => 1,
            'process_id'  => 2,
            'activity_id' => 2,
            'step'        => 1,
        ]);
    }

    public function testManageCheckInSuccessfully_DoubleCheckIn()
    {
        $user = factory(\Jihe\Models\User::class)->create([
            'id'     => 2,
            'mobile' => '13800138001',
        ]);
        factory(\Jihe\Models\ActivityCheckIn::class)->create([
            'user_id'     => 1,
            'process_id'  => 2,
            'activity_id' => 2,
            'step'        => 1,
        ]);
        $this->createCheckInRelatedData();
        $this->startSession();
        $this->actingAs($user)->ajaxPost('api/manage/act/checkin', [
            'user'        => 1,
            'step'        => 1,
            'activity_id' => 2,
        ])->seeJsonContains([
            'code'    => 0,
            'message' => '你已经签过到了',
        ]);
    }

    //=============================================
    //          qrcodeCheckIn
    //=============================================
    public function testQrcodeCheckInSuccessfully()
    {
        $user = factory(\Jihe\Models\User::class)->create([
            'id'            => 2,
            'mobile'        => '13800138001',
        ]);
        $this->createCheckInRelatedData();
        $this->startSession();
        $this->actingAs($user)->ajaxPost('api/manage/act/qrcode/checkin', [
            'identity'    => 'eyJpdiI6IkdGXC95S3VjODM4aFZuNjlmb0pWT0dnPT0iL
                             CJ2YWx1ZSI6ImN4Y1wvNjd4bzArQ3J0Vmhtb0k3S204OEY
                             wN0o2YWFyakYxQkUrNEZUMGtrM3grdVA1bDdSVzhscFNcL
                             0I0ak9KQThmS1NPN3RYTFBGbmM5Y0oyVXh1a2c9PSIsIm1
                             hYyI6IjUxOGM3ZGU2MjdkYzRlOGYwZDJjNGNjMmFjMmEyN
                             jAzMDM5NzY2YTUxZDVlNDVjNGZiYjVmODhlNTQwOGMyYmU
                             ifQ==',
            'step'        => 1,
            'activity'    => 2,
        ])->seeJsonContains(['code' => 0]);

        $this->seeInDatabase('activity_check_in', [
            'user_id'     => 1,
            'process_id'  => 2,
            'activity_id' => 2,
            'step'        => 1,
        ]);
    }

    public function testQrcodeCheckInSuccessfully_DoubleCheckIn()
    {
        $user = factory(\Jihe\Models\User::class)->create([
            'id'     => 2,
            'mobile' => '13800138001',
        ]);
        factory(\Jihe\Models\ActivityCheckIn::class)->create([
            'user_id'     => 1,
            'process_id'  => 2,
            'activity_id' => 2,
            'step'        => 1,
        ]);
        $this->createCheckInRelatedData();
        $this->startSession();
        $this->actingAs($user)->ajaxPost('api/manage/act/qrcode/checkin', [
            'identity'    => 'eyJpdiI6IkdGXC95S3VjODM4aFZuNjlmb0pWT0dnPT0iL
                             CJ2YWx1ZSI6ImN4Y1wvNjd4bzArQ3J0Vmhtb0k3S204OEY
                             wN0o2YWFyakYxQkUrNEZUMGtrM3grdVA1bDdSVzhscFNcL
                             0I0ak9KQThmS1NPN3RYTFBGbmM5Y0oyVXh1a2c9PSIsIm1
                             hYyI6IjUxOGM3ZGU2MjdkYzRlOGYwZDJjNGNjMmFjMmEyN
                             jAzMDM5NzY2YTUxZDVlNDVjNGZiYjVmODhlNTQwOGMyYmU
                             ifQ==',
            'step'        => 1,
            'activity'    => 2,
        ])->seeJsonContains([
            'code'    => 0,
            'message' => '你已经签过到了',
        ]);
    }

    public function testQrcodeCheckInFailed_NotEnrolled()
    {
        $user = factory(\Jihe\Models\User::class)->create([
            'id'     => 2,
            'mobile' => '13800138001',
        ]);
        $this->createCheckInBaseData();
        $this->startSession();
        $this->actingAs($user)->ajaxPost('api/manage/act/qrcode/checkin', [
            'identity'    => 'eyJpdiI6IkdGXC95S3VjODM4aFZuNjlmb0pWT0dnPT0iL
                             CJ2YWx1ZSI6ImN4Y1wvNjd4bzArQ3J0Vmhtb0k3S204OEY
                             wN0o2YWFyakYxQkUrNEZUMGtrM3grdVA1bDdSVzhscFNcL
                             0I0ak9KQThmS1NPN3RYTFBGbmM5Y0oyVXh1a2c9PSIsIm1
                             hYyI6IjUxOGM3ZGU2MjdkYzRlOGYwZDJjNGNjMmFjMmEyN
                             jAzMDM5NzY2YTUxZDVlNDVjNGZiYjVmODhlNTQwOGMyYmU
                             ifQ==',
            'step'        => 1,
            'activity'    => 2,
        ])->seeJsonContains([
            'code'    => ExceptionCode::USER_NOT_ACTIVITY_MEMBER,
            'message' => '未报名',
        ]);
    }

    //=============================================
    //          manageRemoveCheckIn
    //=============================================
    public function testManageRemoveCheckInSuccessfully()
    {
        $user = factory(\Jihe\Models\User::class)->create([
            'id'     => 2,
            'mobile' => '13800138001',
        ]);
        factory(\Jihe\Models\ActivityCheckIn::class)->create([
            'user_id'     => 1,
            'process_id'  => 2,
            'activity_id' => 2,
            'step'        => 1,
        ]);
        $this->createCheckInRelatedData();
        $this->startSession();
        $this->actingAs($user)->ajaxPost('api/manage/act/remove/checkin', [
            'user'        => 1,
            'step'        => 1,
            'activity_id' => 2,
        ])->seeJsonContains([
            'code'    => 0,
        ]);
        $response = json_decode($this->response->getContent());
        self::assertTrue($response->result);
    }

    public function testManageRemoveCheckInSuccessfully_NoData()
    {
        $user = factory(\Jihe\Models\User::class)->create([
            'id'     => 2,
            'mobile' => '13800138001',
        ]);
        $this->createCheckInRelatedData();
        $this->startSession();
        $this->actingAs($user)->ajaxPost('api/manage/act/remove/checkin', [
            'user'        => 1,
            'step'        => 1,
            'activity_id' => 2,
        ])->seeJsonContains([
            'code'    => 10000,
            'message' => '签到数据不存在'
        ]);
    }

    public function testManageRemoveCheckInSuccessfully_NoManagerAdd()
    {
        $user = factory(\Jihe\Models\User::class)->create([
            'id'     => 2,
            'mobile' => '13800138001',
        ]);
        factory(\Jihe\Models\ActivityCheckIn::class)->create([
            'user_id'     => 1,
            'process_id'  => 0,
            'activity_id' => 2,
            'step'        => 1,
        ]);
        $this->createCheckInRelatedData();
        $this->startSession();
        $this->actingAs($user)->ajaxPost('api/manage/act/remove/checkin', [
            'user'        => 1,
            'step'        => 1,
            'activity_id' => 2,
        ])->seeJsonContains([
            'code'    => 10000,
            'message' => '非团长签到,不可取消'
        ]);
    }

    private function createCheckInRelatedData()
    {
        $this->createCheckInBaseData();
        factory(\Jihe\Models\ActivityMember::class)->create([
            'activity_id' => 2,
            'user_id'     => 1,
            'mobile'      => '13800138000',
        ]);
    }

    private function createCheckInBaseData()
    {
        factory(\Jihe\Models\ActivityCheckInQRCode::class)->create([
            'activity_id' => 2,
        ]);
        factory(\Jihe\Models\City::class)->create([
            'id' => 1,
        ]);
        factory(\Jihe\Models\User::class)->create([
            'id'            => 1,
            'mobile'        => '13800138000',
            'identity_salt' => '1234567890123456',
        ]);
        factory(\Jihe\Models\Activity::class)->create([
            'id'      => 2,
            'city_id' => 1,
            'team_id' => 1,
        ]);
        factory(\Jihe\Models\Team::class)->create([
            'id'         => 1,
            'creator_id' => 2,
            'city_id'    => 1,
        ]);
    }

    private function prepareActivityMemberData($beginId, $stopId, $config)
    {
        foreach (range($beginId, $stopId) as $id) {
            $conf = array_merge([
                'id'          => $id,
                'activity_id' => 1,
            ], $config);
            $member = factory(\Jihe\Models\ActivityMember::class)->create($conf);
            if ($config['checkin']) {
                factory(\Jihe\Models\ActivityCheckIn::class)->create([
                    'activity_id'   => $member->activity_id,
                    'user_id'       => $member->user_id,
                    'step'          => 1,
                    'process_id'    => 100,
                ]);
                factory(\Jihe\Models\ActivityCheckIn::class)->create([
                    'activity_id'   => $member->activity_id,
                    'user_id'       => $member->user_id,
                    'step'          => 2,
                    'process_id'    => 100,
                ]);
            }
        }
    }

    private function prepareTeamActivityAndFounder($userId)
    {
        factory(\Jihe\Models\Team::class)->create([
            'id'         => 1,
            'city_id'    => 1,
            'creator_id' => $userId,
        ]);
        factory(\Jihe\Models\Activity::class)->create([
            'id'      => 1,
            'city_id' => 1,
            'team_id' => 1,
        ]);
        factory(\Jihe\Models\City::class)->create([
            'id' => 1,
        ]);
        $user = factory(\Jihe\Models\User::class)->create([
            'id'     => $userId,
            'mobile' => '13800138000',
        ]);

        return $user;
    }

    private function prepareUserData()
    {
        factory(\Jihe\Models\User::class)->create([
            'id'         => 1,
            'mobile'     => '13800138001',
            'nick_name'  => 'zhangsan',
            'created_at' => date('Y-m-d H:i:s', time('-1 day')),
        ]);
        factory(\Jihe\Models\User::class)->create([
            'id'        => 2,
            'mobile'    => '13800138002',
            'nick_name' => 'lisi',
        ]);
    }

    private function prepareCheckInData($activity = 1)
    {
        factory(\Jihe\Models\ActivityCheckIn::class)->create([
            'activity_id' => $activity,
            'user_id'     => 1,
            'step'        => 1,
        ]);
        factory(\Jihe\Models\ActivityCheckIn::class)->create([
            'activity_id' => $activity,
            'user_id'     => 2,
            'step'        => 1,
        ]);
        factory(\Jihe\Models\ActivityCheckIn::class)->create([
            'activity_id' => $activity,
            'user_id'     => 1,
            'step'        => 2,
        ]);
    }
}
