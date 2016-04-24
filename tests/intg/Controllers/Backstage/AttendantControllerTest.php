<?php
namespace intg\Jihe\Controllers\Backstage;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Jihe\Models\User;
use Jihe\Models\Vote;
use Mockery;
use intg\Jihe\TestCase;
use intg\Jihe\RequestSignCheck;
use intg\Jihe\AuthDeviceCheck;
use \PHPUnit_Framework_Assert as Assert;

class AttendantControllerTest extends TestCase
{
    use DatabaseTransactions;
    use RequestSignCheck;
    use AuthDeviceCheck;

    //=========================================
    //              valid cookie
    //=========================================
    public function testFailValidCookie_NotLoginInJihe()
    {
        $this->ajaxGet('/wap/attendant/cookie/valid');

        Assert::assertEquals('Fail: no cookie of jihe.', $this->response->getContent());
    }

    public function testSuccessfulValidCookie_CookieHasLogin()
    {
        $user = factory(\Jihe\Models\User::class)->create([
            'mobile'    => '13800001111',
            'nick_name' => 'yongchang',
        ]);

        $this->actingAs($user)->ajaxGet('/wap/attendant/cookie/valid');

        Assert::assertEquals('Success: Dear "yongchang"" of jihe.', $this->response->getContent());
    }

    //=========================================
    //         list approved attendants
    //=========================================
    public function testSuccessfulListApprovedAttendants_NoAttendants()
    {
        $this->ajaxGet('/attendant/approved/list?page=1&size=2')
             ->seeJsonContains([
                'code' => 0
        ]);

        $result = json_decode($this->response->getContent(), true);
        Assert::assertEquals(0, $result['pages']);
        Assert::assertEquals([], $result['attendants']);
    }

    public function testSuccessfulListApprovedAttendants_FirstPageAttendants()
    {
        for ($i = 10000; $i < 10003; $i++) {
            $this->insert([
                'id' => $i,
                'name'            => 'meinv',
                'gender'          => 2,
                'age'             => 18,
                'height'          => 172,
                'speciality'      => '唱歌,舞蹈',
                'school'          => '成都艺术学院',
                'major'           => '舞蹈',
                'education'       => '本科',
                'graduation_time' => '2015.6',
                'ident_code'      => '5111911995010' . $i,
                'mobile'          => '138000' . $i,
                'wechat_id'       => 'mv168_2011',
                'email'           => 'mv168_2011@qq.com',
                'cover_url'       => 'a',
                'images_url'      => '["a", "b"]',
                'motto'           => '必须选上',
                'introduction'    => '成都艺术学院mv168',
                'status'          => 1,
                'created_at'      => '2011-09-01 00:00:00',
                'updated_at'      => '2011-09-01 00:00:00',
            ]);
        }

        $this->insert([
            'id' => 10004,
            'name'            => 'meinv',
            'gender'          => 2,
            'age'             => 18,
            'height'          => 172,
            'speciality'      => '唱歌,舞蹈',
            'school'          => '成都艺术学院',
            'major'           => '舞蹈',
            'education'       => '本科',
            'graduation_time' => '2015.6',
            'ident_code'      => '511191199501010004',
            'mobile'          => '13800010004',
            'wechat_id'       => 'mv168_2011',
            'email'           => 'mv168_2011@qq.com',
            'cover_url'       => 'a',
            'images_url'      => '["a", "b"]',
            'motto'           => '必须选上',
            'introduction'    => '成都艺术学院mv168',
            'status'          => 0,
            'created_at'      => '2011-09-01 00:00:00',
            'updated_at'      => '2011-09-01 00:00:00',
        ]);

        $this->ajaxGet('/attendant/approved/list?page=1&size=2')
            ->seeJsonContains([
                'code' => 0
            ]);

        $result = json_decode($this->response->getContent(), true);
        $this->assertEquals(2, $result['pages']);
        $this->assertCount(2, $result['attendants']);
        $attendants = $result['attendants'];
        for ($i = 0; $i < 2; $i++) {
            $number = 10000 + $i;
            $this->assertAttendantEquals($number, 'meinv', 2, 18, 172, '唱歌,舞蹈',
                '成都艺术学院', '舞蹈', '本科', '2015.6',
                '5111911995010' . $number, '138000' . $number, 'mv168_2011', 'mv168_2011@qq.com',
                'a', ['a', 'b'], '必须选上', '成都艺术学院mv168',
                $attendants[$i]);
        }
    }

