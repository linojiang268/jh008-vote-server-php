<?php
namespace intg\Jihe\Repositories;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use intg\Jihe\TestCase;

class WechatUserRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    //=============================
    //          saveUser
    //=============================
    public function testSaveUserSuccessfully()
    {
        $userInfo = [
            'openid'    => 'o6_bmjrPTlm6_2sgVt7hMZOPfL2M',
            'nick_name' => 'zhangsan',
            'gender'    => 0,
        ];

        $user = $this->getRepository()->saveUser($userInfo);
        self::assertInstanceOf(\Jihe\Entities\WechatUser::class, $user);
        self::assertEquals('o6_bmjrPTlm6_2sgVt7hMZOPfL2M', $user->getOpenid());
        self::assertEquals('zhangsan', $user->getNickName());
        self::assertEquals(0, $user->getGender());

        $this->seeInDatabase('wechat_users', [
            'openid'    => $userInfo['openid'],
            'nick_name' => $userInfo['nick_name'],
            'gender'    => $userInfo['gender'],
        ]);
    }

    public function testSaveUserSuccessfully_UpdateUser()
    {
        $userInfo = [
            'openid'    => 'o6_bmjrPTlm6_2sgVt7hMZOPfL2M',
            'nick_name' => 'zhangsan',
            'gender'    => 1,
            'country'   => 'china',
            'province'  => 'sichuan',
            'city'      => 'chengdu',
        ];
        factory(\Jihe\Models\WechatUser::class)->create([
            'openid'    => $userInfo['openid'],
            'nick_name' => 'lisi',
            'gender'    => 0,
        ]);

        $user = $this->getRepository()->saveUser($userInfo);
        self::assertInstanceOf(\Jihe\Entities\WechatUser::class, $user);
        self::assertEquals('o6_bmjrPTlm6_2sgVt7hMZOPfL2M', $user->getOpenid());
        self::assertEquals('zhangsan', $user->getNickName());
        self::assertEquals(1, $user->getGender());
        self::assertEquals('china', $user->getCountry());
        self::assertEquals('sichuan', $user->getProvince());
        self::assertEquals('chengdu', $user->getCity());

        $this->seeInDatabase('wechat_users', [
            'openid'    => $userInfo['openid'],
            'nick_name' => 'zhangsan',          // aready changed
            'gender'    => 1,                   // aready changed
            'country'   => 'china',
            'province'  => 'sichuan',
            'city'      => 'chengdu',
        ]);
    }

    //=============================
    //          findOne
    //=============================
    public function testFindOneSuccessfully()
    {
        factory(\Jihe\Models\WechatUser::class)->create([
            'openid'    => 'o6_bmjrPTlm6_2sgVt7hMZOPfL2M',
            'nick_name' => 'lisi',
            'gender'    => 0,
        ]);

        $user = $this->getRepository()->findOne('o6_bmjrPTlm6_2sgVt7hMZOPfL2M');
        self::assertInstanceOf(\Jihe\Entities\WechatUser::class, $user);
        self::assertEquals('o6_bmjrPTlm6_2sgVt7hMZOPfL2M', $user->getOpenid());
        self::assertEquals('lisi', $user->getNickName());
        self::assertEquals(0, $user->getGender());
    }

    public function testFindOneSuccessfully_UserNotExists()
    {
        $user = $this->getRepository()->findOne('o6_bmjrPTlm6_2sgVt7hMZOPfL2M');
        self::assertNull($user);
    }

    /**
     * @return \Jihe\Contracts\Repositories\WechatTokenRepository
     */
    private function getRepository()
    {
        return $this->app[\Jihe\Contracts\Repositories\WechatUserRepository::class];
    }
}
