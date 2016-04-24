<?php
namespace Jihe\Services\Admin;

use Illuminate\Contracts\Hashing\Hasher;
use Jihe\Contracts\Repositories\Admin\UserRepository;
use Jihe\Entities\Admin\User as UserEntity;
use Jihe\Hashing\PasswordHasher;
use Auth;

/**
 * User related service
 *
 */
class UserService
{
    /**
     * @var \Jihe\Contracts\Repositories\Admin\UserRepository
     */
    private $userRepository;
    
    /**
     * hasher to hash user's password. we hardcode our hasher here (
     * since we don't want change framework's hasher), although our
     * hasher complies with \Illuminate\Contracts\Hashing\Hasher.
     * 
     * @var \Jihe\Hashing\PasswordHasher
     */
    private $hasher;

    public function __construct(UserRepository $userRepository,
                                Hasher $hasher = null) {
        $this->userRepository = $userRepository;
        $this->hasher = $hasher ?: new PasswordHasher();
    }
    
    /**
     * user login
     * 
     * @param string $user_name   user name of user
     * @param string $password    plain password
     * @param bool $remember      true to remember the user once successfully logged in.
     *                            false otherwise.
     *
     * @return bool  true if login successfully, false otherwise.
     */
    public function login($userName, $password, $remember = false)
    {
        return Auth::attempt([
            'user_name' => $userName,
            'password'  => $password
        ], $remember);
    }
    
    /**
     * logout user
     */
    public function logout()
    {
        Auth::logout();
    }
    
    /**
     * User reset password
     *
     * @param string $user         id of user
     * @param string $password     plain password
     * @param string $oldPassword  old password of given user
     * @throws \Exception          if user not exist
     * @return boolean             true if password is reset. false otherwise
     */
    public function resetPassword($user, $password, $oldPassword = null)
    {
        
        if (null == ($user = $this->userRepository->findUser($user))) {
            throw new \Exception('用户不存在');
        }
        
        if (!empty($oldPassword) && $user->getPassword() != $this->hashPassword($oldPassword, $user->getSalt())) {
            throw new \Exception('旧密码错误');
        }
        
        $salt = $this->generateSalt(); //  generate salt
        $password = $this->hashPassword($password, $salt);
        
        return $this->userRepository->updatePassword($user->getId(), $password, $salt);
    }
    
    /**
     * hash user's raw password
     * 
     * @param string $password    plain text form of user's password
     * @param string $salt        salt
     * @return string             hashed password
     */
    private function hashPassword($password, $salt)
    {
        return $this->hasher->make($password, [ 'salt' => $salt ]);
    }
    
    /**
     * generate salt for hashing password
     * 
     * @return string
     */
    private function generateSalt()
    {
        return str_random(16);
    }
    
    /**
     * add new user
     * 
     * @param string $userName   user name
     * @param string $password   plain password
     * @param int $role          user's role
     * @return int               id of new user
     */
    public function add($userName, $password, $role)
    {
        if ($this->exists($userName)) {
            throw new \Exception('用户名已存在');
        }
        
        $salt = $this->generateSalt(); //  generate salt
        $password = $this->hashPassword($password, $salt);
        
        return $this->userRepository->add([
            'user_name' => $userName,
            'password'  => $password,
            'salt'      => $salt,
            'role'      => $role,
            'status'    => UserEntity::STATUS_NORMAL
        ]);
    }
    
    private function exists($userName)
    {
        return $this->userRepository->exists($userName);
    }
    
    /**
     * get user by user's id
     * 
     * @param int $user   id of user
     * @return \Jihe\Entities\Admin\User
     */
    public function getUser($user)
    {
        return $this->userRepository->findUser($user);
    }
    
    /**
     * 
     * @param int $page      index of page
     * @param int $size      size of page
     * @param string $role   role of user
     * @return array 
     */
    public function getUsers($page, $size, $role = null)
    {
        return $this->userRepository->findUsers($page, $size, $role);
    }
    
    /**
     * remove user
     *
     * @param int $user   id of user
     * @return array
     */
    public function remove($user)
    {
        return $this->userRepository->remove($user);
    }
}
