<?php
namespace intg\Jihe\Controllers\Admin;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use intg\Jihe\TestCase;
use Jihe\Entities\Admin\User as UserEntity;

class UserControllerTest extends TestCase
{
    use DatabaseTransactions;
    
    //=========================================
    //                 Login
    //=========================================
    public function testSuccessfulLoginForAdmin()
    {
        factory(\Jihe\Models\Admin\User::class)->create([
            'user_name' => 'jihe-admin',
            'salt'      => 'ptrjb30aOvqWJ4mG',
            'password'  => '7907C7ED5F7F4E4872E24CAB8292464F',  // raw password is '*******'
            'role'      => 'admin',
        ]);

        $this->startSession();
        $this->post('admin/login', [
            '_token'    => csrf_token(),
            'username' => 'jihe-admin',
            'password'  => '*******',
        ]);
        
        $this->assertRedirectedTo('/admin/user');
    }
    
    public function testSuccessfulLoginForOperator()
    {
        factory(\Jihe\Models\Admin\User::class)->create([
            'user_name' => 'jihe-operator',
            'salt'      => 'ptrjb30aOvqWJ4mG',
            'password'  => '7907C7ED5F7F4E4872E24CAB8292464F',  // raw password is '*******'
            'role'      => 'operator',
        ]);
    
        $this->startSession();
        $this->post('admin/login', [
            '_token'    => csrf_token(),
            'username' => 'jihe-operator',
            'password'  => '*******',
        ]);
    
        $this->assertRedirectedTo('/admin/teams');
    }
    
    public function testSuccessfulLoginForAccountant()
    {
        factory(\Jihe\Models\Admin\User::class)->create([
            'user_name' => 'jihe-accountant',
            'salt'      => 'ptrjb30aOvqWJ4mG',
            'password'  => '7907C7ED5F7F4E4872E24CAB8292464F',  // raw password is '*******'
            'role'      => 'accountant',
        ]);
    
        $this->startSession();
        $this->post('admin/login', [
                '_token'    => csrf_token(),
                'username' => 'jihe-accountant',
                'password'  => '*******',
        ]);
    
        $this->assertRedirectedTo('/admin/accountant');
    }
    
    public function testSuccessfulLoginForFail()
    {
        factory(\Jihe\Models\Admin\User::class)->create([
            'user_name' => 'jihe-fail',
            'salt'      => 'ptrjb30aOvqWJ4mG',
            'password'  => '7907C7ED5F7F4E4872E24CAB8292464F',  // raw password is '*******'
            'role'      => 'accountant',
        ]);
    
        $this->startSession();
        $this->post('admin/login', [
            '_token'    => csrf_token(),
            'username' => 'jihe-fail',
            'password'  => '------',
        ]);
    
        $this->assertRedirectedTo('/admin');
    }
    
    //=========================================
    //             Reset Password
    //=========================================
    public function testSuccessfulResetPassword()
    {
        $user = factory(\Jihe\Models\Admin\User::class)->create([
                    'id'        => 1,
                    'user_name' => 'jihe-admin',
                    'salt'      => 'ptrjb30aOvqWJ4mG',
                    'password'  => '7907C7ED5F7F4E4872E24CAB8292464F',  // raw password is '*******'
                    'role'      => UserEntity::ROLE_ADMIN,
                ]);
        
        factory(\Jihe\Models\Admin\User::class)->create([
            'id'        => 2,
            'user_name' => 'jihe-operator',
            'salt'      => 'ptrjb30aOvqWJ4mG',
            'password'  => '7907C7ED5F7F4E4872E24CAB8292464F',  // raw password is '*******'
            'role'      => UserEntity::ROLE_OPERATOR,
        ]);
    
        $this->startSession();
        $this->actingAs($user, 'extended-eloquent-admin')
             ->ajaxPost('admin/user/password/reset', [
                            '_token'   => csrf_token(),
                            'user'     => 2,
                            'password' => '123456',
                        ])->seeJsonContains([
                            'code' => 0,
                        ]);
    }
    
    //=========================================
    //           Reset Self Password
    //=========================================
    public function testSuccessfulResetSelfPassword()
    {
        $user = factory(\Jihe\Models\Admin\User::class)->create([
            'id'     => 1,
            'salt'      => 'ptrjb30aOvqWJ4mG',
            'password'  => '7907C7ED5F7F4E4872E24CAB8292464F',  // raw password is '*******'
            'role'      => UserEntity::ROLE_OPERATOR,
        ]);
        
        $this->startSession();
        
        $this->actingAs($user, 'extended-eloquent-admin')
             ->post('admin/user/password/self/reset', [
                        '_token'       => csrf_token(),
                        'password'     => '123456',
                        'old_password' => '*******',
                    ])->seeJsonContains([ 'code' => 0 ]);
    }
    
