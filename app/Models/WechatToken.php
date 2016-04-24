<?php

namespace Jihe\Models;

use Illuminate\Database\Eloquent\Model;
use Jihe\Entities\WechatToken as WechatTokenEntity;

class WechatToken extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'wechat_tokens';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'openid',
        'web_token_access',
        'web_token_expire_at',
        'web_token_refresh',
        'token_access',
        'token_expire_at',
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
        'web_token_expire_at',
        'token_expire_at',
        'created_at',
        'updated_at'
    ];

    public function toEntity()
    {
        $entity = (new WechatTokenEntity())
            ->setOpenid($this->openid)
            ->setWebTokenAccess($this->web_token_access)
            ->setWebTokenExpireAt($this->web_token_expire_at)
            ->setWebTokenRefresh($this->web_token_refresh)
            ->setTokenAccess($this->token_access)
            ->setTokenExpireAt($this->token_expire_at);

        return $entity;
    }
}
