<?php
namespace intg\Jihe\Controllers\Api;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use intg\Jihe\TestCase;
use intg\Jihe\RequestSignCheck;
use intg\Jihe\AuthDeviceCheck;
use \PHPUnit_Framework_Assert as Assert;


class ActivityCheckInControllerTest extends TestCase
{
    use DatabaseTransactions;
    use RequestSignCheck;
    use AuthDeviceCheck;

    //=============================================
    //          getCheckInList
    //=============================================
    public function testGetCheckInListSuccessfully()
    {
        $user = factory(\Jihe\Models\User::class)->create([
            'id'     => 1,
            'mobile' => '13800138000',
        ]);
        factory(\Jihe\Models\ActivityCheckInQRCode::class)->create([
            'activity_id'   => 1,
        ]);
        factory(\Jihe\Models\Activity::class)->create([
            'id'        => 1,
            'city_id'   => 1,
            'team_id'   => 1,
            'title'     => 'test',
        ]);
        factory(\Jihe\Models\ActivityMember::class)->create([
            'activity_id'   => 1,
            'user_id'       => 1,
            'mobile'        => '13800138000',
        ]);
        factory(\Jihe\Models\City::class)->create([
            'id'    => 1,
        ]);
        factory(\Jihe\Models\Team::class)->create([
            'id'    => 1,
        ]);

        $this->startSession();
        $this->actingAs($user)->ajaxGet('api/activity/checkIn/list?' . http_build_query([
            'qrcode_url' => 'http://domain?activity_id=1&step=1&ver=1',
        ]))->seeJsonContains([ 'code' => 0 ]);
        $response = json_decode($this->response->getContent());
        self::assertEquals('test', $response->activity_title);
    }

    public function testGetCheckInList_NotActivityMember()
    {
        $user = factory(\Jihe\Models\User::class)->create([
            'id'     => 1,
            'mobile' => '13800138000',
        ]);
        factory(\Jihe\Models\ActivityCheckInQRCode::class)->create([
            'activity_id'   => 1,
        ]);
        factory(\Jihe\Models\Activity::class)->create([
            'id'        => 1,
            'city_id'   => 1,
            'team_id'   => 1,
        ]);
        factory(\Jihe\Models\City::class)->create([
            'id'    => 1,
        ]);
        factory(\Jihe\Models\Team::class)->create([
            'id'    => 1,
        ]);

        $this->startSession();
        $this->actingAs($user)->ajaxGet('api/activity/checkIn/list?' . http_build_query([
            'qrcode_url' => 'http://domain?activity_id=1&step=1&ver=1',
        ]))->seeJsonContains([
            'code' => 10000,
            'message' => '您没有报名，无法签到'
        ]);
    }

    public function testGetCheckInList_NotNeedCheckIn()
    {
        $user = factory(\Jihe\Models\User::class)->create([
            'id'     => 1,
            'mobile' => '13800138000',
        ]);
        factory(\Jihe\Models\Activity::class)->create([
            'id'        => 1,
            'city_id'   => 1,
            'team_id'   => 1,
        ]);
        factory(\Jihe\Models\City::class)->create([
            'id'    => 1,
        ]);
        factory(\Jihe\Models\Team::class)->create([
            'id'    => 1,
        ]);

        $this->startSession();
        $this->actingAs($user)->ajaxGet('api/activity/checkIn/list?' . http_build_query([
            'qrcode_url' => 'http://domain?activity_id=1&step=1&ver=1',
        ]))->seeJsonContains([
            'code' => 10000,
            'message' => '该活动不需要签到'
        ]);
    }

    public function testGetCheckInList_ParamParseFaild()
    {
        $user = factory(\Jihe\Models\User::class)->create([
            'id'     => 1,
            'mobile' => '13800138000',
        ]);

        $this->startSession();
        $this->actingAs($user)->ajaxGet('api/activity/checkIn/list?' . http_build_query([
            'qrcode_url' => 'http://domain',
        ]))->seeJsonContains([
            'code' => 10000,
            'message' => 'URL解析失败',
        ]);
    }

