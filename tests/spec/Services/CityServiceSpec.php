<?php
namespace spec\Jihe\Services;

use PhpSpec\Laravel\LaravelObjectBehavior;
use Prophecy\Argument;

use Jihe\Contracts\Repositories\CityRepository;
use Jihe\Entities\City;
use Jihe\Contracts\Repositories\TeamRepository;

class CityServiceSpec extends LaravelObjectBehavior
{
    function let(CityRepository $cityRepository)
    {
        $this->beAnInstanceOf(\Jihe\Services\CityService::class, [$cityRepository]);
    }
    
    //======================================
    //          getAvailableCities
    //======================================
    function it_get_available_cities_successfully_if_cities_not_exists(
                        CityRepository $cityRepository)
    {
        $cityRepository->findAvailableCities()
                       ->willReturn([]);
        
        $result = $this->getAvailableCities();
        $result->shouldBe([]);
    }
    
    function it_get_available_cities_successfully_if_cities_exists(
            CityRepository $cityRepository)
    {
        $cities = [];
        
        $city1 = new City();
        $city1->setId(1)
              ->setName('成都');
        
        $city2 = new City();
        $city2->setId(2)
              ->setName('北京');
        
        array_push($cities, $city1, $city2);
        
        $cityRepository->findAvailableCities()
                       ->willReturn($cities);
    
        $result = $this->getAvailableCities();
        $result->shouldBe($cities);
    }
    
    //======================================
    //              getCity
    //======================================
    function it_get_city_successfully_if_city_not_exists(
            CityRepository $cityRepository)
    {
        $cityRepository->findCity(1)
                       ->willReturn(null);
    
        $result = $this->getCity(1);
        $result->shouldBe(null);
    }
    
    function it_get_city_successfully_if_city_exists(
            CityRepository $cityRepository)
    {
        $city = new City();
        $city->setId(1)
             ->setName('成都');
    
        $cityRepository->findCity(1)
                       ->willReturn($city);
    
        $result = $this->getCity(1);
        $result->shouldBe($city);
    }
}
