<?php
namespace Jihe\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Jihe\Entities\Team as TeamEntity;
use Jihe\Entities\User as UserEntity;
use Jihe\Entities\City as CityEntity;

class YoungSingle extends Model
{
    const STATUS_PENDING = 0;
    const STATUS_APPROVED = 1;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'young_singles';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
                            'order_id',
                            'name',
                            'id_number',
                            'gender',
                            'date_of_birth',
                            'height',
                            'graduate_university',
                            'degree',
                            'yearly_salary',
                            'work_unit',
                            'mobile',
                            'cover_url',
                            'images_url',
                            'talent',
                            'mate_choice',
                            'status',
                          ];
}
