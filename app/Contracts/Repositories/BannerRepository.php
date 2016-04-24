<?php
namespace Jihe\Contracts\Repositories;

interface BannerRepository
{
    /**
     * add banner
     * 
     * @param \Jihe\Entities\Banner $banner
     * @return \Jihe\Entities\Banner
     */
    public function add(\Jihe\Entities\Banner $banner);

    /**
     * get banners of given city
     *
     * @param $city    id of city, or null, effentive at the given city
     * @param $time    datetime or null, effective at the given time
     * @return array   array of  \Jihe\Entities\Banner
     */
    public function findBanners($city = null, $time = null);
}
