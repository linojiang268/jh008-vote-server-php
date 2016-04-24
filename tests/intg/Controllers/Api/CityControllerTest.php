<?php
namespace intg\Jihe\Controllers\Api;

use Illuminate\Foundation\Testing\DatabaseTransactions;

use intg\Jihe\TestCase;


class CityControllerTest extends TestCase
{
    use DatabaseTransactions;
    
    //=========================================
    //               getCities
    //=========================================
    public function testSuccessfulGetCities()
    {
        factory(\Jihe\Models\City::class)->create([
                'id' => 1,
        ]);
        
        $this->startSession();
        
        $this->get('/api/city/list')
             ->seeJsonContains([ 'code' => 0 ]);
        
        $result = json_decode($this->response->getContent());
        self::assertObjectHasAttribute('cities', $result);
        
        $city = $result->cities[0];
        self::assertObjectHasAttribute('id', $city);
        self::assertObjectHasAttribute('name', $city);
    }
}
