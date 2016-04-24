<?php
namespace Jihe\Contracts\Repositories;

interface UserRepository
{
    /**
     * find user by his/her mobile number
     *
     * @param string $mobile   mobile number
     * @return int|null   user's id or null if user not exist.
     */
    public function findId($mobile);

    /**
     * Find user by his/her mobile number
     *
     * @param string $mobile    mobile number
     *
     * @return \Jihe\Entities\User|null
     */
    public function findUser($mobile);

    /**
     * find users by mobile numbers
     *
     * @param array $mobile     element is mobile number
     * @return  array           associate array, key is mobile,
     *                          value is user's id or null if user not exist.
     */
    public function findIdsByMobiles(array $mobiles);

    /**
     * add a new user
     *
     * @param array $user
     * @return int   id of the newly added user
     */
    public function add(array $user);

    /**
     * multiple add new user
     *
     * @param array $users
     * @return int   id of the newly added user
     */
    public function multipleAdd(array $users);

    /**
     * update user's password
     * @return int   affected rows
     */
    public function updatePassword($user, $password, $salt);

    /**
     * update user's identity_salt
     * @return boolean   whether affected
     */
    public function updateIdentitySalt($user, $salt);

    /**
     * find a user by id
     *
     * @param int $id   user id
     * @return \Jihe\Entities\User
     */
    public function findById($id);

    /**
     * find a user by id, with user's tag ids
     *
     * @param int $user   user id
     * @return \Jihe\Entities\User|null
     */
    public function findWithTagById($user);

    /**
     * update user profile by user id
     *
     * @param int $user       user id
     * @param array $profile
     */
    public function updateProfile($user, array $profile);

    /**
     * update user's avatar
     *
     * @param int $user        user id
     * @param string $avatar   new avatar uri
     * @return string|null     original avatar uri
     */
    public function updateAvatar($user, $avatar);

    /**
     * find all users by specified conditions
     *
     * @param string $mobile        user mobile number
     * @param string $nickName      user nick name
     * @param int $page             the current page number
     * @param int $pageSize         the number of data per page
     */
    public function findAllUsers($mobile, $nickName, $page, $pageSize);
}