    public function testSuccessfulListApprovedAttendants_SecondPageAttendants()
    {
        for ($i = 10000; $i < 10003; $i++) {
            $this->insert([
                'id' => $i,
                'name'            => 'meinv',
                'gender'          => 2,
                'age'             => 18,
                'height'          => 172,
                'speciality'      => '唱歌,舞蹈',
                'school'          => '成都艺术学院',
                'major'           => '舞蹈',
                'education'       => '本科',
                'graduation_time' => '2015.6',
                'ident_code'      => '5111911995010' . $i,
                'mobile'          => '138000' . $i,
                'wechat_id'       => 'mv168_2011',
                'email'           => 'mv168_2011@qq.com',
                'cover_url'       => 'a',
                'images_url'      => '["a", "b"]',
                'motto'           => '必须选上',
                'introduction'    => '成都艺术学院mv168',
                'status'          => 1,
                'created_at'      => '2011-09-01 00:00:00',
                'updated_at'      => '2011-09-01 00:00:00',
            ]);
        }

        $this->insert([
            'id' => 10004,
            'name'            => 'meinv',
            'gender'          => 2,
            'age'             => 18,
            'height'          => 172,
            'speciality'      => '唱歌,舞蹈',
            'school'          => '成都艺术学院',
            'major'           => '舞蹈',
            'education'       => '本科',
            'graduation_time' => '2015.6',
            'ident_code'      => '511191199501010004',
            'mobile'          => '13800010004',
            'wechat_id'       => 'mv168_2011',
            'email'           => 'mv168_2011@qq.com',
            'cover_url'       => 'a',
            'images_url'      => '["a", "b"]',
            'motto'           => '必须选上',
            'introduction'    => '成都艺术学院mv168',
            'status'          => 0,
            'created_at'      => '2011-09-01 00:00:00',
            'updated_at'      => '2011-09-01 00:00:00',
        ]);

        $this->ajaxGet('/attendant/approved/list?page=2&size=2')
            ->seeJsonContains([
                'code' => 0
            ]);

        $result = json_decode($this->response->getContent(), true);
        $this->assertEquals(2, $result['pages']);
        $this->assertCount(1, $result['attendants']);
        $attendants = $result['attendants'];
        $this->assertAttendantEquals(10002, 'meinv', 2, 18, 172, '唱歌,舞蹈',
            '成都艺术学院', '舞蹈', '本科', '2015.6',
            '5111911995010' . 10002, '138000' . 10002, 'mv168_2011', 'mv168_2011@qq.com',
            'a', ['a', 'b'], '必须选上', '成都艺术学院mv168',
            $attendants[0]);
    }

    //=========================================
    //         list pending attendants
    //=========================================
    public function testSuccessfulListPendingAttendants()
    {
        $this->ajaxGet('/attendant/pending/list?page=1&size=2')
             ->seeJsonContains([
                'code' => 0
        ]);
    }

