<?php
namespace intg\Jihe\Controllers\Admin;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery;
use intg\Jihe\TestCase;
use intg\Jihe\RequestSignCheck;
use Jihe\Models\TeamRequest;
use Jihe\Entities\TeamRequest as TeamRequestEntity;
use Jihe\Models\Team;
use Jihe\Entities\Team as TeamEntity;
use Jihe\Models\TeamCertification;
use Jihe\Entities\TeamCertification as TeamCertificationEntity;

class TeamControllerTest extends TestCase
{
    use DatabaseTransactions, RequestSignCheck;
    
    //=========================================
    //              listRequests
    //=========================================
    public function testSuccessfulListRequests()
    {
        $adminUser = $this->createAdminUser();
        $city = $this->createCity();

        factory(TeamRequest::class)->create([
            'city_id'      => $city->id,
            'initiator_id' => $adminUser['id'],
            'team_id'      => null,
            'status'       => TeamRequestEntity::STATUS_PENDING
        ]);
        
        factory(TeamRequest::class)->create([
            'city_id'      => $city->id,
            'initiator_id' => $adminUser['id'],
            'team_id'      => 1,
            'status'       => TeamRequestEntity::STATUS_PENDING
        ]);
        
        factory(TeamRequest::class)->create([
            'city_id'      => $city->id,
            'initiator_id' => $adminUser['id'],
            'team_id'      => null,
            'status'       => TeamRequestEntity::STATUS_REJECTED
        ]);
        
        factory(TeamRequest::class)->create([
            'city_id'      => $city->id,
            'initiator_id' => $adminUser['id'],
            'team_id'      => 1,
            'status'       => TeamRequestEntity::STATUS_APPROVED
        ]);
    
        $this->startSession();
        $this->actingAs($adminUser, 'extended-eloquent-admin')
             ->ajaxGet('/admin/team/request/list?page=1&size=12');
        $this->seeJsonContains([ 'code' => 0 ]);
        
        $result = json_decode($this->response->getContent());
        self::assertEquals(1, $result->pages);
        
        $requests = $result->requests;
        self::assertCount(2, $requests);
    }
    
    //=========================================
    //     approvePendingEnrollmentRequest
    //=========================================
    public function testSuccessfulApprovePendingEnrollmentRequest()
    {
        $this->expectsJobs(\Jihe\Jobs\TeamSearchIndexRefreshJob::class,
                           \Jihe\Jobs\SendMessageToUserJob::class);

        $adminUser = $this->createAdminUser();
        $user = $this->createUser();
        $city = $this->createCity();

        factory(TeamRequest::class)->create([
            'id'           => 1,
            'city_id'      => $city->id,
            'initiator_id' => $user['id'],
            'team_id'      => null,
            'logo_url'     => __DIR__ . '/test-data/panda.jpg',
            'status'       => TeamRequestEntity::STATUS_PENDING
        ]);

        $this->startSession();
        $this->mockStorageService();
        $this->actingAs($adminUser, 'extended-eloquent-admin')
             ->ajaxPost('/admin/team/request/enrollment/approve', [
                         '_token'  => csrf_token(),
                         'request' => 1,
                         'memo'    => '熟人',
                       ]);

        $this->seeJsonContains([ 'code' => 0 ]);
    }

    //=========================================
    //      rejectPendingEnrollmentRequest
    //=========================================
    public function testSuccessfulRejectPendingEnrollmentRequest()
    {
        $this->expectsJobs(\Jihe\Jobs\SendMessageToUserJob::class);

        $adminUser = $this->createAdminUser();
        $city = $this->createCity();
        $user = $this->createUser();

        factory(TeamRequest::class)->create([
            'id'           => 1,
            'city_id'      => $city->id,
            'initiator_id' => $user['id'],
            'team_id'      => null,
            'logo_url'     => __DIR__ . '/test-data/panda.jpg',
            'status'       => TeamRequestEntity::STATUS_PENDING
        ]);

        $this->startSession();
        $this->actingAs($adminUser, 'extended-eloquent-admin')
             ->ajaxPost('/admin/team/request/enrollment/reject', [
                        '_token'  => csrf_token(),
                        'request' => 1,
                        'memo'    => '熟人',
                       ]);

        $this->seeJsonContains([ 'code' => 0 ]);
    }

