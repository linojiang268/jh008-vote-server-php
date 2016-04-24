<?php

namespace Jihe\Repositories;

use Jihe\Contracts\Repositories\ActivityCheckInQRCodeRepository as ActivityCheckInQRCodeRepositoryContract;
use Jihe\Models\Activity;
use Jihe\Services\Qrcode\QrcodeService;
use Jihe\Utils\FileUtil;
use Jihe\Services\StorageService;
use Illuminate\Support\Facades\Log;

class ActivityCheckInQRCodeRepository implements ActivityCheckInQRCodeRepositoryContract
{

    private $qrcodeService;
    private $storageService;

    public function __construct(QrcodeService $qrcodeService, StorageService $storageService)
    {
        $this->qrcodeService = $qrcodeService;
        $this->storageService = $storageService;
    }

    public function all($activityId)
    {
        $activity = Activity::find(intval($activityId));
        if ($activity) {
            return $activity->checkInQRCode()->orderBy('step', 'ASC')->get()->toArray();
        }
        return [];
    }

    private function generateCheckInURL($activityId, $step)
    {
        return url("/wap/checkin/detail?activity_id={$activityId}&step={$step}&ver=1");
    }

    public function createQRCodes($activityId, $howMany, $size)
    {
        $activity = Activity::find(intval($activityId));
        if ($activity) {
            $howMany = min($howMany, 10); // we create at most 10 check-in QRCodes for an activity

            $qrcodes = [];
            for ($step = 1; $step <= $howMany; $step++) {
                $file = FileUtil::makeTempFileName('png');
                $this->qrcodeService->generate($this->generateCheckInURL($activityId, $step), [
                    'size' => $size,
                    //'logo' => base_path("public/static/images/qrcode/$step.png"),
                    'logo' => base_path("public/static/images/qrcode/0.png"),
                    'padding' => 10,
                    'save_as' => $file,
                    'save_as_format' => 'png',
                ]);

                // store the logo file
                $logo_url = $this->storageService->storeAsImage($file);

                $qrcodes[] = ['activity_id' => $activityId, 'step' => $step, 'url' => $logo_url];
                try {
                    unlink($file);
                } catch (\Exception $e) {
                    Log::warning($e);
                }
            }

            //remove old qrcodes
            $willDelete = $activity->checkInQRCode()->get();
            foreach ($willDelete as $item) {
                $this->storageService->remove($item->url);
            }
            $activity->checkInQRCode()->delete();

            //create new qrcodes
            $qrcodeModels = $activity->checkInQRCode()->createMany($qrcodes);
            $qrcodesAdded = [];
            foreach ($qrcodeModels as $qrcodeModel) {
                $qrcode = $qrcodeModel->toArray();
                $qrcodesAdded[] = $qrcode;
            }
            return $qrcodesAdded;
        }

        return [];
    }
}
