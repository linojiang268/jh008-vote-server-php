<?php
namespace Jihe\Models;

use Illuminate\Database\Eloquent\Model;

class TeamMemberEnrollmentPermission extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'team_member_enrollment_permissions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [ 'team_id', 'mobile', 'name', 'memo' ,'status' ];

    public function team()
    {
        return $this->belongsTo(\Jihe\Models\Team::class, 'team_id', 'id');
    }

    public function toEntity()
    {
        $permission = new \Jihe\Entities\TeamMemberEnrollmentPermission();
        $permission->setId($this->id)
                  ->setMemo($this->memo)
                  ->setStatus($this->status)
                  ->setMobile($this->mobile)
                  ->setName($this->name);

        if ($this->relationLoaded('team')) {
            $permission->setTeam($this->team->toEntity());
        } else {
            $permission->setTeam((new \Jihe\Entities\Team)->setId($this->team_id));
        }

        return $permission;
    }
}
