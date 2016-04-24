<?php
namespace intg\Jihe\Controllers\Api;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use intg\Jihe\TestCase;
use intg\Jihe\RequestSignCheck;
use intg\Jihe\AuthDeviceCheck;
use \PHPUnit_Framework_Assert as Assert;


class UserControllerTest extends TestCase
{
    use DatabaseTransactions;
    use RequestSignCheck;
    use AuthDeviceCheck;


    //=============================================
    //          show profile
    //=============================================
    public function testShowProfileSuccessfully()
    {
        $user = factory(\Jihe\Models\User::class)->create([
            'mobile'    => '13800138000',
            'nick_name' => 'wangwu',
            ]);
        factory(\Jihe\Models\Team::class)->create([
            'creator_id'    => $user->id,
            'city_id'       => 1,
        ]);
        factory(\Jihe\Models\City::class)->create([
            'id' => 1,
        ]);
        $tag1 = factory(\Jihe\Models\UserTag::class)->create(['name' => 'car']);
        $tag2 = factory(\Jihe\Models\UserTag::class)->create(['name' => 'sports']);
        $user->tags()->saveMany([$tag1, $tag2]);

        $this->actingAs($user)->get('api/user/profile')->seeJsonContains([
            'code' => 0,
            'nick_name' => 'wangwu',
            'tag_ids'   => [$tag1->id, $tag2->id],
            'is_team_owner' => true,
        ]);
    }

    public function testSuccessfulSeeOthersProfile()
    {
        $user = factory(\Jihe\Models\User::class)->create([
            'mobile'    => '13800138001',
            'nick_name' => 'zhangshan',
        ]);
        $other = factory(\Jihe\Models\User::class)->create([
            'mobile'    => '13800138000',
            'nick_name' => 'wangwu',
        ]);
        $tag1 = factory(\Jihe\Models\UserTag::class)->create(['name' => 'car']);
        $tag2 = factory(\Jihe\Models\UserTag::class)->create(['name' => 'sports']);
        $other->tags()->saveMany([$tag1, $tag2]);
        $this->startSession();

        $this->actingAs($user)->get('api/user/profile?user_id=' . $other->id)->seeJsonContains([
            'code' => 0,
            'nick_name' => 'wangwu',
            'tag_ids'   => [$tag1->id, $tag2->id],
            'is_team_owner' => false,
        ]);
    }

    public function testSeeProfileOfNonExistingUser()
    {
        $this->actingAs(factory(\Jihe\Models\User::class)->create([
            'mobile'    => '13800138001',
            'nick_name' => 'zhangshan',
        ]))->ajaxGet('api/user/profile?user_id=-1');

        $response = json_decode($this->response->getContent(), true);
        Assert::assertCount(1, $response);  // there's only one 'code' in the response
        Assert::assertEquals('0', $response['code']);
    }

    //================================================
    //          update profile
    //================================================
    public function testUpdateProfileSuccessfully()
    {
        $user = factory(\Jihe\Models\User::class)->create([
            'mobile'    => '13800138000',
            'nick_name' => 'wangwu',
            ]);
        $tag1 = factory(\Jihe\Models\UserTag::class)->create(['name' => 'car']); 
        $tag2 = factory(\Jihe\Models\UserTag::class)->create(['name' => 'sports']); 
        $user->tags()->save($tag1);
        $this->mockStorageService();

        $this->actingAs($user)->call('POST', 'api/user/profile', [
            'nick_name'     => 'john',
            'gender'        => 1,
            'birthday'      => '2000-01-02',
            'tagIds'        => [$tag2->id],
        ], [],[
            'avatar'        => $this->makeUploadFile(__DIR__ . '/test-data/avatar.jpg')
        ]);
        $this->seeJsonContains(['code' => 0]);
    }

    //================================================
    //          complete profile
    //================================================
    public function testCompleteProfileSuccessfully()
    {
        $user = factory(\Jihe\Models\User::class)->create([
            'mobile'    => '13800138000',
            'nick_name' => 'wangwu',
            'status'    => 0,
            ]);
        $tag1 = factory(\Jihe\Models\UserTag::class)->create(['name' => 'car']); 
        $tag2 = factory(\Jihe\Models\UserTag::class)->create(['name' => 'sports']); 
        $user->tags()->save($tag1);
        $this->mockStorageService();

        $this->actingAs($user)->call('POST', 'api/user/profile/complete', [
            'nick_name' => 'john',
            'gender'    => 1,
            'birthday'  => '2000-01-02',
            'tagIds'    => [$tag2->id],
         ], [], [
            'avatar'    => $this->makeUploadFile(__DIR__ . '/test-data/avatar.jpg')
         ]);
        $this->seeJsonContains(['code' => 0]);
    }

    //================================================
    //          reset identity
    //================================================
    public function testResetIdentitySuccessfully()
    {
        $user = factory(\Jihe\Models\User::class)->create([
            'id'        => 1,
            'mobile'    => '13800138000',
            'nick_name' => 'wangwu',
        ]);

        $this->actingAs($user)->ajaxGet('/api/user/identity/reset');
        $this->seeJsonContains(['code' => 0]);
    }

    private function mockStorageService($return = 'key') 
    {
        $storageService = \Mockery::mock(\Jihe\Contracts\Services\Storage\StorageService::class);
        $storageService->shouldReceive('store')->withAnyArgs()->andReturn($return);
        $storageService->shouldReceive('getPortal')->withAnyArgs()->andReturn('http://download.domain.cn/' . $return);
        $storageService->shouldReceive('remove')->withAnyArgs()->andReturn(null);
        $this->app[\Jihe\Contracts\Services\Storage\StorageService::class] = $storageService;
        
        return $storageService;
    }
}
