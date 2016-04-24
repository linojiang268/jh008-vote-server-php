<?php

namespace Jihe\Services;

use Jihe\Contracts\Repositories\ActivityCheckInQRCodeRepository;
use Jihe\Services\ActivityService;
use Jihe\Services\Archiver\ZipArchiver;
use Jihe\Utils\FileUtil;
use Auth;

class ActivityCheckInQRCodeService {

    private $activityCheckInQRCodeRepository;
    private $activityService;
    private $zipArchiver;

    public function __construct(ActivityCheckInQRCodeRepository $activityCheckInQRCodeRepository,
            ActivityService $activityService, ZipArchiver $zipArchiver) {
        $this->activityCheckInQRCodeRepository = $activityCheckInQRCodeRepository;
        $this->activityService = $activityService;
        $this->zipArchiver = $zipArchiver;
    }

    public function createManyQRCodes($creatorId, $activityId, $howMany, $size = 600) {
        if(!$this->activityService->checkActivityOwner($activityId, $creatorId)){
            throw new \Exception("你无权进行此操作");
        }

        $qrcodes = $this->activityCheckInQRCodeRepository->createQRCodes($activityId, $howMany, $size);
        return $qrcodes;
    }

    public function getQRCodes($activityId) {
        $qrcodes = $this->activityCheckInQRCodeRepository->all($activityId);
        return $qrcodes;
    }

    public function compressQRCodes($activityId) {
        $qrcodes = $this->activityCheckInQRCodeRepository->all($activityId);
        if (!empty($qrcodes)) {
            $files = [];
            foreach ($qrcodes as $qrcode) {
                $files[$qrcode['step'] . '.png'] = $qrcode['url'];
            }
            $fileCompressed = FileUtil::makeTempFileName('zip');
            $this->zipArchiver->compress($fileCompressed, $files);
            return $fileCompressed;
        } else {
            throw new \Exception("无二维码可供下载");
        }
    }
}
