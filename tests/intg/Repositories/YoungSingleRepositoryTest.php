<?php
namespace intg\Jihe\Repositories;

use Carbon\Carbon;
use intg\Jihe\TestCase;
use Jihe\Models\YoungSingle;
use Jihe\Repositories\YoungSingleRepository;
use \PHPUnit_Framework_Assert as Assert;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;

class YoungSingleRepositoryTest extends TestCase
{
    use DatabaseTransactions;
    const TABLE = 'young_singles';

    //=======================================
    //                 Add
    //=======================================
    public function testAdd()
    {
        $model = factory(YoungSingle::class)->make([
            'status' => null,
        ]);

        Assert::assertTrue($this->getRepository()->add($model));

        $this->seeInDatabase(self::TABLE, [
            'name'   => $model->name,
            'status' => YoungSingle::STATUS_PENDING,
        ]);
    }

    public function testAdd_MobileExists()
    {
        factory(YoungSingle::class)->create([
            'mobile' => '13800001111',
        ]);

        $model = factory(YoungSingle::class)->make([
            'mobile' => '13800001111',
        ]);

        Assert::assertFalse($this->getRepository()->add($model));
    }

    //=======================================
    //             FindByMobile
    //=======================================
    public function testFindByMobile()
    {
        $model = factory(YoungSingle::class)->create([
            'id'     => 1,
            'mobile' => '13800001111',
        ]);

        Assert::assertEquals($model, $this->getRepository()->findByMobile('13800001111'));
        Assert::assertNull($this->getRepository()->findByMobile('13800001110'));
    }

    //=======================================
    //               Approve
    //=======================================
    public function testApprove()
    {
        Cache::shouldReceive('has')->with($this->createCacheKey(1))->andReturn(false);

        $pendingModel = factory(YoungSingle::class)->create([
            'id'     => 1,
            'mobile' => '13800001111',
            'status' => YoungSingle::STATUS_PENDING,
        ]);
        $approvedModel = factory(YoungSingle::class)->create([
            'id'     => 2,
            'mobile' => '13800001112',
            'status' => YoungSingle::STATUS_APPROVED,
        ]);
        $notExistsModel = factory(YoungSingle::class)->make([
            'id'     => 3,
            'mobile' => '13800001113',
            'status' => YoungSingle::STATUS_PENDING,
        ]);

        Assert::assertTrue($this->getRepository()->approve($pendingModel));
//        Assert::assertFalse($this->getRepository()->approve($approvedModel));
//        Assert::assertFalse($this->getRepository()->approve($notExistsModel));

        $this->seeInDatabase(self::TABLE, [
            'id'     => 1,
            'status' => YoungSingle::STATUS_APPROVED,
        ]);
    }

    public function testApprove_Multi()
    {
        Cache::shouldReceive('has')->with($this->createCacheKey(1))->andReturn(false);

        $models = [];
        for ($i = 1; $i <= 4; $i++) {
            array_push($models, factory(YoungSingle::class)->create([
                'id'     => $i,
                'mobile' => '1380000111' . $i,
                'status' => YoungSingle::STATUS_PENDING,
            ]));
        }

        Assert::assertTrue($this->getRepository()->approve($models));

        for ($i = 1; $i <= 4; $i++) {
            $this->seeInDatabase(self::TABLE, [
                'id'     => $i,
                'status' => YoungSingle::STATUS_APPROVED,
            ]);
        }
    }

    public function testApprove_WithNotifyCacheOnePage()
    {
        $itemsInCache = [
            factory(YoungSingle::class)->create([
                'id'     => 1,
                'order_id' => 1,
                'mobile' => '13800001111',
                'status' => YoungSingle::STATUS_APPROVED,
            ]),
        ];
        $expiresAt = Carbon::now()->addMinutes(1);
        Cache::put($this->createCacheKey(1), json_encode([1, $itemsInCache]), $expiresAt);

        $modelPending = factory(YoungSingle::class)->create([
            'id'     => 2,
            'mobile' => '13800001112',
            'status' => YoungSingle::STATUS_PENDING,
        ]);
        $modelNotExists = factory(YoungSingle::class)->make([
            'id'     => 3,
            'mobile' => '13800001113',
            'status' => YoungSingle::STATUS_PENDING,
        ]);

        Assert::assertTrue($this->getRepository()->approve($modelPending));
//        Assert::assertFalse($this->getRepository()->approve($itemsInCache[0]));
//        Assert::assertFalse($this->getRepository()->approve($modelNotExists));

        $this->seeInDatabase(self::TABLE, [
            'id'     => 2,
            'status' => YoungSingle::STATUS_APPROVED,
        ]);

        list($total, $items) = json_decode(Cache::get($this->createCacheKey(1)));
        Assert::assertEquals(2, $total);
        Assert::assertEquals(1, $items[0]->order_id);
        Assert::assertEquals(2, $items[1]->order_id);
    }

