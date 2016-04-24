<?php
namespace Jihe\Http\Controllers\Api;

use Illuminate\Http\Request;
use Jihe\Entities\Banner;
use Jihe\Http\Controllers\Controller;
use Jihe\Services\BannerService;
use Jihe\Services\CityService;
use Validator;

class BannerController extends Controller
{
    /**
     * get list of teams
     */
    public function listBanners(Request $request, CityService $cityService, BannerService $bannerService)
    {
        $this->validate($request, [
            'city' => 'required|integer',
        ], [
            'city.required' => '城市未填写',
            'city.integer'  => '城市格式错误',
        ]);
        
        try {
            $city = $cityService->getCity($request->input('city'));
            if (is_null($city)) {
                throw new \Exception('城市不存在');
            }

            list($total, $banners) = $bannerService->listEffectiveBanners($city);
            
            return $this->json([
                'banners' => array_map([$this, 'morphToBannerArray'], $banners),
            ]);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }
    
    private function morphToBannerArray(Banner $banner)
    {
        return [
            'id'         => $banner->getId(),
            'image_url'  => $banner->getImageUrlOfThumbnail(),
            'type'       => $banner->getType(),
            'attributes' => $banner->getAttributes() ?: null,
        ];
    }
}