    //=========================================
    //        approvePendingUpdateRequest
    //=========================================
    public function testSuccessfulApprovePendingUpdateRequest()
    {
        $this->expectsJobs(\Jihe\Jobs\TeamSearchIndexRefreshJob::class,
                           \Jihe\Jobs\SendMessageToUserJob::class);

        $adminUser = $this->createAdminUser();
        $city = $this->createCity();
        $user = $this->createUser();
        $team = $this->createTeam($city, $user);

        factory(TeamRequest::class)->create([
            'id'           => 1,
            'city_id'      => $city->id,
            'initiator_id' => $user['id'],
            'team_id'      => $team->id,
            'logo_url'     => __DIR__ . '/test-data/panda.jpg',
            'status'       => TeamRequestEntity::STATUS_PENDING
        ]);

        $this->startSession();
        $this->mockStorageService();
        $this->actingAs($adminUser, 'extended-eloquent-admin')
             ->ajaxPost('/admin/team/request/update/approve', [
                        '_token'  => csrf_token(),
                        'request' => 1,
                        'memo'    => '熟人',
                ]);

        $this->seeJsonContains([ 'code' => 0 ]);
    }

    //=========================================
    //       rejectPendingUpdateRequest
    //=========================================
    public function testSuccessfulRejectPendingUpdateRequest()
    {
        $this->expectsJobs(\Jihe\Jobs\SendMessageToUserJob::class);

        $adminUser = $this->createAdminUser();
        $city = $this->createCity();
        $user = $this->createUser();
        $team = $this->createTeam($city, $user);

        factory(TeamRequest::class)->create([
            'id'           => 1,
            'city_id'      => $city->id,
            'initiator_id' => $user['id'],
            'team_id'      => $team->id,
            'logo_url'     => __DIR__ . '/test-data/panda.jpg',
            'status'       => TeamRequestEntity::STATUS_PENDING
        ]);

        $this->startSession();
        $this->actingAs($adminUser, 'extended-eloquent-admin')
             ->ajaxPost('/admin/team/request/update/reject', [
                        '_token'  => csrf_token(),
                        'request' => 1,
                        'memo'    => '熟人',
                ]);

        $this->seeJsonContains([ 'code' => 0 ]);
    }

    //=========================================
    //     listPendingTeamsForCertification
    //=========================================
    public function testSuccessfulListPendingTeamsForCertification()
    {
        $adminUser = $this->createAdminUser();
        $city = $this->createCity();

        factory(Team::class)->create([
            'city_id'       => $city->id,
            'certification' => TeamEntity::UN_CERTIFICATION,
        ]);

        factory(Team::class)->create([
            'city_id'       => $city->id,
            'certification' => TeamEntity::CERTIFICATION_PENDING,
        ]);

        factory(Team::class)->create([
            'city_id'       => $city->id,
            'certification' => TeamEntity::CERTIFICATION,
        ]);

        $this->startSession();
        $this->actingAs($adminUser, 'extended-eloquent-admin')
             ->ajaxGet('/admin/team/certification/list?page=1&size=12');
        $this->seeJsonContains([ 'code' => 0 ]);

        $result = json_decode($this->response->getContent());
        self::assertEquals(1, $result->pages);

        $teams = $result->teams;
        self::assertCount(1, $teams);
    }

    //=========================================
    //     listPendingTeamsForCertification
    //=========================================
    public function testSuccessfulListCertifications()
    {
        $adminUser = $this->createAdminUser();
        $city = $this->createCity();
        $user = $this->createUser();

        factory(Team::class)->create([
            'id'            => 1,
            'city_id'       => $city->id,
            'creator_id'    => $user->id,
            'certification' => TeamEntity::CERTIFICATION_PENDING,
        ]);

        factory(TeamCertification::class)->create([
            'team_id'           => 1,
            'type'              => TeamCertificationEntity::TYPE_ID_CARD_FRONT,
            'certification_url' => 'http://domain.id_card_front.png',
        ]);

        factory(TeamCertification::class)->create([
            'team_id'           => 1,
            'type'              => TeamCertificationEntity::TYPE_ID_CARD_BACK,
            'certification_url' => 'http://domain.id_card_back.png',
        ]);

        factory(TeamCertification::class)->create([
            'team_id'           => 1,
            'type'              => TeamCertificationEntity::TYPE_BUSSINESS_CERTIFICATES,
            'certification_url' => 'http://domain.business_certificates.png',
        ]);

        $this->startSession();
        $this->actingAs($adminUser, 'extended-eloquent-admin')
             ->ajaxGet('/admin/team/certification/info/list?team=1');
        $this->seeJsonContains([ 'code' => 0 ]);
    }

