<?php
namespace Jihe\Models;

use Illuminate\Database\Eloquent\Model;
use Jihe\Entities\ActivityGroup as ActivityGroupEntity;

class ActivityGroup extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'activity_groups';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['activity_id', 'name'];
    
    /**
     *
     * @return Jihe\Entities\ActivityGroup
     */
    public function convertToEntity()
    {
        return (new ActivityGroupEntity())->setId($this->id)
                    ->setName($this->name)
                    ->setActivityId($this->activity_id);
    }

    public function activity()
    {
        return $this->belongsTo(\Jihe\Models\Activity::class, 'activity_id', 'id');
    }
    
//    public function user()
//    {
//        return $this->belongsTo(\Jihe\Models\User::class, 'user_id', 'id');
//    }
}
