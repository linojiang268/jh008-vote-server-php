<?php
namespace Jihe\Models;

use Illuminate\Database\Eloquent\Model;
use Jihe\Entities\Message as MessageEntity;
use Jihe\Entities\Team as TeamEntity;
use Jihe\Entities\Activity as ActivityEntity;
use Jihe\Entities\User as UserEntity;

class Message extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'messages';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
                            'team_id',
                            'activity_id',
                            'user_id',
                            'content',
                            'type',
                            'attributes',
                            'notified_type',
                          ];
    
    public function team()
    {
        return $this->belongsTo(\Jihe\Models\Team::class, 'team_id', 'id');
    }
    
    public function activity()
    {
        return $this->belongsTo(\Jihe\Models\Activity::class, 'activity_id', 'id');
    }

    public function toEntity()
    {
        $message = (new MessageEntity())
                    ->setId($this->id)
                    ->setContent($this->content)
                    ->setType($this->type)
                    ->setAttributes(empty($this->attributes['attributes']) ? null : json_decode($this->attributes['attributes']))
                    ->setNotifiedType($this->notified_type)
                    ->setCreatedAt($this->created_at->format('Y-m-d H:i:s'));
        
        if ($this->team_id) {
            if ($this->relationLoaded('team')) {
                $message->setTeam($this->team->toEntity());
            } else {
                $message->setTeam((new TeamEntity())->setId($this->team_id));
            }
        }
        if ($this->activity_id) {
            if ($this->relationLoaded('activity')) {
                $message->setActivity($this->activity->toEntity());
            } else {
                $message->setActivity((new ActivityEntity())->setId($this->activity_id));
            }
        }
        if ($this->user_id) {
            if ($this->relationLoaded('user')) {
                $message->setUser($this->user->toEntity());
            } else {
                $message->setUser((new UserEntity())->setId($this->user_id));
            }
        }

        return $message;
    }
}
