<?php
namespace intg\Jihe\Repositories\Admin;

use intg\Jihe\TestCase;
use \PHPUnit_Framework_Assert as Assert;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Jihe\Entities\Admin\User as UserEntity;

class UserRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    //=======================================
    //                Exists
    //=======================================
    public function testExists()
    {
        factory(\Jihe\Models\Admin\User::class)->create([
            'id'        => 1,
            'user_name' => 'jihe',
        ]);
    
        Assert::assertTrue($this->getRepository()->exists('jihe'));
        Assert::assertFalse($this->getRepository()->exists('jhla'));
    }
    
    //=======================================
    //          Find User By UserName
    //=======================================
    public function testFindUserByUserName()
    {
        factory(\Jihe\Models\Admin\User::class)->create([
            'id'        => 1,
            'user_name' => 'jihe',
            'role'      => UserEntity::ROLE_ACCOUNTANT,
            'status'    => UserEntity::STATUS_NORMAL,
        ]);
    
        self::assertUser(1, 'jihe', UserEntity::ROLE_ACCOUNTANT, UserEntity::STATUS_NORMAL,
                         $this->getRepository()->findUserByUserName('jihe'));
        Assert::assertNull($this->getRepository()->findUserByUserName('jhla'));
    }
    
    private static function assertUser($expectedId, $expectedUserName,
                                       $expectedRole, $expectedStatus,
                                       UserEntity $user)
    {
        return null != $user && (!$expectedId || $expectedId == $user->getId()) &&
                    $expectedUserName   == $user->getUserName() &&
                    $expectedRole       == $user->getRole() &&
                    $expectedStatus     == $user->getStatus();
    }
    
    //=======================================
    //              Find User
    //=======================================
    public function testFindUser()
    {
        factory(\Jihe\Models\Admin\User::class)->create([
            'id'        => 1,
            'user_name' => 'jihe',
            'role'      => UserEntity::ROLE_ACCOUNTANT,
            'status'    => UserEntity::STATUS_NORMAL,
        ]);

        self::assertUser(1, 'jihe', UserEntity::ROLE_ACCOUNTANT, UserEntity::STATUS_NORMAL,
                         $this->getRepository()->findUser(1));
        Assert::assertNull($this->getRepository()->findUser(2));
    }
    
    //=======================================
    //              Find Users
    //=======================================
    public function testFindUsers()
    {
        factory(\Jihe\Models\Admin\User::class)->create([
            'id'        => 1,
            'user_name' => 'jihe',
            'role'      => UserEntity::ROLE_ACCOUNTANT,
            'status'    => UserEntity::STATUS_NORMAL,
        ]);
        factory(\Jihe\Models\Admin\User::class)->create([
            'id'        => 2,
            'user_name' => 'jhla',
            'role'      => UserEntity::ROLE_OPERATOR,
            'status'    => UserEntity::STATUS_NORMAL,
        ]);
        factory(\Jihe\Models\Admin\User::class)->create([
            'id'        => 3,
            'user_name' => 'jh008',
            'role'      => UserEntity::ROLE_ADMIN,
            'status'    => UserEntity::STATUS_NORMAL,
        ]);
        factory(\Jihe\Models\Admin\User::class)->create([
            'id'        => 4,
            'user_name' => 'jihe2',
            'role'      => UserEntity::ROLE_ACCOUNTANT,
            'status'    => UserEntity::STATUS_FORBIDDEN,
        ]);
        factory(\Jihe\Models\Admin\User::class)->create([
            'id'        => 5,
            'user_name' => 'jhla2',
            'role'      => UserEntity::ROLE_OPERATOR,
            'status'    => UserEntity::STATUS_FORBIDDEN,
        ]);
        factory(\Jihe\Models\Admin\User::class)->create([
            'id'        => 6,
            'user_name' => 'jh0082',
            'role'      => UserEntity::ROLE_ADMIN,
            'status'    => UserEntity::STATUS_FORBIDDEN,
        ]);

        // 2 users meet the specification
        list($pages, $users) = $this->getRepository()->findUsers(1, 2, UserEntity::ROLE_ADMIN);
        Assert::assertEquals(1, $pages);
        Assert::assertCount(2, $users);

        // 2 users meet the specification
        list($pages, $users) = $this->getRepository()->findUsers(1, 2, UserEntity::ROLE_OPERATOR);
        Assert::assertEquals(1, $pages);
        Assert::assertCount(2, $users);
        
        // 2 users meet the specification
        list($pages, $users) = $this->getRepository()->findUsers(1, 2, UserEntity::ROLE_ACCOUNTANT);
        Assert::assertEquals(1, $pages);
        Assert::assertCount(2, $users);
        
        // 6 users meet the specification
        list($pages, $users) = $this->getRepository()->findUsers(1, 6, null);
        Assert::assertEquals(1, $pages);
        Assert::assertCount(6, $users);

        // first page
        list($pages, $users) = $this->getRepository()->findUsers(1, 4, null);
        Assert::assertEquals(2, $pages);
        Assert::assertCount(4, $users);

        // second page
        list($pages, $users) = $this->getRepository()->findUsers(2, 4, null);
        Assert::assertEquals(2, $pages);
        Assert::assertCount(2, $users);
    }
    
    //=======================================
    //                  Add
    //=======================================
    public function testAdd()
    {
        
        Assert::assertNotFalse($this->getRepository()->add([
            'user_name' => 'operator-xiaoming',
            'salt'      => 'ptrjb30aOvqWJ4mG',
            'password'  => '7907C7ED5F7F4E4872E24CAB8292464F',  // by ******* 
            'role'      => UserEntity::ROLE_OPERATOR,
            'status'    => UserEntity::STATUS_NORMAL,
        ]));
        
        $this->seeInDatabase('admin_users', [
            'user_name'  => 'operator-xiaoming',
            'salt'      => 'ptrjb30aOvqWJ4mG',
            'password'  => '7907C7ED5F7F4E4872E24CAB8292464F',
            'role'      => UserEntity::ROLE_OPERATOR,
            'status'    => UserEntity::STATUS_NORMAL,
        ]);
    }
    
    //=======================================
    //            Update Password
    //=======================================
    public function testUpdatePassword()
    {
        factory(\Jihe\Models\Admin\User::class)->create([
            'id'   => 1,
            'salt' => '1234567890123456',
        ]);
        
        Assert::assertTrue($this->getRepository()->updatePassword(
            1,
            '7907C7ED5F7F4E4872E24CAB8292464F',  // by *******
            'ptrjb30aOvqWJ4mG'
        ));
    
        $this->seeInDatabase('admin_users', [
            'id'       => 1,
            'salt'     => 'ptrjb30aOvqWJ4mG',
            'password' => '7907C7ED5F7F4E4872E24CAB8292464F',
        ]);
    }
    
    //=======================================
    //                Remove
    //=======================================
    public function testRemove()
    {
        factory(\Jihe\Models\Admin\User::class)->create([
            'id'   => 1,
            'salt' => '1234567890123456',
            'role' => UserEntity::ROLE_ADMIN,
        ]);
        
        factory(\Jihe\Models\Admin\User::class)->create([
            'id'   => 2,
            'salt' => '1234567890123456',
            'role' => UserEntity::ROLE_OPERATOR,
        ]);
        
        factory(\Jihe\Models\Admin\User::class)->create([
            'id'   => 3,
            'salt' => '1234567890123456',
            'role' => UserEntity::ROLE_ACCOUNTANT,
        ]);
        
        Assert::assertFalse($this->getRepository()->remove(1));
        Assert::assertTrue($this->getRepository()->remove(2));
        Assert::assertTrue($this->getRepository()->remove(3));
    
        $this->seeInDatabase('admin_users', [
            'id'   => 1,
            'salt' => '1234567890123456',
            'role' => UserEntity::ROLE_ADMIN,
        ]);
        
        $this->notSeeInDatabase('admin_users', [
            'id'         => 2,
            'deleted_at' => null,
        ]);
        
        $this->notSeeInDatabase('admin_users', [
            'id'         => 3,
            'deleted_at' => null,
        ]);
    }
    
    /**
     * @return \Jihe\Contracts\Repositories\Admin\UserRepository
     */
    private function getRepository()
    {
        return $this->app[\Jihe\Contracts\Repositories\Admin\UserRepository::class];
    }
}
