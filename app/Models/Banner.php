<?php
namespace Jihe\Models;

use Illuminate\Database\Eloquent\Model;
use Jihe\Entities\Banner as BannerEntity;
use Jihe\Entities\City as CityEntity;

class Banner extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'banners';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
                            'city_id',
                            'image_url',
                            'type',
                            'attributes',
                            'memo',
                            'begin_time',
                            'end_time',
                          ];

    public function city()
    {
        return $this->belongsTo(\Jihe\Models\City::class, 'city_id', 'id');
    }

    public function toEntity()
    {
        $banner = (new BannerEntity())
                    ->setId($this->id)
                    ->setImageUrl($this->image_url)
                    ->setType($this->type)
                    ->setAttributes(empty($this->attributes['attributes']) ? null : json_decode($this->attributes['attributes']))
                    ->setMemo($this->memo)
                    ->setBeginTime($this->begin_time)
                    ->setEndTime($this->end_time);

        if ($this->city_id) {
            if ($this->relationLoaded('city')) {
                $banner->setCity($this->city->toEntity());
            } else {
                $banner->setCity((new CityEntity())->setId($this->city_id));
            }
        }

        return $banner;
    }
}