    public function testGetCheckInList_ParamActivityIdMissed()
    {
        $user = factory(\Jihe\Models\User::class)->create([
            'id'     => 1,
            'mobile' => '13800138000',
        ]);

        $this->startSession();
        $this->actingAs($user)->ajaxGet('api/activity/checkIn/list?' . http_build_query([
            'qrcode_url' => 'http://domain?step=1&ver=1',
        ]))->seeJsonContains([
            'code' => 10000,
            'message' => '无法识别活动'
        ]);
    }

    //=============================================
    //          sendVerifyCodeForCheckIn
    //=============================================
    public function testSendVerifyCodeForCheckInSuccessfully()
    {
        factory(\Jihe\Models\User::class)->create([
            'id'     => 1,
            'mobile' => '13800138000',
        ]);
        factory(\Jihe\Models\ActivityMember::class)->create([
            'activity_id'   => 1,
            'user_id'       => 1,
        ]);

        $this->expectsJobs(\Jihe\Jobs\SendMessageToUserJob::class);

        $url = 'api/activity/checkin/verifycode?' . http_build_query([
            'mobile'        => '13800138000',
            'activity_id'   => '1',
        ]);
        $this->ajaxGet($url)->seeJsonContains([ 'code' => 0 ]);
        $response = json_decode($this->response->getContent());
        $this->assertInternalType('int', $response->send_interval);
    }

    public function testSendVerifyCodeForCheckInFailed_UserNotExists()
    {
        $url = 'api/activity/checkin/verifycode?' . http_build_query([
            'mobile'        => '13800138000',
            'activity_id'   => '1',
        ]);
        $this->ajaxGet($url)->seeJsonContains([ 'code' => 2 ]);
        $response = json_decode($this->response->getContent());
        $this->assertEquals('您还未注册，请先完成注册', $response->message);
    }

    public function testSendVerifyCodeForCheckInFailed_ApplicantNotSuccess()
    {
        factory(\Jihe\Models\User::class)->create([
            'id'     => 1,
            'mobile' => '13800138000',
        ]);

        $url = 'api/activity/checkin/verifycode?' . http_build_query([
            'mobile'        => '13800138000',
            'activity_id'   => '1',
        ]);
        $this->ajaxGet($url)->seeJsonContains([ 'code' => 10000 ]);
        $response = json_decode($this->response->getContent());
        $this->assertEquals('您未报名活动，请先报名', $response->message);
    }

    //=============================================
    //          firstStepQuickCheckIn
    //=============================================
    public function testFirstStepQuickCheckInSuccessfully()
    {
        $this->mockCaptchaService();
        factory(\Jihe\Models\User::class)->create([
            'id'     => 1,
            'mobile' => '13800138000',
        ]);
        factory(\Jihe\Models\Verification::class)->create([
            'mobile' => '13800138000',
            'code'   => '8952',
        ]);
        factory(\Jihe\Models\ActivityCheckInQRCode::class)->create([
            'activity_id'   => 2,
        ]);
        factory(\Jihe\Models\Activity::class)->create([
            'id'    => 2,
        ]);
        factory(\Jihe\Models\ActivityMember::class)->create([
            'activity_id'   => 2,
            'user_id'       => 1,
            'mobile'        => '13800138000',
        ]);
        $this->ajaxPost('api/activity/checkin/quick', [
            'captcha'   => '1234',
            'mobile'    => '13800138000',
            'activity'  => 2,
        ])->seeJsonContains([ 'code' => 0 ]);

        $this->seeInDatabase('activity_check_in', [
            'user_id'       => 1,
            'activity_id'   => 2,
            'step'          => 1,
        ]);
    }

    public function testFirstStepQuickCheckInSuccessfully_RequestMoreThanOnce()
    {
        $this->mockCaptchaService();

        factory(\Jihe\Models\User::class)->create([
            'id'     => 1,
            'mobile' => '13800138000',
        ]);
        factory(\Jihe\Models\Verification::class)->create([
            'mobile' => '13800138000',
            'code'   => '8952',
        ]);
        factory(\Jihe\Models\ActivityCheckInQRCode::class)->create([
            'activity_id'   => 2,
        ]);
        factory(\Jihe\Models\Activity::class)->create([
            'id'    => 2,
        ]);
        factory(\Jihe\Models\ActivityMember::class)->create([
            'activity_id'   => 2,
            'user_id'       => 1,
            'mobile'        => '13800138000',
        ]);
        factory(\Jihe\Models\ActivityCheckIn::class)->create([
            'user_id'       => 1,
            'activity_id'   => 2,
            'step'          => 1,
        ]);

        $this->ajaxPost('api/activity/checkin/quick', [
            'captcha'   => '8952',
            'mobile'    => '13800138000',
            'activity'  => 2,
        ])->seeJsonContains([ 'code' => 0 ]);
    }

