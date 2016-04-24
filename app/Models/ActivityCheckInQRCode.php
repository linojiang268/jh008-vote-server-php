<?php
namespace Jihe\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityCheckInQRCode extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'activity_check_in_qrcodes';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['activity_id', 'step', 'url'];
    

    public function activity()
    {
        return $this->belongsTo(\Jihe\Models\Activity::class, 'activity_id', 'id');
    }
}