    public function testSuccessfulListPendingAttendants_FirstPageAttendants()
    {
        for ($i = 10000; $i < 10003; $i++) {
            $this->insert([
                'id' => $i,
                'name'            => 'meinv',
                'gender'          => 2,
                'age'             => 18,
                'height'          => 172,
                'speciality'      => '唱歌,舞蹈',
                'school'          => '成都艺术学院',
                'major'           => '舞蹈',
                'education'       => '本科',
                'graduation_time' => '2015.6',
                'ident_code'      => '5111911995010' . $i,
                'mobile'          => '138000' . $i,
                'wechat_id'       => 'mv168_2011',
                'email'           => 'mv168_2011@qq.com',
                'cover_url'       => 'a',
                'images_url'      => '["a", "b"]',
                'motto'           => '必须选上',
                'introduction'    => '成都艺术学院mv168',
                'status'          => 0,
                'created_at'      => '2011-09-01 00:00:00',
                'updated_at'      => '2011-09-01 00:00:00',
            ]);
        }

        $this->insert([
            'id' => 10004,
            'name'            => 'meinv',
            'gender'          => 2,
            'age'             => 18,
            'height'          => 172,
            'speciality'      => '唱歌,舞蹈',
            'school'          => '成都艺术学院',
            'major'           => '舞蹈',
            'education'       => '本科',
            'graduation_time' => '2015.6',
            'ident_code'      => '511191199501010004',
            'mobile'          => '13800010004',
            'wechat_id'       => 'mv168_2011',
            'email'           => 'mv168_2011@qq.com',
            'cover_url'       => 'a',
            'images_url'      => '["a", "b"]',
            'motto'           => '必须选上',
            'introduction'    => '成都艺术学院mv168',
            'status'          => 1,
            'created_at'      => '2011-09-01 00:00:00',
            'updated_at'      => '2011-09-01 00:00:00',
        ]);

        $this->ajaxGet('/attendant/pending/list?page=1&size=2')
            ->seeJsonContains([
                'code' => 0
            ]);

        $result = json_decode($this->response->getContent(), true);
        $this->assertEquals(2, $result['pages']);
        $this->assertCount(2, $result['attendants']);
        $attendants = $result['attendants'];
        for ($i = 0; $i < 2; $i++) {
            $number = 10000 + $i;
            $this->assertAttendantEquals($number, 'meinv', 2, 18, 172, '唱歌,舞蹈',
                '成都艺术学院', '舞蹈', '本科', '2015.6',
                '5111911995010' . $number, '138000' . $number, 'mv168_2011', 'mv168_2011@qq.com',
                'a', ['a', 'b'], '必须选上', '成都艺术学院mv168',
                $attendants[$i]);
        }
    }

    public function testSuccessfulListPendingAttendants_SecondPageAttendants()
    {
        for ($i = 10000; $i < 10003; $i++) {
            $this->insert([
                'id' => $i,
                'name'            => 'meinv',
                'gender'          => 2,
                'age'             => 18,
                'height'          => 172,
                'speciality'      => '唱歌,舞蹈',
                'school'          => '成都艺术学院',
                'major'           => '舞蹈',
                'education'       => '本科',
                'graduation_time' => '2015.6',
                'ident_code'      => '5111911995010' . $i,
                'mobile'          => '138000' . $i,
                'wechat_id'       => 'mv168_2011',
                'email'           => 'mv168_2011@qq.com',
                'cover_url'       => 'a',
                'images_url'      => '["a", "b"]',
                'motto'           => '必须选上',
                'introduction'    => '成都艺术学院mv168',
                'status'          => 0,
                'created_at'      => '2011-09-01 00:00:00',
                'updated_at'      => '2011-09-01 00:00:00',
            ]);
        }

        $this->insert([
            'id' => 10004,
            'name'            => 'meinv',
            'gender'          => 2,
            'age'             => 18,
            'height'          => 172,
            'speciality'      => '唱歌,舞蹈',
            'school'          => '成都艺术学院',
            'major'           => '舞蹈',
            'education'       => '本科',
            'graduation_time' => '2015.6',
            'ident_code'      => '511191199501010004',
            'mobile'          => '13800010004',
            'wechat_id'       => 'mv168_2011',
            'email'           => 'mv168_2011@qq.com',
            'cover_url'       => 'a',
            'images_url'      => '["a", "b"]',
            'motto'           => '必须选上',
            'introduction'    => '成都艺术学院mv168',
            'status'          => 1,
            'created_at'      => '2011-09-01 00:00:00',
            'updated_at'      => '2011-09-01 00:00:00',
        ]);

        $this->ajaxGet('/attendant/pending/list?page=2&size=2')
            ->seeJsonContains([
                'code' => 0
            ]);

        $result = json_decode($this->response->getContent(), true);
        $this->assertEquals(2, $result['pages']);
        $this->assertCount(1, $result['attendants']);
        $attendants = $result['attendants'];
        $this->assertAttendantEquals(10002, 'meinv', 2, 18, 172, '唱歌,舞蹈',
            '成都艺术学院', '舞蹈', '本科', '2015.6',
            '5111911995010' . 10002, '138000' . 10002, 'mv168_2011', 'mv168_2011@qq.com',
            'a', ['a', 'b'], '必须选上', '成都艺术学院mv168',
            $attendants[0]);
    }