    public function testFirstStepQuickCheckIn_UserNotFound()
    {
        $this->mockCaptchaService();

        $this->ajaxPost('api/activity/checkin/quick', [
            'captcha'   => '8952',
            'mobile'    => '13800138000',
            'activity'  => 2,
        ])->seeJsonContains([ 'code' => 2 ]);
        $response = json_decode($this->response->getContent());
        $this->assertEquals('您还未注册，请先注册，请联系现场工作人员', $response->message);
    }

    public function testFirstStepQuickCheckIn_CodeNotMatch()
    {
        $this->ajaxPost('api/activity/checkin/quick', [
            'captcha'   => '1234',
            'mobile'    => '13800138000',
            'activity'  => 2,
        ])->seeJsonContains([ 'code' => 10000 ]);
        $response = json_decode($this->response->getContent());
        $this->assertEquals('验证码错误', $response->message);
    }

    private function mockCaptchaService()
    {
        $captchaService = \Mockery::mock(\Mews\Captcha\Captcha::class);
        $captchaService->shouldReceive('check')->withAnyArgs()->andReturn(true);
        $this->app['captcha'] = $captchaService;
    }

    //=============================================
    //          CheckIn
    //=============================================
    public function testCheckInSuccessfully()
    {
         $user = factory(\Jihe\Models\User::class)->create([
            'id'     => 1,
            'mobile' => '13800138000',
        ]);
        $this->createCheckInRelatedData();
        $this->startSession();
        $this->actingAs($user)->ajaxPost('api/activity/checkIn/checkIn', [
            'step'    => 1,
            'activity_id'  => 2,
        ])->seeJsonContains([ 'code' => 0 ]);

        $this->seeInDatabase('activity_check_in', [
            'user_id'       => 1,
            'activity_id'   => 2,
            'step'          => 1,
            'process_id'    => 0,
        ]);
    }

    public function testCheckInSuccessfully_double()
    {
        $user = factory(\Jihe\Models\User::class)->create([
            'id'     => 1,
            'mobile' => '13800138000',
        ]);
        factory(\Jihe\Models\ActivityCheckIn::class)->create([
            'id'     => 1,
            'user_id'       => 1,
            'activity_id'   => 2,
            'step'          => 1,
            'process_id'    => 3,
        ]);
        $this->createCheckInRelatedData();
        $this->startSession();
        $this->actingAs($user)->ajaxPost('api/activity/checkIn/checkIn', [
            'step'    => 1,
            'activity_id'  => 2,
        ])->seeJsonContains([ 'code' => 0 ]);

        $this->seeInDatabase('activity_check_in', [
            'user_id'       => 1,
            'activity_id'   => 2,
            'step'          => 1,
            'process_id'    => 0,
        ]);
    }

    private function createCheckInRelatedData()
    {
        factory(\Jihe\Models\ActivityCheckInQRCode::class)->create([
            'activity_id'   => 2,
        ]);
        factory(\Jihe\Models\City::class)->create([
            'id' => 1,
        ]);
        factory(\Jihe\Models\User::class)->create([
            'id'     => 2,
            'mobile' => '13800138001',
        ]);
        factory(\Jihe\Models\Activity::class)->create([
            'id'    => 2,
            'city_id' => 1,
            'team_id' => 1,
        ]);
        factory(\Jihe\Models\Team::class)->create([
            'id'    => 1,
            'creator_id' => 2,
        ]);
        factory(\Jihe\Models\ActivityMember::class)->create([
            'activity_id'   => 2,
            'user_id'       => 1,
            'mobile'        => '13800138000',
        ]);
    }



}
