<?php
namespace intg\Jihe\Repositories;

use Carbon\Carbon;
use intg\Jihe\TestCase;
use Jihe\Repositories\AttendantRepository;
use \PHPUnit_Framework_Assert as Assert;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;

class AttendantRepositoryTest extends TestCase
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
            'age'             => 18,
            'height'          => 172,
            'speciality'      => '唱歌,舞蹈',
            'school'          => '成都艺术学院',
            'major'           => '舞蹈',
            'education'       => '本科',
            'graduation_time' => '2015.6',
            'ident_code'      => '51119119950101098X',
            'mobile'          => '13800001111',
            'wechat_id'       => 'mv168_2011',
            'email'           => 'mv168_2011@qq.com',
            'images_url'      => $images,
            'motto'           => '必须选上',
            'introduction'    => '成都艺术学院mv168',
        ];

        Assert::assertTrue($this->getRepository()->add($attendant));

        $this->seeInDatabase('attendants', [
            'name'            => 'meinv',
            'gender'          => 2,
            'age'             => 18,
            'height'          => 172,
            'speciality'      => '唱歌,舞蹈',
            'school'          => '成都艺术学院',
            'major'           => '舞蹈',
            'education'       => '本科',
            'graduation_time' => '2015.6',
            'ident_code'      => '51119119950101098X',
            'mobile'          => '13800001111',
            'wechat_id'       => 'mv168_2011',
            'email'           => 'mv168_2011@qq.com',
            'cover_url'       => 'http://domain/1.png',
            'images_url'      => json_encode($images),
            'motto'           => '必须选上',
            'introduction'    => '成都艺术学院mv168',
            'status'          => 0,
        ]);
    }

    public function testAdd_MobileExists()
    {
        $this->createData(10000, '51119119950101098X', '13800001111', AttendantRepository::STATUS_PENDING);

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
            'age'             => 18,
            'height'          => 172,
            'speciality'      => '唱歌,舞蹈',
            'school'          => '成都艺术学院',
            'major'           => '舞蹈',
            'education'       => '本科',
            'graduation_time' => '2015.6',
            'ident_code'      => '511191199501010980',
            'mobile'          => '13800001111',
            'wechat_id'       => 'mv168_2011',
            'email'           => 'mv168_2011@qq.com',
            'images_url'      => $images,
            'motto'           => '必须选上',
            'introduction'    => '成都艺术学院mv168'
        ];

        Assert::assertFalse($this->getRepository()->add($attendant));
    }

    public function testAdd_IdentCodeExists()
    {
        $this->createData(10000, '51119119950101098X', '13800001111', AttendantRepository::STATUS_PENDING);

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
            'age'             => 18,
            'height'          => 172,
            'speciality'      => '唱歌,舞蹈',
            'school'          => '成都艺术学院',
            'major'           => '舞蹈',
            'education'       => '本科',
            'graduation_time' => '2015.6',
            'ident_code'      => '51119119950101098X',
            'mobile'          => '13800001110',
            'wechat_id'       => 'mv168_2011',
            'email'           => 'mv168_2011@qq.com',
            'images_url'      => $images,
            'motto'           => '必须选上',
            'introduction'    => '成都艺术学院mv168'
        ];

        Assert::assertFalse($this->getRepository()->add($attendant));
    }

    //=======================================
    //             FindIdByMobile
    //=======================================
    public function testFindIdByMobile()
    {
        $this->createData(10000, '51119119950101098X', '13800001111', AttendantRepository::STATUS_PENDING);

        Assert::assertEquals(10000, $this->getRepository()->findIdByMobile('13800001111'));
        Assert::assertNull($this->getRepository()->findIdByMobile('13800001110'));
    }

    //=======================================
    //           FindIdByIdentCode
    //=======================================
    public function testFindIdByIdentCode()
    {
        $this->createData(10000, '51119119950101098X', '13800001111', AttendantRepository::STATUS_PENDING);

        Assert::assertEquals(10000, $this->getRepository()->findIdByIdentCode('51119119950101098X'));
        Assert::assertNull($this->getRepository()->findIdByIdentCode('511191199501010980'));
    }

    //=======================================
    //               Approve
    //=======================================
    public function testApprove()
    {
        Cache::shouldReceive('has')->with($this->createCacheKey(1))->andReturn(false);

        $this->createData(10000, '51119119950101098X', '13800001111', AttendantRepository::STATUS_PENDING);
        $this->createData(10001, '511191199501010001', '13800010001', AttendantRepository::STATUS_APPROVED);

        $std10000 = $this->createStdClass(10000, '51119119950101098X', '13800001111', AttendantRepository::STATUS_PENDING);
        $std10001 = $this->createStdClass(10001, '51119119950101098X', '13800001111', AttendantRepository::STATUS_APPROVED);
        $std10002 = $this->createStdClass(10002, '51119119950101098X', '13800001111', AttendantRepository::STATUS_PENDING);

        Assert::assertTrue($this->getRepository()->approve($std10000));
        Assert::assertFalse($this->getRepository()->approve($std10001));
        Assert::assertFalse($this->getRepository()->approve($std10002));

        $this->seeInDatabase('attendants', [
            'id'     => 10000,
            'status' => 1,
        ]);
    }

    public function testApprove_Multi()
    {
        Cache::shouldReceive('has')->with($this->createCacheKey(1))->andReturn(false);

        for ($i = 10000; $i < 10004; $i++) {
            $this->createData($i, '5111911995010' . $i, '138000' . $i, AttendantRepository::STATUS_PENDING);
        }

        $std10000 = $this->createStdClass(10000, '51119119950101098X', '13800001111', AttendantRepository::STATUS_PENDING);
        $std10001 = $this->createStdClass(10001, '51119119950101098X', '13800001111', AttendantRepository::STATUS_PENDING);
        $std10002 = $this->createStdClass(10002, '51119119950101098X', '13800001111', AttendantRepository::STATUS_PENDING);
        $std10003 = $this->createStdClass(10003, '51119119950101098X', '13800001111', AttendantRepository::STATUS_PENDING);

        Assert::assertTrue($this->getRepository()->approve([$std10000, $std10001, $std10002, $std10003]));

        $this->seeInDatabase('attendants', [
            'id'     => 10000,
            'status' => 1,
        ]);
        $this->seeInDatabase('attendants', [
            'id'     => 10001,
            'status' => 1,
        ]);
        $this->seeInDatabase('attendants', [
            'id'     => 10002,
            'status' => 1,
        ]);
        $this->seeInDatabase('attendants', [
            'id'     => 10003,
            'status' => 1,
        ]);
    }

    public function testApprove_WithNotifyCacheOnePage()
    {
        $attendantsInCache = [
            $this->createStdClass(10000, '511191199501010000',
                '13800000000', AttendantRepository::STATUS_APPROVED),
        ];
        $expiresAt = Carbon::now()->addMinutes(1);
        Cache::put($this->createCacheKey(1), json_encode([1, $attendantsInCache]), $expiresAt);

        $stdPending = $this->createStdClass(10001, '511191199501010001',
            '13800000001', AttendantRepository::STATUS_PENDING);
        $stdNotExists = $this->createStdClass(11111, '51119119950101098X',
            '13800001111', AttendantRepository::STATUS_PENDING);

        $this->createData(10000, '511191199501010000',
            '13800000000', AttendantRepository::STATUS_APPROVED);
        $this->createData(10001, '511191199501010002', '13800001112', AttendantRepository::STATUS_PENDING);

        Assert::assertTrue($this->getRepository()->approve($stdPending));
        Assert::assertFalse($this->getRepository()->approve($attendantsInCache[0]));
        Assert::assertFalse($this->getRepository()->approve($stdNotExists));

        $this->seeInDatabase('attendants', [
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
            $this->createStdClass(10000, '511191199501010000',
                '13800000000', AttendantRepository::STATUS_APPROVED),
            $this->createStdClass(10001, '511191199501010001',
                '13800000001', AttendantRepository::STATUS_APPROVED),
            $this->createStdClass(10002, '511191199501010002',
                '13800000002', AttendantRepository::STATUS_APPROVED),
            $this->createStdClass(10003, '511191199501010003',
                '13800000003', AttendantRepository::STATUS_APPROVED),
            $this->createStdClass(10004, '511191199501010004',
                '13800000004', AttendantRepository::STATUS_APPROVED),
            $this->createStdClass(10005, '511191199501010005',
                '13800000005', AttendantRepository::STATUS_APPROVED),
            $this->createStdClass(10006, '511191199501010006',
                '13800000006', AttendantRepository::STATUS_APPROVED),
            $this->createStdClass(10007, '511191199501010007',
                '13800000007', AttendantRepository::STATUS_APPROVED),
            $this->createStdClass(10008, '511191199501010008',
                '13800000008', AttendantRepository::STATUS_APPROVED),
            $this->createStdClass(100010, '511191199501010010',
                '13800000009', AttendantRepository::STATUS_APPROVED),
        ];
        $expiresAt = Carbon::now()->addMinutes(1);
        Cache::put($this->createCacheKey(1), json_encode([10, $attendantsInCache]), $expiresAt);

        $stdPending = $this->createStdClass(10009, '511191199501010009',
            '13800000009', AttendantRepository::STATUS_PENDING);
        $stdNotExists = $this->createStdClass(11111, '51119119950101098X',
            '13800001111', AttendantRepository::STATUS_PENDING);

        $this->createData(10000, '511191199501010000',
            '13800000000', AttendantRepository::STATUS_APPROVED);
        $this->createData(10001, '511191199501010001',
            '13800000001', AttendantRepository::STATUS_APPROVED);
        $this->createData(10002, '511191199501010002',
            '13800000002', AttendantRepository::STATUS_APPROVED);
        $this->createData(10003, '511191199501010003',
            '13800000003', AttendantRepository::STATUS_APPROVED);
        $this->createData(10004, '511191199501010004',
            '13800000004', AttendantRepository::STATUS_APPROVED);
        $this->createData(10005, '511191199501010005',
            '13800000005', AttendantRepository::STATUS_APPROVED);
        $this->createData(10006, '511191199501010006',
            '13800000006', AttendantRepository::STATUS_APPROVED);
        $this->createData(10007, '511191199501010007',
            '13800000007', AttendantRepository::STATUS_APPROVED);
        $this->createData(10008, '511191199501010008',
            '13800000008', AttendantRepository::STATUS_APPROVED);
        $this->createData(10010, '511191199501010010',
            '13800000010', AttendantRepository::STATUS_APPROVED);
        $this->createData(10009, '511191199501010009',
            '13800000009', AttendantRepository::STATUS_PENDING);

        Assert::assertTrue($this->getRepository()->approve($stdPending));
        Assert::assertFalse($this->getRepository()->approve($attendantsInCache[0]));
        Assert::assertFalse($this->getRepository()->approve($stdNotExists));

        $this->seeInDatabase('attendants', [
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
            $this->createStdClass(10000, '511191199501010000',
                                  '13800000000', AttendantRepository::STATUS_APPROVED),
            $this->createStdClass(10001, '511191199501010001',
                                  '13800000001', AttendantRepository::STATUS_APPROVED),
            $this->createStdClass(10002, '511191199501010002',
                                  '13800000002', AttendantRepository::STATUS_APPROVED),
            $this->createStdClass(10003, '511191199501010003',
                                  '13800000003', AttendantRepository::STATUS_APPROVED),
            $this->createStdClass(10004, '511191199501010004',
                                  '13800000004', AttendantRepository::STATUS_APPROVED),
            $this->createStdClass(10005, '511191199501010005',
                                  '13800000005', AttendantRepository::STATUS_APPROVED),
            $this->createStdClass(10006, '511191199501010006',
                                  '13800000006', AttendantRepository::STATUS_APPROVED),
            $this->createStdClass(10007, '511191199501010007',
                                  '13800000007', AttendantRepository::STATUS_APPROVED),
            $this->createStdClass(10008, '511191199501010008',
                                  '13800000008', AttendantRepository::STATUS_APPROVED),
            $this->createStdClass(10009, '511191199501010009',
                                  '13800000009', AttendantRepository::STATUS_APPROVED),
        ];
        $expiresAt = Carbon::now()->addMinutes(1);
        Cache::put($this->createCacheKey(1), json_encode([10, $attendantsInCache]), $expiresAt);

        $stdPending = $this->createStdClass(10010, '511191199501010010',
                                            '13800000010', AttendantRepository::STATUS_PENDING);
        $stdNotExists = $this->createStdClass(11111, '51119119950101098X',
                                              '13800001111', AttendantRepository::STATUS_PENDING);

        $this->createData(10000, '511191199501010000',
                          '13800000000', AttendantRepository::STATUS_APPROVED);
        $this->createData(10001, '511191199501010001',
                          '13800000001', AttendantRepository::STATUS_APPROVED);
        $this->createData(10002, '511191199501010002',
                          '13800000002', AttendantRepository::STATUS_APPROVED);
        $this->createData(10003, '511191199501010003',
                          '13800000003', AttendantRepository::STATUS_APPROVED);
        $this->createData(10004, '511191199501010004',
                          '13800000004', AttendantRepository::STATUS_APPROVED);
        $this->createData(10005, '511191199501010005',
                          '13800000005', AttendantRepository::STATUS_APPROVED);
        $this->createData(10006, '511191199501010006',
                          '13800000006', AttendantRepository::STATUS_APPROVED);
        $this->createData(10007, '511191199501010007',
                          '13800000007', AttendantRepository::STATUS_APPROVED);
        $this->createData(10008, '511191199501010008',
                          '13800000008', AttendantRepository::STATUS_APPROVED);
        $this->createData(10009, '511191199501010009',
                          '13800000009', AttendantRepository::STATUS_APPROVED);
        $this->createData(10010, '511191199501010010',
                          '13800000010', AttendantRepository::STATUS_PENDING);


        Assert::assertTrue($this->getRepository()->approve($stdPending));
        Assert::assertFalse($this->getRepository()->approve($attendantsInCache[0]));
        Assert::assertFalse($this->getRepository()->approve($stdNotExists));

        $this->seeInDatabase('attendants', [
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
        $this->createData(10000, '51119119950101098X', '13800001111', AttendantRepository::STATUS_APPROVED);

        $this->assertAttendantEquals(10000, 'meinv', 2, 18, 172, '唱歌,舞蹈',
                                     '成都艺术学院', '舞蹈', '本科', '2015.6',
                                     '51119119950101098X', '13800001111', 'mv168_2011', 'mv168_2011@qq.com',
                                     'a', '["a", "b"]', '必须选上', '成都艺术学院mv168', 1,
                                     $this->getRepository()->find(10000));
    }

    //=======================================
    //        List Approved Attendants
    //=======================================
    public function testListApprovedAttendants()
    {
        for ($i = 10000; $i < 10003; $i++) {
            $this->createData($i, '5111911995010' . $i, '138000' . $i,
                AttendantRepository::STATUS_APPROVED);
        }

        $this->createData(10004, '511191199501010004', '13800010004',
            AttendantRepository::STATUS_PENDING);

        // 1 page
        list($total, $attentans) = $this->getRepository()->listApprovedAttendants(1, 10);
        Assert::assertEquals(3, $total);
        Assert::assertCount(3, $attentans);
        for ($i = 0; $i < 3; $i++) {
            $number = 10000 + $i;
            $this->assertAttendantEquals($number, 'meinv', 2, 18, 172, '唱歌,舞蹈',
                                         '成都艺术学院', '舞蹈', '本科', '2015.6',
                                         '5111911995010' . $number, '138000' . $number, 'mv168_2011', 'mv168_2011@qq.com',
                                         'a', '["a", "b"]', '必须选上', '成都艺术学院mv168', 1,
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
            $this->createData($i, '5111911995010' . $i, '138000' . $i,
                AttendantRepository::STATUS_APPROVED);
        }

        $this->createData(10004, '511191199501010004', '13800010004',
            AttendantRepository::STATUS_PENDING);

        // 1 page
        list($total, $attentans) = $this->getRepository()->listApprovedAttendants(1, 10);
        Assert::assertEquals(3, $total);
        Assert::assertCount(3, $attentans);
        for ($i = 0; $i < 3; $i++) {
            $number = 10000 + $i;
            $this->assertAttendantEquals($number, 'meinv', 2, 18, 172, '唱歌,舞蹈',
                '成都艺术学院', '舞蹈', '本科', '2015.6',
                '5111911995010' . $number, '138000' . $number, 'mv168_2011', 'mv168_2011@qq.com',
                'a', '["a", "b"]', '必须选上', '成都艺术学院mv168', 1,
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
            $this->createData($i, '5111911995010' . $i, '138000' . $i,
                AttendantRepository::STATUS_PENDING);
        }

        $this->createData(10004, '511191199501010004', '13800010004',
            AttendantRepository::STATUS_APPROVED);

        // 1 page
        list($total, $attentans) = $this->getRepository()->listPendingAttendants(1, 10);
        Assert::assertEquals(3, $total);
        Assert::assertCount(3, $attentans);
        for ($i = 0; $i < 3; $i++) {
            $number = 10000 + $i;
            $this->assertAttendantEquals($number, 'meinv', 2, 18, 172, '唱歌,舞蹈',
                                         '成都艺术学院', '舞蹈', '本科', '2015.6',
                                         '5111911995010' . $number, '138000' . $number, 'mv168_2011', 'mv168_2011@qq.com',
                                         'a', '["a", "b"]', '必须选上', '成都艺术学院mv168', 0,
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
        $expectedId, $expectedName, $expectedGender, $expectedAge, $expectedHeight, $expectedSpeciality,
        $expectedSchool, $expectedMajor, $expectedEducation, $expectedGraduationTime,
        $expectedIdentCode, $expectedMobile, $expectedWechatId, $expectedEmail,
        $expectedCoverUrl, $expectedImagesUrl, $expectedMotto, $expectedIntroduction, $expectedStatus,
        $attendant)
    {
        Assert::assertEquals($expectedId, $attendant->id);
        Assert::assertEquals($expectedName, $attendant->name);
        Assert::assertEquals($expectedGender, $attendant->gender);
        Assert::assertEquals($expectedAge, $attendant->age);
        Assert::assertEquals($expectedHeight, $attendant->height);
        Assert::assertEquals($expectedSpeciality, $attendant->speciality);
        Assert::assertEquals($expectedSchool, $attendant->school);
        Assert::assertEquals($expectedMajor, $attendant->major);
        Assert::assertEquals($expectedEducation, $attendant->education);
        Assert::assertEquals($expectedGraduationTime, $attendant->graduation_time);
        Assert::assertEquals($expectedIdentCode, $attendant->ident_code);
        Assert::assertEquals($expectedMobile, $attendant->mobile);
        Assert::assertEquals($expectedWechatId, $attendant->wechat_id);
        Assert::assertEquals($expectedEmail, $attendant->email);
        Assert::assertEquals($expectedCoverUrl, $attendant->cover_url);
        Assert::assertEquals($expectedImagesUrl, $attendant->images_url);
        Assert::assertEquals($expectedMotto, $attendant->motto);
        Assert::assertEquals($expectedIntroduction, $attendant->introduction);
        Assert::assertEquals($expectedStatus, $attendant->status);
    }

    private function createStdClass($id, $identCode, $mobile, $status)
    {
        $attendant = new \stdClass();
        $attendant->id = $id;
        $attendant->name = 'meinv';
        $attendant->gender = 2;
        $attendant->age = 18;
        $attendant->height = 172;
        $attendant->speciality = '唱歌,舞蹈';
        $attendant->school = '成都艺术学院';
        $attendant->major = '舞蹈';
        $attendant->education = '本科';
        $attendant->graduation_time = '2015.6';
        $attendant->ident_code = $identCode; // 51119119950101000X
        $attendant->mobile = $mobile; // 13800010000
        $attendant->wechat_id = 'mv168_2011';
        $attendant->email = 'mv168_2011@qq.com';
        $attendant->cover_url = 'a';
        $attendant->image_url = ["a", "b"];
        $attendant->motto = '必须选上';
        $attendant->introduction = '成都艺术学院mv168';
        $attendant->status = $status;
        $attendant->created_at = '2011-09-01 00:00:00';
        $attendant->updated_at = '2011-09-01 00:00:00';
        return $attendant;
    }

    private function createData($id, $identCode, $mobile, $status)
    {
        $this->insert([
            'id'              => $id,
            'name'            => 'meinv',
            'gender'          => 2,
            'age'             => 18,
            'height'          => 172,
            'speciality'      => '唱歌,舞蹈',
            'school'          => '成都艺术学院',
            'major'           => '舞蹈',
            'education'       => '本科',
            'graduation_time' => '2015.6',
            'ident_code'      => $identCode, // 51119119950101000X
            'mobile'          => $mobile, // 13800010000
            'wechat_id'       => 'mv168_2011',
            'email'           => 'mv168_2011@qq.com',
            'cover_url'       => 'a',
            'images_url'      => '["a", "b"]',
            'motto'           => '必须选上',
            'introduction'    => '成都艺术学院mv168',
            'status'          => $status,
            'created_at'      => '2011-09-01 00:00:00',
            'updated_at'      => '2011-09-01 00:00:00',
        ]);
    }

    private function insert(array $values)
    {
        \DB::insert('insert into attendants (
                    id, name, gender, age, height, speciality,
                    school, major, education, graduation_time,
                    ident_code, mobile, wechat_id, email,
                    cover_url, images_url, motto, introduction, status,
                    created_at, updated_at)
                    values (
                    :id, :name, :gender, :age, :height, :speciality,
                    :school, :major, :education, :graduation_time,
                    :ident_code, :mobile, :wechat_id, :email,
                    :cover_url, :images_url, :motto, :introduction, :status,
                    :created_at, :updated_at)', $values);
    }

    private static function createCacheKey($page) {
        return md5(AttendantRepository::ATTENDANT_LIST_KEY) . '_' . $page;
    }

    /**
     * @return \Jihe\Repositories\AttendantRepository
     */
    private function getRepository()
    {
        return $this->app[\Jihe\Repositories\AttendantRepository::class];
    }
}