    //=========================================
    //   approvePendingTeamForCertification
    //=========================================
    public function testSuccessfulApprovePendingTeamForCertification()
    {
        $this->expectsJobs(\Jihe\Jobs\TeamSearchIndexRefreshJob::class,
                           \Jihe\Jobs\SendMessageToUserJob::class);
        $adminUser = $this->createAdminUser();
        $city = $this->createCity();
        $user = $this->createUser();

        factory(Team::class)->create([
            'id'            => 1,
            'city_id'       => $city->id,
            'creator_id'    => $user->id,
            'certification' => TeamEntity::CERTIFICATION_PENDING,
        ]);

        $this->startSession();
        $this->actingAs($adminUser, 'extended-eloquent-admin')
             ->ajaxPost('/admin/team/certification/approve', [
                        '_token'  => csrf_token(),
                        'team' => 1,
                       ]);

        $this->seeJsonContains([ 'code' => 0 ]);
    }

    //=========================================
    //    rejectPendingTeamForCertification
    //=========================================
    public function testSuccessfulRejectPendingTeamForCertification()
    {
        $this->expectsJobs(\Jihe\Jobs\TeamSearchIndexRefreshJob::class,
                           \Jihe\Jobs\SendMessageToUserJob::class);

        $adminUser = $this->createAdminUser();
        $city = $this->createCity();
        $user = $this->createUser();

        factory(Team::class)->create([
            'id'            => 1,
            'city_id'       => $city->id,
            'creator_id'    => $user->id,
            'certification' => TeamEntity::CERTIFICATION_PENDING,
        ]);

        $this->startSession();
        $this->actingAs($adminUser, 'extended-eloquent-admin')
             ->ajaxPost('/admin/team/certification/reject', [
                        '_token'  => csrf_token(),
                        'team' => 1,
                       ]);

        $this->seeJsonContains([ 'code' => 0 ]);
    }

    //=========================================
    //               listTeams
    //=========================================
    public function testSuccessfulListTeams()
    {
        $adminUser = $this->createAdminUser();

        factory(Team::class)->create([
            'status' => TeamEntity::STATUS_NORMAL,
        ]);

        factory(Team::class)->create([
            'status' => TeamEntity::STATUS_NORMAL,
            'tags'   => json_encode(['运动', '健康']),
        ]);

        factory(Team::class)->create([
            'status' => TeamEntity::STATUS_FORBIDDEN,
        ]);

        $this->startSession();
        $this->actingAs($adminUser, 'extended-eloquent-admin')
        ->ajaxGet('/admin/team/list?page=1&size=12');
        $this->seeJsonContains([ 'code' => 0 ]);

        $result = json_decode($this->response->getContent());
        self::assertEquals(1, $result->pages);

        $teams = $result->teams;
        self::assertCount(3, $teams);
    }

    public function testSuccessfulListTeams_Tagged()
    {
        $adminUser = $this->createAdminUser();
        $city = $this->createCity();
        $user = $this->createUser();
        $team = $this->createTeam($city, $user);

        factory(Team::class)->create([
                'status' => TeamEntity::STATUS_NORMAL,
        ]);
        factory(Team::class)->create([
                'status' => TeamEntity::STATUS_NORMAL,
                'tags'   => json_encode(['运动', '健康']),
        ]);

        $this->startSession();
        $this->actingAs($adminUser, 'extended-eloquent-admin')
             ->ajaxGet('/admin/team/list?page=1&size=2&tagged=1');
        $this->seeJsonContains([ 'code' => 0 ]);

        $result = json_decode($this->response->getContent());
        self::assertEquals(1, $result->pages);

        $teams = $result->teams;
        self::assertCount(1, $teams);
    }

