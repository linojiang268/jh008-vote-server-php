<?php
namespace intg\Jihe\Controllers\Backstage;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Jihe\Models\XinNianVote as Vote;
use Jihe\Models\YoungSingle;
use Mockery;
use intg\Jihe\TestCase;
use intg\Jihe\RequestSignCheck;
use intg\Jihe\AuthDeviceCheck;
use \PHPUnit_Framework_Assert as Assert;

class XinNianAttendantControllerTest extends TestCase
{
    use DatabaseTransactions;
    use RequestSignCheck;
    use AuthDeviceCheck;

    const TABLE = 'young_singles';
    const ROUTE_PREFIX = 'xinnian';

    //=========================================
    //         list approved attendants
    //=========================================
    public function testSuccessfulListApprovedAttendants_NoAttendants()
    {
        $this->ajaxGet('/' . self::ROUTE_PREFIX . '/approved/list?page=1&size=2')
            ->seeJsonContains([
                'code' => 0,
            ]);

        $result = json_decode($this->response->getContent(), true);
        Assert::assertEquals(0, $result['pages']);
        Assert::assertEquals([], $result['attendants']);
    }

    public function testSuccessfulListApprovedAttendants_FirstPageAttendants()
    {
        for ($i = 1; $i <= 3; $i++) {
            factory(YoungSingle::class)->create([
                'id'     => $i,
                'status' => YoungSingle::STATUS_APPROVED,
            ]);
        }

        factory(YoungSingle::class)->create([
            'id'     => 4,
            'status' => YoungSingle::STATUS_PENDING,
        ]);

        $this->ajaxGet('/' . self::ROUTE_PREFIX . '/approved/list?page=1&size=2')
            ->seeJsonContains([
                'code' => 0,
            ]);

        $result = json_decode($this->response->getContent(), true);
        $this->assertEquals(2, $result['pages']);
        $this->assertCount(2, $result['attendants']);
    }

    public function testSuccessfulListApprovedAttendants_SecondPageAttendants()
    {
        for ($i = 1; $i <= 3; $i++) {
            factory(YoungSingle::class)->create([
                'id'     => $i,
                'status' => YoungSingle::STATUS_APPROVED,
            ]);
        }

        factory(YoungSingle::class)->create([
            'id'     => 4,
            'status' => YoungSingle::STATUS_PENDING,
        ]);

        $this->ajaxGet('/' . self::ROUTE_PREFIX . '/approved/list?page=2&size=2')
            ->seeJsonContains([
                'code' => 0,
            ]);

        $result = json_decode($this->response->getContent(), true);
        $this->assertEquals(2, $result['pages']);
        $this->assertCount(1, $result['attendants']);
    }

    //=========================================
    //         list pending attendants
    //=========================================
    public function testSuccessfulListPendingAttendants()
    {
        $this->ajaxGet('/' . self::ROUTE_PREFIX . '/pending/list?page=1&size=2')
            ->seeJsonContains([
                'code' => 0,
            ]);
    }

    public function testSuccessfulListPendingAttendants_FirstPageAttendants()
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

        $this->ajaxGet('/' . self::ROUTE_PREFIX . '/pending/list?page=1&size=2')
            ->seeJsonContains([
                'code' => 0,
            ]);

