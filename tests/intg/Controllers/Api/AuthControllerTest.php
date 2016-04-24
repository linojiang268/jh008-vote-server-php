<?php
namespace intg\Jihe\Controllers\Api;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use intg\Jihe\TestCase;
use intg\Jihe\RequestSignCheck;
use intg\Jihe\AuthDeviceCheck;


class AuthControllerTest extends TestCase
{
    use DatabaseTransactions;
    use RequestSignCheck;
    use AuthDeviceCheck;
    
    //=========================================
    //             Registration
    //=========================================
    public function testSuccessfulRegistration()
    {
        factory(\Jihe\Models\Verification::class)->create([
            'mobile' => '13800138000', 
            'code'   => '8952', 
        ]);
        $this->mockStorageService();

        $this->ajaxCall('POST', 'api/register', [
            'code'      => '8952',
            'mobile'    => '13800138000',
            'password'  => '*******',
            'nick_name' => 'john',
            'gender'    => 1,
            'birthday'  => '1990-01-01',
            'tagIds'    => [1, 2, 3],
        ], [], [
            'avatar'    => $this->makeUploadFile(__DIR__ . '/test-data/avatar.jpg')
        ])->seeJsonContains([ 'code' => 0 ]);

        $this->seeInDatabase('users', [
            'mobile'    => '13800138000',
            'birthday'  => '1990-01-01',
            'nick_name' => 'john',
        ]);
    }
    
    public function testRegistrationWithInvalidMobile()
    {
        $this->ajaxPost('api/register', [
            'code'     => '8952',
            'mobile'   => '13800',
            'password' => '*******',
        ])->seeJsonContains([ 'code' => 10000 ]);
        
        $this->assertContains('\u624b\u673a\u53f7' /* 手机号 */, $this->response->getContent(), 
                              'invalid mobile should be detected');
    }
    
    public function testRegistrationUserExists()
    {
        factory(\Jihe\Models\Verification::class)->create([
            'mobile' => '13800138000',
            'code'   => '8952',
        ]);
        factory(\Jihe\Models\User::class)->create([
            'mobile' => '13800138000',
        ]);

        $this->ajaxPost('api/register', [
            'code'     => '8952',
            'mobile'   => '13800138000',
            'password' => '*******',
        ])->seeJsonContains([ 'code' => 1 ]);
    }

    //=========================================
    //             Login
    //=========================================
    public function testSuccessfulLogin()
    {
        factory(\Jihe\Models\User::class)->create([
            'mobile'    => '13800138000',
            'salt'      => 'ptrjb30aOvqWJ4mG',
            'nick_name' => 'victory',
            'password'  => '7907C7ED5F7F4E4872E24CAB8292464F',  // raw password is '*******'
        ]);

        $this->ajaxPost('api/login', [
            'mobile'   => '13800138000',
            'password' => '*******',
        ])->seeJsonContains([
            'code'          => 0,
            'mobile'        => '13800138000',
            'nick_name'     => 'victory',
            'is_team_owner' => false,
        ]);

        $response = json_decode($this->response->getContent());
        $this->assertObjectHasAttribute('push_alias', $response);
        $this->seeInDatabase('login_devices', [
            'mobile'        => '13800138000',
            'source'        => 1,
            'identifier'    => $response->push_alias,
        ]);

        // Check cookie jihe_deviceno
        $cookies = $this->response->headers->getCookies();
        $this->assertCount(1, $cookies);
        $this->assertEquals('jihe_deviceno', $cookies[0]->getName());
    }

    public function testSuccessfulLoginReplaceDeviceIdentifier()
    {
        factory(\Jihe\Models\User::class)->create([
            'mobile'    => '13800138000',
            'salt'      => 'ptrjb30aOvqWJ4mG',
            'nick_name' => 'victory',
            'password'  => '7907C7ED5F7F4E4872E24CAB8292464F',  // raw password is '*******'
        ]);
        \Jihe\Models\LoginDevice::create([
            'mobile'        => '13800138000',
            'source'        => 1,
            'identifier'    => '13800138000|fea14b14d85417019a22977abe7620fbYXoa',
        ]);
        \Jihe\Models\LoginDevice::create([
            'mobile'        => '13800138000',
            'source'        => 2,
            'identifier'    => '13800138000|AAAAAA14d85417019a22977abe7620fbYXoa',
        ]);

        $this->ajaxPost('api/login', [
            'mobile'   => '13800138000',
            'password' => '*******',
        ])->seeJsonContains([
            'code'          => 0,
            'mobile'        => '13800138000',
            'nick_name'     => 'victory',
            'is_team_owner' => false,
        ]);

        $response = json_decode($this->response->getContent());
        $this->assertObjectHasAttribute('push_alias', $response);
        $this->assertNotEquals('13800138000|fea14b14d85417019a22977abe7620fbYXoa', $response->push_alias);
        $this->seeInDatabase('login_devices', [
            'mobile'        => '13800138000',
            'source'        => 1,
            'identifier'    => $response->push_alias,   // value changed
        ]);
        $this->seeInDatabase('login_devices', [
            'mobile'        => '13800138000',
            'source'        => 2,
            'identifier'    => '13800138000|AAAAAA14d85417019a22977abe7620fbYXoa',
        ]);
    }

