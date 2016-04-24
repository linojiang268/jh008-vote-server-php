<?php
namespace intg\Jihe\Controllers\Admin;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use intg\Jihe\TestCase;

class AccountantControllerTest extends TestCase
{
    use DatabaseTransactions;
    
    //================================
    //          listIncome
    //================================
    public function testListIncomeWithoutFilter()
    {
        $user = $this->prepareUser('admin');
        $this->prepareIncomeData();
        $this->startSession();
        $this->actingAs($user, 'extended-eloquent-admin')
             ->ajaxGet('admin/accountant/income/list')->seeJsonContains(['code' => 0]);

        $response = json_decode($this->response->getContent());
        $this->assertObjectHasAttribute('incomes', $response);
        $this->assertCount(3, $response->incomes);
        $this->assertEquals(1, $response->incomes[0]->id);
        $this->assertEquals('成都跑团', $response->incomes[0]->team_name);
        $this->assertEquals('成都夜跑', $response->incomes[0]->activity_title);
        $this->assertEquals(20, $response->incomes[0]->transfered_fee);
        $this->assertEquals(20, $response->incomes[0]->detail[0]->fee);
        $this->assertEquals('2015-08-05 14:00:00', 
            $response->incomes[0]->detail[0]->op_time);
        $this->assertEquals('成都慢生活', $response->incomes[1]->activity_title);
    }

    public function testListIncomeWithStatusFilter()
    {
        $user = $this->prepareUser('admin');
        $this->prepareIncomeData();
        $this->startSession();
        $status = \Jihe\Entities\ActivityEnrollIncome::STATUS_TRANSFERING;
        $this->actingAs($user, 'extended-eloquent-admin')
             ->ajaxGet('admin/accountant/income/list?status=' . $status)->seeJsonContains(['code' => 0]);

        $response = json_decode($this->response->getContent());
        $this->assertObjectHasAttribute('incomes', $response);
        $this->assertCount(1, $response->incomes);
        $this->assertEquals(1, $response->incomes[0]->id);
        $this->assertEquals('成都跑团', $response->incomes[0]->team_name);
        $this->assertEquals('成都夜跑', $response->incomes[0]->activity_title);
        $this->assertEquals(20, $response->incomes[0]->transfered_fee);
        $this->assertEquals(20, $response->incomes[0]->detail[0]->fee);
        $this->assertEquals('2015-08-05 14:00:00', 
            $response->incomes[0]->detail[0]->op_time);
    }

    public function testListIncomeWithTimeFilter()
    {
        $user = $this->prepareUser('admin');
        $this->prepareIncomeData();
        $this->startSession();
        $status = \Jihe\Entities\ActivityEnrollIncome::STATUS_TRANSFERING;
        $query = [
            'begin_time'    => date('Y-m-d H:i:s', strtotime('2015-08-01 00:00:00')),
            'end_time'      => date('Y-m-d H:i:s', strtotime('2015-08-02 10:00:00')),
        ];
        $url = 'admin/accountant/income/list' . '?' . http_build_query($query);
        $this->actingAs($user, 'extended-eloquent-admin')
             ->ajaxGet($url)->seeJsonContains(['code' => 0]);

        $response = json_decode($this->response->getContent());
        $this->assertObjectHasAttribute('incomes', $response);
        $this->assertCount(1, $response->incomes);
        $this->assertEquals(2, $response->incomes[0]->id);
        $this->assertEquals('成都跑团', $response->incomes[0]->team_name);
        $this->assertEquals('成都慢生活', $response->incomes[0]->activity_title);
        $this->assertEquals(100, $response->incomes[0]->transfered_fee);
        $this->assertEquals(100, $response->incomes[0]->detail[0]->fee);
        $this->assertEquals('2015-08-05 11:00:00', 
            $response->incomes[0]->detail[0]->op_time);
    }

    //================================
    //          doIncomeTransfer
    //================================
    public function testDoIncomeTransferSuccessfully()
    {
        $user = $this->prepareUser('accountant');
        $this->prepareIncomeData();
        $this->startSession();
        $this->actingAs($user, 'extended-eloquent-admin')
             ->ajaxPost('admin/accountant/income/transfer/do', [
                '_token'    => csrf_token(),
                'id' => 3,
             ])->seeJsonContains(['code' => 0, 'message' => '操作成功']);
        $this->seeInDatabase('activity_enroll_incomes', [
            'id'        => 3,
            'status'    => 2,       // status be changed to 2, original is 1
        ]);
    }

    public function testDoIncomeTransfer_StatusInValid()
    {
        $user = $this->prepareUser('accountant');
        $this->prepareIncomeData();
        $this->startSession();
        $this->actingAs($user, 'extended-eloquent-admin')
             ->ajaxPost('admin/accountant/income/transfer/do', [
                '_token'    => csrf_token(),
                'id' => 2,
             ])->seeJsonContains(['code' => 10000, 'message' => '操作失败']);
        $this->seeInDatabase('activity_enroll_incomes', [
            'id'        => 2,
            'status'    => 3,       // status not changed
        ]);

    }

    //================================
    //          finishIncomeTransfer
    //================================
    public function testFinishIncomeTransferSuccessfully()
    {
        $user = $this->prepareUser('accountant');
        $this->prepareIncomeData();
        $this->startSession();
        $this->actingAs($user, 'extended-eloquent-admin')
             ->ajaxPost('admin/accountant/income/transfer/finish', [
                '_token'    => csrf_token(),
                'id' => 1,
             ])->seeJsonContains(['code' => 0, 'message' => '操作成功']);
        $this->seeInDatabase('activity_enroll_incomes', [
            'id'        => 1,
            'status'    => 3,       // status be changed to 3, original is 2
        ]);
    }

