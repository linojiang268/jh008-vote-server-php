<?php
namespace Jihe\Repositories\Admin;

use Jihe\Contracts\Repositories\Admin\UserRepository as UserRepositoryContract;
use Jihe\Models\Admin\User;
use Jihe\Entities\Admin\User as UserEntity;

class UserRepository implements UserRepositoryContract
{
    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\Admin\UserRepository::exists()
     */
    public function exists($userName)
    {
        return null != User::where('user_name', $userName)->value('id');
    }
    
    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\Admin\UserRepository::findUserByUserName()
     */
    public function findUserByUserName($userName)
    {
        $user = User::where('user_name', $userName)->first();
        
        if (is_null($user)) {
            return null;
        }
        
        return $user->toEntity();
    }
    
    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\Admin\UserRepository::findUser()
     */
    public function findUser($user)
    {
        $user = User::where('id', $user)->first();
        
        if (is_null($user)) {
            return null;
        }
        
        return $user->toEntity();
    }
    
    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\Admin\UserRepository::findUsers()
     */
    public function findUsers($page, $size, $role)
    {
        $query = new User();
        
        if (!is_null($role)) {
            $query = $query->where('role', $role);
        }

        $total = $query->getCountForPagination()->count();
        
        $pages = ceil($total / $size);
        if ($page > $pages) {
            $page = $pages;
        }
        
        return [$pages, array_map(function (User $user) {
            return $user->toEntity();
        }, $query->forPage($page, $size)->get()->all())];
    }
    
    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\Admin\UserRepository::add()
     */
    public function add(array $user)
    {
        unset($user['id']);
        return User::create($user)->id;
    }
    
    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\Admin\UserRepository::updatePassword()
     */
    public function updatePassword($user, $password, $salt)
    {
        $query = User::where('id', $user);
        
        return 1 == $query->update([
                                        'password' => $password,
                                        'salt'     => $salt,
                                  ]);
    }
    
    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\Admin\UserRepository::remove()
     */
    public function remove($user) 
    {
        return 1 == User::where('id', $user)
                        ->where('role', '<>', UserEntity::ROLE_ADMIN)
                        ->delete();
    }
}
