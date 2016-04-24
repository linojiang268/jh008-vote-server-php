<?php
namespace intg\Jihe\Repositories;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use intg\Jihe\TestCase;
use Jihe\Models\City;

class CityRepositoryTest extends TestCase
{
    use DatabaseTransactions;
    
    //===========================================
    //            findAvailableCities
    //===========================================
    public function testFindAvailableCities_CitiesNotExists()
    {
        self::assertEmpty($this->getRepository()->findAvailableCities());
    }
    
    public function testFindAvailableCities_OneCityExists()
    {
        factory(City::class)->create([
                'id'   => 1,
                'name' => '成都',
        ]);
        
        self::assertNotEmpty($this->getRepository()->findAvailableCities());
    }
    
    public function testFindAvailableCities_MultiCitiesExists()
    {
        factory(City::class)->create([
                'id'   => 1,
                'name' => '成都',
        ]);
        
        factory(City::class)->create([
                'id'   => 2,
                'name' => '北京',
        ]);
        
        $cities = $this->getRepository()->findAvailableCities();
        self::assertCount(2, $cities);
        self::assertCityEquals(1, '成都', $cities[0]);
        self::assertCityEquals(2, '北京', $cities[1]);
    }
    
    private static function assertCityEquals($expectedId, $expectedName, $city)
    {
        self::assertNotNull($city);
        self::assertEquals($expectedId, $city->getId());
        self::assertEquals($expectedName, $city->getName());
    }
    
    //===========================================
    //                 findCity
    //===========================================
    public function testFindCity_CityExists()
    {
        factory(City::class)->create([
                'id'   => 1,
                'name' => '成都',
        ]);
        
        $city = $this->getRepository()->findCity(1);
        
        self::assertCityEquals(1, '成都', $city);
    }
    
    public function testFindCity_CityNotExists()
    {
        self::assertNull($this->getRepository()->findCity(2));
    }
    
    /**
     * @return \Jihe\Contracts\Repositories\CityRepository
     */
    private function getRepository()
    {
        return $this->app[\Jihe\Contracts\Repositories\CityRepository::class];
    }
}