    public function testFinishIncomeTransfer_StatusInValid()
    {
        $user = $this->prepareUser('accountant');
        $this->prepareIncomeData();
        $this->startSession();
        $this->actingAs($user, 'extended-eloquent-admin')
             ->ajaxPost('admin/accountant/income/transfer/finish', [
                '_token'    => csrf_token(),
                'id' => 3,
             ])->seeJsonContains(['code' => 10000, 'message' => '操作失败']);
        $this->seeInDatabase('activity_enroll_incomes', [
            'id'        => 3,
            'status'    => 1,       // status not changed
        ]);

    }

    //================================
    //          confirmIncomeTransfer
    //================================
    public function testConfirmIncomeTransferSuccessfully()
    {
        $user = $this->prepareUser('accountant');
        $this->prepareIncomeData();

        $this->mockStorageService();

        $this->startSession();
        $this->actingAs($user, 'extended-eloquent-admin')
             ->ajaxPost('admin/accountant/income/transfer/confirm', [
                '_token'    => csrf_token(),
                'id'        => 1,
                'fee'       => '10',
                'evidence'  => 'http://domain/evidence.jpg',
             ])->seeJsonContains(['code' => 0, 'message' => '操作成功']);
        $this->seeInDatabase('activity_enroll_incomes', [
            'id'                => 1,
            'status'            => 2,
            'transfered_fee'    => 30,      // 10 + 20
        ]);
    }

    public function testConfirmIncomeTransferFail_IncomeNotExists()
    {
        $user = $this->prepareUser('accountant');

        $this->mockStorageService();

        $this->startSession();
        $this->actingAs($user, 'extended-eloquent-admin')
             ->ajaxPost('admin/accountant/income/transfer/confirm', [
                '_token'    => csrf_token(),
                'id'        => 1,
                'fee'       => 10,
                'evidence'  => 'http://domain/evidence.jpg',
             ])->seeJsonContains(['code' => 10000, 'message' => '您不能执行确认转账操作']);
    }

    private function prepareUser($role)
    {
        return factory(\Jihe\Models\Admin\User::class)->create([
            'id'        => 1,
            'user_name' => 'jihe-admin',
            'salt'      => 'ptrjb30aOvqWJ4mG',
            'password'  => '7907C7ED5F7F4E4872E24CAB8292464F',  // raw password is '*******'
            'role'      => $role,
        ]);
    }

    private function prepareIncomeData()
    {
        factory(\Jihe\Models\Team::class)->create([
            'id'    => 1,
            'name'  => '成都跑团',
        ]);
        factory(\Jihe\Models\Activity::class)->create([
            'id'        => 1,
            'title'     => '成都夜跑',
            'team_id'   => 1,
            'enroll_end_time'   => '2015-08-05 10:00:00',
        ]);
        factory(\Jihe\Models\Activity::class)->create([
            'id'        => 2,
            'title'     => '成都慢生活',
            'team_id'   => 1,
            'enroll_end_time'   => '2015-08-01 10:00:00',
        ]);
        factory(\Jihe\Models\Activity::class)->create([
            'id'        => 3,
            'title'     => '成都乐活',
            'team_id'   => 1,
            'enroll_end_time'   => '2015-07-15 10:00:00',
        ]);
        factory(\Jihe\Models\ActivityEnrollIncome::class)->create([
            'id'                => 1,
            'team_id'           => 1,
            'activity_id'       => 1,
            'total_fee'         => 100,
            'transfered_fee'    => 20,
            'enroll_end_time'   => '2015-08-05 10:00:00',
            'financial_action_result' => [
                    [strtotime('2015-08-05 14:00:00'), 20, 'http://domain/res1.jpg'],
            ],
            'status'            => \Jihe\Entities\ActivityEnrollIncome::STATUS_TRANSFERING,
        ]);
        factory(\Jihe\Models\ActivityEnrollIncome::class)->create([
            'id'                        => 2,
            'team_id'                   => 1,
            'activity_id'               => 2,
            'total_fee'                 => 100,
            'transfered_fee'            => 100,
            'enroll_end_time'   => '2015-08-01 10:00:00',
            'financial_action_result'   => [
                    [strtotime('2015-08-05 11:00:00'), 100, 'http://domain/res1.jpg'],
            ],
            'status'                    => \Jihe\Entities\ActivityEnrollIncome::STATUS_FINISHED,
        ]);
        factory(\Jihe\Models\ActivityEnrollIncome::class)->create([
            'id'                => 3,
            'team_id'           => 1,
            'activity_id'       => 3,
            'total_fee'         => 100,
            'transfered_fee'    => 0,
            'enroll_end_time'   => '2015-07-15 10:00:00',
            'financial_action_result' => null,
            'status'            => \Jihe\Entities\ActivityEnrollIncome::STATUS_WAIT,
        ]);
    }

    private function mockStorageService($return = 'key')
    {
        $storageService = \Mockery::mock(\Jihe\Contracts\Services\Storage\StorageService::class);
        $storageService->shouldReceive('store')->withAnyArgs()->andReturn($return);
        $storageService->shouldReceive('getPortal')->withAnyArgs()->andReturn('http://download.domain.cn/' . $return);
        $storageService->shouldReceive('remove')->withAnyArgs()->andReturn(null);
        $this->app[\Jihe\Contracts\Services\Storage\StorageService::class] = $storageService;

        return $storageService;
    }
}
