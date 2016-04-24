<?php
namespace spec\Jihe\Services;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use PhpSpec\Laravel\LaravelObjectBehavior;
use Prophecy\Argument;
use Bus;
use Crypt;

use Jihe\Services\StorageService;
use Jihe\Contracts\Repositories\UserRepository;
use Jihe\Entities\User as UserEntity;

class UserServiceSpec extends LaravelObjectBehavior
{
    /**
     * @var array $randomStr that will be returned by str_random()
     */
    public static $randomStr;

    function let(UserRepository $repository, StorageService $storageService)
    {
        $this->beAnInstanceOf(\Jihe\Services\UserService::class, [$repository, $storageService]);
        Bus::shouldReceive('dispatch')->with(\Mockery::on(function ($command) {
            // simple check, it should be SendSms that is going to be dispatched
            return ($command instanceof \Jihe\Jobs\SendMessageToUserJob);
        }))->andReturn(null);
    }


    //=============================================
    //          Registration
    //=============================================
    function it_registers_successfully_if_user_not_exists_and_verification_passes(UserRepository $repository)
    {
        $mobile = '13811111111';
        $password = '123456';
        $profile = [
            'nick_name' => 'zhangsan',
            'gender'    => 1,
            'birthday'  => '2015-07-08',
            'tags'      => [1, 3, 5],
        ];

        $repository->findUser($mobile)->shouldBeCalledTimes(1)->willReturn(null);
        $repository->findId($mobile)->shouldBeCalledTimes(1)->willReturn(null);
        $repository->add(Argument::allOf(Argument::withEntry('mobile', $mobile),
                                         Argument::withKey('password'),
                                         Argument::withKey('salt'),
                                         Argument::withEntry('status', UserEntity::STATUS_INCOMPLETE)))
                   ->shouldBeCalledTimes(1)
                   ->willReturn(1);
        $repository->updateProfile(1, Argument::allOf(Argument::withEntry('nick_name', 'zhangsan'),
                                                      Argument::withEntry('gender', 1),
                                                      Argument::withEntry('birthday', '2015-07-08'),
                                                      Argument::withEntry('tags', [1, 3, 5]),
                                                      Argument::withEntry('status', UserEntity::STATUS_NORMAL),
                                                      Argument::not(Argument::withKey('password'))))
            ->shouldBeCalledTimes(1)->willReturn(null);
        $repository->findById(1)->willReturn((new UserEntity())->setId(1));
        $repository->updateIdentitySalt(Argument::cetera())->willReturn(true);

        $this->register($mobile, $password, $profile)->shouldBe(1);
    }

    function it_registers_successfully_if_user_is_exists_but_not_completed_info(UserRepository $repository)
    {
        $mobile = '13811111111';
        $password = '123456';
        $profile = [
            'nick_name' => 'zhangsan',
            'gender'    => 1,
            'birthday'  => '2015-07-08',
            'tags'      => [1, 3, 5],
        ];

        $user = (new \Jihe\Entities\User())->setId(1)->setStatus(\Jihe\Entities\User::STATUS_INCOMPLETE);
        $repository->findUser($mobile)->shouldBeCalledTimes(1)->willReturn($user);
        $repository->updateProfile(1, Argument::allOf(Argument::withEntry('nick_name', 'zhangsan'),
            Argument::withKey('password'),
            Argument::withEntry('gender', 1),
            Argument::withEntry('birthday', '2015-07-08'),
            Argument::withEntry('tags', [1, 3, 5]),
            Argument::withEntry('status', UserEntity::STATUS_NORMAL)))
            ->shouldBeCalledTimes(1)->willReturn(null);

        $this->register($mobile, $password, $profile)->shouldBe(1);
    }

    function it_throws_exception_in_register_if_user_exists_and_completed_info(UserRepository $repository)
    {
        $mobile = '13811111111';
        $password = '123456';
        $profile = [
            'nick_name' => 'zhangsan',
            'gender'    => 1,
            'birthday'  => '2015-07-08',
            'tags'      => [1, 2, 3],
        ];
        $user = (new \Jihe\Entities\User())->setId(1)->setStatus(\Jihe\Entities\User::STATUS_NORMAL);
        $repository->findUser($mobile)->shouldBeCalledTimes(1)->willReturn($user);
        $repository->add(Argument::cetera())->shouldNotBeCalled();

        $this->shouldThrow(\Jihe\Exceptions\User\UserExistsException::class)
             ->duringRegister($mobile, $password, $profile);
    }

