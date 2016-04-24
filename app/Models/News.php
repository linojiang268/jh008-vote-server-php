<?php
namespace Jihe\Models;

use Illuminate\Database\Eloquent\Model;
use Jihe\Entities\News as NewsEntity;
use Jihe\Entities\User as UserEntity;
use Jihe\Entities\Activity as ActivityEntity;
use Jihe\Entities\Team as TeamEntity;
use Illuminate\Database\Eloquent\SoftDeletes;

class News extends Model
{
    use SoftDeletes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'news';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'content',
        'cover_url',
        'activity_id',
        'publisher_id',
        'team_id',
        'click_num',
    ];

    public function publisher()
    {
        return $this->belongsTo(\Jihe\Models\User::class, 'publisher_id', 'id');
    }

    public function activity()
    {
        return $this->belongsTo(\Jihe\Models\Activity::class, 'activity_id', 'id');
    }

    public function team()
    {
        return $this->belongsTo(\Jihe\Models\Team::class, 'team_id', 'id');
    }

    /**
     * convert the model to entity
     *
     * @return \Jihe\Entities\News
     */
    public function toEntity()
    {
        $news = (new NewsEntity())
            ->setId($this->id)
            ->setTitle($this->title)
            ->setContent($this->content)
            ->setCoverUrl($this->cover_url)
            ->setClickNum($this->click_num)
            ->setPublishTime($this->created_at);

        if ($this->relationLoaded('publisher')) {
            $news->setPublisher($this->publisher->toEntity());
        } else {
            $news->setPublisher((new UserEntity())->setId($this->publisher_id));
        }

        if ($this->relationLoaded('activity')) {
            $news->setActivity($this->activity->toEntity());
        } else {
            $news->setActivity((new ActivityEntity)->setId($this->activity_id));
        }

        if ($this->relationLoaded('team')) {
            $news->setTeam($this->team->toEntity());
        } else {
            $news->setTeam((new TeamEntity())->setId($this->team_id));
        }
        return $news;
    }
}
