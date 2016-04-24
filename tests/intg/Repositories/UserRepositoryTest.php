<?php
namespace intg\Jihe\Repositories;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use intg\Jihe\TestCase;
use Jihe\Models\User;

class UserRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    //=========================================
    //            findId
    //=========================================
    public function testFindIdFound()
    {
        factory(\Jihe\Models\User::class)->create([
            'id' => 1,
            'mobile' => '13800138000',
        ]);
        
        self::assertEquals(1, $this->getRepository()->findId('13800138000'));
    }

    public function testFindIdNotFound()
    {
        self::assertNull($this->getRepository()->findId('13800138000'));
    }

    //=========================================
    //            findUser
    //=========================================
    public function testFindUserFound()
    {
        factory(\Jihe\Models\User::class)->create([
            'id' => 1,
            'mobile' => '13800138000',
        ]);
        
        self::assertEquals(1, $this->getRepository()->findUser('13800138000')->getId());
        self::assertEquals('13800138000', $this->getRepository()
                                               ->findUser('13800138000')->getMobile());
    }

    public function testFindUserNotFound()
    {
        self::assertNull($this->getRepository()->findUser('13800138000'));
    }

    //=========================================
    //            add
    //=========================================
    public function testAddUserNotExists()
    {
        $user = factory(User::class)->make(['mobile' => '13800138000'])->toArray();
        // salt & password are hidden in model, so toArray() cann't fetch these 
        // attributes, we should set them
        $user['salt'] = str_random(16);
        $user['password'] = str_random(32);
        self::assertGreaterThanOrEqual(1, $this->getRepository()->add($user));
    }

    public function testAddUserExists()
    {
        $user = factory(User::class)->create(['mobile' => '13800138000'])->toArray();
        $user['salt'] = str_random(16);
        $user['password'] = str_random(32);
        try {
            $this->getRepository()->add($user);
        } catch (\Exception $e) {
            self::assertContains('1062 Duplicate entry', $e->getMessage());
        }
    }

    //============================================
    //          updatePassword
    //============================================
    public function testUpdatePassword()
    {
        $user = factory(User::class)->create(['mobile' => '13800138000']);
        self::assertEquals(1, $this->getRepository()->updatePassword($user->id, str_random(32), str_random(16)));
    }

    //============================================
    //          updateIdentitySalt
    //============================================
    public function testUpdateIdentitySalt()
    {
        $user = factory(User::class)->create(['mobile' => '13800138000']);
        self::assertTrue($this->getRepository()->updateIdentitySalt($user->id, str_random(16)));
    }

    //============================================
    //          findWithTagById
    //============================================
    public function testFindWithTagByIdUserExists()
    {
        $user = factory(User::class)->create(['mobile' => '13800138000']);
        $tag1 = factory(\Jihe\Models\UserTag::class)->create(['name' => 'car']); 
        $tag2 = factory(\Jihe\Models\UserTag::class)->create(['name' => 'sports']); 
        $user->tags()->saveMany([$tag1, $tag2]);
       
        $userEntity = $this->getRepository()->findWithTagById($user->id);

        self::assertEquals($user->mobile, $userEntity->getMobile());
        $tags = $userEntity->getTags()->map(function($item, $key) {
            return [$item->getId(), $item->getName()]; 
        })->toArray();
        self::assertEquals([[$tag1->id, $tag1->name], [$tag2->id, $tag2->name]], $tags);
    }

    public function testFindWithTagByIdUserNotExists()
    {
        self::assertNull($this->getRepository()->findWithTagById(1));
    }

    //============================================
    //          findById
    //============================================
    public function testFindByIdUserExists()
    {
        $user = factory(User::class)->create(['mobile' => '13800138000']);
        self::assertEquals('13800138000', $this->getRepository()->findById($user->id)->getMobile());
    }

    public function testFindByIdUserNotExists()
    {
        self::assertNull($this->getRepository()->findById(1));
    }

    //============================================
    //          updateProfileById
    //============================================
    public function testUpdateProfileById()
    {
        $user = factory(User::class)->create(['mobile' => '13800138000', 'nick_name' => 'lisi']);
        $tag = factory(\Jihe\Models\UserTag::class)->create(['name' => 'car']); 

        $this->getRepository()->updateProfile($user->id, [
            'nick_name' => 'wangwu',
            'tags'      => [ $tag->id ]
        ]);

        $updatedUser = $this->getRepository()->findWithTagById($user->id);
        self::assertEquals('wangwu', $updatedUser->getNickName());
        self::assertEquals('car', $updatedUser->getTags()[0]->getName());
    }

    //============================================
    //          updateAvatar
    //============================================
    public function testUpdateAvatar()
    {
        $user = factory(User::class)->create(['mobile' => '13800138000', 'avatar_url' => 'old_avatar']);

        self::assertEquals('old_avatar', $this->getRepository()->updateAvatar($user->id, 'new_avatar'));
        // cross validation, 'new_avatar' should be associated
        self::assertEquals('new_avatar', $this->getRepository()->findById($user->id)->getAvatarUrl());
    }

    //============================================
    //          findAllUsers
    //============================================
    public function testFindAllUsersFound()
    {
        $userOne = factory(User::class)->create([
            'id'            => 1,
            'mobile'        => '13800138000',
            'avatar_url'    => 'old_avatar_1'
        ]);
        $userTwo = factory(User::class)->create([
            'id'            => 2,
            'mobile'        => '13800138001',
            'avatar_url'    => 'old_avatar_2'
        ]);
        $tagOne = factory(\Jihe\Models\UserTag::class)->create(['name' => 'car']);
        $tagTwo = factory(\Jihe\Models\UserTag::class)->create(['name' => 'sports']);

        $this->getRepository()->updateProfile($userOne->id, [
            'nick_name' => 'wangwu',
            'tags'      => [ $tagOne->id ]
        ]);
        $this->getRepository()->updateProfile($userTwo->id, [
            'nick_name' => 'zhangsan',
            'tags'      => [ $tagTwo->id ]
        ]);

        list($total, $users) = $this->getRepository()->findAllUsers(null, null, 1, 10);

        self::assertEquals(2, $total);
        self::assertCount(2, $users);
        self::assertEquals(1, $users[0]->getId());
        self::assertEquals('car', $users[0]->getTags()[0]->getName());
    }

    public function testFindAllUsersFound_UseMobileCondition()
    {
        $this->prepareUserData();

        list($total, $users) = $this->getRepository()->findAllUsers(
                                                    '13800138001', null, 1, 10);
        self::assertEquals(1, $total);
        self::assertCount(1, $users);
        self::assertEquals('13800138001', $users[0]->getMobile());
        self::assertEquals('sports', $users[0]->getTags()[0]->getName());
    }

    //============================================
    //          findAllUsers
    //============================================
    public function testFindIdsByMobilesFound()
    {
        $this->prepareUserData();

        $mobileUsers = $this->getRepository()->findIdsByMobiles([
            '13800138000', '13800138001', '13500135000'
        ]);

        self::assertCount(3, $mobileUsers);
        self::assertEquals(1, array_get($mobileUsers, '13800138000'));
        self::assertEquals(2, array_get($mobileUsers, '13800138001'));
        self::assertEquals(null, array_get($mobileUsers, '13500135000'));
    }
    //============================================
    //          multipleAdd
    //============================================
    public function testMultipleAdd()
    {
        $users = [];
        $mobiles = [];
        for($i=0;$i<20;$i++){
            $mobile = strval(13800138000 + $i);
            $user = factory(User::class)->make(['mobile' => $mobile])->toArray();
            $user['salt'] = str_random(16);
            $user['password'] = str_random(32);
            $users[] = $user;
            $mobiles[] = $mobile;
        }
        self::assertGreaterThanOrEqual(true, $this->getRepository()->multipleAdd($users));
        $mobileUsers = $this->getRepository()->findIdsByMobiles($mobiles);
        self::assertCount(20, $mobileUsers);
        self::assertEquals(false, array_search(null, $mobileUsers));
    }

    /**
     * @return \Jihe\Contracts\Repositories\UserRepository
     */
    private function getRepository()
    {
        return $this->app[\Jihe\Contracts\Repositories\UserRepository::class];
    }

    private function prepareUserData()
    {
        factory(User::class)->create([
            'id'            => 1,
            'mobile'        => '13800138000',
            'avatar_url'    => 'old_avatar_1'
        ]);
        factory(\Jihe\Models\UserTag::class)->create([
            'id'    => 1,
            'name'  => 'car'
        ]);
        $this->getRepository()->updateProfile(1, [
            'nick_name' => 'wangwu',
            'tags'      => [1],
        ]);

        factory(User::class)->create([
            'id'            => 2,
            'mobile'        => '13800138001',
            'avatar_url'    => 'old_avatar_2'
        ]);
        factory(\Jihe\Models\UserTag::class)->create([
            'id'    => 2,
            'name'  => 'sports'
        ]);
        $this->getRepository()->updateProfile(2, [
            'nick_name' => 'zhangsan',
            'tags'      => [2],
        ]);

    }
}
