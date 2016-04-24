<?php
namespace Jihe\Models\Admin;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\SoftDeletes;
use Jihe\Entities\Admin\User as UserEntity;

class User extends Model implements AuthenticatableContract, CanResetPasswordContract
{
    use Authenticatable, CanResetPassword, SoftDeletes;
    
    protected $table = 'admin_users';

    protected $fillable = ['user_name', 'salt', 'password', 'remember_token', 'role', 'status'];
    
    protected $hidden = ['salt', 'password', 'remember_token'];

    public function toEntity()
    {
        return (new UserEntity())
                ->setId($this->id)
                ->setUserName($this->user_name)
                ->setSalt($this->salt)
                ->setPassword($this->password)
                ->setRole($this->role)
                ->setStatus($this->status);
    }
}
