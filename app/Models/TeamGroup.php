<?php
namespace Jihe\Models;

use Illuminate\Database\Eloquent\Model;
use Jihe\Entities\TeamGroup as TeamGroupEntity;
use Jihe\Entities\Team as TeamEntity;

class TeamGroup extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'team_groups';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'team_id',
        'name'
    ];

    public function team()
    {
        return $this->belongsTo(\Jihe\Models\Team::class, 'team_id', 'id');
    }

    public function toEntity()
    {
        $group = (new TeamGroupEntity)
                  ->setId($this->id)
                  ->setName($this->name);

        if (array_key_exists('team', $this->relations)) {
            $group->setTeam($this->team->toEntity());
        } else {
            $group->setTeam((new TeamEntity)->setId($this->team_id));
        }

        return $group;
    }
}
