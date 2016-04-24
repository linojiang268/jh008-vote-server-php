<?php
namespace intg\Jihe\Controllers\Backstage;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Jihe\Models\User;
use Jihe\Models\PlaneModelVote as Vote;
use Mockery;
use intg\Jihe\TestCase;
use intg\Jihe\RequestSignCheck;
use intg\Jihe\AuthDeviceCheck;
use \PHPUnit_Framework_Assert as Assert;

class PlaneModelControllerTest extends TestCase
{
    use DatabaseTransactions;
    use RequestSignCheck;
    use AuthDeviceCheck;

    //=========================================
    //         list approved attendants
    //=========================================
    public function testSuccessfulListApprovedAttendants_NoAttendants()
    {
        $this->ajaxGet('/planemodel/approved/list?page=1&size=2')
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
                'height'          => 172,
                'weight'          => 50,
                'bwh'             => '23,78,98',
                'shoe_size'       => 45.0,
                'mobile'          => '138000' . $i,
                'cover_url'       => 'a',
                'images_url'      => '["a", "b"]',
                'intro'           => '成都艺术学院mv168',
                'status'          => 1,
                'created_at'      => '2011-09-01 00:00:00',
                'updated_at'      => '2011-09-01 00:00:00',
            ]);
        }

        $this->insert([
            'id' => 10004,
            'name'            => 'meinv',
            'gender'          => 2,
            'height'          => 172,
            'weight'          => 50,
            'bwh'             => '23,78,98',
            'shoe_size'       => 45.0,
            'mobile'          => '13800010004',
            'cover_url'       => 'a',
            'images_url'      => '["a", "b"]',
            'intro'           => '成都艺术学院mv168',
            'status'          => 0,
            'created_at'      => '2011-09-01 00:00:00',
            'updated_at'      => '2011-09-01 00:00:00',
        ]);

        $this->ajaxGet('/planemodel/approved/list?page=1&size=2')
            ->seeJsonContains([
                'code' => 0
            ]);

        $result = json_decode($this->response->getContent(), true);
        $this->assertEquals(2, $result['pages']);
        $this->assertCount(2, $result['attendants']);
        $attendants = $result['attendants'];
        for ($i = 0; $i < 2; $i++) {
            $number = 10000 + $i;
            $this->assertAttendantEquals($number, 'meinv', 2, 172, 50, '23,78,98', 45.0,
                '138000' . $number, 'a', ['a', 'b'], '成都艺术学院mv168',
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
                'height'          => 172,
                'weight'          => 50,
                'bwh'             => '23,78,98',
                'shoe_size'       => 45.0,
                'mobile'          => '138000' . $i,
                'cover_url'       => 'a',
                'images_url'      => '["a", "b"]',
                'intro'           => '成都艺术学院mv168',
                'status'          => 1,
                'created_at'      => '2011-09-01 00:00:00',
                'updated_at'      => '2011-09-01 00:00:00',
            ]);
        }

        $this->insert([
            'id' => 10004,
            'name'            => 'meinv',
            'gender'          => 2,
            'height'          => 172,
            'weight'          => 50,
            'bwh'             => '23,78,98',
            'shoe_size'       => 45.0,
            'mobile'          => '13800010004',
            'cover_url'       => 'a',
            'images_url'      => '["a", "b"]',
            'intro'           => '成都艺术学院mv168',
            'status'          => 0,
            'created_at'      => '2011-09-01 00:00:00',
            'updated_at'      => '2011-09-01 00:00:00',
        ]);

        $this->ajaxGet('/planemodel/approved/list?page=2&size=2')
            ->seeJsonContains([
                'code' => 0
            ]);

        $result = json_decode($this->response->getContent(), true);
        $this->assertEquals(2, $result['pages']);
        $this->assertCount(1, $result['attendants']);
        $attendants = $result['attendants'];
        $this->assertAttendantEquals(10002, 'meinv', 2, 172, 50, '23,78,98', 45.0,
            '138000' . 10002, 'a', ['a', 'b'], '成都艺术学院mv168',
            $attendants[0]);
    }

    //=========================================
    //         list pending attendants
    //=========================================
    public function testSuccessfulListPendingAttendants()
    {
        $this->ajaxGet('/planemodel/pending/list?page=1&size=2')
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
                'height'          => 172,
                'weight'          => 50,
                'bwh'             => '23,78,98',
                'shoe_size'       => 45.0,
                'mobile'          => '138000' . $i,
                'cover_url'       => 'a',
                'images_url'      => '["a", "b"]',
                'intro'           => '成都艺术学院mv168',
                'status'          => 0,
                'created_at'      => '2011-09-01 00:00:00',
                'updated_at'      => '2011-09-01 00:00:00',
            ]);
        }

        $this->insert([
            'id' => 10004,
            'name'            => 'meinv',
            'gender'          => 2,
            'height'          => 172,
            'weight'          => 50,
            'bwh'             => '23,78,98',
            'shoe_size'       => 45.0,
            'mobile'          => '13800010004',
            'cover_url'       => 'a',
            'images_url'      => '["a", "b"]',
            'intro'           => '成都艺术学院mv168',
            'status'          => 1,
            'created_at'      => '2011-09-01 00:00:00',
            'updated_at'      => '2011-09-01 00:00:00',
        ]);

        $this->ajaxGet('/planemodel/pending/list?page=1&size=2')
            ->seeJsonContains([
                'code' => 0
            ]);

        $result = json_decode($this->response->getContent(), true);
        $this->assertEquals(2, $result['pages']);
        $this->assertCount(2, $result['attendants']);
        $attendants = $result['attendants'];
        for ($i = 0; $i < 2; $i++) {
            $number = 10000 + $i;
            $this->assertAttendantEquals($number, 'meinv', 2, 172, 50, '23,78,98', 45.0,
                '138000' . $number, 'a', ['a', 'b'], '成都艺术学院mv168',
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
                'height'          => 172,
                'weight'          => 50,
                'bwh'             => '23,78,98',
                'shoe_size'       => 45.0,
                'mobile'          => '138000' . $i,
                'cover_url'       => 'a',
                'images_url'      => '["a", "b"]',
                'intro'           => '成都艺术学院mv168',
                'status'          => 0,
                'created_at'      => '2011-09-01 00:00:00',
                'updated_at'      => '2011-09-01 00:00:00',
            ]);
        }

        $this->insert([
            'id' => 10004,
            'name'            => 'meinv',
            'gender'          => 2,
            'height'          => 172,
            'weight'          => 50,
            'bwh'             => '23,78,98',
            'shoe_size'       => 45.0,
            'mobile'          => '138000' . $i,
            'cover_url'       => 'a',
            'images_url'      => '["a", "b"]',
            'intro'           => '成都艺术学院mv168',
            'status'          => 1,
            'created_at'      => '2011-09-01 00:00:00',
            'updated_at'      => '2011-09-01 00:00:00',
        ]);

        $this->ajaxGet('/planemodel/pending/list?page=2&size=2')
            ->seeJsonContains([
                'code' => 0
            ]);

        $result = json_decode($this->response->getContent(), true);
        $this->assertEquals(2, $result['pages']);
        $this->assertCount(1, $result['attendants']);
        $attendants = $result['attendants'];
        $this->assertAttendantEquals(10002, 'meinv', 2, 172, 50, '23,78,98', 45.0,
            '138000' . 10002, 'a', ['a', 'b'], '成都艺术学院mv168',
            $attendants[0]);
    }

    //=========================================
    //             get attendants
    //=========================================
    public function testSuccessfulGetAttendants_NoAttendants()
    {
        $this->ajaxGet('/wap/planemodel/approved/list?page=1')
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
                'height'          => 172,
                'weight'          => 50,
                'bwh'             => '23,78,98',
                'shoe_size'       => 45.0,
                'mobile'          => '138000' . $i,
                'cover_url'       => 'a',
                'images_url'      => '["a", "b"]',
                'intro'           => '成都艺术学院mv168',
                'status'          => 1,
                'created_at'      => '2011-09-01 00:00:00',
                'updated_at'      => '2011-09-01 00:00:00',
            ]);
        }

        $this->insert([
            'id' => 10004,
            'name'            => 'meinv',
            'gender'          => 2,
            'height'          => 172,
            'weight'          => 50,
            'bwh'             => '23,78,98',
            'shoe_size'       => 45.0,
            'mobile'          => '138000' . $i,
            'cover_url'       => 'a',
            'images_url'      => '["a", "b"]',
            'intro'           => '成都艺术学院mv168',
            'status'          => 0,
            'created_at'      => '2011-09-01 00:00:00',
            'updated_at'      => '2011-09-01 00:00:00',
        ]);

        $this->ajaxGet('/wap/planemodel/approved/list?page=1')
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
            'height'          => 172,
            'weight'          => 50,
            'bwh'             => '23,78,98',
            'shoe_size'       => 45.0,
            'mobile'          => '13800000001',
            'cover_url'       => 'a',
            'images_url'      => '["a", "b"]',
            'intro'           => '成都艺术学院mv168',
            'status'          => 1,
            'created_at'      => '2011-09-01 00:00:00',
            'updated_at'      => '2011-09-01 00:00:00',
        ]);

        $this->ajaxGet('/wap/planemodel/detail?attendant=10000')
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
            'height'          => 172,
            'weight'          => 50,
            'bwh'             => '23,78,98',
            'shoe_size'       => 45.0,
            'mobile'          => '13800000001',
            'cover_url'       => 'a',
            'images_url'      => '["a", "b"]',
            'intro'           => '成都艺术学院mv168',
            'status'          => 0,
            'created_at'      => '2011-09-01 00:00:00',
            'updated_at'      => '2011-09-01 00:00:00',
        ]);

        $this->ajaxGet('/wap/planemodel/detail?attendant=10000')
            ->seeJsonContains([
                'code'    => 10000,
                'message' => '选手不存在',
            ]);
    }

    public function testFailDetail_NoAttendant()
    {
        $this->ajaxGet('/wap/planemodel/detail?attendant=10000')
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
            'height'          => 172,
            'weight'          => 50,
            'bwh'             => '23,78,98',
            'shoe_size'       => 45.0,
            'mobile'          => '13800001111',
            'cover_url'       => 'a',
            'images_url'      => '["a", "b"]',
            'intro'           => '成都艺术学院mv168',
            'status'          => 0,
            'created_at'      => '2011-09-01 00:00:00',
            'updated_at'      => '2011-09-01 00:00:00',
        ]);

        $this->startSession();

        $this->ajaxPost('/planemodel/approve', [
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
            'height'          => 172,
            'weight'          => 50,
            'bwh'             => '23,78,98',
            'shoe_size'       => 45.0,
            'mobile'          => '13800001111',
            'cover_url'       => 'a',
            'images_url'      => '["a", "b"]',
            'intro'           => '成都艺术学院mv168',
            'status'          => 1,
            'created_at'      => '2011-09-01 00:00:00',
            'updated_at'      => '2011-09-01 00:00:00',
        ]);

        $this->startSession();

        $this->ajaxPost('/planemodel/approve', [
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

        $this->ajaxPost('/planemodel/approve', [
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
            'height'          => 172,
            'weight'          => 50,
            'bwh'             => '23,78,98',
            'shoe_size'       => 45.0,
            'mobile'          => '13800001111',
            'cover_url'       => 'a',
            'images_url'      => '["a", "b"]',
            'intro'           => '成都艺术学院mv168',
            'status'          => 0,
            'created_at'      => '2011-09-01 00:00:00',
            'updated_at'      => '2011-09-01 00:00:00',
        ]);

        $this->ajaxPost('/planemodel/remove', [
            '_token'    => csrf_token(),
            'attendant' => 10000,
        ])->seeJsonContains([
            'code' => 0
        ]);
    }

    public function testFailRemove_NoAttendant()
    {
        $this->ajaxPost('/planemodel/remove', [
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

        $this->ajaxPost('/wap/planemodel/enroll', [
            'name'            => 'meinv',
            'gender'          => 2,
            'height'          => 172,
            'weight'          => 50,
            'bwh'             => '23,78,98',
            'shoe_size'       => 45.0,
            'mobile'          => '13800001111',
            'images_url'      => ['a', 'b', 'c', 'd', 'e'],
            'intro'           => '成都艺术学院mv168',
        ])->seeJsonContains([
            'code'    => 0,
        ]);

        $this->seeInDatabase('plane_model_attendants', [
            'name'            => 'meinv',
            'gender'          => 2,
            'height'          => 172,
            'weight'          => 50,
            'bwh'             => '23,78,98',
            'shoe_size'       => 45.0,
            'mobile'          => '13800001111',
            'cover_url'       => 'http://domain/image.png',
            'images_url'      => json_encode([
                                                 'http://domain/image.png',
                                                 'http://domain/image.png',
                                                 'http://domain/image.png',
                                                 'http://domain/image.png',
                                                 'http://domain/image.png',
                                             ]),
            'intro'           => '成都艺术学院mv168',
            'status'          => 0,
        ]);
    }

    public function testFailEnroll_MobileHasExists()
    {
        $this->insert([
            'id'              => 10000,
            'name'            => 'meinv',
            'gender'          => 2,
            'height'          => 172,
            'weight'          => 50,
            'bwh'             => '23,78,98',
            'shoe_size'       => 45.0,
            'mobile'          => '13800001111',
            'cover_url'       => 'a',
            'images_url'      => '["a", "b"]',
            'intro'           => '成都艺术学院mv168',
            'status'          => 0,
            'created_at'      => '2011-09-01 00:00:00',
            'updated_at'      => '2011-09-01 00:00:00',
        ]);

        $this->startSession();

        $this->mockStorageService();

        $this->ajaxPost('/wap/planemodel/enroll', [
            'name'            => 'meinv',
            'gender'          => 2,
            'height'          => 172,
            'weight'          => 50,
            'bwh'             => '23,78,98',
            'shoe_size'       => 45.0,
            'mobile'          => '13800001111',
            'images_url'      => ['a', 'b', 'c', 'd', 'e'],
            'intro'           => '成都艺术学院mv168',
        ])->seeJsonContains([
            'code'    => 10000,
            'message' => '手机号已存在',
        ]);
    }

    public function testFailEnroll_TooManyPhotoes()
    {
        $this->startSession();

        $this->mockStorageService();

        $this->ajaxPost('/wap/planemodel/enroll', [
            'name'            => 'meinv',
            'gender'          => 2,
            'height'          => 172,
            'weight'          => 50,
            'bwh'             => '23,78,98',
            'shoe_size'       => 45.0,
            'mobile'          => '13800001110',
            'images_url'      => ['a', 'b', 'c', 'd', 'e', 'f'],
            'intro'           => '成都艺术学院mv168',
        ])->seeJsonContains([
            'code'    => 10000,
            'message' => '上传照片超过5张',
        ]);
    }

    private function assertAttendantEquals(
        $expectedId, $expectedName, $expectedGender, $expectedHeight, $expectedWeight,
        $expectedBwh, $expectedShoeSize, $expectedMobile,
        $expectedCoverUrl, $expectedImagesUrl, $expectedIntro,
        $attendant)
    {
        Assert::assertEquals($expectedId, $attendant['id']);
        Assert::assertEquals($expectedName, $attendant['name']);
        //Assert::assertEquals($expectedGender, $attendant['gender']);
        Assert::assertEquals($expectedHeight, $attendant['height']);
        Assert::assertEquals($expectedWeight, $attendant['weight']);
        Assert::assertEquals($expectedBwh, $attendant['bwh']);
        Assert::assertEquals($expectedShoeSize, $attendant['shoe_size']);
        Assert::assertEquals($expectedMobile, $attendant['mobile']);
        //Assert::assertEquals($expectedCoverUrl, $attendant['cover_url']);
        //Assert::assertEquals($expectedImagesUrl, $attendant['images_url']);
        Assert::assertEquals($expectedIntro, $attendant['intro']);
    }

    //=========================================
    //                vote
    //=========================================
    public function testSuccessfulVote_AppVote()
    {
        $this->createAllData();
        $this->startSession();
        $user = factory(User::class)->create([
            'id' => 48888,
            'mobile' => '12345678912',
        ]);
        $this->actingAs($user)
            ->ajaxPost('/wap/planemodel/vote', [
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
        $this->createAllData();
        $this->startSession();
        $user = factory(User::class)->create([
            'id' => 48888,
            'mobile' => '12345678913',
        ]);
        $this->actingAs($user)
            ->ajaxPost('/wap/planemodel/vote', [
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
        $this->createAllData();
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
            ->ajaxPost('/wap/planemodel/vote', [
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
        $this->createAllData();
        $openid = 'o6_bmjrPTlm6_2sgVt7hMZOPfL2M';
        $this->app['session']->put('wechat', ['openid' => $openid]);
        factory(\Jihe\Models\WechatToken::class)->create([
            'openid'            => $openid,
            'web_token_access'  => 'OLD_ACCESS_TOKEN',
            'web_token_refresh' => 'OLD_REFRESH_TOKEN',
        ]);
        $this->ajaxPost('/wap/planemodel/vote', [
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
        $this->createAllData();
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
        $this->ajaxPost('/wap/planemodel/vote', [
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
        $this->createAllData();
        $openid = 'o6_bmjrPTlm6_2sgVt7hMZOPfL2M';
        $this->app['session']->put('wechat', ['openid' => $openid]);
        factory(\Jihe\Models\WechatToken::class)->create([
            'openid'            => $openid,
            'web_token_access'  => 'OLD_ACCESS_TOKEN',
            'web_token_refresh' => 'OLD_REFRESH_TOKEN',
        ]);
        $this->ajaxPost('/wap/planemodel/vote', [
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

    private function createAllData()
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
            'height'          => 172,
            'weight'          => 50,
            'bwh'             => '23,78,98',
            'shoe_size'       => 45.0,
            'mobile'          => '13800001111',
            'cover_url'       => 'a',
            'images_url'      => "['a', 'b]",
            'intro'           => '成都艺术学院mv168',
            'status'          => 0,
            'created_at'      => '2011-09-01 00:00:00',
            'updated_at'      => '2011-09-01 00:00:00',
        ]);

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
            'shoe_size'       => '45',
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