    //=============================================
    //          CompleteProfile
    //=============================================
    function it_completes_profile_successfully(UserRepository $repository)
    {
        $user = 1;
        $profile = [
            'nick_name' => 'zhangsan',
            'gender'    => 1,
            'birthday'  => '2015-07-08',
            'status'    => UserEntity::STATUS_NORMAL,
            'tags'      => [1, 3, 5],
        ];
        $repository->updateProfile($user, $profile)->shouldBeCalledTimes(1)->willReturn(null);

        $this->completeProfile($user, $profile)->shouldBeNull();
    }

    function it_completes_profile_successfully_with_password(UserRepository $repository)
    {
        $user = 1;
        $profile = [
            'password'  => '123456',
            'nick_name' => 'zhangsan',
            'gender'    => 1,
            'birthday'  => '2015-07-08',
            'tags'      => [1, 3, 5],
        ];

        $repository->updateProfile($user, Argument::allOf(Argument::withEntry('nick_name', 'zhangsan'),
                                                          Argument::withEntry('gender', 1),
                                                          Argument::withEntry('birthday', '2015-07-08'),
                                                          Argument::withEntry('status', UserEntity::STATUS_NORMAL),
                                                          Argument::withKey('password'),
                                                          Argument::withKey('salt'),
                                                          Argument::withEntry('tags', [1, 3, 5])))
            ->shouldBeCalledTimes(1);

        $this->completeProfile($user, $profile)->shouldBeNull();
    }

    //=========================================
    //          Reset password
    //=========================================
    function it_reset_password_successfully(UserRepository $repository)
    {
        $mobile   = '13800000000';
        $password = '123456';
        $salt = 'ptrjb30aOvqWJ4mG';

        $repository->findId($mobile)->shouldBeCalledTimes(1)->willReturn(1);
        // 3E598067A98A229B6935051499F3E2BC is hashed version of $password
        $repository->updatePassword(1, '3E598067A98A229B6935051499F3E2BC', $salt)
                   ->shouldBeCalledTimes(1)->willReturn(1);

        $this->resetPassword($mobile, $password, $salt)
             ->shouldBe(true);
    }

    function it_throws_exception_if_reset_password_user_not_exists(UserRepository $repository)
    {
        $mobile = '13800000000';
        $password = '123456';
        $repository->findId($mobile)->shouldBeCalledTimes(1)->willReturn(null);

        $this->shouldThrow(\Jihe\Exceptions\User\UserNotExistsException::class)
             ->duringResetPassword($mobile, $password);
    }

    //=========================================
    //          update avatar
    //=========================================
    function it_updates_avatar(UserRepository $repository, StorageService $storageService)
    {
        $user = 1;
        $avatar = '/path/to/avatar.jpg';

        $storageService->storeAsImage($avatar, [ 'ext' => 'jpg' ])->shouldBeCalledTimes(1)->willReturn('http://domain.com/new_avatar');
        $repository->updateAvatar($user, 'http://domain.com/new_avatar')->shouldBeCalledTimes(1)->willReturn('http://domain.com/old_avatar');
        $storageService->remove('http://domain.com/old_avatar')->shouldBeCalledTimes(1);

        $this->updateAvatar($user, $avatar, [
            'ext' => 'jpg',
        ])->shouldBe('http://domain.com/new_avatar');
    }

    function it_updates_avatar_with_no_old_avatar(UserRepository $repository, StorageService $storageService)
    {
        $user = 1;
        $avatar = '/path/to/avatar.jpg';

        $storageService->storeAsImage($avatar, [ 'ext' => 'jpg' ])->shouldBeCalledTimes(1)->willReturn('http://domain.com/new_avatar');
        $repository->updateAvatar($user, 'http://domain.com/new_avatar')->shouldBeCalledTimes(1)->willReturn(null);
        $storageService->remove(Argument::cetera())->shouldNotBeCalled();

        $this->updateAvatar($user, $avatar, [
            'ext' => 'jpg',
        ])->shouldBe('http://domain.com/new_avatar');
    }

    //====================================================
    //          fetchUserOrRegisterIfUserNotExists
    //====================================================
    function it_fetch_user_successfully_if_user_exists(UserRepository $repository)
    {
        $mobile = '13800138000';
        $repository->findId($mobile)->shouldBeCalledTimes(1)->willReturn(1);
        $this->fetchUserOrRegisterIfUserNotExists($mobile)->shouldBe([1, null]);
    }

    function it_register_and_fetch_user_if_user_not_exists(UserRepository $repository)
    {
        $mobile = '13800138000';
        $repository->findId($mobile)->shouldBeCalledTimes(2)->willReturn(null);
        $repository->add(Argument::any())->shouldBeCalledTimes(1)->willReturn(1);
        $repository->findById(1)->willReturn((new UserEntity())->setId(1));
        $repository->updateIdentitySalt(Argument::cetera())->willReturn(true);
        $this->fetchUserOrRegisterIfUserNotExists($mobile)->shouldHaveKeyWithValue(0, 1);
    }

