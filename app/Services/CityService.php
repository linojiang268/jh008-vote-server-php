<?php
namespace Jihe\Services;

use Jihe\Contracts\Repositories\CityRepository;

class CityService
{
    /**
     * @var \Jihe\Contracts\Repositories\CityRepository
     */
    private $cityRepository;
    
    public function __construct(CityRepository $cityRepository) 
    {
        $this->cityRepository = $cityRepository;
    }
    
    /**
     * get array of available cities
     * 
     * @return array  array of \Jihe\Entities\City
     */
    public function getAvailableCities()
    {
        return $this->cityRepository->findAvailableCities();
    }
    
    /**
     * get detail of city by its id
     * 
     * @param int $city                id of the city
     *
     * @return \Jihe\Entities\City|null
     */
    public function getCity($city)
    {
        return $this->cityRepository->findCity($city);
    }
}
