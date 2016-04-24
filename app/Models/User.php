<?php
namespace Jihe\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\SoftDeletes;
use Jihe\Entities\User as UserEntity;

class User extends Model implements AuthenticatableContract, CanResetPasswordContract
{
    use Authenticatable, CanResetPassword, SoftDeletes;
    
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];
    
    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['salt', 'password', 'remember_token'];

    public function teams()
    {
        return $this->hasManyThrough(\Jihe\Models\Team::class,
                                     \Jihe\Models\TeamMember::class,
                                     'user_id', 'team_id');
    }

    /**
     * relationship between users and user_tags are many-to-many
     */
    public function tags()
    {
        return $this->belongsToMany(\Jihe\Models\UserTag::class,
                                    'user_tag_maps', 'user_id', 'tag_id');
    }

    public function toEntity()
    {
        $userEntity = (new UserEntity())
            ->setId($this->id)
            ->setMobile($this->mobile)
            ->setType($this->type)
            ->setNickName($this->nick_name)
            ->setGender($this->gender)
            ->setBirthday($this->birthday)
            ->setSignature($this->signature)
            ->setAvatarUrl($this->avatar_url)
            ->setRegisterAt($this->created_at)
            ->setHashedPassword($this->password)
            ->setSalt($this->salt)
            ->setIdentitySalt($this->identity_salt)
            ->setStatus($this->status);

        // Process relationship
        if ($this->relationLoaded('tags')) {
            $userEntity->setTags($this->tags->map(function ($item, $key) {
                                    return $item->toEntity();
                                }));
        } else {
            $userEntity->setTags(collect());
        }

        return $userEntity;
    }
}
