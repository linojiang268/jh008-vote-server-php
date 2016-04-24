<?php
namespace intg\Jihe\Repositories;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use intg\Jihe\TestCase;

class VerificationRepositoryTest extends TestCase
{
    use DatabaseTransactions;
    
    //=========================================
    //            count
    //=========================================
    public function testCount_Simple()
    {
        factory(\Jihe\Models\Verification::class)->create([
            'mobile'     => '13800138000',
            'created_at' => '2015-06-12 23:10:24', // no exceed 'now'
        ]);
    
        self::assertEquals(1, $this->getRepository()
                                   ->count('13800138000', '2015-06-12 23:10:24'),
                          'there should be one code since given time');
    }
    
    public function testCount_NoVerification()
    {
        self::assertEquals(0, $this->getRepository()
                                   ->count('13800138000'),
                          'there should be no code');
    }
    
    public function testCount_NoUnused()
    {
        factory(\Jihe\Models\Verification::class)->create([
            'mobile'     => '13800138000',
            'created_at' => '2015-06-12 23:10:24', // no exceed 'now'
            'deleted_at' => '2015-06-12 23:11:24'  // used 1 min after its creation
        ]);
        self::assertEquals(0, $this->getRepository()
                                   ->count('13800138000', '2015-06-12 23:08:24', false),
                          'there should be no unused code');
    }
    
    public function testCount_WithUnused()
    {
        factory(\Jihe\Models\Verification::class)->create([
            'mobile'     => '13800138000',
            'created_at' => '2015-06-12 23:10:24', // no exceed 'now'
            'deleted_at' => '2015-06-12 23:11:24'  // used 1 min after its creation
        ]);
        self::assertEquals(1, $this->getRepository()
                                   ->count('13800138000', '2015-06-12 23:08:24'),
                          'there should be one unused code');
    }
    
    //=========================================
    //            findLastRequested
    //=========================================
    public function testFindLastRequested_OneUnexpiredVerification()
    {
        factory(\Jihe\Models\Verification::class)->create([
            'mobile'     => '13800138000',
            'code'       => '1234',
            'expired_at' => '2015-06-17 23:10:24',
        ]);
        
        $verification = $this->getRepository()->findLastRequested('13800138000',
                                                                 '2015-06-17 23:08:24');
        self::assertNotNull($verification);
        self::assertEquals('1234', $verification->getCode());
        self::assertFalse($verification->isExpired('2015-06-17 23:08:24'));
    }
    
    public function testFindLastRequested_OneUnexpiredAndTwoExpiredVerifications()
    {
        factory(\Jihe\Models\Verification::class)->create([ // unexpired
                'mobile'     => '13800138000',
                'code'       => '2854',
                'expired_at' => '2015-06-17 23:10:24',
        ]);
        factory(\Jihe\Models\Verification::class)->create([ // expired
            'mobile'     => '13800138000',
            'code'       => '5830',
            'expired_at' => '2015-06-17 23:06:24',
        ]);
        factory(\Jihe\Models\Verification::class)->create([ // expired
            'mobile'     => '13800138000',
            'code'       => '0836',
            'expired_at' => '2015-06-17 23:08:14',
        ]);
    
        $verification = $this->getRepository()->findLastRequested('13800138000',
                                                                 '2015-06-17 23:08:24');
        self::assertNotNull($verification);
        self::assertEquals('2854', $verification->getCode());
        self::assertFalse($verification->isExpired('2015-06-17 23:08:24'));
    }
    
    public function testFindLastRequested_NoUnexpiredVerifications()
    {
        factory(\Jihe\Models\Verification::class)->create([ // expired
            'mobile'     => '13800138000',
            'code'       => '2854',
            'expired_at' => '2015-06-17 23:06:24',
        ]);
        factory(\Jihe\Models\Verification::class)->create([ // expired
            'mobile'     => '13800138000',
            'code'       => '5830',
            'expired_at' => '2015-06-17 23:05:24',
        ]);
        factory(\Jihe\Models\Verification::class)->create([ // expired
            'mobile'     => '13800138000',
            'code'       => '0836',
            'expired_at' => '2015-06-17 23:08:14',
        ]);
    
        self::assertNull($this->getRepository()->findLastRequested('13800138000',
                                                                  '2015-06-17 23:08:24'));
    }
    
