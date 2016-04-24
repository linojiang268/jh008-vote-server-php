<?php
namespace Jihe\Repositories;

use Jihe\Contracts\Repositories\UserRepository as UserRepositoryContract;
use Jihe\Models\User;
use Jihe\Entities\User as UserEntity;
use Jihe\Utils\PaginationUtil;
use DB;

class UserRepository implements UserRepositoryContract
{
    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\UserRepository::findId()
     */
    public function findId($mobile)
    {
        $user = User::where(['mobile' => $mobile], ['id'])->first();
            
        return ($user != null) ? $user->id : null;
    }

    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\UserRepository::findUser()
     */
    public function findUser($mobile)
    {
        return $this->convertToEntity(User::where('mobile', $mobile)->first());        
    }

    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\UserRepository::findIdsByMobiles()
     */
    public function findIdsByMobiles(array $mobiles)
    {
        if (empty($mobiles)) {
            return [];
        }
        // initialize mobileUsers, key is mobile, value is null,
        $mobileUsers = array_combine($mobiles, array_fill(0, count($mobiles), null));
        User::whereIn('mobile', $mobiles)->get()
                                         ->each(function ($user) use (&$mobileUsers) {
                                                $mobileUsers[$user->mobile] = $user->id;
                                         });
        return $mobileUsers;
    }

    /**
     * @see \Jihe\Contracts\Repositories\UserRepository::add()
     */
    public function add(array $user)
    {
        return User::create($user)->id;
    }

    /**
     * @see \Jihe\Contracts\Repositories\UserRepository::add()
     */
    public function multipleAdd(array $users)
    {
        return DB::table('users')->insert($users);
    }

    /**
     * @see \Jihe\Contracts\Repositories\UserRepository::updatePassword()
     */
    public function updatePassword($user, $password, $salt)
    {
        return User::where('id', $user)
                    ->update(['password' => $password,
                              'salt' => $salt]);
    }

    /**
     * @see \Jihe\Contracts\Repositories\UserRepository::updateIdentitySalt()
     */
    public function updateIdentitySalt($user, $salt)
    {
        return 1 == User::where('id', $user)
                        ->update(['identity_salt' => $salt]);
    }

    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\UserRepository::findWithTagById()
     */
    public function findWithTagById($user)
    {
        $user = User::with('tags')->find($user);

        return $user ? $user->toEntity() : null;
    }

    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\UserRepository::findById()
     */
    public function findById($id)
    {
        $userModel = User::find($id);

        return $userModel ? $userModel->toEntity() : null;
    }

    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\UserRepository::updateById()
     */
    public function updateProfile($user, array $profile)
    {
        // extract tags
        if ($tags = array_get($profile, 'tags')) {
            unset($profile['tags']);
        }

        User::where('id', $user)->update($profile);
        User::find($user)->tags()->sync($tags);
    }

    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\UserRepository::updateAvatar()
     */
    public function updateAvatar($user, $avatar) {
        if (null == $user = User::find($user)) {
            throw new \Exception('非法用户');
        }
        $oldAvatarUrl = $user->avatar_url;

        User::where('id', $user->id)
            ->update([ 'avatar_url' => $avatar ]);

        return $oldAvatarUrl;
    }

    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\UserRepository::findAllUsers()
     */
    public function findAllUsers($mobile, $nickName, $page, $pageSize)
    {
        $query = User::with('tags');
        if ($mobile) {
            $query->where('mobile', $mobile);
        }
        if ($nickName) {
            $query->where('nick_name', $nickName);
        }

        $count = $query->count();
        $page = PaginationUtil::genValidPage($page, $count, $pageSize);
        $users = $query->forPage($page, $pageSize)->get()->all();

        $users = array_map([ $this, 'convertToEntity' ], $users);

        return [$count, $users];
    }

    /**
     * (non-PHPdoc)
     *
     * @return \Jihe\Entities\User|null
     */
    private function convertToEntity($user)
    {
        return $user ? $user->toEntity() : null;
    }
}