        $result = json_decode($this->response->getContent(), true);
        $this->assertEquals(2, $result['pages']);
        $this->assertCount(2, $result['attendants']);
        $attendants = $result['attendants'];
        for ($i = 0; $i < 2; $i++) {
            $this->assertEquals($i + 1, $attendants[$i]['id']);
        }
    }

    public function testSuccessfulListPendingAttendants_SecondPageAttendants()
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

        $this->ajaxGet('/' . self::ROUTE_PREFIX . '/pending/list?page=2&size=2')
            ->seeJsonContains([
                'code' => 0,
            ]);

        $result = json_decode($this->response->getContent(), true);
        $this->assertEquals(2, $result['pages']);
        $this->assertCount(1, $result['attendants']);
        $attendants = $result['attendants'];
        $this->assertEquals(3, $attendants[0]['id']);
    }

    //=========================================
    //             get attendants
    //=========================================
    public function testSuccessfulGetAttendants_NoAttendants()
    {
        $this->ajaxGet('/wap/' . self::ROUTE_PREFIX . '/approved/list?page=1')
            ->seeJsonContains([
                'code' => 0,
            ]);

        $result = json_decode($this->response->getContent(), true);
        Assert::assertEquals(0, $result['pages']);
        Assert::assertEquals([], $result['attendants']);
    }

    public function testSuccessfulGetApprovedAttendants()
    {
        for ($i = 1; $i <= 3; $i++) {
            factory(YoungSingle::class)->create([
                'id'     => $i,
                'status' => YoungSingle::STATUS_APPROVED,
            ]);
        }

        factory(YoungSingle::class)->create([
            'id'     => 4,
            'status' => YoungSingle::STATUS_PENDING,
        ]);

        $this->ajaxGet('/wap/' . self::ROUTE_PREFIX . '/approved/list?page=1')
            ->seeJsonContains([
                'code' => 0,
            ]);

        $result = json_decode($this->response->getContent(), true);
        $this->assertEquals(1, $result['pages']);
        $this->assertCount(3, $result['attendants']);
    }

    //=========================================
    //             get sort attendants
    //=========================================
    public function testSuccessfulGetSortAttendants()
    {
        $voters = [];
        for ($i = 1; $i <= 3; $i++) {
            array_push($voters, factory(YoungSingle::class)->create([
                'id'     => $i,
                'status' => YoungSingle::STATUS_APPROVED,
            ]));
        }

        for ($i = 0; $i < 3; $i++) {
            factory(Vote::class)->create([
                'voter'   => $voters[$i]->mobile,
                'type'    => Vote::TYPE_APP,
                'user_id' => $voters[$i]->id,
            ]);
        }

        factory(YoungSingle::class)->create([
            'id'     => 4,
            'status' => YoungSingle::STATUS_PENDING,
        ]);

        $this->ajaxGet('/wap/' . self::ROUTE_PREFIX . '/approved/sort/list?page=1')
            ->seeJsonContains([
                'code' => 0,
            ]);
        $result = json_decode($this->response->getContent(), true);
        $this->assertEquals(1, $result['pages']);
        $this->assertCount(3, $result['attendants']);
    }

    public function testSuccessfulGetSortAttendants_NoAttendants()
    {
        $this->ajaxGet('/wap/' . self::ROUTE_PREFIX . '/approved/sort/list?page=1')
            ->seeJsonContains([
                'code' => 0,
            ]);

        $result = json_decode($this->response->getContent(), true);
        Assert::assertEquals(0, $result['pages']);
        Assert::assertEquals([], $result['attendants']);
    }

    //=========================================
    //                detail
    //=========================================
    public function testSuccessfulDetail()
    {
        factory(YoungSingle::class)->create([
            'id'     => 1,
            'status' => YoungSingle::STATUS_APPROVED,
        ]);

        $this->ajaxGet('/wap/' . self::ROUTE_PREFIX . '/detail?attendant=1')
            ->seeJsonContains([
                'code' => 0,
            ]);

        $result = json_decode($this->response->getContent(), true);
        Assert::assertEquals(1, $result['id']);
        Assert::assertArrayHasKey('vote_count', $result);
        Assert::assertArrayHasKey('vote_sort', $result);
    }

    public function testFailDetail_ExistsPendingAttendant()
    {
        factory(YoungSingle::class)->create([
            'id'     => 1,
            'status' => YoungSingle::STATUS_PENDING,
        ]);

        $this->ajaxGet('/wap/' . self::ROUTE_PREFIX . '/detail?attendant=1')
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
        factory(YoungSingle::class)->create([
            'id'     => 1,
            'status' => YoungSingle::STATUS_PENDING,
        ]);

        $this->startSession();

        $this->ajaxPost('/' . self::ROUTE_PREFIX . '/approve', [
            '_token'    => csrf_token(),
            'attendant' => 1,
        ])->seeJsonContains([
            'code' => 0,
        ]);
    }

    public function testFailApprove_ExistsApprovedAttendant()
    {
        factory(YoungSingle::class)->create([
            'id'     => 1,
            'status' => YoungSingle::STATUS_APPROVED,
        ]);

        $this->startSession();

        $this->ajaxPost('/' . self::ROUTE_PREFIX . '/approve', [
            '_token'    => csrf_token(),
            'attendant' => 1,
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
            'attendant' => 1,
        ])->seeJsonContains([
            'code'    => 10000,
            'message' => '选手不存在',
        ]);
    }

    //=========================================
    //           remove Applicants
    //=========================================
    public function testSuccessfulRemoveApplicants()
    {
        factory(YoungSingle::class)->create([
            'id'     => 1,
            'status' => YoungSingle::STATUS_PENDING,
        ]);

        $this->ajaxPost('/' . self::ROUTE_PREFIX . '/remove', [
            '_token'    => csrf_token(),
            'attendant' => 1,
        ])->seeJsonContains([
            'code' => 0,
        ]);
    }

    public function testFailRemove_NoApplicant()
    {
        $this->ajaxPost('/' . self::ROUTE_PREFIX . '/remove', [
            '_token'    => csrf_token(),
            'attendant' => 1,
        ])->seeJsonContains([
            'code'    => 10000,
            'message' => '选手不存在',
        ]);
    }

    //=========================================
    //           remove Attendants
    //=========================================
    public function testSuccessfulRemoveAttendants()
    {
        factory(YoungSingle::class)->create([
            'id'     => 1,
            'status' => YoungSingle::STATUS_APPROVED,
        ]);

        $this->ajaxPost('/' . self::ROUTE_PREFIX . '/attendant/remove', [
            '_token'    => csrf_token(),
            'attendant' => 1,
        ])->seeJsonContains([
            'code' => 0,
        ]);
    }

    public function testFailRemove_NoAttendant()
    {
        $this->ajaxPost('/' . self::ROUTE_PREFIX . '/attendant/remove', [
            '_token'    => csrf_token(),
            'attendant' => 1,
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
            'name'          => 'meinv',
            'id_number'     => '511100222233336987',
            'gender'        => 2,
            'date_of_birth' => '1996年6月',
            'height'        => 165,
            'graduate_university' => '斯坦福',
            'degree'        => 3,
            'yearly_salary' => 99,
            'work_unit'     => '移动',
            'mobile'        => '15896321458',
            'images_url'    => ['a'],
            'talent'        => '唱歌，跳舞',
            'mate_choice'   => '长得帅',
        ])->seeJsonContains([
            'code' => 0,
        ]);

        $this->seeInDatabase(self::TABLE, [
            'name'          => 'meinv',
            'id_number'     => '511100222233336987',
            'gender'        => 2,
            'date_of_birth' => '1996年6月',
            'height'        => 165,
            'graduate_university' => '斯坦福',
            'degree'        => 3,
            'yearly_salary' => 99,
            'work_unit'     => '移动',
            'mobile'        => '15896321458',
            'cover_url'     => 'http://domain/image.png',
            'images_url'    => json_encode(['http://domain/image.png']),
            'talent'        => '唱歌，跳舞',
            'mate_choice'   => '长得帅',
            'status'        => YoungSingle::STATUS_PENDING,
        ]);
    }

    public function testSuccessfulEnroll_OnlyUseRequiredParam()
    {
        $this->startSession();

        $this->mockStorageService();

        $this->ajaxPost('/wap/' . self::ROUTE_PREFIX . '/enroll', [
            'name'          => 'meinv',
            'id_number'     => '511100222233336987',
            'gender'        => 2,
            'date_of_birth' => '1996年6月',
            'height'        => 165,
            'graduate_university' => '斯坦福',
            'degree'        => 3,
            'yearly_salary' => 99,
            'work_unit'     => '移动',
            'mobile'        => '15896321458',
            'images_url'    => ['a'],
        ])->seeJsonContains(['code' => 0]);

        $this->seeInDatabase(self::TABLE, [
            'name'          => 'meinv',
            'id_number'     => '511100222233336987',
            'gender'        => 2,
            'date_of_birth' => '1996年6月',
            'height'        => 165,
            'graduate_university' => '斯坦福',
            'degree'        => 3,
            'yearly_salary' => 99,
            'work_unit'     => '移动',
            'mobile'        => '15896321458',
            'cover_url'     => 'http://domain/image.png',
            'images_url'    => json_encode(['http://domain/image.png']),
            'talent'        => null,
            'mate_choice'   => null,
            'status'        => YoungSingle::STATUS_PENDING,
        ]);
    }

    public function testFailEnroll_MobileHasExists()
    {
        factory(YoungSingle::class)->create([
            'mobile' => '13800000001',
        ]);

        $this->startSession();

        $this->mockStorageService();

        $this->ajaxPost('/wap/' . self::ROUTE_PREFIX . '/enroll', [
            'name'          => 'meinv',
            'id_number'     => '511100222233336987',
            'gender'        => 2,
            'date_of_birth' => '1996年6月',
            'height'        => 165,
            'graduate_university' => '斯坦福',
            'degree'        => 3,
            'yearly_salary' => 99,
            'work_unit'     => '移动',
            'mobile'        => '13800000001',
            'images_url'    => ['a'],
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
            'name'          => 'meinv',
            'id_number'     => '511100222233336987',
            'gender'        => 2,
            'date_of_birth' => '1996年6月',
            'height'        => 165,
            'graduate_university' => '斯坦福',
            'degree'        => 3,
            'yearly_salary' => 99,
            'work_unit'     => '移动',
            'mobile'        => '13800000001',
            'images_url'    => ['a', 'b', 'c', 'd', 'e', 'f'],
        ])->seeJsonContains([
            'code'    => 10000,
            'message' => '上传照片超过5张',
        ]);
    }

    //=========================================
    //                vote
    //=========================================
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
            '_token' => csrf_token(),
            'voter'  => $openid,
            'type'   => Vote::TYPE_WX,
            'user'   => 48888,
            'page'   => 1,
        ])->seeJsonContains([
            'code' => 0,
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
            'id'      => 4,
            'voter'   => $openid,
            'type'    => Vote::TYPE_WX,
            'user_id' => 48888,
        ]);
        for ($i = 0; $i < 5; $i++) {
            factory(Vote::class)->create([
                'voter'   => $openid,
                'type'    => Vote::TYPE_WX,
                'user_id' => 48888,
            ]);
        }

        $this->ajaxPost('/wap/' . self::ROUTE_PREFIX . '/vote', [
            '_token' => csrf_token(),
            'voter'  => $openid,
            'type'   => Vote::TYPE_WX,
            'user'   => 48888,
            'page'   => 1,
        ])->seeJsonContains([
            'code' => 2,
        ]);
        $response = json_decode($this->response->getContent());
        self::assertEquals('对不起，您今日微信投票次数已满。', $response->message);
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
            '_token' => csrf_token(),
            'voter'  => 'o6_bmjrPTlm6_2sgVt7hMZOPfL2M1',
            'type'   => Vote::TYPE_WX,
            'user'   => 48888,
            'page'   => 1,
        ])->seeJsonContains([
            'code' => 10000,
        ]);
        $response = json_decode($this->response->getContent());
        self::assertEquals('未授权的非法操作!', $response->message);
    }

    private function createAllData()
    {
        factory(Vote::class)->create([
            'id'      => 1,
            'voter'   => '12345678912',
            'type'    => Vote::TYPE_APP,
            'user_id' => 48888,
        ]);
        factory(Vote::class)->create([
            'id'      => 5,
            'voter'   => '12345678912',
            'type'    => Vote::TYPE_APP,
            'user_id' => 48889,
        ]);
        factory(Vote::class)->create([
            'id'      => 6,
            'voter'   => '12345678912',
            'type'    => Vote::TYPE_WX,
            'user_id' => 48887,
        ]);
        factory(YoungSingle::class)->create([
            'id'     => 48888,
            'mobile' => '13800001111',
            'status' => YoungSingle::STATUS_PENDING,
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
