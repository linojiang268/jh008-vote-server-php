<?php
namespace Jihe\Models;

use Illuminate\Database\Eloquent\Model;
use Jihe\Models\ActivityCheckInQRCode;
use Jihe\Entities\Activity as ActivityEntity;
use Jihe\Entities\User as UserEntity;
use Jihe\Entities\ActivityCheckIn as ActivityCheckInEntity;

class ActivityCheckIn extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'activity_check_in';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'activity_id',
        'step',
        'process_id',
    ];
    
    public function user()
    {
        return $this->belongsTo(\Jihe\Models\User::class, 'user_id', 'id');
    }
    
    public function activity()
    {
        return $this->belongsTo(\Jihe\Models\User::class, 'activity_id', 'id');
    }
    
    public function qrcode()
    {
        return ActivityCheckInQRCode::where('activity_id', $this->activity_id)->where('step', $this->step)->first();
    }

    public function toEntity()
    {
        $entity = (new ActivityCheckInEntity())
            ->setId($this->id)
            ->setStep($this->step)
            ->setProcessId($this->process_id);

        if ($this->relationLoaded('activity') && $this->activity) {
            $entity->setActivity($this->activity->toEntity());
        } else {
            $entity->setActivity((new ActivityEntity())->setId($this->activity_id));
        }
        if ($this->relationLoaded('user') && $this->user) {
            $entity->setUser($this->user->toEntity());
        } else {
            $entity->setUser((new UserEntity())->setId($this->user_id));
        }

        return $entity;
    }
}
