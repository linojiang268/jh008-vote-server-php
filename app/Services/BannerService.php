<?php
namespace Jihe\Services;

use Jihe\Contracts\Repositories\BannerRepository;
use Jihe\Entities\Banner;
use Jihe\Entities\City;

class BannerService
{
    /**
     * \Jihe\Contracts\Repositories\BannerRepository
     */
    private $banners;

    public function __construct(BannerRepository $banners)
    {
        $this->banners = $banners;
    }

    /**
     * @param City $city
     * @param      $imageUrl     image of banner
     * @param      $options      options of banner, keys taken:
     *                            - memo           memo of banner
     *                            - begin_time     datetime that banner begin to effective
     *                            - end_time       after datetime that banner is not effective
     *                            - type           (string)type given banner relate to,
     *                                              - url
     *                                              - team
     *                                              - activity
     *                            - attributes     (array)attributes given banner relate to, keys taken,
     *                                              - url          morph when type is url
     *                                              - team_id      morph when type is team
     *                                              - activity_id  morph when type is activity
     * @return \Jihe\Entities\Banner
     */
    public function add(City $city, $imageUrl, $options = [])
    {
        $banner = new Banner();
        $banner->setCity($city);
        $banner->getImageUrl(array_get($options, 'image_url'));
        $banner->setMemo(array_get($options, 'memo'));
        $banner->setType(array_get($options, 'type'));
        $banner->setAttributes(array_get($options, 'attributes'));
        $banner->setBeginTime(array_get($options, 'begin_time'));
        $banner->setEndTime(array_get($options, 'end_time'));
        return $this->banners->add($banner);
    }

    /**
     * list banners of given city
     *
     * @param City|null $city   entity of city
     */
    public function listEffectiveBanners(City $city = null)
    {
        return $this->banners->findBanners($city->getId(), date('Y-m-d H:i:s'));
    }

    /**
     * list all banners of given city
     *
     * @param City|null $city   entity of city
     */
    public function listBanners(City $city = null)
    {
        return $this->banners->findBanners($city->getId());
    }
}