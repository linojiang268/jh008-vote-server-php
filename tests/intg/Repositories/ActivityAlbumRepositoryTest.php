<?php
namespace intg\Jihe\Repositories;

use intg\Jihe\TestCase;
use \PHPUnit_Framework_Assert as Assert;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Jihe\Entities\ActivityAlbumImage as ActivityAlbumImageEntity;
use Jihe\Entities\User as UserEntity;
use Jihe\Entities\Activity as ActivityEntity;

class ActivityAlbumRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    //=======================================
    //               Add Image
    //=======================================
    public function testAddImage()
    {
        self::assertAlbumImage(null, 1, 1, 1, 'http://domain/default/activity1.png', 0,
            $this->getRepository()->addImage(
                (new ActivityAlbumImageEntity())
                    ->setActivity((new ActivityEntity())->setId(1))
                    ->setCreatorType(ActivityAlbumImageEntity::USER)
                    ->setCreator((new UserEntity())->setId(1))
                    ->setImageUrl('http://domain/default/activity1.png')
                    ->setStatus(ActivityAlbumImageEntity::STATUS_PENDING)
        ));
        
        $this->seeInDatabase('activity_album_images', [
            'activity_id'  => 1,
            'creator_type' => ActivityAlbumImageEntity::USER,
            'creator_id'   => 1,
            'image_url'    => 'http://domain/default/activity1.png',
            'status'       => ActivityAlbumImageEntity::STATUS_PENDING,
        ]);
    }
    
    private static function assertAlbumImage($expectedId, $expectedActivityId, 
                                 $expectedCreatorType, $expectedCreatorId, 
                                 $expectedImageUrl, $expectedStatus, 
                                 ActivityAlbumImageEntity $album)
    {
        return null != $album && (!$expectedId || $expectedId == $album->getId()) && 
                    $expectedActivityId  == $album->getActivity()->getId() && 
                    $expectedCreatorType == $album->getCreatorType() && 
                    $expectedCreatorId   == $album->getCreator()->getId() && 
                    $expectedImageUrl    == $album->getImageUrl() && 
                    $expectedStatus      == $album->getStatus();
    }
    
    //=======================================
    //              Find Imagess
    //=======================================
    public function testFindImages()
    {
        factory(\Jihe\Models\ActivityAlbumImage::class)->create([
            'id' => 1,
            'activity_id'  => 1,
            'creator_type' => ActivityAlbumImageEntity::SPONSOR,
            'creator_id'   => 1,
            'status'       => ActivityAlbumImageEntity::STATUS_APPROVED,
            'created_at'   => '1990-10-01 00:00:00',
        ]);
        factory(\Jihe\Models\ActivityAlbumImage::class)->create([
            'id' => 2,
            'activity_id'  => 1,
            'creator_type' => ActivityAlbumImageEntity::SPONSOR,
            'creator_id'   => 1,
            'status'       => ActivityAlbumImageEntity::STATUS_APPROVED,
            'created_at'   => '2015-10-01 00:00:00',
        ]);
        factory(\Jihe\Models\ActivityAlbumImage::class)->create([
            'id' => 3,
            'activity_id'  => 2,
            'creator_type' => ActivityAlbumImageEntity::SPONSOR,
            'creator_id'   => 1,
            'status'       => ActivityAlbumImageEntity::STATUS_APPROVED,
        ]);
        factory(\Jihe\Models\ActivityAlbumImage::class)->create([
            'id' => 4,
            'activity_id'  => 1,
            'creator_type' => ActivityAlbumImageEntity::USER,
            'creator_id'   => 1,
            'status'       => ActivityAlbumImageEntity::STATUS_PENDING,
            'created_at'   => '1990-10-01 00:00:00',
        ]);
        factory(\Jihe\Models\ActivityAlbumImage::class)->create([
            'id' => 5,
            'activity_id'  => 1,
            'creator_type' => ActivityAlbumImageEntity::USER,
            'creator_id'   => 2,
            'status'       => ActivityAlbumImageEntity::STATUS_PENDING,
            'created_at'   => '2015-10-01 00:00:00',
        ]);
        factory(\Jihe\Models\ActivityAlbumImage::class)->create([
            'id' => 6,
            'activity_id'  => 1,
            'creator_type' => ActivityAlbumImageEntity::USER,
            'creator_id'   => 1,
            'status'       => ActivityAlbumImageEntity::STATUS_APPROVED,
        ]);
        
        // 2 albums meet the specification
        list($pages, $images) = $this->getRepository()->findImages(1, 1, 2, ActivityAlbumImageEntity::SPONSOR);
        Assert::assertEquals(1, $pages);
        Assert::assertCount(2, $images);
        
        // 1 albums meet the specification
        list($pages, $images) = $this->getRepository()->findImages(1, 1, 2, ActivityAlbumImageEntity::SPONSOR, null, null, '2015-10-01 00:00:00', 2);
        Assert::assertEquals(1, $pages);
        Assert::assertCount(1, $images);
        
        // 1 albums meet the specification
        list($pages, $images) = $this->getRepository()->findImages(1, 1, 2, ActivityAlbumImageEntity::USER, null, ActivityAlbumImageEntity::STATUS_PENDING);
        Assert::assertEquals(1, $pages);
        Assert::assertCount(2, $images);
        
        // 1 albums meet the specification
        list($pages, $images) = $this->getRepository()->findImages(1, 1, 2, ActivityAlbumImageEntity::USER, null, ActivityAlbumImageEntity::STATUS_PENDING, '1990-10-01 00:00:00', 1);
        Assert::assertEquals(1, $pages);
        Assert::assertCount(1, $images);
        
        // 1 albums meet the specification
        list($pages, $images) = $this->getRepository()->findImages(1, 1, 2, ActivityAlbumImageEntity::USER, null, ActivityAlbumImageEntity::STATUS_APPROVED);
        Assert::assertEquals(1, $pages);
        Assert::assertCount(1, $images);
        
        // first page
        list($pages, $images) = $this->getRepository()->findImages(1, 1, 1, ActivityAlbumImageEntity::SPONSOR);
        Assert::assertEquals(2, $pages);
        Assert::assertCount(1, $images);
        
        // second page
        list($pages, $images) = $this->getRepository()->findImages(1, 2, 1, ActivityAlbumImageEntity::SPONSOR);
        Assert::assertEquals(2, $pages);
        Assert::assertCount(1, $images);
    }
    
    //=======================================
    //      updateImageStatusToApproved
    //=======================================
    public function testUpdateImageStatusToApproved_OneImage()
    {
        factory(\Jihe\Models\ActivityAlbumImage::class)->create([
            'id'           => 1,
            'activity_id'  => 1,
            'status'       => ActivityAlbumImageEntity::STATUS_PENDING,
        ]);
        
        factory(\Jihe\Models\ActivityAlbumImage::class)->create([
            'id'           => 2,
            'activity_id'  => 1,
            'status'       => ActivityAlbumImageEntity::STATUS_APPROVED,
        ]);
        
        factory(\Jihe\Models\ActivityAlbumImage::class)->create([
            'id'           => 3,
            'activity_id'  => 2,
            'status'       => ActivityAlbumImageEntity::STATUS_PENDING,
        ]);
    
        Assert::assertTrue($this->getRepository()->updateImageStatusToApproved(1, [1]));
        Assert::assertFalse($this->getRepository()->updateImageStatusToApproved(1, [2]));
        Assert::assertFalse($this->getRepository()->updateImageStatusToApproved(1, [3]));
    
        $this->seeInDatabase('activity_album_images', [
            'id'           => 1,
            'activity_id'  => 1,
            'status'       => ActivityAlbumImageEntity::STATUS_APPROVED,   // status changed
        ]);
        
        $this->seeInDatabase('activity_album_images', [
            'id'           => 2,
            'activity_id'  => 1,
            'status'       => ActivityAlbumImageEntity::STATUS_APPROVED,   // status not changed
        ]);
        
        $this->seeInDatabase('activity_album_images', [                        // record not changed
            'id'           => 3,
            'activity_id'  => 2,
            'status'       => ActivityAlbumImageEntity::STATUS_PENDING,
        ]);
    }
    
    public function testUpdateImageStatusToApproved_MultiImages()
    {
        factory(\Jihe\Models\ActivityAlbumImage::class)->create([
            'id'           => 1,
            'activity_id'  => 1,
            'status'       => ActivityAlbumImageEntity::STATUS_PENDING,
        ]);
    
        factory(\Jihe\Models\ActivityAlbumImage::class)->create([
            'id'           => 2,
            'activity_id'  => 1,
            'status'       => ActivityAlbumImageEntity::STATUS_PENDING,
        ]);
    
        Assert::assertTrue($this->getRepository()->updateImageStatusToApproved(1, [1, 2]));
        
        $this->seeInDatabase('activity_album_images', [
            'id'           => 1,
            'activity_id'  => 1,
            'status'       => ActivityAlbumImageEntity::STATUS_APPROVED,   // status changed
        ]);
        
        $this->seeInDatabase('activity_album_images', [
            'id'           => 2,
            'activity_id'  => 1,
            'status'       => ActivityAlbumImageEntity::STATUS_APPROVED,   // status changed
        ]);
    }
    
    //=======================================
    //              Remove Images
    //=======================================
    public function testRemoveImages_OneImage()
    {
        factory(\Jihe\Models\ActivityAlbumImage::class)->create([
            'id'           => 1,
            'activity_id'  => 1,
            'status'       => ActivityAlbumImageEntity::STATUS_PENDING,
            'deleted_at'   => null,
        ]);
        
        factory(\Jihe\Models\ActivityAlbumImage::class)->create([
            'id'           => 2,
            'activity_id'  => 1,
            'status'       => ActivityAlbumImageEntity::STATUS_APPROVED,
            'deleted_at'   => null,
        ]);
        
        factory(\Jihe\Models\ActivityAlbumImage::class)->create([
            'id'           => 3,
            'activity_id'  => 2,
            'status'       => ActivityAlbumImageEntity::STATUS_PENDING,
            'deleted_at'   => null,
        ]);
    
        Assert::assertTrue($this->getRepository()->removeImages(1, [1]));
        Assert::assertTrue($this->getRepository()->removeImages(1, [2]));
        Assert::assertFalse($this->getRepository()->removeImages(1, [3]));
    
        $this->notSeeInDatabase('activity_album_images', [
            'id'           => 1,
            'activity_id'  => 1,
            'status'       => ActivityAlbumImageEntity::STATUS_PENDING,
            'deleted_at'   => null,
        ]);
        
        $this->notSeeInDatabase('activity_album_images', [
            'id'           => 2,
            'activity_id'  => 1,
            'status'       => ActivityAlbumImageEntity::STATUS_APPROVED,
            'deleted_at'   => null,
        ]);
        
        $this->seeInDatabase('activity_album_images', [
            'id'           => 3,
            'activity_id'  => 2,
            'status'       => ActivityAlbumImageEntity::STATUS_PENDING,
        ]);
    }

    public function testRemoveImages_OneImageAndSpecialCreator()
    {
        factory(\Jihe\Models\ActivityAlbumImage::class)->create([
            'id'           => 1,
            'activity_id'  => 1,
            'creator_id'   => 1,
            'creator_type' => ActivityAlbumImageEntity::USER,
            'status'       => ActivityAlbumImageEntity::STATUS_PENDING,
            'deleted_at'   => null,
        ]);

        factory(\Jihe\Models\ActivityAlbumImage::class)->create([
            'id'           => 2,
            'activity_id'  => 1,
            'creator_id'   => 1,
            'creator_type' => ActivityAlbumImageEntity::USER,
            'status'       => ActivityAlbumImageEntity::STATUS_APPROVED,
            'deleted_at'   => null,
        ]);

        factory(\Jihe\Models\ActivityAlbumImage::class)->create([
            'id'           => 3,
            'activity_id'  => 1,
            'creator_id'   => 2,
            'creator_type' => ActivityAlbumImageEntity::USER,
            'status'       => ActivityAlbumImageEntity::STATUS_PENDING,
            'deleted_at'   => null,
        ]);

        factory(\Jihe\Models\ActivityAlbumImage::class)->create([
            'id'           => 4,
            'activity_id'  => 1,
            'creator_id'   => 1,
            'creator_type' => ActivityAlbumImageEntity::SPONSOR,
            'status'       => ActivityAlbumImageEntity::STATUS_PENDING,
            'deleted_at'   => null,
        ]);

        Assert::assertTrue($this->getRepository()->removeImages(1, [1], 1));
        Assert::assertTrue($this->getRepository()->removeImages(1, [2], 1));
        Assert::assertFalse($this->getRepository()->removeImages(1, [3], 1));
        Assert::assertFalse($this->getRepository()->removeImages(1, [4], 1));

        $this->notSeeInDatabase('activity_album_images', [
            'id'           => 1,
            'deleted_at'   => null,
        ]);

        $this->notSeeInDatabase('activity_album_images', [
            'id'           => 2,
            'deleted_at'   => null,
        ]);

        $this->seeInDatabase('activity_album_images', [
            'id'           => 3,
        ]);

        $this->seeInDatabase('activity_album_images', [
            'id'           => 4,
        ]);
    }
    
    public function testRemoveImages_MultiImages()
    {
        factory(\Jihe\Models\ActivityAlbumImage::class)->create([
            'id'           => 1,
            'activity_id'  => 1,
            'status'       => ActivityAlbumImageEntity::STATUS_PENDING,
            'deleted_at'   => null,
        ]);
    
        factory(\Jihe\Models\ActivityAlbumImage::class)->create([
            'id'           => 2,
            'activity_id'  => 1,
            'status'       => ActivityAlbumImageEntity::STATUS_APPROVED,
            'deleted_at'   => null,
        ]);
    
        Assert::assertTrue($this->getRepository()->removeImages(1, [1, 2]));
        
        $this->notSeeInDatabase('activity_album_images', [
            'id'           => 1,
            'activity_id'  => 1,
            'status'       => ActivityAlbumImageEntity::STATUS_PENDING,
            'deleted_at'   => null,
        ]);
        
        $this->notSeeInDatabase('activity_album_images', [
            'id'           => 2,
            'activity_id'  => 1,
            'status'       => ActivityAlbumImageEntity::STATUS_APPROVED,
            'deleted_at'   => null,
        ]);
    }
    
    //=======================================
    //          Stat Pending Images
    //=======================================
    public function testStatPendingImages()
    {
        factory(\Jihe\Models\Activity::class)->create([
            'id' => 1,
            'status'  => ActivityEntity::STATUS_PUBLISHED,
        ]);
        factory(\Jihe\Models\Activity::class)->create([
            'id' => 2,
            'status'  => ActivityEntity::STATUS_PUBLISHED,
        ]);
        factory(\Jihe\Models\Activity::class)->create([
            'id' => 3,
            'status'  => ActivityEntity::STATUS_PUBLISHED,
        ]);
        factory(\Jihe\Models\Activity::class)->create([
            'id' => 4,
            'status'  => ActivityEntity::STATUS_PUBLISHED,
        ]);
        factory(\Jihe\Models\Activity::class)->create([
            'id' => 5,
            'status'  => ActivityEntity::STATUS_NOT_PUBLISHED,
        ]);
        
        factory(\Jihe\Models\ActivityAlbumImage::class)->create([
            'id' => 1,
            'activity_id'  => 1,
            'creator_type' => ActivityAlbumImageEntity::SPONSOR,
            'creator_id'   => 1,
            'status'       => ActivityAlbumImageEntity::STATUS_APPROVED,
            'created_at'   => '1990-10-01 00:00:00',
        ]);
        factory(\Jihe\Models\ActivityAlbumImage::class)->create([
            'id' => 2,
            'activity_id'  => 2,
            'creator_type' => ActivityAlbumImageEntity::USER,
            'creator_id'   => 1,
            'status'       => ActivityAlbumImageEntity::STATUS_PENDING,
            'created_at'   => '2015-10-01 00:00:00',
        ]);
        factory(\Jihe\Models\ActivityAlbumImage::class)->create([
            'id' => 3,
            'activity_id'  => 3,
            'creator_type' => ActivityAlbumImageEntity::USER,
            'creator_id'   => 1,
            'status'       => ActivityAlbumImageEntity::STATUS_PENDING,
        ]);
        factory(\Jihe\Models\ActivityAlbumImage::class)->create([
            'id' => 4,
            'activity_id'  => 4,
            'creator_type' => ActivityAlbumImageEntity::USER,
            'creator_id'   => 1,
            'status'       => ActivityAlbumImageEntity::STATUS_PENDING,
            'created_at'   => '1990-10-01 00:00:00',
        ]);
        factory(\Jihe\Models\ActivityAlbumImage::class)->create([
            'id' => 5,
            'activity_id'  => 5,
            'creator_type' => ActivityAlbumImageEntity::USER,
            'creator_id'   => 1,
            'status'       => ActivityAlbumImageEntity::STATUS_APPROVED,
            'created_at'   => '1990-10-01 00:00:00',
        ]);
        
        $stats = $this->getRepository()->statPendingImages();
        self::assertCount(3, $stats);
        foreach ($stats as $stat) {
            self::assertArrayHasKey('pending_images', $stat);
            self::assertArrayHasKey('activity', $stat);
            $activity = $stat['activity'];
            self::assertArrayHasKey('id', $activity);
            self::assertArrayHasKey('title', $activity);
            self::assertArrayHasKey('telephone', $activity);
            self::assertTrue(in_array($activity['id'], [2, 3, 4]));
        }
    }
    
    //=======================================
    //          Count Album Images
    //=======================================
    public function testCountAlbumImages()
    {
        factory(\Jihe\Models\Team::class)->create([
            'id' => 1,
        ]);
        
        factory(\Jihe\Models\Activity::class)->create([
            'id' => 1,
            'team_id' => 1,
            'status'  => ActivityEntity::STATUS_PUBLISHED,
        ]);
        factory(\Jihe\Models\Activity::class)->create([
            'id' => 2,
            'team_id' => 1,
            'status'  => ActivityEntity::STATUS_NOT_PUBLISHED,
        ]);
        factory(\Jihe\Models\Activity::class)->create([
            'id' => 3,
            'team_id' => 2,
            'status'  => ActivityEntity::STATUS_PUBLISHED,
        ]);
    
        factory(\Jihe\Models\ActivityAlbumImage::class)->create([
            'id' => 1,
            'activity_id'  => 1,
            'creator_type' => ActivityAlbumImageEntity::SPONSOR,
            'creator_id'   => 1,
            'status'       => ActivityAlbumImageEntity::STATUS_APPROVED,
            'created_at'   => '1990-10-01 00:00:00',
        ]);
        factory(\Jihe\Models\ActivityAlbumImage::class)->create([
            'id' => 2,
            'activity_id'  => 1,
            'creator_type' => ActivityAlbumImageEntity::USER,
            'creator_id'   => 1,
            'status'       => ActivityAlbumImageEntity::STATUS_PENDING,
            'created_at'   => '2015-10-01 00:00:00',
        ]);
        factory(\Jihe\Models\ActivityAlbumImage::class)->create([
            'id' => 3,
            'activity_id'  => 2,
            'creator_type' => ActivityAlbumImageEntity::USER,
            'creator_id'   => 1,
            'status'       => ActivityAlbumImageEntity::STATUS_PENDING,
        ]);
        factory(\Jihe\Models\ActivityAlbumImage::class)->create([
            'id' => 4,
            'activity_id'  => 3,
            'creator_type' => ActivityAlbumImageEntity::USER,
            'creator_id'   => 1,
            'status'       => ActivityAlbumImageEntity::STATUS_PENDING,
            'created_at'   => '1990-10-01 00:00:00',
        ]);
        
        $countOfAll = $this->getRepository()->countAlbumImages();
        self::assertEquals(3, $countOfAll);
        
        $countOfTeam = $this->getRepository()->countAlbumImages([
            'team'   => 1,
            'status' => ActivityAlbumImageEntity::STATUS_APPROVED,
        ]);
        
        self::assertEquals(1, $countOfTeam);
        
        $countOfActivity = $this->getRepository()->countAlbumImages([
            'activity' => 1,
            'status'   => ActivityAlbumImageEntity::STATUS_APPROVED,
        ]);
        
        self::assertEquals(1, $countOfActivity);
    }

    //=======================================
    //        countActivityImages
    //=======================================
    public function testCountActivityImages()
    {
        factory(\Jihe\Models\Team::class)->create([
            'id' => 1,
        ]);

        factory(\Jihe\Models\Activity::class)->create([
            'id' => 1,
            'team_id' => 1,
            'status'  => ActivityEntity::STATUS_PUBLISHED,
        ]);
        factory(\Jihe\Models\Activity::class)->create([
            'id' => 2,
            'team_id' => 1,
            'status'  => ActivityEntity::STATUS_PUBLISHED,
        ]);
        factory(\Jihe\Models\Activity::class)->create([
            'id' => 3,
            'team_id' => 2,
            'status'  => ActivityEntity::STATUS_PUBLISHED,
        ]);

        factory(\Jihe\Models\ActivityAlbumImage::class)->create([
            'id' => 1,
            'activity_id'  => 1,
            'creator_type' => ActivityAlbumImageEntity::SPONSOR,
            'creator_id'   => 1,
            'status'       => ActivityAlbumImageEntity::STATUS_APPROVED,
            'created_at'   => '1990-10-01 00:00:00',
        ]);
        factory(\Jihe\Models\ActivityAlbumImage::class)->create([
            'id' => 11,
            'activity_id'  => 1,
            'creator_type' => ActivityAlbumImageEntity::USER,
            'creator_id'   => 1,
            'status'       => ActivityAlbumImageEntity::STATUS_APPROVED,
            'created_at'   => '1990-10-01 00:00:00',
        ]);
        factory(\Jihe\Models\ActivityAlbumImage::class)->create([
            'id' => 2,
            'activity_id'  => 1,
            'creator_type' => ActivityAlbumImageEntity::USER,
            'creator_id'   => 1,
            'status'       => ActivityAlbumImageEntity::STATUS_APPROVED,
            'created_at'   => '2015-10-01 00:00:00',
        ]);
        factory(\Jihe\Models\ActivityAlbumImage::class)->create([
            'id' => 3,
            'activity_id'  => 2,
            'creator_type' => ActivityAlbumImageEntity::USER,
            'creator_id'   => 1,
            'status'       => ActivityAlbumImageEntity::STATUS_APPROVED,
        ]);
        factory(\Jihe\Models\ActivityAlbumImage::class)->create([
            'id' => 4,
            'activity_id'  => 3,
            'creator_type' => ActivityAlbumImageEntity::USER,
            'creator_id'   => 1,
            'status'       => ActivityAlbumImageEntity::STATUS_APPROVED,
            'created_at'   => '1990-10-01 00:00:00',
        ]);
        $user = $this->getRepository()->countActivityImages([1, 2, 3], ActivityAlbumImageEntity::USER, ActivityAlbumImageEntity::STATUS_APPROVED);
        $sponsor = $this->getRepository()->countActivityImages([1, 2, 3], ActivityAlbumImageEntity::SPONSOR, ActivityAlbumImageEntity::STATUS_APPROVED);
        self::assertEquals(2, $user[1]);
        self::assertEquals(1, $user[2]);
        self::assertEquals(1, $user[3]);
        self::assertEquals(1, $sponsor[1]);
    }

    /**
     * @return \Jihe\Contracts\Repositories\ActivityAlbumRepository
     */
    private function getRepository()
    {
        return $this->app[\Jihe\Contracts\Repositories\ActivityAlbumRepository::class];
    }
}
