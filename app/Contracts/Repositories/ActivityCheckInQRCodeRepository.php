<?php

namespace Jihe\Contracts\Repositories;

interface ActivityCheckInQRCodeRepository {

    /**
     * @param int $activityId
     * @return array all qr codes
     */
    public function all($activityId);

    /**
     * @param int $activityId
     * @param int $howMany   how many qr codes should be generated
     * @param int $size   size of qr code image
     * @return array|false all qrcodes created
     */
    public function createQRCodes($activityId, $howMany, $size);
}
