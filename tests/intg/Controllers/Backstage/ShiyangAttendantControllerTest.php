<?php
namespace intg\Jihe\Controllers\Backstage;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Jihe\Models\User;
use Jihe\Models\ShiYangVote as Vote;
use Mockery;
use intg\Jihe\TestCase;
use intg\Jihe\RequestSignCheck;
use intg\Jihe\AuthDeviceCheck;
use \PHPUnit_Framework_Assert as Assert;

class ShiyangAttendantControllerTest extends TestCase
{
    use DatabaseTransactions;
    use RequestSignCheck;
    use AuthDeviceCheck;

    const TABLE = 'shiyang_attendants';
    const ROUTE_PREFIX = 'shiyang';

    //=========================================
    //         list approved attendants
    //=========================================
    public function testSuccessfulListApprovedAttendants_NoAttendants()
    {
        $this->ajaxGet('/' . self::ROUTE_PREFIX . '/approved/list?page=1&size=2')
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
            $this->createData($i, '138000' . $i, 1);
        }

        $this->createData(10004, '13800010004', 0);

        $this->ajaxGet('/' . self::ROUTE_PREFIX . '/approved/list?page=1&size=2')
            ->seeJsonContains([
                'code' => 0
            ]);

        $result = json_decode($this->response->getContent(), true);
        $this->assertEquals(2, $result['pages']);
        $this->assertCount(2, $result['attendants']);
        $attendants = $result['attendants'];
        for ($i = 0; $i < 2; $i++) {
            $number = 10000 + $i;
            $this->assertAttendantEquals($number, '138000' . $number,
                $attendants[$i]);
        }
    }

    public function testSuccessfulListApprovedAttendants_SecondPageAttendants()
    {
        for ($i = 10000; $i < 10003; $i++) {
            $this->createData($i, '138000' . $i, 1);
        }

        $this->createData(10004, '13800010004', 0);

        $this->ajaxGet('/' . self::ROUTE_PREFIX . '/approved/list?page=2&size=2')
            ->seeJsonContains([
                'code' => 0
            ]);

        $result = json_decode($this->response->getContent(), true);
        $this->assertEquals(2, $result['pages']);
        $this->assertCount(1, $result['attendants']);
        $attendants = $result['attendants'];
        $this->assertAttendantEquals(10002, '138000' . 10002,
            $attendants[0]);
    }

    //=========================================
    //         list pending attendants
    //=========================================
    public function testSuccessfulListPendingAttendants()
    {
        $this->ajaxGet('/' . self::ROUTE_PREFIX . '/pending/list?page=1&size=2')
             ->seeJsonContains([
                'code' => 0
        ]);
    }

    public function testSuccessfulListPendingAttendants_FirstPageAttendants()
    {
        for ($i = 10000; $i < 10003; $i++) {
            $this->createData($i, '138000' . $i, 0);
        }

        $this->createData(10004, '13800010004', 1);

        $this->ajaxGet('/' . self::ROUTE_PREFIX . '/pending/list?page=1&size=2')
            ->seeJsonContains([
                'code' => 0
            ]);

        $result = json_decode($this->response->getContent(), true);
        $this->assertEquals(2, $result['pages']);
        $this->assertCount(2, $result['attendants']);
        $attendants = $result['attendants'];
        for ($i = 0; $i < 2; $i++) {
            $number = 10000 + $i;
            $this->assertAttendantEquals($number, '138000' . $number,
                $attendants[$i]);
        }
    }

    public function testSuccessfulListPendingAttendants_SecondPageAttendants()
    {
        for ($i = 10000; $i < 10003; $i++) {
            $this->createData($i, '138000' . $i, 0);
        }

        $this->createData(10004, '13800010004', 1);

        $this->ajaxGet('/' . self::ROUTE_PREFIX . '/pending/list?page=2&size=2')
            ->seeJsonContains([
                'code' => 0
            ]);

        $result = json_decode($this->response->getContent(), true);
        $this->assertEquals(2, $result['pages']);
        $this->assertCount(1, $result['attendants']);
        $attendants = $result['attendants'];
        $this->assertAttendantEquals(10002, '138000' . 10002,
            $attendants[0]);
    }

    //=========================================
    //             get attendants
    //=========================================
    public function testSuccessfulGetAttendants_NoAttendants()
    {
        $this->ajaxGet('/wap/' . self::ROUTE_PREFIX . '/approved/list?page=1')
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
            $this->createData($i, '138000' . $i, 1);
        }

        $this->createData(10004, '13800010004', 0);

        $this->ajaxGet('/wap/' . self::ROUTE_PREFIX . '/approved/list?page=1')
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
        $this->createData(10000, '13800000001', 1);

        $this->ajaxGet('/wap/' . self::ROUTE_PREFIX . '/detail?attendant=10000')
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
        $this->createData(10000, '13800000001', 0);

        $this->ajaxGet('/wap/' . self::ROUTE_PREFIX . '/detail?attendant=10000')
            ->seeJsonContains([
                'code'    => 10000,
                'message' => '选手不存在',
            ]);
    }

    public function testFailDetail_NoAttendant()
    {
        $this->ajaxGet('/wap/' . self::ROUTE_PREFIX . '/detail?attendant=10000')
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
        $this->createData(10000, '13800001111', 0);

        $this->startSession();

        $this->ajaxPost('/' . self::ROUTE_PREFIX . '/approve', [
            '_token'    => csrf_token(),
            'attendant' => 10000,
        ])->seeJsonContains([
            'code' => 0
        ]);
    }

    public function testFailApprove_ExistsApprovedAttendant()
    {
        $this->createData(10000, '13800001111', 1);

        $this->startSession();

        $this->ajaxPost('/' . self::ROUTE_PREFIX . '/approve', [
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

        $this->ajaxPost('/' . self::ROUTE_PREFIX . '/approve', [
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
        $this->createData(10000, '13800001111', 0);

        $this->ajaxPost('/' . self::ROUTE_PREFIX . '/remove', [
            '_token'    => csrf_token(),
            'attendant' => 10000,
        ])->seeJsonContains([
            'code' => 0
        ]);
    }

    public function testFailRemove_NoAttendant()
    {
        $this->ajaxPost('/' . self::ROUTE_PREFIX . '/remove', [
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

        $this->ajaxPost('/wap/' . self::ROUTE_PREFIX . '/enroll', [
            'name'            => 'meinv',
            'gender'          => 2,
            'age'             => 23,
            'mobile'          => '13800001111',
            'work_unit'       => '俱乐部',
            'position'        => '经理',
            'yearly_salary'   => '7万',
            'wechat_id'       => 'mv_ch',
            'images_url'      => ['a'],
            'talent'          => '舞蹈',
            'guest_apply'     => 0,
            'motto'           => '我要出位',
            'mate_choice'     => '穷的丑的',
        ])->seeJsonContains([
            'code'    => 0,
        ]);

        $this->seeInDatabase(self::TABLE, [
            'name'            => 'meinv',
            'gender'          => 2,
            'age'             => 23,
            'mobile'          => '13800001111',
            'work_unit'       => '俱乐部',
            'position'        => '经理',
            'yearly_salary'   => '7万',
            'wechat_id'       => 'mv_ch',
            'cover_url'       => 'http://domain/image.png',
            'images_url'      => json_encode(['http://domain/image.png']),
            'talent'          => '舞蹈',
            'guest_apply'     => 0,
            'motto'           => '我要出位',
            'mate_choice'     => '穷的丑的',
            'status'          => 0,
        ]);
    }

    public function testSuccessfulEnroll_OnlyUseRequiredParam()
    {
        $this->startSession();

        $this->mockStorageService();

        $this->ajaxPost('/wap/' . self::ROUTE_PREFIX . '/enroll', [
            'name'            => 'meinv',
            'gender'          => 2,
            'age'             => 23,
            'mobile'          => '13800001111',
            'work_unit'       => '俱乐部',
            'position'        => '经理',
            'yearly_salary'   => '7万',
            'wechat_id'       => 'mv_ch',
            'images_url'      => ['a'],
        ])->seeJsonContains(['code' => 0]);

        $this->seeInDatabase(self::TABLE, [
            'name'            => 'meinv',
            'gender'          => 2,
            'age'             => 23,
            'mobile'          => '13800001111',
            'work_unit'       => '俱乐部',
            'position'        => '经理',
            'yearly_salary'   => '7万',
            'wechat_id'       => 'mv_ch',
            'cover_url'       => 'http://domain/image.png',
            'images_url'      => json_encode(['http://domain/image.png']),
            'talent'          => null,
            'guest_apply'     => 0,
            'motto'           => null,
            'mate_choice'     => null,
            'status'          => 0,
        ]);
    }

    public function testFailEnroll_MobileHasExists()
    {
        $this->createData(10000, '13800001111', 0);

        $this->startSession();

        $this->mockStorageService();

        $this->ajaxPost('/wap/' . self::ROUTE_PREFIX . '/enroll', [
            'name'            => 'meinv',
            'gender'          => 2,
            'age'             => 23,
            'mobile'          => '13800001111',
            'work_unit'       => '俱乐部',
            'position'        => '经理',
            'yearly_salary'   => '7万',
            'wechat_id'       => 'mv_ch',
            'images_url'      => ['a'],
            'talent'          => '舞蹈',
            'guest_apply'     => 0,
            'motto'           => '我要出位',
            'mate_choice'     => '穷的丑的',
        ])->seeJsonContains([
            'code'    => 10000,
            'message' => '手机号已存在',
        ]);
    }

    public function testFailEnroll_TooManyPhotoes()
    {
        $this->startSession();

        $this->mockStorageService();

        $this->ajaxPost('/wap/' . self::ROUTE_PREFIX . '/enroll', [
            'name'            => 'meinv',
            'gender'          => 2,
            'age'             => 23,
            'mobile'          => '13800001111',
            'work_unit'       => '俱乐部',
            'position'        => '经理',
            'yearly_salary'   => '7万',
            'wechat_id'       => 'mv_ch',
            'images_url'      => ['a', 'b', 'c', 'd', 'e', 'f'],
            'talent'          => '舞蹈',
            'guest_apply'     => 0,
            'motto'           => '我要出位',
            'mate_choice'     => '穷的丑的',
        ])->seeJsonContains([
            'code'    => 10000,
            'message' => '上传照片超过5张',
        ]);
    }

    private function assertAttendantEquals(
        $expectedId, $expectedMobile, $attendant)
    {
        Assert::assertEquals($expectedId, $attendant['id']);
        Assert::assertEquals($expectedMobile, $attendant['mobile']);
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
            ->ajaxPost('/wap/' . self::ROUTE_PREFIX . '/vote', [
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
            ->ajaxPost('/wap/' . self::ROUTE_PREFIX . '/vote', [
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
            ->ajaxPost('/wap/' . self::ROUTE_PREFIX . '/vote', [
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
        $this->ajaxPost('/wap/' . self::ROUTE_PREFIX . '/vote', [
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
        $this->ajaxPost('/wap/' . self::ROUTE_PREFIX . '/vote', [
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
        $this->ajaxPost('/wap/' . self::ROUTE_PREFIX . '/vote', [
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
        $this->createData(48888, '13800001111', 0);
    }

    private function createData($id, $mobile, $status)
    {
        $this->insert([
            'id'              => $id,
            'name'            => 'meinv',
            'gender'          => 2,
            'age'             => 24,
            'mobile'          => $mobile, // 13800010000
            'work_unit'       => '俱乐部',
            'position'        => '经理',
            'yearly_salary'   => '7万',
            'wechat_id'       => 'mv_ch',
            'cover_url'       => 'a',
            'images_url'      => '["a", "b"]',
            'talent'          => '舞蹈',
            'guest_apply'     => 0,
            'motto'           => '我要出位',
            'mate_choice'     => '穷的丑的',
            'status'          => $status,
            'created_at'      => '2011-09-01 00:00:00',
            'updated_at'      => '2011-09-01 00:00:00',
        ]);
    }

    private function insert(array $values)
    {
        \DB::insert('insert into ' . self::TABLE . ' (
                    id, name, gender, age, mobile,
                    work_unit, position, yearly_salary, wechat_id,
                    cover_url, images_url, talent,
                    guest_apply, motto, mate_choice, status,
                    created_at, updated_at)
                    values (
                    :id, :name, :gender, :age, :mobile,
                    :work_unit, :position, :yearly_salary, :wechat_id,
                    :cover_url, :images_url, :talent,
                    :guest_apply, :motto, :mate_choice, :status,
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
