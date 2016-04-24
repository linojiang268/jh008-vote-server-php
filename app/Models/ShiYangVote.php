<?php
namespace Jihe\Models;

use Illuminate\Database\Eloquent\Model;

class ShiYangVote extends Model
{
    const TYPE_APP = 1;
    const TYPE_WX = 2;
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'shiyang_votes';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['voter', 'user_id', 'type'];
}
