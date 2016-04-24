<?php
namespace spec\Jihe\Services\Admin;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use PhpSpec\Laravel\LaravelObjectBehavior;
use Prophecy\Argument;

use Jihe\Contracts\Repositories\Admin\UserRepository;
use Jihe\Entities\Admin\User as UserEntity;

class UserServiceSpec extends LaravelObjectBehavior
{
    function let(UserRepository $repository)
    {
        $this->beAnInstanceOf(\Jihe\Services\Admin\UserService::class, [$repository]);
    }
    
    //=========================================
    //             Reset password
    //=========================================
    function it_resets_password_successfully(UserRepository $repository)
    {
        $oldSalt = 'ptrjb30aOvqWJ4mG';
        //$oldPassword = '*******';
        $oldHashedPassword = '7907C7ED5F7F4E4872E24CAB8292464F';
        
        $user = (new UserEntity())
                    ->setId(1)
                    ->setUserName('jihe')
                    ->setPassword($oldHashedPassword)
                    ->setSalt($oldSalt)
                    ->setRole(UserEntity::ROLE_OPERATOR)
                    ->setStatus(UserEntity::STATUS_NORMAL);

        $repository->findUser(1)->shouldBeCalled()->willReturn($user);
        $repository->updatePassword(1, 
                                    Argument::that(function ($password) {
                                        return 32 == strlen($password);
                                    }),
                                    Argument::that(function ($salt) {
                                        return 16 == strlen($salt);
                                    }))
        ->shouldBeCalled(1)->willReturn(true);

        $this->resetPassword(1, '123456')
             ->shouldBe(true);
    }
    
    function it_resets_password_by_old_password_successfully(UserRepository $repository)
    {
        $oldSalt = 'ptrjb30aOvqWJ4mG';
        $oldPassword = '*******';
        $oldHashedPassword = '7907C7ED5F7F4E4872E24CAB8292464F';
    
        $user = (new UserEntity())
                    ->setId(1)
                    ->setUserName('jihe')
                    ->setPassword($oldHashedPassword)
                    ->setSalt($oldSalt)
                    ->setRole(UserEntity::ROLE_OPERATOR)
                    ->setStatus(UserEntity::STATUS_NORMAL);
    
        $repository->findUser(1)->shouldBeCalled()->willReturn($user);
        $repository->updatePassword(1, 
                                    Argument::that(function ($password) {
                                        return 32 == strlen($password);
                                    }),
                                    Argument::that(function ($salt) {
                                        return 16 == strlen($salt);
                                    }))
                   ->shouldBeCalled()
                   ->willReturn(true);
    
        $this->resetPassword(1, '123456', $oldPassword)
             ->shouldBe(true);
    }

    function it_throws_exception_if_user_not_exists(UserRepository $repository)
    {
        $repository->findUser(1)->shouldBeCalled()->willReturn(null);
        $repository->updatePassword(Argument::cetera())->shouldNotBeCalled();

        $this->shouldThrow(new \Exception('用户不存在'))
             ->duringResetPassword(1, '123456');
    }
    
    function it_throws_exception_if_old_password_is_wrong(UserRepository $repository)
    {
        $oldSalt = 'ptrjb30aOvqWJ4mG';
        $oldPassword = '*******';
        $oldHashedPassword = '7907C7ED5F7F4E4872E24CAB8292464F';
    
        $user = (new UserEntity())
                    ->setId(1)
                    ->setUserName('jihe')
                    ->setPassword($oldHashedPassword)
                    ->setSalt($oldSalt)
                    ->setRole(UserEntity::ROLE_OPERATOR)
                    ->setStatus(UserEntity::STATUS_NORMAL);
    
        $repository->findUser(1)->shouldBeCalled()->willReturn($user);
        $repository->updatePassword(Argument::cetera())->shouldNotBeCalled();
    
        $this->shouldThrow(new \Exception('旧密码错误'))
             ->duringResetPassword(1, '123456', 'wrongpass');
    }
    
    //=========================================
    //                  add
    //=========================================
    function it_adds_successfully(UserRepository $repository)
    {
        $repository->exists('jihe')->shouldBeCalled()->willReturn(false);
        $repository->add(Argument::that(function ($user) {
                            return 'jihe' == $user['user_name'] && 
                                   32 == strlen($user['password']) && 
                                   16 == strlen($user['salt']) &&
                                   UserEntity::ROLE_OPERATOR == $user['role'] && 
                                   UserEntity::STATUS_NORMAL == $user['status'];
                        }))
                   ->shouldBeCalled()
                   ->willReturn(1);
    
        $this->add('jihe', '*******', UserEntity::ROLE_OPERATOR)->shouldBe(1);
    }
    
    function it_adds_throw_exception_if_user_exists(UserRepository $repository)
    {
        $repository->exists('jihe')->shouldBeCalled()->willReturn(true);
        $repository->add(Argument::cetera())->shouldNotBeCalled();
    
        $this->shouldThrow(new \Exception('用户名已存在'))
             ->duringAdd('jihe', '*******', UserEntity::ROLE_OPERATOR);
    }
}