    public function testLoginIncorrectPassword()
    {
        factory(\Jihe\Models\User::class)->create([
            'mobile'   => '13800138000',
            'salt'     => 'ptrjb30aOvqWJ4mG',
            'password' => '7907C7ED5F7F4E4872E24CAB8292464F',  // raw password is '*******'
        ]);
        $this->ajaxPost('api/login', [
            'mobile'   => '13800138000',
            'password' => 'WRONG',
        ])->seeJsonContains([ 'code' => 10000 ]);

        $this->assertContains('\u5bc6\u7801\u9519\u8bef' /* 密码错误 */, $this->response->getContent(),
                              'incorrect password should be detected');
    }

    public function testSuccessfulLoginAndUserOwnATeam()
    {
        factory(\Jihe\Models\User::class)->create([
            'id'        => 1,
            'mobile'    => '13800138000',
            'salt'      => 'ptrjb30aOvqWJ4mG',
            'nick_name' => 'victory',
            'password'  => '7907C7ED5F7F4E4872E24CAB8292464F',  // raw password is '*******'
        ]);
        factory(\Jihe\Models\Team::class)->create([
            'id'         => 1,
            'city_id'    => 1,
            'creator_id' => 1,
        ]);
        factory(\Jihe\Models\City::class)->create([
            'id' => 1,
        ]);

        $this->ajaxPost('api/login', [
            'mobile'   => '13800138000',
            'password' => '*******',
        ])->seeJsonContains([
            'code'          => 0,
            'mobile'        => '13800138000',
            'nick_name'     => 'victory',
            'is_team_owner' => true
        ]);
    }

    public function testSuccessfulLoginAndNeedComplete()
    {
        factory(\Jihe\Models\User::class)->create([
            'id'        => 1,
            'mobile'    => '13800138000',
            'salt'      => 'ptrjb30aOvqWJ4mG',
            'nick_name' => 'victory',
            'password'  => '7907C7ED5F7F4E4872E24CAB8292464F',  // raw password is '*******'
            'status'    => 0,       // need complete profile
        ]);

        $this->ajaxPost('api/login', [
            'mobile'   => '13800138000',
            'password' => '*******',
        ])->seeJsonContains([
            'code' => 10102,
        ]);

        $response = json_decode($this->response->getContent());
        self::assertObjectHasAttribute('push_alias', $response);
        self::assertNotNull($response->push_alias);
    }

    //=========================================
    //          logout
    //=========================================
    public function testSuccessfullyLogout()
    {
        $user = factory(\Jihe\Models\User::class)->create([
            'id'                => 1,
            'mobile'            => '13800138000',
            'remember_token'    => 'FAxm3Uk2awKO1MlRqD7OxKmYUdstEIUNkp4OqjHxzKDBtCgC2ZSw1KEF3jxN',
        ]);

        $this->startSession();
        $this->actingAs($user)
             ->ajaxGet('api/logout')
             ->seeJsonContains(['code' => 0]);

        $this->seeInDatabase('users', [
            'mobile'            => '13800138000',
            'remember_token'    => 'FAxm3Uk2awKO1MlRqD7OxKmYUdstEIUNkp4OqjHxzKDBtCgC2ZSw1KEF3jxN',
        ]);
    }

    public function testSuccessfullyLogout_SessionAreadyExpired()
    {
        $this->ajaxGet('community/logout')->seeJsonContains(['code' => 0]);
    }

    //=============================================
    //          Send verify code for registration
    //=============================================
    public function testSuccessfullySendVerifyCodeForRegistration()
    {
        $this->expectsJobs(\Jihe\Jobs\SendMessageToUserJob::class);

        $url = 'api/register/verifycode?' . http_build_query([
            'mobile' => '13800138000',
        ]);
        $this->ajaxGet($url)->seeJsonContains([ 'code' => 0 ]);
    }

    public function testSendVerifyCodeWithInvalidMobile()
    {
        $url = 'api/register/verifycode?' . http_build_query([
            'mobile' => '13800',
        ]);

        $this->ajaxGet($url)->seeJsonContains([ 'code' => 10000 ]);
        $this->assertContains('\u624b\u673a\u53f7' /* 手机号 */, $this->response->getContent(), 
                              'invalid mobile should be detected');
    }

