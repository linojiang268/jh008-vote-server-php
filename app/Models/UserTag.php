<?php

namespace Jihe\Models;

use Illuminate\Database\Eloquent\Model;
use Jihe\Entities\UserTag as UserTagEntity;

class UserTag extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'user_tags';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['id', 'name', 'resource_url'];

    public function users()
    {
        return $this->belongsToMany(\Jihe\Model\User::class,
                                    'user_tag_maps', 'tag_id', 'user_id');
    }
    
    public function toEntity()
    {
        $userTagEntity = new UserTagEntity();
        return $userTagEntity
            ->setId($this->id)
            ->setName($this->name)
            ->setResourceUrl($this->resource_url);
    }
}
