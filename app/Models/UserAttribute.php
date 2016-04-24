<?php

namespace Jihe\Models;

use Illuminate\Database\Eloquent\Model;

class UserAttribute extends Model {

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'user_attributes';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'mobile',
        'attr_name',
        'attr_value',
    ];

}