    //====================================================
    //          changePassword
    //====================================================
    function it_change_password_successfully(UserRepository $repository)
    {
        $user = (new UserEntity())
            ->setId(1)
            ->setSalt('jhrVAvWNrBMNwVSa')
            ->setHashedPassword('5149E71D5420CBE082C16FE1F17217CD');    // password: 123456
        $repository->findById(1)->shouldBeCalledTimes(1)->willReturn($user);
        $repository->updatePassword(1, Argument::any(), Argument::any())
                   ->shouldBeCalledTimes(1)
                   ->willReturn(true);
        $this->changePassword(1, '123456', '111111')->shouldBe(true);
    }

    function it_throw_exception_user_not_exists_when_change_user_password(
        UserRepository $repository
    ) {
        $repository->findById(1)->shouldBeCalledTimes(1)->willReturn(null);
        $repository->updatePassword(Argument::cetera())->shouldNotBeCalled();
        $this->shouldThrow(\Jihe\Exceptions\User\UserNotExistsException::class)
             ->duringChangePassword(1, '123456', '111111');
    }

    function it_throw_exception_password_not_match_when_change_user_password(
        UserRepository $repository
    ) {
        $user = (new UserEntity())
            ->setId(1)
            ->setSalt('jhrVAvWNrBMNwVSa')
            ->setHashedPassword('5149E71D5420CBE082C16FE1F17217CD');    // password: 123456
        $repository->findById(1)->shouldBeCalledTimes(1)->willReturn($user);
        $this->shouldThrow(new \Exception('当前密码不正确'))
             ->duringChangePassword(1, '222222', '111111');
    }

    //====================================================
    //          resetIdentity
    //====================================================
    function it_reset_identity_successfully(UserRepository $repository)
    {
        $user = (new UserEntity())
            ->setId(1);

        self::$randomStr = '1234567890123456';
        $repository->updateIdentitySalt(1, '1234567890123456')->willReturn(true);
        Crypt::shouldReceive('encrypt')->once()->andReturn('secret');

        $this->resetIdentity($user)->shouldBe('secret');
        self::$randomStr = null;
    }

    function it_reset_identity_failed(UserRepository $repository)
    {
        $user = (new UserEntity())
            ->setId(1);

        self::$randomStr = '1234567890123456';
        $repository->updateIdentitySalt(1, '1234567890123456')->willReturn(false);

        $this->resetIdentity($user)->shouldBe(false);
        self::$randomStr = null;
    }

    //====================================================
    //          getUserByIdentity
    //====================================================
    function it_get_user_by_identity_successfully(UserRepository $repository)
    {
        $user = (new UserEntity())
            ->setId(1)->setIdentitySalt('1234567890123456');

        Crypt::shouldReceive('decrypt')->once()->andReturn(json_encode(
            [
                'key' => UserEntity::IDENTITY_KEY,
                'uid' => '1',
                'salt' => '1234567890123456',
            ]
        ));
        $repository->findById(1)->shouldBeCalledTimes(1)->willReturn($user);

        $this->getUserByIdentity('secret')->shouldBe($user);
    }

    function it_get_user_by_identity_failed_used_illegal_identity(UserRepository $repository)
    {
        Crypt::shouldReceive('decrypt')->once()->andReturn(json_encode(
            [
                'key' => 'illegal_identity',
                'uid' => '1',
                'salt' => '1234567890123456',
            ]
        ));
        $repository->findById(1)->shouldNotBeCalled();

        $this->shouldThrow(new \Exception('非法凭证'))
             ->duringGetUserByIdentity('secret');
    }

    function it_get_user_by_identity_failed_used_invalid_identity(UserRepository $repository)
    {
        $user = (new UserEntity())
            ->setId(1)->setIdentitySalt('1234567890123456');

        Crypt::shouldReceive('decrypt')->once()->andReturn(json_encode(
            [
                'key' => UserEntity::IDENTITY_KEY,
                'uid' => '1',
                'salt' => '1234567890123450',
            ]
        ));
        $repository->findById(1)->shouldBeCalledTimes(1)->willReturn($user);

        $this->shouldThrow(new \Exception('身份无效'))
             ->duringGetUserByIdentity('secret');
    }
}

namespace Jihe\Services;

/**
 * Override str_random() in current namespace for testing
 *
 * @return array
 */
function str_random($length = 16)
{
    return \spec\Jihe\Services\UserServiceSpec::$randomStr ?: \str_random($length);
}