    //==============================================
    //          Send verify code for reset password
    //==============================================
    public function testSuccessfullySendVerifyCodeForResetPassword()
    {
        factory(\Jihe\Models\User::class)->create([
            'mobile' => '13800138000',
        ]);

        $this->expectsJobs(\Jihe\Jobs\SendMessageToUserJob::class);

        $url = 'api/password/reset/verifycode?' . http_build_query([
            'mobile' => '13800138000',
        ]);
        $this->ajaxGet($url)->seeJsonContains([ 'code' => 0 ]);
        $response = json_decode($this->response->getContent());
        $this->assertInternalType('int', $response->send_interval);
    }

    public function testSendVerifyCodeForResetPasswordWithUserNotExists()
    {
        $url = 'api/password/reset/verifycode?' . http_build_query([
            'mobile' => '13800138000',
        ]);
        $this->ajaxGet($url)->seeJsonContains([
            'code' => 2,
            'message'   => '您还未注册，请先完成注册',
        ]);
    }

    //==========================================
    //          Reset user password
    //==========================================
    public function testSuccessfullyResetPassword()
    {
        factory(\Jihe\Models\Verification::class)->create([
            'mobile' => '13800138000', 
            'code'   => '8952', 
        ]);
        factory(\Jihe\Models\User::class)->create([
            'mobile'   => '13800138000',
            'salt'     => 'ptrjb30aOvqWJ4mG',
            'password' => '7907C7ED5F7F4E4872E24CAB8292464F',  // raw password is '*******'
        ]);
        $this->startSession();

        $this->ajaxPost('api/password/reset', [
            '_token'    => csrf_token(),
            'mobile'    => '13800138000',
            'password'  => '*******',
            'code'      => '8952',
        ])->seeJsonContains([ 'code' => 0 ]);     
    }

    public function testResetPasswordWithInvalidMobile()
    {
        $this->startSession();
        $this->ajaxPost('api/password/reset', [
            '_token'    => csrf_token(),
            'mobile'    => '13800',
            'password'  => '123456',
            'code'      => '8952',
        ])->seeJsonContains([ 'code' => 10000 ]);

        $this->assertContains('\u624b\u673a\u53f7' /* 手机号 */, $this->response->getContent(), 
                              'invalid mobile should be detected');
    }

    public function testResetPasswordWithUnmatchedCode()
    {
        // Prepare test data
        factory(\Jihe\Models\Verification::class)->create([
            'mobile' => '13800138000', 
            'code'   => '8952', 
        ]);
        $this->startSession();

        $this->post('api/password/reset', [
            '_token'   => csrf_token(),
            'mobile'   => '13800138000',
            'password' => '*******',
            'code'     => '1111',
        ])->seeJsonContains([ 'code' => 10001 ]);

        $this->assertContains('\u9a8c\u8bc1\u7801\u9519\u8bef' /* 验证码错误 */, 
            $this->response->getContent(), 'invalid verify code be detected');
    }

    public function testResetPasswordUserNotExists()
    {
        factory(\Jihe\Models\Verification::class)->create([
            'mobile' => '13800138000',
            'code'   => '8952', 
        ]);  
        $this->startSession();

        $this->ajaxPost('api/password/reset', [
            '_token'   => csrf_token(),
            'mobile'   => '13800138000',
            'password' => '*******',
            'code'     => '8952',
        ])->seeJsonContains([ 'code' => 2 ]);

        $this->assertContains('\u7528\u6237\u4e0d\u5b58\u5728' /* 用户不存在 */, 
            $this->response->getContent(), 'no such user');
    }

    public function testResetPasswordLackOfCsrfToken()
    {
        $this->ajaxPost('api/password/reset', [
            'mobile'   => '13800138000',
            'password' => '*******',
            'code'     => '8952',
        ])->seeJsonContains([ 'code' => 10000 ]);
    }
    
    public function testResetPasswordLackOfCsrfTokenWithPlainRequest()
    {
        $this->post('api/password/reset', [
            'mobile'   => '13800138000',
            'password' => '*******',
            'code'     => '8952',
        ])->seeStatusCode(500);  // we get an html page shows a general error
                                 // technically, this page is from underlying symfony
    }

    //==========================================
    //          changePassword
    //==========================================
    public function testChangePasswordSuccessfully_WithAuthDevice()
    {
        // enable auth device
        $this->enableAuthDeviceMiddleware();
        $this->expectsJobs(\Jihe\Jobs\SendMessageToUserJob::class);
        $identifier = '13800138000|fea14b14d85417019a22977abe7620fbYXoa';
        $encryptIdentifier = $this->getEncrypter()->encrypt($identifier);
        $user = factory(\Jihe\Models\User::class)->create([
            'mobile'   => '13800138000',
            'salt'     => 'ptrjb30aOvqWJ4mG',
            'password' => '7907C7ED5F7F4E4872E24CAB8292464F',  // raw password is '*******'
        ]);
        \Jihe\Models\LoginDevice::create([
            'mobile'        => '13800138000',
            'source'        => 1,
            'identifier'    => $identifier,
        ]);

        $this->startSession();

        $this->actingAs($user)->ajaxPost('api/password/change', [
            'original_password' => '*******',
            'new_password'      => '111111',
        ], [
            'jihe_deviceno' => $encryptIdentifier,
        ])->seeJsonContains(['code' => 0, 'message' => '修改密码成功']);
    }