    public function testSuccessfulListTeams_Forbidden()
    {
        $adminUser = $this->createAdminUser();
        $city = $this->createCity();
        $user = $this->createUser();
        $team = $this->createTeam($city, $user);

        factory(Team::class)->create([
            'status' => TeamEntity::STATUS_NORMAL,
        ]);
        factory(Team::class)->create([
            'status' => TeamEntity::STATUS_FORBIDDEN,
        ]);

        $this->startSession();
        $this->actingAs($adminUser, 'extended-eloquent-admin')
             ->ajaxGet('/admin/team/list?page=1&size=2&forbidden=1');
        $this->seeJsonContains([ 'code' => 0 ]);

        $result = json_decode($this->response->getContent());
        self::assertEquals(1, $result->pages);

        $teams = $result->teams;
        self::assertCount(1, $teams);
    }

    public function testSuccessfulListTeams_NoTagged()
    {
        $adminUser = $this->createAdminUser();

        factory(Team::class)->create([
            'id'     => 1,
            'status' => TeamEntity::STATUS_NORMAL,
            'tags'   => null,
        ]);
        factory(Team::class)->create([
            'id'     => 2,
            'status' => TeamEntity::STATUS_NORMAL,
            'tags'   => json_encode(['运动', '健康']),
        ]);

        $this->startSession();
        $this->actingAs($adminUser, 'extended-eloquent-admin')
             ->ajaxGet('/admin/team/list?page=1&size=2&tagged=0');
        $this->seeJsonContains([ 'code' => 0 ]);

        $result = json_decode($this->response->getContent());
        self::assertEquals(1, $result->pages);

        $teams = $result->teams;
        self::assertCount(1, $teams);
    }

    public function testSuccessfulListTeams_NoForbidden()
    {
        $adminUser = $this->createAdminUser();

        factory(Team::class)->create([
            'status' => TeamEntity::STATUS_NORMAL,
        ]);
        factory(Team::class)->create([
            'status' => TeamEntity::STATUS_FORBIDDEN,
        ]);

        $this->startSession();
        $this->actingAs($adminUser, 'extended-eloquent-admin')
             ->ajaxGet('/admin/team/list?page=1&size=2&forbidden=0');
        $this->seeJsonContains([ 'code' => 0 ]);

        $result = json_decode($this->response->getContent());
        self::assertEquals(1, $result->pages);

        $teams = $result->teams;
        self::assertCount(1, $teams);
    }

    //=========================================
    //              freeze Team
    //=========================================
    public function testSuccessfulFreezeTeam()
    {
        $this->expectsJobs(\Jihe\Jobs\TeamSearchIndexRefreshJob::class);

        $adminUser = $this->createAdminUser();
        $city = $this->createCity();
        $user = $this->createUser();

        factory(Team::class)->create([
            'id'         => 1,
            'city_id'    => $city->id,
            'creator_id' => $user->id,
            'status'     => TeamEntity::STATUS_NORMAL,
        ]);

        $this->startSession();
        $this->actingAs($adminUser, 'extended-eloquent-admin')
             ->ajaxPost('/admin/team/freeze', [
                        '_token'  => csrf_token(),
                        'team' => 1,
                ]);

        $this->seeJsonContains([ 'code' => 0 ]);

        $this->seeInDatabase('teams', [
            'id'     => 1,
            'status' => TeamEntity::STATUS_FREEZE
        ]);
    }

    //=========================================
    //            cancel freeze Team
    //=========================================
    public function testSuccessfulCancelFreezeTeam()
    {
        $this->expectsJobs(\Jihe\Jobs\TeamSearchIndexRefreshJob::class);

        $adminUser = $this->createAdminUser();
        $city = $this->createCity();
        $user = $this->createUser();

        factory(Team::class)->create([
            'id'         => 1,
            'city_id'    => $city->id,
            'creator_id' => $user->id,
            'status'     => TeamEntity::STATUS_FREEZE,
        ]);

        $this->startSession();
        $this->actingAs($adminUser, 'extended-eloquent-admin')
             ->ajaxPost('/admin/team/freeze/cancel', [
                        '_token'  => csrf_token(),
                        'team' => 1,
                ]);

        $this->seeJsonContains([ 'code' => 0 ]);

        $this->seeInDatabase('teams', [
            'id'     => 1,
            'status' => TeamEntity::STATUS_NORMAL
        ]);
    }