    public function testApprove_WithNotifyCacheTwoPage()
    {
        $itemsInCache = [];
        for ($i = 1; $i <= 9; $i++) {
            array_push($itemsInCache, factory(YoungSingle::class)->create([
                'id'     => $i,
                'order_id'     => $i,
                'mobile' => '1380000000' . $i,
                'status' => YoungSingle::STATUS_APPROVED,
            ]));
        }
        array_push($itemsInCache, factory(YoungSingle::class)->create([
            'id'     => 11,
            'order_id'     => 10,
            'mobile' => '13800000011',
            'status' => YoungSingle::STATUS_APPROVED,
        ]));
        $expiresAt = Carbon::now()->addMinutes(1);
        Cache::put($this->createCacheKey(1), json_encode([10, $itemsInCache]), $expiresAt);

        $modelPending = factory(YoungSingle::class)->create([
            'id'     => 10,
            'order_id'     => 0,
            'mobile' => '13800000010',
            'status' => YoungSingle::STATUS_PENDING,
        ]);
        $modelNotExists = factory(YoungSingle::class)->make([
            'id'     => 12,
            'order_id'     => 0,
            'mobile' => '13800000012',
            'status' => YoungSingle::STATUS_PENDING,
        ]);

        Assert::assertTrue($this->getRepository()->approve($modelPending));
//        Assert::assertFalse($this->getRepository()->approve($itemsInCache[0]));
//        Assert::assertFalse($this->getRepository()->approve($modelNotExists));

        $this->seeInDatabase(self::TABLE, [
            'id'     => 10,
            'status' => YoungSingle::STATUS_APPROVED,
        ]);

        list($total, $items) = json_decode(Cache::get($this->createCacheKey(1)));
        Assert::assertEquals(11, $total);
        for ($i = 0; $i < 10; $i++) {
            Assert::assertEquals($i + 1, $items[$i]->order_id);
        }
    }

    public function testApprove_WithNotifyCacheTwoPageAndIndexSecondPage()
    {
        $itemsInCache = [];
        for ($i = 1; $i <= 9; $i++) {
            array_push($itemsInCache, factory(YoungSingle::class)->create([
                'id'     => $i,
                'order_id' => $i,
                'mobile' => '1380000000' . $i,
                'status' => YoungSingle::STATUS_APPROVED,
            ]));
        }
        array_push($itemsInCache, factory(YoungSingle::class)->create([
            'id'     => 10,
            'order_id' => 10,
            'mobile' => '13800000010',
            'status' => YoungSingle::STATUS_APPROVED,
        ]));
        $expiresAt = Carbon::now()->addMinutes(1);
        Cache::put($this->createCacheKey(1), json_encode([10, $itemsInCache]), $expiresAt);

        $modelPending = factory(YoungSingle::class)->create([
            'id'     => 11,
            'order_id' => 0,
            'mobile' => '13800000011',
            'status' => YoungSingle::STATUS_PENDING,
        ]);
        $modelNotExists = factory(YoungSingle::class)->make([
            'id'     => 12,
            'order_id' => 0,
            'mobile' => '13800000012',
            'status' => YoungSingle::STATUS_PENDING,
        ]);

        Assert::assertTrue($this->getRepository()->approve($modelPending));
//        Assert::assertFalse($this->getRepository()->approve($itemsInCache[0]));
//        Assert::assertFalse($this->getRepository()->approve($modelNotExists));

        $this->seeInDatabase(self::TABLE, [
            'id'     => 11,
            'status' => YoungSingle::STATUS_APPROVED,
        ]);

        list($total, $items) = json_decode(Cache::get($this->createCacheKey(1)));
        Assert::assertEquals(11, $total);

        for ($i = 0; $i < 10; $i++) {
            Assert::assertEquals($i + 1, $items[$i]->id);
        }
    }

    //=======================================
    //           Remove Applicants
    //=======================================
    public function testRemoveApplicants()
    {
        factory(YoungSingle::class)->create([
            'id'     => 1,
            'status' => YoungSingle::STATUS_PENDING,
        ]);
        factory(YoungSingle::class)->create([
            'id'     => 2,
            'status' => YoungSingle::STATUS_PENDING,
        ]);
        factory(YoungSingle::class)->create([
            'id'     => 3,
            'status' => YoungSingle::STATUS_PENDING,
        ]);

        Assert::assertTrue($this->getRepository()->removeApplicants([1, 2]));

        $this->notSeeInDatabase(self::TABLE, [
            'id'     => 1,
        ]);
        $this->notSeeInDatabase(self::TABLE, [
            'id'     => 2,
        ]);
        $this->seeInDatabase(self::TABLE, [
            'id'     => 3,
            'status' => YoungSingle::STATUS_PENDING,
        ]);
    }

