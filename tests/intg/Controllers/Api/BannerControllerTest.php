<?php
namespace intg\Jihe\Controllers\Api;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use intg\Jihe\TestCase;
use intg\Jihe\RequestSignCheck;
use intg\Jihe\AuthDeviceCheck;
use Jihe\Entities\Banner as BannerEntity;

class BannerControllerTest extends TestCase
{
    use DatabaseTransactions;
    use RequestSignCheck;
    use AuthDeviceCheck;
    
    // =========================================
    //               listBanners
    // =========================================
    public function testSuccessfulListBanners()
    {
        $user = factory(\Jihe\Models\User::class)->create([ 
            'id' => 1 
        ]);
        factory(\Jihe\Models\City::class)->create([ 
            'id' => 1 
        ]);

        factory(\Jihe\Models\Banner::class)->create([
            'id'         => 1,
            'city_id'    => null,
            'type'       => BannerEntity::TYPE_URL,
            'attributes' => json_encode(['url' => 'http://domain.com']),
            'begin_time' => date('Y-m-d H:i:s', strtotime('-1500 seconds')),
            'end_time'   => date('Y-m-d H:i:s', strtotime('-1400 seconds')),
            'created_at' => '2015-08-02 00:00:00',
        ]);

        factory(\Jihe\Models\Banner::class)->create([
            'id'         => 2,
            'city_id'    => null,
            'type'       => BannerEntity::TYPE_URL,
            'attributes' => json_encode(['url' => 'http://domain.com']),
            'begin_time' => date('Y-m-d H:i:s', strtotime('-1200 seconds')),
            'end_time'   => date('Y-m-d H:i:s', strtotime('1200 seconds')),
            'created_at' => '2015-08-04 00:00:00',
        ]);

        factory(\Jihe\Models\Banner::class)->create([
            'id'         => 6,
            'city_id'    => null,
            'type'       => BannerEntity::TYPE_URL,
            'attributes' => null,
            'begin_time' => date('Y-m-d H:i:s', strtotime('-1200 seconds')),
            'end_time'   => date('Y-m-d H:i:s', strtotime('1200 seconds')),
            'created_at' => '2015-08-04 12:00:00',
        ]);

        factory(\Jihe\Models\Banner::class)->create([
            'id'         => 3,
            'city_id'    => 1,
            'type'       => BannerEntity::TYPE_TEAM,
            'attributes' => json_encode(['team_id' => 2]),
            'begin_time' => date('Y-m-d H:i:s', strtotime('-1200 seconds')),
            'end_time'   => date('Y-m-d H:i:s', strtotime('1200 seconds')),
            'created_at' => '2015-08-05 00:00:00',
        ]);

        factory(\Jihe\Models\Banner::class)->create([
            'id'         => 4,
            'city_id'    => 1,
            'type'       => BannerEntity::TYPE_ACTIVITY,
            'attributes' => json_encode(['activity_id' => 5]),
            'begin_time' => date('Y-m-d H:i:s', strtotime('-1200 seconds')),
            'end_time'   => date('Y-m-d H:i:s', strtotime('1200 seconds')),
            'created_at' => '2015-08-03 00:00:00',
        ]);

        factory(\Jihe\Models\Banner::class)->create([
            'id'         => 5,
            'city_id'    => 1,
            'type'       => BannerEntity::TYPE_URL,
            'attributes' => json_encode(['url' => 'http://domain.com']),
            'begin_time' => date('Y-m-d H:i:s', strtotime('1500 seconds')),
            'end_time'   => date('Y-m-d H:i:s', strtotime('1700 seconds')),
            'created_at' => '2015-08-01 00:00:00',
        ]);
        
        $this->startSession();
        
        $this->actingAs($user)->ajaxGet("/api/banner/list?city=1");
        $this->seeJsonContains([ 
            'code' => 0 
        ]);

        $result = json_decode($this->response->getContent(), true);
        
        $this->assertArrayHasKey('banners', $result);
        $banners = $result['banners'];
        $this->assertCount(4, $banners);

        $this->assertBannerEquals(3, 'team', ['team_id' => 2], $banners[0]);
        $this->assertBannerEquals(6, 'url', null, $banners[1]);
        $this->assertBannerEquals(2, 'url', ['url' => 'http://domain.com'], $banners[2]);
        $this->assertBannerEquals(4, 'activity', ['activity_id' => 5], $banners[3]);
    }

    private function assertBannerEquals($expectedId, $expectedType,
                                        array $expectedAttributes = null, $banner)
    {
        $this->assertEquals($expectedId, $banner['id']);
        $this->assertArrayHasKey('image_url', $banner);
        $this->assertEquals($expectedType, $banner['type']);
        if ($expectedAttributes === null) {
            $this->assertNull($banner['attributes']);
        } else {
            $attributes = $banner['attributes'];
            $this->assertCount(count($expectedAttributes), $attributes);
            foreach ($expectedAttributes as $key => $value) {
                $this->assertArrayHasKey($key, $attributes);
                $this->assertEquals($value, $attributes[$key]);
            }
        }
    }

    public function testSuccessfulListBanners_NoBanners()
    {
        $user = factory(\Jihe\Models\User::class)->create([
            'id' => 1
        ]);
        factory(\Jihe\Models\City::class)->create([
            'id' => 1
        ]);

        factory(\Jihe\Models\Banner::class)->create([
            'id'         => 1,
            'city_id'    => null,
            'type'       => BannerEntity::TYPE_URL,
            'attributes' => json_encode(['url' => 'http://domain.com']),
            'begin_time' => date('Y-m-d H:i:s', strtotime('-1500 seconds')),
            'end_time'   => date('Y-m-d H:i:s', strtotime('-1400 seconds')),
            'created_at' => '2015-08-02 00:00:00',
        ]);

        factory(\Jihe\Models\Banner::class)->create([
            'id'         => 5,
            'city_id'    => 1,
            'type'       => BannerEntity::TYPE_URL,
            'attributes' => json_encode(['url' => 'http://domain.com']),
            'begin_time' => date('Y-m-d H:i:s', strtotime('1500 seconds')),
            'end_time'   => date('Y-m-d H:i:s', strtotime('1700 seconds')),
            'created_at' => '2015-08-01 00:00:00',
        ]);

        $this->startSession();

        $this->actingAs($user)->ajaxGet("/api/banner/list?city=1");
        $this->seeJsonContains([
            'code' => 0
        ]);

        $result = json_decode($this->response->getContent(), true);

        $this->assertArrayHasKey('banners', $result);
        $this->assertEquals([], $result['banners']);
    }
}
