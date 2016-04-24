<?php
namespace Jihe\Contracts\Repositories\Admin;

interface UserRepository
{
    /**
     * check whether user userd given userName is exists 
     * 
     * @param string $userName   user name of user
     * @return boolean           true is exists, otherwise false
     */
    public function exists($userName);
    
    /**
     * find user by given user name of user
     * 
     * @param string $userName   user name of user
     * @return \Jihe\Entities\Admin\User
     */
    public function findUserByUserName($userName);
    
    /**
     * find user by given id of user
     * 
     * @param int $user   id of user
     * @return \Jihe\Entities\Admin\User
     */
    public function findUser($user);
    
    /**
     * find users
     * 
     * @param int $page   index of page
     * @param int $size   size of page
     * @param int $role   role of user
     * @return array      array of \Jihe\Entities\Admin\User
     */
    public function findUsers($page, $size, $role);
    
    /**
     * add new user
     * 
     * @param array $user  attributes of user model
     * @return int         id of new user
     */
    public function add(array $user);
    
    /**
     * update password of user
     * 
     * @param int $user          id of user
     * @param string $password   new password of given user
     * @param string $salt       salt of password
     * @return boolean
     */
    public function updatePassword($user, $password, $salt);
    
    /**
     * remove user
     * 
     * @param int $user   id of user
     * @return boolean
     */
    public function remove($user);
}
