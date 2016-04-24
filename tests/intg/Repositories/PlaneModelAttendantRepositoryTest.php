<?php
namespace intg\Jihe\Repositories;

use Carbon\Carbon;
use intg\Jihe\TestCase;
use Jihe\Repositories\PlaneModelAttendantRepository as AttendantRepository;
use \PHPUnit_Framework_Assert as Assert;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;

class PlaneModelAttendantRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    //=======================================
    //                 Add
    //=======================================
    public function testAdd()
    {
        $images = [
            'http://domain/1.png',
            'http://domain/2.png',
            'http://domain/3.png',
            'http://domain/4.png',
            'http://domain/5.png',
        ];

        $attendant = [
            'name'            => 'meinv',
            'gender'          => 2,
            'height'          => 172,
            'weight'          => 50,
            'bwh'             => '23,78,98',
            'shoe_size'       => 45.0,
            'mobile'          => '13800001111',
            'images_url'      => $images,
            'intro'           => '成都艺术学院mv168',
        ];

        Assert::assertTrue($this->getRepository()->add($attendant));

        $this->seeInDatabase('plane_model_attendants', [
            'name'            => 'meinv',
            'gender'          => 2,
            'height'          => 172,
            'weight'          => 50,
            'bwh'             => '23,78,98',
            'shoe_size'       => 45.0,
            'mobile'          => '13800001111',
            'cover_url'       => 'http://domain/1.png',
            'images_url'      => json_encode($images),
            'intro'           => '成都艺术学院mv168',
            'status'          => 0,
        ]);
    }

    public function testAdd_MobileExists()
    {
        $this->createData(10000, '13800001111', AttendantRepository::STATUS_PENDING);

        $images = [
            'http://domain/1.png',
            'http://domain/2.png',
            'http://domain/3.png',
            'http://domain/4.png',
            'http://domain/5.png',
        ];

        $attendant = [
            'name'            => 'meinv',
            'gender'          => 2,
            'height'          => 172,
            'weight'          => 50,
            'bwh'             => '23,78,98',
            'shoe_size'       => 45.0,
            'mobile'          => '13800001111',
            'images_url'      => $images,
            'intro'           => '成都艺术学院mv168'
        ];

        Assert::assertFalse($this->getRepository()->add($attendant));
    }

    //=======================================
    //             FindIdByMobile
    //=======================================
    public function testFindIdByMobile()
    {
        $this->createData(10000, '13800001111', AttendantRepository::STATUS_PENDING);

        Assert::assertEquals(10000, $this->getRepository()->findIdByMobile('13800001111'));
        Assert::assertNull($this->getRepository()->findIdByMobile('13800001110'));
    }

    //=======================================
    //               Approve
    //=======================================
    public function testApprove()
    {
        Cache::shouldReceive('has')->with($this->createCacheKey(1))->andReturn(false);

        $this->createData(10000, '13800001111', AttendantRepository::STATUS_PENDING);
        $this->createData(10001, '13800010001', AttendantRepository::STATUS_APPROVED);

        $std10000 = $this->createStdClass(10000, '13800001111', AttendantRepository::STATUS_PENDING);
        $std10001 = $this->createStdClass(10001, '13800001111', AttendantRepository::STATUS_APPROVED);
        $std10002 = $this->createStdClass(10002, '13800001111', AttendantRepository::STATUS_PENDING);

        Assert::assertTrue($this->getRepository()->approve($std10000));
        Assert::assertFalse($this->getRepository()->approve($std10001));
        Assert::assertFalse($this->getRepository()->approve($std10002));

        $this->seeInDatabase('plane_model_attendants', [
            'id'     => 10000,
            'status' => 1,
        ]);
    }

    public function testApprove_Multi()
    {
        Cache::shouldReceive('has')->with($this->createCacheKey(1))->andReturn(false);

        for ($i = 10000; $i < 10004; $i++) {
            $this->createData($i, '138000' . $i, AttendantRepository::STATUS_PENDING);
        }

        $std10000 = $this->createStdClass(10000, '13800001111', AttendantRepository::STATUS_PENDING);
        $std10001 = $this->createStdClass(10001, '13800001111', AttendantRepository::STATUS_PENDING);
        $std10002 = $this->createStdClass(10002, '13800001111', AttendantRepository::STATUS_PENDING);
        $std10003 = $this->createStdClass(10003, '13800001111', AttendantRepository::STATUS_PENDING);

        Assert::assertTrue($this->getRepository()->approve([$std10000, $std10001, $std10002, $std10003]));

        $this->seeInDatabase('plane_model_attendants', [
            'id'     => 10000,
            'status' => 1,
        ]);
        $this->seeInDatabase('plane_model_attendants', [
            'id'     => 10001,
            'status' => 1,
        ]);
        $this->seeInDatabase('plane_model_attendants', [
            'id'     => 10002,
            'status' => 1,
        ]);
        $this->seeInDatabase('plane_model_attendants', [
            'id'     => 10003,
            'status' => 1,
        ]);
    }

    public function testApprove_WithNotifyCacheOnePage()
    {
        $attendantsInCache = [
            $this->createStdClass(10000, '13800000000', AttendantRepository::STATUS_APPROVED),
        ];
        $expiresAt = Carbon::now()->addMinutes(1);
        Cache::put($this->createCacheKey(1), json_encode([1, $attendantsInCache]), $expiresAt);

        $stdPending = $this->createStdClass(10001, '13800000001', AttendantRepository::STATUS_PENDING);
        $stdNotExists = $this->createStdClass(11111, '13800001111', AttendantRepository::STATUS_PENDING);

        $this->createData(10000, '13800000000', AttendantRepository::STATUS_APPROVED);
        $this->createData(10001, '13800001112', AttendantRepository::STATUS_PENDING);

        Assert::assertTrue($this->getRepository()->approve($stdPending));
        Assert::assertFalse($this->getRepository()->approve($attendantsInCache[0]));
        Assert::assertFalse($this->getRepository()->approve($stdNotExists));

        $this->seeInDatabase('plane_model_attendants', [
            'id'     => 10001,
            'status' => 1,
        ]);

        list($total, $attendants) = json_decode(Cache::get($this->createCacheKey(1)));
        Assert::assertEquals(2, $total);
        Assert::assertEquals(10000, $attendants[0]->id);
        Assert::assertEquals(10001, $attendants[1]->id);
    }

    public function testApprove_WithNotifyCacheTwoPage()
    {
        $attendantsInCache = [
            $this->createStdClass(10000, '13800000000', AttendantRepository::STATUS_APPROVED),
            $this->createStdClass(10001, '13800000001', AttendantRepository::STATUS_APPROVED),
            $this->createStdClass(10002, '13800000002', AttendantRepository::STATUS_APPROVED),
            $this->createStdClass(10003, '13800000003', AttendantRepository::STATUS_APPROVED),
            $this->createStdClass(10004, '13800000004', AttendantRepository::STATUS_APPROVED),
            $this->createStdClass(10005, '13800000005', AttendantRepository::STATUS_APPROVED),
            $this->createStdClass(10006, '13800000006', AttendantRepository::STATUS_APPROVED),
            $this->createStdClass(10007, '13800000007', AttendantRepository::STATUS_APPROVED),
            $this->createStdClass(10008, '13800000008', AttendantRepository::STATUS_APPROVED),
            $this->createStdClass(100010, '13800000009', AttendantRepository::STATUS_APPROVED),
        ];
        $expiresAt = Carbon::now()->addMinutes(1);
        Cache::put($this->createCacheKey(1), json_encode([10, $attendantsInCache]), $expiresAt);

        $stdPending = $this->createStdClass(10009, '13800000009', AttendantRepository::STATUS_PENDING);
        $stdNotExists = $this->createStdClass(11111, '13800001111', AttendantRepository::STATUS_PENDING);

        $this->createData(10000, '13800000000', AttendantRepository::STATUS_APPROVED);
        $this->createData(10001, '13800000001', AttendantRepository::STATUS_APPROVED);
        $this->createData(10002, '13800000002', AttendantRepository::STATUS_APPROVED);
        $this->createData(10003, '13800000003', AttendantRepository::STATUS_APPROVED);
        $this->createData(10004, '13800000004', AttendantRepository::STATUS_APPROVED);
        $this->createData(10005, '13800000005', AttendantRepository::STATUS_APPROVED);
        $this->createData(10006, '13800000006', AttendantRepository::STATUS_APPROVED);
        $this->createData(10007, '13800000007', AttendantRepository::STATUS_APPROVED);
        $this->createData(10008, '13800000008', AttendantRepository::STATUS_APPROVED);
        $this->createData(10010, '13800000010', AttendantRepository::STATUS_APPROVED);
        $this->createData(10009, '13800000009', AttendantRepository::STATUS_PENDING);

        Assert::assertTrue($this->getRepository()->approve($stdPending));
        Assert::assertFalse($this->getRepository()->approve($attendantsInCache[0]));
        Assert::assertFalse($this->getRepository()->approve($stdNotExists));

        $this->seeInDatabase('plane_model_attendants', [
            'id'     => 10009,
            'status' => 1,
        ]);

        list($total, $attendants) = json_decode(Cache::get($this->createCacheKey(1)));
        Assert::assertEquals(11, $total);
        Assert::assertEquals(10000, $attendants[0]->id);
        Assert::assertEquals(10001, $attendants[1]->id);
        Assert::assertEquals(10002, $attendants[2]->id);
        Assert::assertEquals(10003, $attendants[3]->id);
        Assert::assertEquals(10004, $attendants[4]->id);
        Assert::assertEquals(10005, $attendants[5]->id);
        Assert::assertEquals(10006, $attendants[6]->id);
        Assert::assertEquals(10007, $attendants[7]->id);
        Assert::assertEquals(10008, $attendants[8]->id);
        Assert::assertEquals(10009, $attendants[9]->id);
    }

    public function testApprove_WithNotifyCacheTwoPageAndIndexSecondPage()
    {
        $attendantsInCache = [
            $this->createStdClass(10000, '13800000000', AttendantRepository::STATUS_APPROVED),
            $this->createStdClass(10001, '13800000001', AttendantRepository::STATUS_APPROVED),
            $this->createStdClass(10002, '13800000002', AttendantRepository::STATUS_APPROVED),
            $this->createStdClass(10003, '13800000003', AttendantRepository::STATUS_APPROVED),
            $this->createStdClass(10004, '13800000004', AttendantRepository::STATUS_APPROVED),
            $this->createStdClass(10005, '13800000005', AttendantRepository::STATUS_APPROVED),
            $this->createStdClass(10006, '13800000006', AttendantRepository::STATUS_APPROVED),
            $this->createStdClass(10007, '13800000007', AttendantRepository::STATUS_APPROVED),
            $this->createStdClass(10008, '13800000008', AttendantRepository::STATUS_APPROVED),
            $this->createStdClass(10009, '13800000009', AttendantRepository::STATUS_APPROVED),
        ];
        $expiresAt = Carbon::now()->addMinutes(1);
        Cache::put($this->createCacheKey(1), json_encode([10, $attendantsInCache]), $expiresAt);

        $stdPending = $this->createStdClass(10010, '13800000010', AttendantRepository::STATUS_PENDING);
        $stdNotExists = $this->createStdClass(11111, '13800001111', AttendantRepository::STATUS_PENDING);

        $this->createData(10000, '13800000000', AttendantRepository::STATUS_APPROVED);
        $this->createData(10001, '13800000001', AttendantRepository::STATUS_APPROVED);
        $this->createData(10002, '13800000002', AttendantRepository::STATUS_APPROVED);
        $this->createData(10003, '13800000003', AttendantRepository::STATUS_APPROVED);
        $this->createData(10004, '13800000004', AttendantRepository::STATUS_APPROVED);
        $this->createData(10005, '13800000005', AttendantRepository::STATUS_APPROVED);
        $this->createData(10006, '13800000006', AttendantRepository::STATUS_APPROVED);
        $this->createData(10007, '13800000007', AttendantRepository::STATUS_APPROVED);
        $this->createData(10008, '13800000008', AttendantRepository::STATUS_APPROVED);
        $this->createData(10009, '13800000009', AttendantRepository::STATUS_APPROVED);
        $this->createData(10010, '13800000010', AttendantRepository::STATUS_PENDING);


        Assert::assertTrue($this->getRepository()->approve($stdPending));
        Assert::assertFalse($this->getRepository()->approve($attendantsInCache[0]));
        Assert::assertFalse($this->getRepository()->approve($stdNotExists));

        $this->seeInDatabase('plane_model_attendants', [
            'id'     => 10010,
            'status' => 1,
        ]);

        list($total, $attendants) = json_decode(Cache::get($this->createCacheKey(1)));
        Assert::assertEquals(11, $total);
        Assert::assertEquals(10000, $attendants[0]->id);
        Assert::assertEquals(10001, $attendants[1]->id);
        Assert::assertEquals(10002, $attendants[2]->id);
        Assert::assertEquals(10003, $attendants[3]->id);
        Assert::assertEquals(10004, $attendants[4]->id);
        Assert::assertEquals(10005, $attendants[5]->id);
        Assert::assertEquals(10006, $attendants[6]->id);
        Assert::assertEquals(10007, $attendants[7]->id);
        Assert::assertEquals(10008, $attendants[8]->id);
        Assert::assertEquals(10009, $attendants[9]->id);
    }

    //=======================================
    //                Find
    //=======================================
    public function testFind()
    {
        $this->createData(10000, '13800001111', AttendantRepository::STATUS_APPROVED);

        $this->assertAttendantEquals(10000, 'meinv', 2, 172, 50, '23,78,98', 45.0,
                                     '13800001111', 'a', '["a", "b"]', '成都艺术学院mv168', 1,
                                     $this->getRepository()->find(10000));
    }

    //=======================================
    //        List Approved Attendants
    //=======================================
    public function testListApprovedAttendants()
    {
        for ($i = 10000; $i < 10003; $i++) {
            $this->createData($i, '138000' . $i,
                AttendantRepository::STATUS_APPROVED);
        }

        $this->createData(10004, '13800010004',
            AttendantRepository::STATUS_PENDING);

        // 1 page
        list($total, $attentans) = $this->getRepository()->listApprovedAttendants(1, 10);
        Assert::assertEquals(3, $total);
        Assert::assertCount(3, $attentans);
        for ($i = 0; $i < 3; $i++) {
            $number = 10000 + $i;
            $this->assertAttendantEquals($number, 'meinv', 2, 172, 50, '23,78,98', 45.0,
                                         '138000' . $number, 'a', '["a", "b"]', '成都艺术学院mv168', 1,
                                         $attentans[$i]);
        }

        // first page
        list($total, $attentans) = $this->getRepository()->listApprovedAttendants(1, 2, false);
        Assert::assertEquals(3, $total);
        Assert::assertCount(2, $attentans);

        // second page
        list($total, $attentans) = $this->getRepository()->listApprovedAttendants(2, 2, false);
        Assert::assertEquals(3, $total);
        Assert::assertCount(1, $attentans);
    }

    public function testListApprovedAttendants_WithCache()
    {
        for ($i = 10000; $i < 10003; $i++) {
            $this->createData($i, '138000' . $i,
                AttendantRepository::STATUS_APPROVED);
        }

        $this->createData(10004, '13800010004',
            AttendantRepository::STATUS_PENDING);

        // 1 page
        list($total, $attentans) = $this->getRepository()->listApprovedAttendants(1, 10);
        Assert::assertEquals(3, $total);
        Assert::assertCount(3, $attentans);
        for ($i = 0; $i < 3; $i++) {
            $number = 10000 + $i;
            $this->assertAttendantEquals($number, 'meinv', 2, 172, 50, '23,78,98', 45.0,
                '138000' . $number, 'a', '["a", "b"]', '成都艺术学院mv168', 1,
                $attentans[$i]);
        }

        // first page
        list($total, $attentans) = $this->getRepository()->listApprovedAttendants(1, 2, true);
        Assert::assertEquals(3, $total);
        Assert::assertCount(2, $attentans);

        // second page
        list($total, $attentans) = $this->getRepository()->listApprovedAttendants(2, 2, true);
        Assert::assertEquals(3, $total);
        Assert::assertCount(1, $attentans);

        list($total, $attentans) = json_decode(Cache::get($this->createCacheKey(1)));
        Assert::assertEquals(3, $total);
        Assert::assertCount(2, $attentans);
        list($total, $attentans) = json_decode(Cache::get($this->createCacheKey(2)));
        Assert::assertEquals(3, $total);
        Assert::assertCount(1, $attentans);
    }

    //=======================================
    //        List Pending Attendants
    //=======================================
    public function testListPendingAttendants()
    {
        for ($i = 10000; $i < 10003; $i++) {
            $this->createData($i, '138000' . $i,
                AttendantRepository::STATUS_PENDING);
        }

        $this->createData(10004, '13800010004',
            AttendantRepository::STATUS_APPROVED);

        // 1 page
        list($total, $attentans) = $this->getRepository()->listPendingAttendants(1, 10);
        Assert::assertEquals(3, $total);
        Assert::assertCount(3, $attentans);
        for ($i = 0; $i < 3; $i++) {
            $number = 10000 + $i;
            $this->assertAttendantEquals($number, 'meinv', 2, 172, 50, '23,78,98', 45.0,
                                         '138000' . $number, 'a', '["a", "b"]', '成都艺术学院mv168', 0,
                                         $attentans[$i]);
        }

        // first page
        list($total, $attentans) = $this->getRepository()->listPendingAttendants(1, 2);
        Assert::assertEquals(3, $total);
        Assert::assertCount(2, $attentans);

        // second page
        list($total, $attentans) = $this->getRepository()->listPendingAttendants(2, 2);
        Assert::assertEquals(3, $total);
        Assert::assertCount(1, $attentans);
    }

    private function assertAttendantEquals(
        $expectedId, $expectedName, $expectedGender, $expectedHeight, $expectedWeight,
        $expectedBwh, $expectedShoeSize, $expectedMobile,
        $expectedCoverUrl, $expectedImagesUrl, $expectedIntro, $expectedStatus,
        $attendant)
    {
        Assert::assertEquals($expectedId, $attendant->id);
        Assert::assertEquals($expectedName, $attendant->name);
        Assert::assertEquals($expectedGender, $attendant->gender);
        Assert::assertEquals($expectedHeight, $attendant->height);
        Assert::assertEquals($expectedWeight, $attendant->weight);
        Assert::assertEquals($expectedBwh, $attendant->bwh);
        Assert::assertEquals($expectedShoeSize, $attendant->shoe_size);
        Assert::assertEquals($expectedMobile, $attendant->mobile);
        Assert::assertEquals($expectedCoverUrl, $attendant->cover_url);
        Assert::assertEquals($expectedImagesUrl, $attendant->images_url);
        Assert::assertEquals($expectedIntro, $attendant->intro);
        Assert::assertEquals($expectedStatus, $attendant->status);
    }

    private function createStdClass($id, $mobile, $status)
    {
        $attendant = new \stdClass();
        $attendant->id = $id;
        $attendant->name = 'meinv';
        $attendant->gender = 2;
        $attendant->height = 172;
        $attendant->weight = 50;
        $attendant->bwh = '23,78,98';
        $attendant->shoe_size = 45.0;
        $attendant->mobile = $mobile; // 13800010000
        $attendant->cover_url = 'a';
        $attendant->image_url = ["a", "b"];
        $attendant->intro = '成都艺术学院mv168';
        $attendant->status = $status;
        $attendant->created_at = '2011-09-01 00:00:00';
        $attendant->updated_at = '2011-09-01 00:00:00';
        return $attendant;
    }

    private function createData($id, $mobile, $status)
    {
        $this->insert([
            'id'              => $id,
            'name'            => 'meinv',
            'gender'          => 2,
            'height'          => 172,
            'weight'          => 50,
            'bwh'             => '23,78,98',
            'shoe_size'       => 45.0,
            'mobile'          => $mobile, // 13800010000
            'cover_url'       => 'a',
            'images_url'      => '["a", "b"]',
            'intro'           => '成都艺术学院mv168',
            'status'          => $status,
            'created_at'      => '2011-09-01 00:00:00',
            'updated_at'      => '2011-09-01 00:00:00',
        ]);
    }

    private function insert(array $values)
    {
        \DB::insert('insert into plane_model_attendants (
                    id, name, gender, height, weight, bwh, shoe_size,
                    mobile, cover_url, images_url, intro, status,
                    created_at, updated_at)
                    values (
                    :id, :name, :gender, :height, :weight, :bwh, :shoe_size,
                    :mobile, :cover_url, :images_url, :intro, :status,
                    :created_at, :updated_at)', $values);
    }

    private static function createCacheKey($page) {
        return md5(AttendantRepository::ATTENDANT_LIST_KEY) . '_' . $page;
    }

    /**
     * @return \Jihe\Repositories\PlaneModelAttendantRepository
     */
    private function getRepository()
    {
        return $this->app[\Jihe\Repositories\PlaneModelAttendantRepository::class];
    }
}
