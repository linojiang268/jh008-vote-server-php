<?php
namespace Jihe\Repositories;

use Jihe\Contracts\Repositories\WechatUserRepository as WechatUserRepositoryContract;
use Jihe\Models\WechatUser;

class WechatUserRepository implements WechatUserRepositoryContract
{
    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\WechatUserRepository::findOne()
     */
    public function findOne($openid)
    {
        return $this->convertToEntity(
            WechatUser::where('openid', $openid)->first()
        );
    }

    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\WechatUserRepository::saveUser()
     */
    public function saveUser(array $user)
    {
        $userModel = WechatUser::where('openid', $user['openid'])->first();
        if ($userModel) {
            foreach ($user as $key => $value) {
                $userModel->{$key} = $value;
            }
            $userModel->save();
        } else {
            $userModel = WechatUser::create($user);
        }

        return $this->convertToEntity($userModel);
    }

    /**
     * convert modle to entity
     *
     * @return \Jihe\Entities\WechatUser|null
     */
    private function convertToEntity($wechatUser)
    {
        return $wechatUser ? $wechatUser->toEntity() : null;
    }
}