    //=======================================
    //           Remove Attendants
    //=======================================
    public function testRemoveAttendants()
    {
        factory(YoungSingle::class)->create([
            'id'     => 1,
            'status' => YoungSingle::STATUS_APPROVED,
        ]);
        factory(YoungSingle::class)->create([
            'id'     => 2,
            'status' => YoungSingle::STATUS_APPROVED,
        ]);
        factory(YoungSingle::class)->create([
            'id'     => 3,
            'status' => YoungSingle::STATUS_APPROVED,
        ]);

        Assert::assertTrue($this->getRepository()->removeYoungSingles([1, 2]));

        $this->notSeeInDatabase(self::TABLE, [
            'id'     => 1,
        ]);
        $this->notSeeInDatabase(self::TABLE, [
            'id'     => 2,
        ]);
        $this->seeInDatabase(self::TABLE, [
            'id'     => 3,
            'status' => YoungSingle::STATUS_APPROVED,
        ]);
    }

    //=======================================
    //                Find
    //=======================================
    public function testFind()
    {
        $model = factory(YoungSingle::class)->create([
            'id'     => 1,
            'status' => YoungSingle::STATUS_APPROVED,
        ]);

        Assert::assertEquals($model, $this->getRepository()->find(1));
    }

    //=======================================
    //        List Approved Attendants
    //=======================================
    public function testListApprovedAttendants()
    {
        for ($i = 1; $i <= 3; $i++) {
            factory(YoungSingle::class)->create([
                'id'     => $i,
                'order_id' => $i,
                'status' => YoungSingle::STATUS_APPROVED,
            ]);
        }
        factory(YoungSingle::class)->create([
            'id'     => 4,
            'status' => YoungSingle::STATUS_PENDING,
        ]);

        // 1 page
        list($total, $items) = $this->getRepository()->listApprovedYoungSingles(1, 10);
        Assert::assertEquals(3, $total);
        Assert::assertCount(3, $items);
        for ($i = 0; $i < 3; $i++) {
            Assert::assertEquals($i + 1, $items[$i]->order_id);
        }

        // first page
        list($total, $items) = $this->getRepository()->listApprovedYoungSingles(1, 2, false);
        Assert::assertEquals(3, $total);
        Assert::assertCount(2, $items);

        // second page
        list($total, $items) = $this->getRepository()->listApprovedYoungSingles(2, 2, false);
        Assert::assertEquals(3, $total);
        Assert::assertCount(1, $items);
    }

    public function testListApprovedAttendants_WithCache()
    {
        for ($i = 1; $i <= 3; $i++) {
            factory(YoungSingle::class)->create([
                'id'     => $i,
                'order_id' => $i,
                'status' => YoungSingle::STATUS_APPROVED,
            ]);
        }

        factory(YoungSingle::class)->create([
            'id'     => 4,
            'status' => YoungSingle::STATUS_PENDING,
        ]);

        // 1 page
        list($total, $items) = $this->getRepository()->listApprovedYoungSingles(1, 10);
        Assert::assertEquals(3, $total);
        Assert::assertCount(3, $items);
        for ($i = 0; $i < 3; $i++) {
            Assert::assertEquals($i + 1, $items[$i]->order_id);
        }

        // first page
        list($total, $items) = $this->getRepository()->listApprovedYoungSingles(1, 2, true);
        Assert::assertEquals(3, $total);
        Assert::assertCount(2, $items);

        // second page
        list($total, $items) = $this->getRepository()->listApprovedYoungSingles(2, 2, true);
        Assert::assertEquals(3, $total);
        Assert::assertCount(1, $items);

        list($total, $items) = json_decode(Cache::get($this->createCacheKey(1)));
        Assert::assertEquals(3, $total);
        Assert::assertCount(2, $items);
        list($total, $items) = json_decode(Cache::get($this->createCacheKey(2)));
        Assert::assertEquals(3, $total);
        Assert::assertCount(1, $items);
    }

    //=======================================
    //        List Pending Attendants
    //=======================================
    public function testListPendingAttendants()
    {
        for ($i = 1; $i <= 3; $i++) {
            factory(YoungSingle::class)->create([
                'id'     => $i,
                'status' => YoungSingle::STATUS_PENDING,
            ]);
        }

        factory(YoungSingle::class)->create([
            'id'     => 4,
            'status' => YoungSingle::STATUS_APPROVED,
        ]);

        // 1 page
        list($total, $items) = $this->getRepository()->listPendingYoungSingles(1, 10);
        Assert::assertEquals(3, $total);
        Assert::assertCount(3, $items);
        for ($i = 0; $i < 3; $i++) {
            Assert::assertEquals($i + 1, $items[$i]->id);
        }

        // first page
        list($total, $items) = $this->getRepository()->listPendingYoungSingles(1, 2);
        Assert::assertEquals(3, $total);
        Assert::assertCount(2, $items);

        // second page
        list($total, $items) = $this->getRepository()->listPendingYoungSingles(2, 2);
        Assert::assertEquals(3, $total);
        Assert::assertCount(1, $items);
    }

    private static function createCacheKey($page, $key = YoungSingleRepository::LIST_KEY) {
        return md5($key) . '_' . date('Y-m-d') . '_' . $page;
    }

    /**
     * @return \Jihe\Repositories\YoungSingleRepository
     */
    private function getRepository()
    {
        return $this->app[\Jihe\Repositories\YoungSingleRepository::class];
    }
}