    //=========================================
    //             get attendants
    //=========================================
    public function testSuccessfulGetAttendants_NoAttendants()
    {
        $this->ajaxGet('/wap/attendant/approved/list?page=1')
            ->seeJsonContains([
                'code' => 0
            ]);

        $result = json_decode($this->response->getContent(), true);
        Assert::assertEquals(0, $result['pages']);
        Assert::assertEquals([], $result['attendants']);
    }

    public function testSuccessfulGetApprovedAttendants()
    {
        for ($i = 10000; $i < 10003; $i++) {
            $this->insert([
                'id' => $i,
                'name'            => 'meinv',
                'gender'          => 2,
                'age'             => 18,
                'height'          => 172,
                'speciality'      => '唱歌,舞蹈',
                'school'          => '成都艺术学院',
                'major'           => '舞蹈',
                'education'       => '本科',
                'graduation_time' => '2015.6',
                'ident_code'      => '5111911995010' . $i,
                'mobile'          => '138000' . $i,
                'wechat_id'       => 'mv168_2011',
                'email'           => 'mv168_2011@qq.com',
                'cover_url'       => 'a',
                'images_url'      => '["a", "b"]',
                'motto'           => '必须选上',
                'introduction'    => '成都艺术学院mv168',
                'status'          => 1,
                'created_at'      => '2011-09-01 00:00:00',
                'updated_at'      => '2011-09-01 00:00:00',
            ]);
        }

        $this->insert([
            'id' => 10004,
            'name'            => 'meinv',
            'gender'          => 2,
            'age'             => 18,
            'height'          => 172,
            'speciality'      => '唱歌,舞蹈',
            'school'          => '成都艺术学院',
            'major'           => '舞蹈',
            'education'       => '本科',
            'graduation_time' => '2015.6',
            'ident_code'      => '511191199501010004',
            'mobile'          => '13800010004',
            'wechat_id'       => 'mv168_2011',
            'email'           => 'mv168_2011@qq.com',
            'cover_url'       => 'a',
            'images_url'      => '["a", "b"]',
            'motto'           => '必须选上',
            'introduction'    => '成都艺术学院mv168',
            'status'          => 0,
            'created_at'      => '2011-09-01 00:00:00',
            'updated_at'      => '2011-09-01 00:00:00',
        ]);

        $this->ajaxGet('/wap/attendant/approved/list?page=1')
            ->seeJsonContains([
                'code' => 0
            ]);

        $result = json_decode($this->response->getContent(), true);
        $this->assertEquals(1, $result['pages']);
        $this->assertCount(3, $result['attendants']);
    }

