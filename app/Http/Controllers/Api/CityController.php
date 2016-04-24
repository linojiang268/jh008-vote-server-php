<?php
namespace Jihe\Http\Controllers\Api;

use Jihe\Http\Controllers\Controller;
use Jihe\Services\CityService;
use Jihe\Entities\City as CityEntity;

class CityController extends Controller
{
    /**
     * get cities
     */
    public function getCities(CityService $cityService)
    {
        //get available cities
        $cities = $cityService->getAvailableCities();
        
        return $this->json(['cities' => $this->morphToPlainArray($cities)]);
    }

    private function morphToPlainArray(array $cities = [])
    {
        if (empty($cities)) {
            return [];
        }

        return array_map(function (CityEntity $city) {
            return [
                'id'   => $city->getId(),
                'name' => $city->getName(),
            ];
        }, $cities);
    }
}