<?php
namespace Jihe\Repositories;

use Jihe\Contracts\Repositories\BannerRepository as BannerRepositoryContract;
use Jihe\Contracts\Repositories\id;use Jihe\Entities\Banner as BannerEntity;
use Jihe\Models\Banner;

class BannerRepository implements BannerRepositoryContract
{
    /**
     * @inheritDoc
     */
    public function add(BannerEntity $banner)
    {
        return Banner::create($this->morphToAttributes($banner))->toEntity();
    }

    private function morphToAttributes(BannerEntity $banner)
    {
        return [
            'city_id'    => is_null($banner->getCity()) ? null : $banner->getCity()->getId(),
            'image_url'  => $banner->getImageUrl(),
            'type'       => $banner->getType(),
            'attributes' => $banner->getAttributes() ? json_encode($banner->getAttributes()) : null,
            'memo'       => $banner->getMemo(),
            'begin_time' => $banner->getBeginTime(),
            'end_time'   => $banner->getEndTime(),
        ];
    }

    /**
     * @inheritDoc
     */
    public function findBanners($city = null, $time = null)
    {
        $query = Banner::orderBy('created_at', 'desc')
                       ->orderBy('id', 'desc');

        if (!is_null($city)) {
            $query->where(function ($query) use ($city) {
                $query->orWhere('city_id', $city)
                      ->orWhereNull('city_id');
            });
        }

        if (!is_null($time)) {
            $query->where('begin_time', '<', $time)
                  ->where('end_time', '>', $time);
        }

        $total = $query->getCountForPagination()->count();

        return [
            $total,
            array_map(function (Banner $banner) {
                return $banner->toEntity();
            }, $query->get()->all())];
    }
}
