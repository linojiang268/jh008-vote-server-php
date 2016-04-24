<?php
namespace Jihe\Repositories;

use Jihe\Contracts\Repositories\CityRepository as CityRepositoryContract;
use Jihe\Entities\City as CityEntity;
use Jihe\Models\City;

class CityRepository implements CityRepositoryContract
{
    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\CityRepository::findAvailableCities()
     */
    public function findAvailableCities()
    {
        $cities = City::get()->all();
        
        return array_map(function (City $city) {
            return $city->toEntity();
        }, $cities);
    }
    
    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\CityRepository::findCity()
     */
    public function findCity($city)
    {
        /* @var $cityModel \Jihe\Models\City */
        $cityModel = City::where('id', $city)->first();
        
        if (null == $cityModel) {
            return null;
        }
        
        return $cityModel->toEntity();
    }
}
