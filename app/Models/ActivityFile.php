<?php
namespace Jihe\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Jihe\Entities\ActivityFile as ActivityFileEntity;
use Jihe\Entities\Activity as ActivityEntity;

class ActivityFile extends Model
{
    use SoftDeletes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'activity_files';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
                            'activity_id',
                            'name',
                            'memo',
                            'size',
                            'extension',
                            'url',
                          ];
    
    public function activity()
    {
        return $this->belongsTo(\Jihe\Models\Activity::class, 'activity_id', 'id');
    }

    public function toEntity()
    {
        $activityFile = (new ActivityFileEntity())
                         ->setId($this->id)
                         ->setName($this->name)
                         ->setMemo($this->memo)
                         ->setSize($this->size)
                         ->setExtension($this->extension)
                         ->setUrl($this->url)
                         ->setCreatedAt($this->created_at);


        if ($this->relationLoaded('activity')) {
            $activityFile->setActivity($this->activity->toEntity());
        } else {
            $activityFile->setActivity((new ActivityEntity)->setId($this->activity_id));
        }
        
        return $activityFile;
    }
}
