<?php
namespace Jihe\Entities\Admin;

class User
{
    const STATUS_NORMAL = 1; // 正常用户
    const STATUS_FORBIDDEN = 2; // 封号

    const ROLE_ADMIN = 'admin'; // 管理员
    const ROLE_OPERATOR = 'operator'; // 运营
    const ROLE_ACCOUNTANT = 'accountant'; // 财务

    private $id;
    private $userName;
    private $password;
    private $salt;
    private $role;
    private $status;

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setUserName($userName)
    {
        $this->userName = $userName;
        return $this;
    }

    public function getUserName()
    {
        return $this->userName;
    }

    public function setPassword($password)
    {
        $this->password = $password;
        return $this;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function setSalt($salt)
    {
        $this->salt = $salt;
        return $this;
    }

    public function getSalt()
    {
        return $this->salt;
    }

    public function setRole($role)
    {
        $this->role = $role;
        return $this;
    }

    public function getRole()
    {
        return $this->role;
    }

    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function isAdmin()
    {
        return $this->role == self::ROLE_ADMIN ? true : false;
    }

    public function isOperator()
    {
        return $this->role == self::ROLE_OPERATOR ? true : false;
    }

    public function isAccountant()
    {
        return $this->role == self::ROLE_ACCOUNTANT ? true : false;
    }

}
