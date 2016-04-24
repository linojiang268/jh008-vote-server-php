<?php
namespace intg\Jihe\Repositories;

use intg\Jihe\TestCase;
use \PHPUnit_Framework_Assert as Assert;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Jihe\Entities\Banner as BannerEntity;
use Jihe\Entities\City as CityEntity;

class BannerRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    //=======================================
    //                 Add
    //=======================================
    public function testAdd()
    {
        $city = (new CityEntity())
                ->setId(1)
                ->setName('城市');

        $bannerWithCity = (new BannerEntity())
            ->setCity($city)->setMemo('banner1')
            ->setImageUrl('http://domain.com')
            ->setType(BannerEntity::TYPE_URL)
            ->setAttributes(['url' => 'http://domain.com/abc.html'])
            ->setBeginTime(date('Y-m-d H:i:s', strtotime('240 seconds')))
            ->setEndTime(date('Y-m-d H:i:s', strtotime('1200 seconds')));

        $bannerWithoutCity = (new BannerEntity())
            ->setCity(null)->setMemo('banner2')
            ->setImageUrl('http://domain.com')
            ->setType(BannerEntity::TYPE_URL)
            ->setAttributes(null)
            ->setBeginTime(date('Y-m-d H:i:s', strtotime('240 seconds')))
            ->setEndTime(date('Y-m-d H:i:s', strtotime('1200 seconds')));

        Assert::assertNotFalse($this->getRepository()->add($bannerWithCity));
        Assert::assertNotFalse($this->getRepository()->add($bannerWithoutCity));

        $this->seeInDatabase('banners', [
            'city_id' => 1,
            'memo'    => 'banner1',
        ]);

        $this->notSeeInDatabase('banners', [
            'city_id' => 1,
            'memo'    => 'banner1',
            'attributes' => null,
        ]);
        
        $this->seeInDatabase('banners', [
            'city_id'    => null,
            'memo'       => 'banner2',
            'attributes' => null,
        ]);
    }
    
    //=======================================
    //             FindBanners
    //=======================================
    public function testFindBanners()
    {

        factory(\Jihe\Models\Banner::class)->create([
            'id'         => 1,
            'city_id'    => null,
            'begin_time' => '2015-08-10 00:00:00',
            'end_time'   => '2015-08-20 00:00:00',
        ]);

        factory(\Jihe\Models\Banner::class)->create([
            'id'         => 2,
            'city_id'    => 1,
            'begin_time' => '2015-08-10 00:00:00',
            'end_time'   => '2015-08-20 00:00:00',
        ]);

        factory(\Jihe\Models\Banner::class)->create([
            'id'         => 3,
            'city_id'    => 2,
            'begin_time' => '2015-08-10 00:00:00',
            'end_time'   => '2015-08-20 00:00:00',
        ]);

        factory(\Jihe\Models\Banner::class)->create([
            'id'         => 4,
            'city_id'    => 1,
            'begin_time' => '2015-08-1 00:00:00',
            'end_time'   => '2015-08-14 00:00:00',
        ]);

        factory(\Jihe\Models\Banner::class)->create([
            'id'         => 5,
            'city_id'    => 1,
            'begin_time' => '2015-08-16 00:00:00',
            'end_time'   => '2015-08-20 00:00:00',
        ]);

        list($total_num, $banners) = $this->getRepository()->findBanners();
        Assert::assertEquals(5, $total_num);
        Assert::assertCount(5, $banners);

        list($total_num, $banners) = $this->getRepository()->findBanners(null, date('Y-m-d H:i:s', strtotime('2015-08-15 00:00:00')));
        Assert::assertEquals(3, $total_num);
        Assert::assertCount(3, $banners);

        list($total_num, $banners) = $this->getRepository()->findBanners(1, date('Y-m-d H:i:s', strtotime('2015-08-15 00:00:00')));
        Assert::assertEquals(2, $total_num);
        Assert::assertCount(2, $banners);
    }
    
    /**
     * @return \Jihe\Contracts\Repositories\BannerRepository
     */
    private function getRepository()
    {
        return $this->app[\Jihe\Contracts\Repositories\BannerRepository::class];
    }
}
