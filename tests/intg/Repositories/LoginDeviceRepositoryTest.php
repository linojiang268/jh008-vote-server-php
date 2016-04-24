<?php
namespace intg\Jihe\Repositories;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use intg\Jihe\TestCase;

class LoginDeviceRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    //===============================================
    //          addOrUpdateIdentifierIfExists
    //===============================================
    public function testAddOrUpdateIdentifierIfExists_NotExists()
    {
        $mobile = '13800138000';
        $source = 1;
        $identifier = str_random(48);
        $oldLoginIdentifier = $this->getRepository()
             ->addOrUpdateIdentifierIfExists($mobile, $source, $identifier);

        self::assertNull($oldLoginIdentifier);
        
        $this->seeInDatabase('login_devices', [
            'mobile'            => $mobile,
            'source'            => $source,
            'identifier'        => $identifier,
            'old_identifier'    => '',
        ]);
    }

    public function testAddOrUpdateIdentifierIfExists_ExistsAndIDChanged()
    {
        $mobile = '13800138000';
        $source = 1;
        $identifier = str_random(48);

        \Jihe\Models\LoginDevice::create([
            'mobile'        => $mobile,
            'source'        => $source,
            'identifier'    => $identifier,
        ]);

        $newIdentifier = str_random(48);

        $this->getRepository()
             ->addOrUpdateIdentifierIfExists(
                $mobile, $source, $newIdentifier);

        $this->seeInDatabase('login_devices', [
            'mobile'            => $mobile,
            'source'            => $source,
            'identifier'        => $newIdentifier,
            'old_identifier'    => $identifier,
        ]);
    }

    public function testAddOrUpdateIdentifierIfExists_ExistsAndIDNotChanged()
    {
        $mobile = '13800138000';
        $source = 1;
        $identifier = str_random(48);

        \Jihe\Models\LoginDevice::create([
            'mobile'        => $mobile,
            'source'        => $source,
            'identifier'    => $identifier,
        ]);

        $oldLoginIdentifier = $this->getRepository()
             ->addOrUpdateIdentifierIfExists(
                $mobile, $source, $identifier);

        self::assertNull($oldLoginIdentifier);

        $this->seeInDatabase('login_devices', [
            'mobile'        => $mobile,
            'source'        => $source,
            'identifier'    => $identifier,
        ]);
    }

    //===============================================
    //          findOneByMobileAndSource
    //===============================================
    public function testFindOneByMobileAndSource()
    {
        $mobile = '13800138000';
        $source = 1;
        $identifier = str_random(48);
        \Jihe\Models\LoginDevice::create([
            'mobile'        => $mobile,
            'source'        => $source,
            'identifier'    => $identifier,
        ]);
        
        $loginDevice = $this->getRepository()
            ->findOneByMobileAndSource($mobile, $source);

        self::assertInstanceOf(\Jihe\Entities\LoginDevice::class, $loginDevice);
        self::assertEquals($mobile, $loginDevice->getMobile());   
        self::assertEquals($source, $loginDevice->getSource());   
        self::assertEquals($identifier, $loginDevice->getIdentifier());   
    }

    //===============================================
    //          findClientIdentifier
    //===============================================
    public function testFindClientIdentifier()
    {
        $identifier1 = str_random(48);
        \Jihe\Models\LoginDevice::create([
            'mobile'        => '13800138000',
            'source'        => 1,
            'identifier'    => $identifier1,
        ]);

        $identifier2 = str_random(48);
        \Jihe\Models\LoginDevice::create([
            'mobile'        => '13800138000',
            'source'        => 2,
            'identifier'    => $identifier2,
        ]);

        $identifier3 = str_random(48);
        \Jihe\Models\LoginDevice::create([
            'mobile'        => '13800138001',
            'source'        => 1,
            'identifier'    => $identifier3,
        ]);

        $identifiers = $this->getRepository()->findClientIdentifiers([
            '13800138000', '13800138001'
        ]);
        self::assertCount(2, $identifiers);
        self::assertEquals($identifier1, $identifiers['13800138000']);
        self::assertEquals($identifier3, $identifiers['13800138001']);
    }

    /**
     * @return \Jihe\Contracts\Repositories\LoginDeviceRepository
     */
    private function getRepository()
    {
        return $this->app[\Jihe\Contracts\Repositories\LoginDeviceRepository::class];
    }
}