    // close kick temporary
    /*
    public function testChangePasswordFailed_WithAuthDeviceRejection()
    {
        // enable auth device
        $this->enableAuthDeviceMiddleware();
        $identifier = '13800138000_fea14b14d85417019a22977abe7620fbYXoa';
        $user = factory(\Jihe\Models\User::class)->create([
            'mobile'   => '13800138001',
            'salt'     => 'ptrjb30aOvqWJ4mG',
            'password' => '7907C7ED5F7F4E4872E24CAB8292464F',  // raw password is '*******'
        ]);
        \Jihe\Models\LoginDevice::create([
            'mobile'        => '13800138001',
            'source'        => 1,
            'identifier'    => $identifier,
        ]);

        $this->startSession();

        $this->actingAs($user)->ajaxPost('api/password/change', [
            'original_password' => '*******',
            'new_password'      => '111111',
        ], [
            'jihe_deviceno' => '************************',
        ])->seeJsonContains(['code' => 10103]);     // user was kicked
    }
    */

    public function testChangePasswordFailed_PasswordNotMatch()
    {
        $user = factory(\Jihe\Models\User::class)->create([
            'mobile'   => '13800138002',
            'salt'     => 'ptrjb30aOvqWJ4mG',
            'password' => '7907C7ED5F7F4E4872E24CAB8292464F',  // raw password is '*******'
        ]);

        $this->startSession();

        $this->actingAs($user)->ajaxPost('api/password/change', [
            'original_password' => 'c******',
            'new_password'      => '111111',
        ])->seeJsonContains(['code' => 10000, 'message' => '当前密码不正确']);
    }

    //==========================================
    //          pushAliasBound
    //==========================================
    // close kick temporary
    /*
    public function testPushAliasBound()
    {
        $this->expectsJobs([
            \Jihe\Jobs\PushToAliasMessageJob::class,
        ]);

        $user = factory(\Jihe\Models\User::class)->create([
            'mobile'    => '13800138000',
            'salt'      => 'ptrjb30aOvqWJ4mG',
            'nick_name' => 'victory',
            'password'  => '7907C7ED5F7F4E4872E24CAB8292464F',  // raw password is '*******'
        ]);
        \Jihe\Models\LoginDevice::create([
            'mobile'        => '13800138000',
            'source'        => 1,
            'identifier'    => '13800138000|fea14b14d85417019a22977abe7620fbYXoa',
            'old_identifier'    => '13800138000|eeeeee14d85417019a22977abe7620fbYXoa',
        ]);

        $this->startSession();
        $this->actingAs($user)->ajaxGet('api/alias/bound')
             ->seeJsonContains([ 'code' => 0 ]);
    }

    public function testPushAliasBound_SameDevice()
    {
        $user = factory(\Jihe\Models\User::class)->create([
            'mobile'    => '13800138000',
            'salt'      => 'ptrjb30aOvqWJ4mG',
            'nick_name' => 'victory',
            'password'  => '7907C7ED5F7F4E4872E24CAB8292464F',  // raw password is '*******'
        ]);
        \Jihe\Models\LoginDevice::create([
            'mobile'        => '13800138000',
            'source'        => 1,
            'identifier'    => '13800138000|fea14b14d85417019a22977abe7620fbYXoa',
            'old_identifier'    => '13800138000|fea14b14d85417019a22977abe7620fbYXoa',
        ]);

        $this->startSession();
        $this->actingAs($user)->ajaxGet('api/alias/bound')
             ->seeJsonContains([ 'code' => 0 ]);
    }
    */

    private function mockStorageService($return = 'http://domain/tmp/key.png')
    {
        $storageService = \Mockery::mock(\Jihe\Services\StorageService::class);
        $storageService->shouldReceive('isTmp')->withAnyArgs()->andReturn(true);
        $storageService->shouldReceive('storeAsFile')->withAnyArgs()->andReturn($return);
        $storageService->shouldReceive('storeAsImage')->withAnyArgs()->andReturn($return);
        $storageService->shouldReceive('remove')->withAnyArgs()->andReturn(false);
        $this->app [\Jihe\Services\StorageService::class] = $storageService;

        return $storageService;
    }

    private function getEncrypter()
    {
        return $this->app[\Illuminate\Contracts\Encryption\Encrypter::class];
    }
}
