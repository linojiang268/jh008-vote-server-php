<?php
namespace intg\Jihe\Controllers\Admin;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use intg\Jihe\TestCase;

class OperateControllerTest extends TestCase
{
    use DatabaseTransactions;

    //================================
    //          listClientUser
    //================================
    public function testListClientUserSuccessfully()
    {
        $user = $this->prepareUser('operator');
        $this->prepareClientUser();
        $this->startSession();
        $this->actingAs($user, 'extended-eloquent-admin')
             ->ajaxGet('admin/client/user/list')
             ->seeJsonContains(['code' => 0]);
        $response = json_decode($this->response->getContent());
        $this->assertObjectHasAttribute('total', $response);
        $this->assertEquals(4, $response->total);
        $this->assertCount(4, $response->users);
        $this->assertEquals(1, $response->users[0]->id);
        $this->assertEquals('13800138000', $response->users[0]->mobile);
        $this->assertEquals('正常', $response->users[0]->status_desc);
        $this->assertEquals([1, 2], $response->users[0]->tag_ids);
        $this->assertEquals('已封号', $response->users[2]->status_desc);
    }

    public function testListClientUserSuccessfully_SpecifiedMobile()
    {
        $user = $this->prepareUser('operator');
        $this->prepareClientUser();
        $this->startSession();
        $this->actingAs($user, 'extended-eloquent-admin')
             ->ajaxGet('admin/client/user/list?mobile=13800138001')
             ->seeJsonContains(['code' => 0]);
        $response = json_decode($this->response->getContent());
        $this->assertObjectHasAttribute('total', $response);
        $this->assertEquals(1, $response->total);
        $this->assertCount(1, $response->users);
        $this->assertEquals(2, $response->users[0]->id);
        $this->assertEquals('13800138001', $response->users[0]->mobile);
        $this->assertEquals('正常', $response->users[0]->status_desc);
        $this->assertEquals([1, 4], $response->users[0]->tag_ids);
    }

    public function testListClientUserSuccessfully_FetchIncompleteUser()
    {
        $user = $this->prepareUser('operator');
        $this->prepareClientUser();
        $this->startSession();
        $this->actingAs($user, 'extended-eloquent-admin')
             ->ajaxGet('admin/client/user/list?mobile=13800138003')
             ->seeJsonContains(['code' => 0]);
        $response = json_decode($this->response->getContent());
        $this->assertEquals(1, $response->total);
        $this->assertCount(1, $response->users);
        $this->assertEquals(4, $response->users[0]->id);
        $this->assertEquals('待完善', $response->users[0]->status_desc);
    }

    private function prepareUser($role)
    {
        return factory(\Jihe\Models\Admin\User::class)->create([
            'id'        => 1,
            'user_name' => 'jihe-admin',
            'salt'      => 'ptrjb30aOvqWJ4mG',
            'password'  => '7907C7ED5F7F4E4872E24CAB8292464F',  // raw password is '*******'
            'role'      => $role,
        ]);
    }

    private function prepareClientUser()
    {
        $this->prepareClientUserTags();

        factory(\Jihe\Models\User::class)->create([
            'id'            => 1,
            'mobile'        => '13800138000',
            'nick_name'     => 'zhangsan_1',
            'avatar_url'    => 'avatar_1'
        ]);
        $this->getUserRepository()->updateProfile(1, ['tags' => [1, 2]]);

        factory(\Jihe\Models\User::class)->create([
            'id'            => 2,
            'mobile'        => '13800138001',
            'nick_name'     => 'zhangsan_2',
            'avatar_url'    => 'avatar_2'
        ]);
        $this->getUserRepository()->updateProfile(2, ['tags' => [1, 4]]);
        
        factory(\Jihe\Models\User::class)->create([
            'id'            => 3,
            'mobile'        => '13800138002',
            'nick_name'     => 'zhangsan_3',
            'avatar_url'    => 'avatar_3',
            'status'        => 2,
        ]);
        $this->getUserRepository()->updateProfile(3, ['tags' => [4]]);

        factory(\Jihe\Models\User::class)->create([
            'id'            => 4,
            'mobile'        => '13800138003',
            'status'        => 0,
        ]);
    }

    private function prepareClientUserTags()
    {
        $data = [
            [1, 'car'],
            [2, 'sports'],
            [3, 'culture'],
            [4, 'ship'],
        ];
        foreach ($data as $item) {
            list($id, $name) = $item;
            factory(\Jihe\Models\UserTag::class)->create([
                'id'    => $id,
                'name'  => $name,
            ]);
        }
    }

    private function getUserRepository()
    {
        return $this->app[\Jihe\Contracts\Repositories\UserRepository::class];
    }
}