    //=========================================
    //                detail
    //=========================================
    public function testSuccessfulDetail()
    {
        $this->insert([
            'id'              => 10000,
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
            'cover_url'       => 'a',
            'images_url'      => '["a", "b"]',
            'motto'           => '必须选上',
            'introduction'    => '成都艺术学院mv168',
            'status'          => 1,
            'created_at'      => '2011-09-01 00:00:00',
            'updated_at'      => '2011-09-01 00:00:00',
        ]);

        $this->ajaxGet('/wap/attendant/detail?attendant=10000')
            ->seeJsonContains([
                'code' => 0
        ]);

        $result = json_decode($this->response->getContent(), true);
        Assert::assertEquals(10000, $result['id']);
        Assert::assertArrayHasKey('vote_count', $result);
        Assert::assertArrayHasKey('vote_sort', $result);
    }

    public function testFailDetail_ExistsPendingAttendant()
    {
        $this->insert([
            'id'              => 10000,
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
            'cover_url'       => 'a',
            'images_url'      => '["a", "b"]',
            'motto'           => '必须选上',
            'introduction'    => '成都艺术学院mv168',
            'status'          => 0,
            'created_at'      => '2011-09-01 00:00:00',
            'updated_at'      => '2011-09-01 00:00:00',
        ]);

        $this->ajaxGet('/wap/attendant/detail?attendant=10000')
            ->seeJsonContains([
                'code'    => 10000,
                'message' => '选手不存在',
            ]);
    }

    public function testFailDetail_NoAttendant()
    {
        $this->ajaxGet('/wap/attendant/detail?attendant=10000')
            ->seeJsonContains([
                'code'    => 10000,
                'message' => '选手不存在',
            ]);
    }

    //=========================================
    //                approve
    //=========================================
    public function testSuccessfulApprove()
    {
        $this->insert([
            'id'              => 10000,
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
            'cover_url'       => 'a',
            'images_url'      => '["a", "b"]',
            'motto'           => '必须选上',
            'introduction'    => '成都艺术学院mv168',
            'status'          => 0,
            'created_at'      => '2011-09-01 00:00:00',
            'updated_at'      => '2011-09-01 00:00:00',
        ]);

        $this->startSession();

        $this->ajaxPost('/attendant/approve', [
            '_token'    => csrf_token(),
            'attendant' => 10000,
        ])->seeJsonContains([
            'code' => 0
        ]);
    }

    public function testFailApprove_ExistsApprovedAttendant()
    {
        $this->insert([
            'id'              => 10000,
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
            'cover_url'       => 'a',
            'images_url'      => '["a", "b"]',
            'motto'           => '必须选上',
            'introduction'    => '成都艺术学院mv168',
            'status'          => 1,
            'created_at'      => '2011-09-01 00:00:00',
            'updated_at'      => '2011-09-01 00:00:00',
        ]);

        $this->startSession();

        $this->ajaxPost('/attendant/approve', [
            '_token'    => csrf_token(),
            'attendant' => 10000,
        ])->seeJsonContains([
            'code'    => 10000,
            'message' => '选手已通过审核',
        ]);
    }

    public function testFailApprove_NoAttendant()
    {
        $this->startSession();

        $this->ajaxPost('/attendant/approve', [
            '_token'    => csrf_token(),
            'attendant' => 10000,
        ])->seeJsonContains([
            'code'    => 10000,
            'message' => '选手不存在',
        ]);
    }

    //=========================================
    //                remove
    //=========================================
    public function testSuccessfulRemove()
    {
        $this->insert([
            'id'              => 10000,
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
            'cover_url'       => 'a',
            'images_url'      => '["a", "b"]',
            'motto'           => '必须选上',
            'introduction'    => '成都艺术学院mv168',
            'status'          => 0,
            'created_at'      => '2011-09-01 00:00:00',
            'updated_at'      => '2011-09-01 00:00:00',
        ]);

        $this->ajaxPost('/attendant/remove', [
            '_token'    => csrf_token(),
            'attendant' => 10000,
        ])->seeJsonContains([
            'code' => 0
        ]);
    }

    public function testFailRemove_NoAttendant()
    {
        $this->ajaxPost('/attendant/remove', [
            '_token'    => csrf_token(),
            'attendant' => 10000,
        ])->seeJsonContains([
            'code'    => 10000,
            'message' => '选手不存在',
        ]);
    }

