<?php
namespace intg\Jihe\Repositories;

use intg\Jihe\TestCase;
use \PHPUnit_Framework_Assert as Assert;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Jihe\Entities\ActivityFile as ActivityFileEntity;
use Jihe\Entities\Activity as ActivityEntity;

class ActivityFileRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    //=======================================
    //                 Add
    //=======================================
    public function testAdd()
    {
        self::assertNotNull($this->getRepository()->add(
            (new ActivityFileEntity())
                ->setActivity((new ActivityFileEntity())->setId(101))
                ->setName('activity_file.doc')
                ->setSize(1024)
                ->setExtension('doc')
                ->setUrl('http://domain/default/activity_file.doc')
        ));

        $this->seeInDatabase('activity_files', [
            'activity_id'  => 101,
            'name'         => 'activity_file.doc',
            'size'         => 1024,
            'extension'    => 'doc',
            'url'          => 'http://domain/default/activity_file.doc',
        ]);
    }
    
    //=======================================
    //                Find
    //=======================================
    public function testFind()
    {
        factory(\Jihe\Models\ActivityFile::class)->create(
            [
                'id'           => 10,
                'activity_id'  => 101,
                'name'         => 'activity_file.doc',
                'memo'         => null,
                'size'         => 1024,
                'extension'    => 'doc',
                'url'          => 'http://domain/default/activity_file.doc',
            ]
        );

        factory(\Jihe\Models\ActivityFile::class)->create(
            [
                'id'           => 11,
                'activity_id'  => 101,
                'name'         => 'activity_file.doc',
                'memo'         => null,
                'size'         => 1024,
                'url'          => 'http://domain/default/activity_file.doc',
            ]
        );

        factory(\Jihe\Models\ActivityFile::class)->create(
            [
                'id'           => 12,
                'activity_id'  => 102,
                'name'         => 'activity_file.doc',
                'memo'         => null,
                'size'         => 1024,
                'extension'    => 'doc',
                'url'          => 'http://domain/default/activity_file.doc',
            ]
        );

        // 0 files meet the specification
        list($total, $files) = $this->getRepository()->find(100, 1, 1);
        Assert::assertEquals(0, $total);
        Assert::assertCount(0, $files);

        // 1 files meet the specification
        list($total, $files) = $this->getRepository()->find(102, 1, 10);
        Assert::assertEquals(1, $total);
        Assert::assertCount(1, $files);

        // 2 files meet the specification
        list($total, $files) = $this->getRepository()->find(101, 1, 10);
        Assert::assertEquals(2, $total);
        Assert::assertCount(2, $files);

        // first page
        list($total, $files) = $this->getRepository()->find(101, 1, 1);
        Assert::assertEquals(2, $total);
        Assert::assertCount(1, $files);

        // second page
        list($total, $files) = $this->getRepository()->find(101, 2, 1);
        Assert::assertEquals(2, $total);
        Assert::assertCount(1, $files);
    }

    //=======================================
    //                Remove
    //=======================================
    public function testRemoveImages_OneImage()
    {
        factory(\Jihe\Models\ActivityFile::class)->create(
            [
                'id'           => 11,
                'activity_id'  => 101,
                'name'         => 'activity_file.doc',
                'memo'         => null,
                'size'         => 1024,
                'extension'    => 'doc',
                'url'          => 'http://domain/default/activity_file.doc',
            ]
        );

        factory(\Jihe\Models\ActivityFile::class)->create(
            [
                'id'           => 12,
                'activity_id'  => 101,
                'name'         => 'activity_file.doc',
                'memo'         => null,
                'size'         => 1024,
                'extension'    => 'doc',
                'url'          => 'http://domain/default/activity_file.doc',
            ]
        );

        factory(\Jihe\Models\ActivityFile::class)->create(
            [
                'id'           => 13,
                'activity_id'  => 101,
                'name'         => 'activity_file.doc',
                'memo'         => null,
                'size'         => 1024,
                'extension'    => 'doc',
                'url'          => 'http://domain/default/activity_file.doc',
            ]
        );

        factory(\Jihe\Models\ActivityFile::class)->create(
            [
                'id'           => 14,
                'activity_id'  => 101,
                'name'         => 'activity_file.doc',
                'memo'         => null,
                'size'         => 1024,
                'extension'    => 'doc',
                'url'          => 'http://domain/default/activity_file.doc',
            ]
        );

        factory(\Jihe\Models\ActivityFile::class)->create(
            [
                'id'           => 15,
                'activity_id'  => 102,
                'name'         => 'activity_file.doc',
                'memo'         => null,
                'size'         => 1024,
                'extension'    => 'doc',
                'url'          => 'http://domain/default/activity_file.doc',
            ]
        );
    
        Assert::assertTrue($this->getRepository()->remove(101, [11]));
        Assert::assertTrue($this->getRepository()->remove(101, [12, 13]));
        Assert::assertFalse($this->getRepository()->remove(101, [10]));
        Assert::assertFalse($this->getRepository()->remove(101, [15]));

        $this->notSeeInDatabase('activity_files', [
            'id'         => 11,
            'deleted_at' => null,
        ]);

        $this->notSeeInDatabase('activity_files', [
            'id'         => 12,
            'deleted_at' => null,
        ]);

        $this->notSeeInDatabase('activity_files', [
            'id'         => 13,
            'deleted_at' => null,
        ]);

        $this->seeInDatabase('activity_files', [
            'id'         => 14,
            'deleted_at' => null,
        ]);

        $this->seeInDatabase('activity_files', [
            'id'         => 15,
            'deleted_at' => null,
        ]);
    }

    //=======================================
    //                Count
    //=======================================
    public function testCount()
    {
        factory(\Jihe\Models\ActivityFile::class)->create(
            [
                'id'           => 10,
                'activity_id'  => 101,
                'name'         => 'activity_file.doc',
                'memo'         => null,
                'size'         => 1024,
                'extension'    => 'doc',
                'url'          => 'http://domain/default/activity_file.doc',
            ]
        );

        factory(\Jihe\Models\ActivityFile::class)->create(
            [
                'id'           => 11,
                'activity_id'  => 101,
                'name'         => 'activity_file.doc',
                'memo'         => null,
                'size'         => 1024,
                'extension'    => 'doc',
                'url'          => 'http://domain/default/activity_file.doc',
            ]
        );

        factory(\Jihe\Models\ActivityFile::class)->create(
            [
                'id'           => 12,
                'activity_id'  => 102,
                'name'         => 'activity_file.doc',
                'memo'         => null,
                'size'         => 1024,
                'extension'    => 'doc',
                'url'          => 'http://domain/default/activity_file.doc',
            ]
        );

        Assert::assertEquals(0, $this->getRepository()->count(100));
        Assert::assertEquals(2, $this->getRepository()->count(101));
        Assert::assertEquals(1, $this->getRepository()->count(102));
    }

    /**
     * @return \Jihe\Contracts\Repositories\ActivityFileRepository
     */
    private function getRepository()
    {
        return $this->app[\Jihe\Contracts\Repositories\ActivityFileRepository::class];
    }
}