    //=========================================
    //             forbidden Team
    //=========================================
    public function testSuccessfulForbiddenTeam()
    {
        $this->expectsJobs(\Jihe\Jobs\TeamSearchIndexRefreshJob::class);

        $adminUser = $this->createAdminUser();
        $city = $this->createCity();
        $user = $this->createUser();

        factory(Team::class)->create([
            'id'         => 1,
            'city_id'    => $city->id,
            'creator_id' => $user->id,
            'status'     => TeamEntity::STATUS_NORMAL,
        ]);

        $this->startSession();
        $this->actingAs($adminUser, 'extended-eloquent-admin')
             ->ajaxPost('/admin/team/forbidden', [
                        '_token'  => csrf_token(),
                        'team' => 1,
                ]);

        $this->seeJsonContains([ 'code' => 0 ]);

        $this->seeInDatabase('teams', [
                'id'     => 1,
                'status' => TeamEntity::STATUS_FORBIDDEN
        ]);
    }

    //=========================================
    //          cancel forbidden Team
    //=========================================
    public function testSuccessfulCancelForbiddenTeam()
    {
        $this->expectsJobs(\Jihe\Jobs\TeamSearchIndexRefreshJob::class);

        $adminUser = $this->createAdminUser();
        $city = $this->createCity();
        $user = $this->createUser();

        factory(Team::class)->create([
            'id'         => 1,
            'city_id'    => $city->id,
            'creator_id' => $user->id,
            'status'     => TeamEntity::STATUS_FORBIDDEN,
        ]);

        $this->startSession();
        $this->actingAs($adminUser, 'extended-eloquent-admin')
             ->ajaxPost('/admin/team/forbidden/cancel', [
                        '_token'  => csrf_token(),
                        'team' => 1,
                ]);

        $this->seeJsonContains([ 'code' => 0 ]);

        $this->seeInDatabase('teams', [
            'id'     => 1,
            'status' => TeamEntity::STATUS_NORMAL
        ]);
    }

    //=========================================
    //               tag Team
    //=========================================
    public function testSuccessfulTagTeam()
    {
        $this->expectsJobs(\Jihe\Jobs\TeamSearchIndexRefreshJob::class);

        $adminUser = $this->createAdminUser();
        $city = $this->createCity();
        $user = $this->createUser();

        factory(Team::class)->create([
            'id'         => 1,
            'city_id'    => $city->id,
            'creator_id' => $user->id,
            'status'     => TeamEntity::STATUS_NORMAL,
            'tags'       => null,
        ]);

        $this->startSession();
        $this->actingAs($adminUser, 'extended-eloquent-admin')
        ->ajaxPost('/admin/team/tag', [
                '_token'  => csrf_token(),
                'team' => 1,
                'tags' => ['a', 'b', 'c'],
        ]);

        $this->seeJsonContains([ 'code' => 0 ]);

        $this->notSeeInDatabase('teams', [
            'id'     => 1,
            'status' => TeamEntity::STATUS_NORMAL,
            'tags'   => null,
        ]);
    }
    
    /**
     * @return \Jihe\Models\Admin\User
     */
    private function createAdminUser()
    {
        return factory(\Jihe\Models\Admin\User::class)->create();
    }
    
    /**
     * @return \Jihe\Models\User
     */
    private function createUser()
    {
        return factory(\Jihe\Models\User::class)->create();
    }

    /**
     * @return \Jihe\Models\City
     */
    private function createCity()
    {
        return factory(\Jihe\Models\City::class)->create();
    }

    /**
     * @return \Jihe\Models\Team
     */
    private function createTeam($city = null, $creator = null)
    {
        $team = [];
        
        if (null != $city) {
            $team['city_id'] = $city['id'];
        }
        
        if (null != $creator) {
            $team['creator_id'] = $creator['id'];
        }
        
        return factory(\Jihe\Models\Team::class)->create($team);
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
