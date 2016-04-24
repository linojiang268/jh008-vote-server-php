<?php
namespace Jihe\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Jihe\Entities\ActivityAlbumImage as ActivityAlbumImageEntity;
use Jihe\Entities\Activity as ActivityEntity;
use Jihe\Entities\User as UserEntity;

class ActivityAlbumImage extends Model
{
    use SoftDeletes;
    
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'activity_album_images';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
                            'activity_id',
                            'creator_type',
                            'creator_id',
                            'image_url',
                            'status',
                          ];
    
    public function activity()
    {
        return $this->belongsTo(\Jihe\Models\Activity::class, 'activity_id', 'id');
    } 
    public function creator()
    {
        return $this->belongsTo(\Jihe\Models\User::class, 'creator_id', 'id');
    }
    
    public function toEntity()
    {
        $album = (new ActivityAlbumImageEntity())
                  ->setId($this->id)
                  ->setCreatorType($this->creator_type)
                  ->setImageUrl($this->image_url)
                  ->setStatus($this->status)
                  ->setCreatedAt($this->created_at->format('Y-m-d H:i:s'));
        
        if ($this->relationLoaded('activity')) {
            $album->setActivity($this->activity->toEntity());
        } else {
            $album->setActivity((new ActivityEntity())->setId($this->activity_id));
        }
        
        if ($this->relationLoaded('creator')) {
            $album->setCreator($this->creator->toEntity());
        } else {
            $album->setCreator((new UserEntity())->setId($this->creator_id));
        }
        
        return $album;
    }
}