    //=========================================
    //               get Users
    //=========================================
    public function testSuccessfulGetUsers()
    {
        $user = factory(\Jihe\Models\Admin\User::class)->create();
    
        $this->actingAs($user, 'extended-eloquent-admin')
             ->get('/admin/user/list?page=1&size=12');
    
        $this->seeJsonContains([ 'code' => 0 ]);
    
        $result = json_decode($this->response->getContent());
    
        $this->assertObjectHasAttribute('pages', $result);
        $this->assertObjectHasAttribute('users', $result);
    
        $user = $result->users[0];
        $this->assertObjectHasAttribute('id', $user);
        $this->assertObjectHasAttribute('user_name', $user);
        $this->assertObjectHasAttribute('role', $user);
        $this->assertObjectHasAttribute('status', $user);
    }
    
    //=========================================
    //                 Add
    //=========================================
    public function testSuccessfulAdd()
    {
        $user = factory(\Jihe\Models\Admin\User::class)->create([
                    'user_name' => 'jihe-admin',
                    'salt'      => 'ptrjb30aOvqWJ4mG',
                    'password'  => '7907C7ED5F7F4E4872E24CAB8292464F',  // raw password is '*******'
                    'role'      => UserEntity::ROLE_ADMIN,
        ]);
    
        $this->startSession();
        $this->actingAs($user, 'extended-eloquent-admin')
             ->ajaxPost('admin/user/add', [
                        '_token'    => csrf_token(),
                        'user_name' => 'jihe-operator',
                        'password'  => '*******',
                        'role'      => UserEntity::ROLE_OPERATOR
        ])->seeJsonContains([
            'code'      => 0,
        ]);
    }
    
    //=========================================
    //                Remove
    //=========================================
    public function testSuccessfulRemove()
    {
        $user = factory(\Jihe\Models\Admin\User::class)->create([
                    'id'        => 1,
                    'user_name' => 'jihe-admin',
                    'salt'      => 'ptrjb30aOvqWJ4mG',
                    'password'  => '7907C7ED5F7F4E4872E24CAB8292464F',  // raw password is '*******'
                    'role'      => UserEntity::ROLE_ADMIN,
        ]);
        
        factory(\Jihe\Models\Admin\User::class)->create([
            'id'        => 2,
            'user_name' => 'jihe-operator',
            'salt'      => 'ptrjb30aOvqWJ4mG',
            'password'  => '7907C7ED5F7F4E4872E24CAB8292464F',  // raw password is '*******'
            'role'      => UserEntity::ROLE_OPERATOR,
        ]);
    
        $this->startSession();
        $this->actingAs($user, 'extended-eloquent-admin')
             ->ajaxPost('admin/user/remove', [
                        '_token' => csrf_token(),
                        'user'   => '2',
        ])->seeJsonContains([
            'code'      => 0,
        ]);
    }
    
    //=========================================
    //           send system notice
    //=========================================
    public function testSuccessfulSendSystemNotice_ToAllAndSms()
    {
        $this->expectsJobs(\Jihe\Jobs\SendMessageToUserJob::class);
    
        $user = factory(\Jihe\Models\Admin\User::class)->create([
            'id'        => 1,
            'user_name' => 'jihe-admin',
            'salt'      => 'ptrjb30aOvqWJ4mG',
            'password'  => '7907C7ED5F7F4E4872E24CAB8292464F',  // raw password is '*******'
            'role'      => UserEntity::ROLE_OPERATOR,
        ]);
        
        $this->startSession();
        $this->actingAs($user, 'extended-eloquent-admin')
             ->ajaxPost('/admin/notice/send', [
                        '_token' => csrf_token(),
                        'to_all'   => true,
                        'send_way' => 'sms',
                        'content'  => 'smscontent',
                    ])->seeJsonContains([
                        'code'     => 0,
                    ]);
    }

    //=========================================
    //               listNotices
    //=========================================
    public function testSuccessfullListNotices()
    {
        $user = factory(\Jihe\Models\Admin\User::class)->create([
            'id'        => 1,
            'user_name' => 'jihe-admin',
            'salt'      => 'ptrjb30aOvqWJ4mG',
            'password'  => '7907C7ED5F7F4E4872E24CAB8292464F',  // raw password is '*******'
            'role'      => UserEntity::ROLE_OPERATOR,
        ]);
        factory(\Jihe\Models\Message::class)->create([
            'team_id'     => null,
            'activity_id' => null,
            'user_id'     => null,
        ]);
        factory(\Jihe\Models\Message::class)->create([
            'team_id'     => null,
            'activity_id' => null,
            'user_id'     => 1,
        ]);
        factory(\Jihe\Models\Message::class)->create([
            'team_id'     => null,
            'activity_id' => null,
            'user_id'     => 2,
        ]);
        factory(\Jihe\Models\Message::class)->create([
            'team_id'     => 1,
            'activity_id' => null,
            'user_id'     => null,
        ]);
        factory(\Jihe\Models\Message::class)->create([
            'team_id'     => 1,
            'activity_id' => 1,
            'user_id'     => null,
        ]);
        
        $this->startSession();
        $this->actingAs($user, 'extended-eloquent-admin')
             ->ajaxGet('/admin/notice/list')
             ->seeJsonContains([
                    'code'     => 0,
                ]);
    
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


}