    //=========================================
    //                enroll
    //=========================================
    public function testSuccessfulEnroll()
    {
        $this->startSession();

        $this->mockStorageService();

        $this->ajaxPost('/wap/attendant/enroll', [
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
            'images_url'      => ['a', 'b'],
            'motto'           => '必须选上',
            'introduction'    => '成都艺术学院mv168',
        ])->seeJsonContains([
            'code'    => 0,
        ]);

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
            'cover_url'       => 'http://domain/image.png',
            'images_url'      => json_encode(['http://domain/image.png', 'http://domain/image.png']),
            'motto'           => '必须选上',
            'introduction'    => '成都艺术学院mv168',
            'status'          => 0,
        ]);
    }

    public function testFailEnroll_MobileHasExists()
    {
        $this->insert([
            'id'              => 10000,
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
            'cover_url'       => 'a',
            'images_url'      => '["a", "b"]',
            'motto'           => '必须选上',
            'introduction'    => '成都艺术学院mv168',
            'status'          => 0,
            'created_at'      => '2011-09-01 00:00:00',
            'updated_at'      => '2011-09-01 00:00:00',
        ]);

        $this->startSession();

        $this->mockStorageService();

        $this->ajaxPost('/wap/attendant/enroll', [
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
            'images_url'      => ['a', 'b'],
            'motto'           => '必须选上',
            'introduction'    => '成都艺术学院mv168',
        ])->seeJsonContains([
            'code'    => 10000,
            'message' => '手机号已存在',
        ]);
    }

    public function testFailEnroll_IdentCodeHasExists()
    {
        $this->insert([
            'id'              => 10000,
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
            'cover_url'       => 'a',
            'images_url'      => '["a", "b"]',
            'motto'           => '必须选上',
            'introduction'    => '成都艺术学院mv168',
            'status'          => 0,
            'created_at'      => '2011-09-01 00:00:00',
            'updated_at'      => '2011-09-01 00:00:00',
        ]);

        $this->startSession();

        $this->mockStorageService();

        $this->ajaxPost('/wap/attendant/enroll', [
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
            'images_url'      => ['a', 'b'],
            'motto'           => '必须选上',
            'introduction'    => '成都艺术学院mv168',
        ])->seeJsonContains([
            'code'    => 10000,
            'message' => '身份证号已存在',
        ]);
    }

    public function testFailEnroll_TooManyPhotoes()
    {
        $this->startSession();

        $this->mockStorageService();

        $this->ajaxPost('/wap/attendant/enroll', [
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
            'images_url'      => ['a', 'b', 'c', 'd', 'e', 'f'],
            'motto'           => '必须选上',
            'introduction'    => '成都艺术学院mv168',
        ])->seeJsonContains([
            'code'    => 10000,
            'message' => '最多上传5张图片',
        ]);
    }

    private function assertAttendantEquals(
        $expectedId, $expectedName, $expectedGender, $expectedAge, $expectedHeight, $expectedSpeciality,
        $expectedSchool, $expectedMajor, $expectedEducation, $expectedGraduationTime,
        $expectedIdentCode, $expectedMobile, $expectedWechatId, $expectedEmail,
        $expectedCoverUrl, $expectedImagesUrl, $expectedMotto, $expectedIntroduction,
        $attendant)
    {
        Assert::assertEquals($expectedId, $attendant['id']);
        Assert::assertEquals($expectedName, $attendant['name']);
        Assert::assertEquals($expectedGender, $attendant['gender']);
        Assert::assertEquals($expectedAge, $attendant['age']);
        Assert::assertEquals($expectedHeight, $attendant['height']);
        Assert::assertEquals($expectedSpeciality, $attendant['speciality']);
        Assert::assertEquals($expectedSchool, $attendant['school']);
        Assert::assertEquals($expectedMajor, $attendant['major']);
        Assert::assertEquals($expectedEducation, $attendant['education']);
        Assert::assertEquals($expectedGraduationTime, $attendant['graduation_time']);
        Assert::assertEquals($expectedIdentCode, $attendant['ident_code']);
        Assert::assertEquals($expectedMobile, $attendant['mobile']);
        Assert::assertEquals($expectedWechatId, $attendant['wechat_id']);
        Assert::assertEquals($expectedEmail, $attendant['email']);
        //Assert::assertEquals($expectedCoverUrl, $attendant['cover_url']);
        //Assert::assertEquals($expectedImagesUrl, $attendant['images_url']);
        Assert::assertEquals($expectedMotto, $attendant['motto']);
        Assert::assertEquals($expectedIntroduction, $attendant['introduction']);
    }

    //=========================================
    //                vote
    //=========================================
    public function testSuccessfulVote_AppVote()
    {
        $this->createData();
        $this->startSession();
        $user = factory(User::class)->create([
            'id' => 48888,
            'mobile' => '12345678912',
        ]);
        $this->actingAs($user)
            ->ajaxPost('/wap/attendant/vote', [
            '_token'    => csrf_token(),
            'voter' => '12345678912',
            'type' => Vote::TYPE_APP,
            'user' => 48888,
            'page' => 1,
        ])->seeJsonContains([
            'code' => 0
        ]);
        $response = json_decode($this->response->getContent());
        self::assertEquals('投票成功', $response->message);
    }

    public function testSuccessfulVote_AppVoteError()
    {
        $this->createData();
        $this->startSession();
        $user = factory(User::class)->create([
            'id' => 48888,
            'mobile' => '12345678913',
        ]);
        $this->actingAs($user)
            ->ajaxPost('/wap/attendant/vote', [
                '_token'    => csrf_token(),
                'voter' => '12345678912',
                'type' => Vote::TYPE_APP,
                'user' => 48888,
                'page' => 1,
            ])->seeJsonContains([
                'code' => 10000
            ]);
        $response = json_decode($this->response->getContent());
        self::assertEquals('用户非法操作!', $response->message);
    }

    public function testSuccessfulVote_AppNotVote()
    {
        $this->startSession();
        $this->createData();
        factory(Vote::class)->create([
            'voter' => '12345678912',
            'type' => Vote::TYPE_APP,
            'user_id' => 48888,
        ]);
        factory(Vote::class)->create([
            'voter' => '12345678912',
            'type' => Vote::TYPE_APP,
            'user_id' => 48888,
        ]);
        factory(Vote::class)->create([
            'voter' => '12345678912',
            'type' => Vote::TYPE_APP,
            'user_id' => 48888,
        ]);
        $user = factory(User::class)->create([
            'id' => 48888,
            'mobile' => '12345678912',
        ]);
        $this->actingAs($user)
            ->ajaxPost('/wap/attendant/vote', [
                '_token'    => csrf_token(),
                'voter' => '12345678912',
                'type' => Vote::TYPE_APP,
                'user' => 48888,
                'page' => 1,
            ])->seeJsonContains([
                'code' => 1
            ]);
        $response = json_decode($this->response->getContent());
        self::assertEquals('对不起，您今日集合app投票次数已满，请尝试微信投票。', $response->message);
    }

    public function testSuccessfulVote_WXVote()
    {
        $this->createData();
        $openid = 'o6_bmjrPTlm6_2sgVt7hMZOPfL2M';
        $this->app['session']->put('wechat', ['openid' => $openid]);
        factory(\Jihe\Models\WechatToken::class)->create([
            'openid'            => $openid,
            'web_token_access'  => 'OLD_ACCESS_TOKEN',
            'web_token_refresh' => 'OLD_REFRESH_TOKEN',
        ]);
        $this->ajaxPost('/wap/attendant/vote', [
                '_token'    => csrf_token(),
                'voter' => $openid,
                'type' => Vote::TYPE_WX,
                'user' => 48888,
                'page' => 1,
            ])->seeJsonContains([
                'code' => 0
            ]);
        $response = json_decode($this->response->getContent());
        self::assertEquals('投票成功', $response->message);
    }

    public function testSuccessfulVote_WXNotVote()
    {
        $this->createData();
        $this->startSession();
        $openid = 'o6_bmjrPTlm6_2sgVt7hMZOPfL2M';
        $this->app['session']->put('wechat', ['openid' => $openid]);
        factory(\Jihe\Models\WechatToken::class)->create([
            'openid'            => $openid,
            'web_token_access'  => 'OLD_ACCESS_TOKEN',
            'web_token_refresh' => 'OLD_REFRESH_TOKEN',
        ]);
        factory(Vote::class)->create([
            'id'   => 4,
            'voter' => $openid,
            'type' => Vote::TYPE_WX,
            'user_id' => 48888,
        ]);
        $this->ajaxPost('/wap/attendant/vote', [
            '_token'    => csrf_token(),
            'voter' => $openid,
            'type' => Vote::TYPE_WX,
            'user' => 48888,
            'page' => 1,
        ])->seeJsonContains([
            'code' => 2
        ]);
        $response = json_decode($this->response->getContent());
        self::assertEquals('对不起，您今日微信投票次数已满，请尝试集合app投票。', $response->message);
    }

    public function testSuccessfulVote_WXVoteError()
    {
        $this->createData();
        $openid = 'o6_bmjrPTlm6_2sgVt7hMZOPfL2M';
        $this->app['session']->put('wechat', ['openid' => $openid]);
        factory(\Jihe\Models\WechatToken::class)->create([
            'openid'            => $openid,
            'web_token_access'  => 'OLD_ACCESS_TOKEN',
            'web_token_refresh' => 'OLD_REFRESH_TOKEN',
        ]);
        $this->ajaxPost('/wap/attendant/vote', [
            '_token'    => csrf_token(),
            'voter' => 'o6_bmjrPTlm6_2sgVt7hMZOPfL2M1',
            'type' => Vote::TYPE_WX,
            'user' => 48888,
            'page' => 1,
        ])->seeJsonContains([
            'code' => 10000
        ]);
        $response = json_decode($this->response->getContent());
        self::assertEquals('未授权的非法操作!', $response->message);
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

    private function createData()
    {
        factory(Vote::class)->create([
            'id'   => 1,
            'voter' => '12345678912',
            'type' => Vote::TYPE_APP,
            'user_id' => 48888,
        ]);
        factory(Vote::class)->create([
            'id'   => 5,
            'voter' => '12345678912',
            'type' => Vote::TYPE_APP,
            'user_id' => 48889,
        ]);
        factory(Vote::class)->create([
            'id'   => 6,
            'voter' => '12345678912',
            'type' => Vote::TYPE_WX,
            'user_id' => 48887,
        ]);
        $this->insert([
            'id'              => 48888,
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
            'cover_url'       => 'a',
            'images_url'      => "['a', 'b]",
            'motto'           => '必须选上',
            'introduction'    => '成都艺术学院mv168',
            'status'          => 0,
            'created_at'      => '2011-09-01 00:00:00',
            'updated_at'      => '2011-09-01 00:00:00',
        ]);

    }

    private function mockStorageService($return = 'http://domain/image.png')
    {
        $storageService = \Mockery::mock(\Jihe\Services\StorageService::class);
        $storageService->shouldReceive('isTmp')->withAnyArgs()->andReturn(true);
        $storageService->shouldReceive('storeAsFile')->withAnyArgs()->andReturn($return);
        $storageService->shouldReceive('storeAsImage')->withAnyArgs()->andReturn($return);
        $storageService->shouldReceive('remove')->withAnyArgs()->andReturn(false);
        $this->app [\Jihe\Services\StorageService::class] = $storageService;

        return $storageService;
    }
}