    public function testFindLastRequested_WithDeletedVerification()
    {
        factory(\Jihe\Models\Verification::class)->create([ // unexpired, but deleted
            'mobile'      => '13800138000',
            'code'        => '2854',
            'expired_at'  => '2015-06-17 23:16:24',
            'deleted_at'  => '2015-06-17 23:10:24'
        ]);
        
        self::assertNull($this->getRepository()->findLastRequested('13800138000',
                                                                  '2015-06-17 23:12:24'));
    }
    
    public function testFindLastRequested_WithDeletedAndUnexpiredVerification()
    {
        factory(\Jihe\Models\Verification::class)->create([ // unexpired, but deleted
            'mobile'      => '13800138000',
            'code'        => '2854',
            'expired_at'  => '2015-06-17 23:16:24',
            'deleted_at'  => '2015-06-17 23:10:24'
        ]);
        factory(\Jihe\Models\Verification::class)->create([ // unexpired
            'mobile'      => '13800138000',
            'code'        => '7652',
            'expired_at'  => '2015-06-17 23:16:24',
        ]);
    
        $verification = $this->getRepository()->findLastRequested('13800138000',
                                                                 '2015-06-17 23:12:24');
        self::assertNotNull($verification);
        self::assertEquals('7652', $verification->getCode());
        self::assertFalse($verification->isExpired('2015-06-17 23:12:24'));
    }

    public function testFindLastRequested_WithDeletedAndMultiUnexpiredVerifications()
    {
        factory(\Jihe\Models\Verification::class)->create([ // unexpired, but deleted
            'mobile'      => '13800138000',
            'code'        => '2854',
            'expired_at'  => '2015-06-17 23:16:24',
            'deleted_at'  => '2015-06-17 23:10:24'
        ]);
        factory(\Jihe\Models\Verification::class)->create([ // unexpired
            'mobile'      => '13800138000',
            'code'        => '7652',
            'expired_at'  => '2015-06-17 23:16:24',
        ]);
        factory(\Jihe\Models\Verification::class)->create([ // unexpired, this one is newer
            'mobile'      => '13800138000',
            'code'        => '0853',
            'expired_at'  => '2015-06-17 23:18:24',
        ]);
    
        $verification = $this->getRepository()->findLastRequested('13800138000',
                                                                 '2015-06-17 23:12:24');
        self::assertNotNull($verification);
        self::assertEquals('0853', $verification->getCode());
        self::assertFalse($verification->isExpired('2015-06-17 23:12:24'));
    }

    //=========================================
    //            removeExpiredBefore
    //=========================================
    public function testRemoveExpiredBefore()
    {
        factory(\Jihe\Models\Verification::class)->create([ // expired before given time
            'mobile'      => '13800138000',
            'code'        => '2854',
            'expired_at'  => '2015-06-17 23:10:24',
            'deleted_at'  => '2015-06-17 23:10:34'
        ]);
        factory(\Jihe\Models\Verification::class)->create([ // expired before given time
            'mobile'      => '13800138000',
            'code'        => '7652',
            'expired_at'  => '2015-06-17 23:11:24',
        ]);
        factory(\Jihe\Models\Verification::class)->create([ // not expired before given time
            'mobile'      => '13800138000',
            'code'        => '0853',
            'expired_at'  => '2015-06-17 23:18:24',
        ]);
    
        $this->getRepository()->removeExpiredBefore('2015-06-17 23:12:24');
        
        // check the database
        $verifications = \Jihe\Models\Verification::all()->toArray();
        self::assertEquals(1, count($verifications));
        self::assertEquals('0853', $verifications[0]['code']);
    }
    
    /**
     * @return \Jihe\Contracts\Repositories\VerificationRepository
     */
    private function getRepository()
    {
        return $this->app[\Jihe\Contracts\Repositories\VerificationRepository::class];
    }
}
