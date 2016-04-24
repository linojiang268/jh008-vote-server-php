<?php
namespace Jihe\Models;

use Illuminate\Database\Eloquent\Model;
use Jihe\Entities\LoginDevice as LoginDeviceEntity;

class LoginDevice extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'login_devices';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'mobile',
        'source',
        'identifier',
        'old_identifier',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];

    public function toEntity()
    {
        return (new LoginDeviceEntity())
            ->setId($this->id)
            ->setMobile($this->mobile)
            ->setSource($this->source)
            ->setIdentifier($this->identifier)
            ->setOldIdentifier($this->old_identifier);
    }
}
