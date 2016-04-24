<?php

namespace Jihe\Models;

use Illuminate\Database\Eloquent\Model;
use Jihe\Entities\WechatUser as WechatUserEntity;

class WechatUser extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'wechat_users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'openid',
        'nick_name',
        'gender',
        'country',
        'province',
        'city',
        'headimgurl',
        'privilege',
        'unionid',
        'subscribe',
        'subscribe_at',
        'groupid',
        'remark',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'subscribe_at',
        'created_at',
        'updated_at'
    ];

    public function toEntity()
    {
        $entity = (new WechatUserEntity())
            ->setOpenid($this->openid)
            ->setNickName($this->nick_name)
            ->setGender((int) $this->gender)
            ->setCountry($this->country)
            ->setProvince($this->province)
            ->setCity($this->city)
            ->setHeadimgurl($this->headimgurl)
            ->setUnionid($this->unionid)
            ->setSubscribe((int) $this->subscribe)
            ->setSubscribeAt($this->subscribe_at)
            ->setGroupid($this->groupid)
            ->setRemark($this->remark);

        return $entity;
    }
}
