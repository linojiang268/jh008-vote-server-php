<?php
namespace Jihe\Repositories;

use Jihe\Contracts\Repositories\LoginDeviceRepository as LoginDeviceRepositoryContract;
use Jihe\Models\LoginDevice;
use Jihe\Entities\LoginDevice as LoginDeviceEntity;

class LoginDeviceRepository implements LoginDeviceRepositoryContract
{
    /**
     * (non-PHPdoc)
     *
     * @see \Jihe\Contracts\Repositories\LoginDeviceRepository::addOrUpdateIfExists
     */
    public function addOrUpdateIdentifierIfExists($mobile, $source, $identifier)
    {
        $loginDevice = LoginDevice::where('mobile', $mobile)
                            ->where('source', $source)
                            ->get()
                            ->first();
        if ($loginDevice) {
            $loginDevice->old_identifier = $loginDevice->identifier;
            $loginDevice->identifier = $identifier;
            $loginDevice->save();
        } else {
            LoginDevice::create([
                'mobile'    => $mobile,
                'source'    => $source,
                'identifier' => $identifier,
            ]);
        }

        return null;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \Jihe\Contracts\Repositories\LoginDeviceRepository::findOneByMobileAndSource()
     */
    public function findOneByMobileAndSource($mobile, $source)
    {
        $loginDevice = LoginDevice::where('mobile', $mobile)
                            ->where('source', $source)
                            ->get()
                            ->first();

        return $this->convertToEntity($loginDevice);
    }

    /**
     * (non-PHPdoc)
     *
     * @see \Jihe\Contracts\Repositories\LoginDeviceRepository::findClientIdentifier()
     */
    public function findClientIdentifiers($mobiles)
    {
        $identifiers = [];
        $mobiles = (array) $mobiles;
        if (empty($mobiles)) {
            return $identifiers;
        }
        LoginDevice::whereIn('mobile', $mobiles)
                   ->where('source', LoginDeviceEntity::SOURCE_CLIENT)
                   ->get()->each(function ($item, $key) use (&$identifiers) {
                        $identifiers[$item->mobile] = $item->identifier;
                   });

        return $identifiers;
    }

    /**
     * convert model to entity
     *
     * @param \Jihe\Models\LoginDevice|null
     *
     * @return \Jihe\Entities\LoginDevice|null
     */
    public function convertToEntity($loginDevice)
    {
        return $loginDevice ? $loginDevice->toEntity() : null;
    }
}
