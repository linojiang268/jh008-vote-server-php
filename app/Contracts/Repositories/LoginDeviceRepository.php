<?php
namespace Jihe\Contracts\Repositories;

interface LoginDeviceRepository
{
    /**
     * Insert a user device map record, if record matched mobile and source aready
     * exists, then update identifer
     *
     * @param string $mobile        user mobile
     * @param integer $source       user login source
     * @param string $identifier    48 bit string, unique identify a device
     *
     * @return void
     */
    public function addOrUpdateIdentifierIfExists($mobile, $source, $identifier);

    /**
     * Find a login device by mobile and source
     *
     * @param string $mobile        user mobile
     * @param integer $source       user login source
     *
     * @return \Jihe\Entities\LoginDevice|null
     */
    public function findOneByMobileAndSource($mobile, $source);

    /**
     * Get push alias for client
     *
     * @param string|array $mobiles     user mobiles
     *
     * @return array                    key is mobile, value is identifier
     */
    public function findClientIdentifiers($mobiles);
}
