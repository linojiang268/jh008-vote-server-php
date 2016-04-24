<?php
namespace intg\Jihe\Controllers\Backstage;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery;
use intg\Jihe\TestCase;
use intg\Jihe\RequestSignCheck;
use intg\Jihe\AuthDeviceCheck;
use Jihe\Models\TeamRequest;
use Jihe\Models\TeamRequirement;
use Jihe\Models\TeamCertification;
use Jihe\Entities\TeamRequest as TeamRequestEntity;
use Jihe\Jobs\SendMessageToTeamMemberJob;

class TeamControllerTest extends TestCase
{
    use DatabaseTransactions;
    use RequestSignCheck;
    use AuthDeviceCheck;
    
    // =========================================
    // requestForEnrollment
    // =========================================
    public function testSuccessfulRequestForEnrollment()
    {
        $this->expectsJobs(\Jihe\Jobs\SendMessageToUserJob::class);
        
        $user = $this->createUser();
        $city = $this->createCity();
        $this->mockPhotoService();
        $this->mockStorageService('http://domain/tmp/20150635/20152685.jpg');
        $this->startSession();

        $this->actingAs($user)->ajaxPost('/community/team/request/create', [ 
                '_token'        => csrf_token(),
                'city'          => $city ['id'],
                'name'          => 'team name',
                'email'         => 'email@email.com',
                'logo_id'       => 'http://domain/default/team_logo.png',
                'contact_phone' => '15882330912',
                'crop_start_x'  => 0,
                'crop_start_y'  => 0,
                'crop_end_x'    => 10,
                'crop_end_y'    => 10,
        ])->seeJsonContains([ 
                'code' => 0 
        ]);
    }
    
    // =========================================
    // requestForUpdate
    // =========================================
    public function testSuccessfulRequestForUpdate()
    {
        $this->expectsJobs(\Jihe\Jobs\SendMessageToUserJob::class);
        
        $user = $this->createUser();
        $city = $this->createCity();
        $team = $this->createTeam($city, $user);
        
        $this->startSession();
        $this->mockPhotoService();
        $this->mockStorageService('http://domain/tmp/20150635/20152685.jpg');
        $this->actingAs($user)->post('/community/team/request/update', [ 
                '_token'        => csrf_token(),
                'team'          => $team ['id'],
                'name'          => 'new team name',
                'email'         => 'email@email.com',
                'logo_id'       => 'http://dev.image.jhla.com.cn/default/team_logo.png',
                'contact_phone' => '028-89897876',
                'logo_crop'     => 1,
                'crop_start_x'  => 0,
                'crop_start_y'  => 0,
                'crop_end_x'    => 100,
                'crop_end_y'    => 100,
        ]);

        $this->seeJsonContains([
                'code' => 0 
        ]);
    }

    public function testSuccessfulRequestForUpdate_logoNotReset()
    {
        $this->expectsJobs(\Jihe\Jobs\SendMessageToUserJob::class);

        $user = $this->createUser();
        $city = $this->createCity();
        $team = $this->createTeam($city, $user);

        $this->startSession();
        $this->actingAs($user)->post('/community/team/request/update', [
            '_token'        => csrf_token(),
            'team'          => $team ['id'],
            'name'          => 'new team name',
            'email'         => 'email@email.com',
            'logo_id'       => 'http://domain/default/team_logo.png',
            'contact_phone' => '028-89897876',
            'logo_crop'     => 0,
        ]);

        $this->seeJsonContains([
            'code' => 0
        ]);
    }

    // =========================================
    // update
    // =========================================
    public function testSuccessfulUpdate()
    {
        $user = $this->createUser();
        $city = $this->createCity();
        $team = $this->createTeam($city, $user);

        $this->startSession();
        $this->actingAs($user)->post('/community/team/update', [
            '_token' => csrf_token(),
            'team' => $team ['id'],
            'email' => 'email@email.com',
            'contact_phone' => '028-89897876',
        ]);

        $this->seeJsonContains([
            'code' => 0
        ]);
    }
    
    // =========================================
    // inspectRequest
    // =========================================
    public function testSuccessfulInspectRequest()
    {
        $user = $this->createUser();
        $city = $this->createCity();
        $team = $this->createTeam($city, $user);
        
        factory(\Jihe\Models\TeamRequest::class)->create([ 
                'id' => 1,
                'city_id' => $city ['id'],
                'initiator_id' => $user ['id'],
                'email' => 'email@email.com',
                'logo_url' => 'http://domain/tmp/20150635/20152685.jpg',
                'status' => TeamRequestEntity::STATUS_APPROVED,
                'read' => TeamRequest::UN_READ 
        ]);
        
        $this->startSession();
        
        $this->actingAs($user)->post('/community/team/request/inspect', [ 
                '_token' => csrf_token(),
                'request' => 1 
        ]);
        $this->seeJsonContains([ 
                'code' => 0 
        ]);
    }
    
    // =========================================
    // updateRequirements
    // =========================================
    public function testSuccessfulUpdateRequirements_Anyone()
    {
        $this->expectsJobs(\Jihe\Jobs\TeamSearchIndexRefreshJob::class);

        $user = $this->createUser();
        $city = $this->createCity();
        $team = factory(\Jihe\Models\Team::class)->create([
            'city_id'    => $city->id,
            'creator_id' => $user->id,
            'join_type'  => 1,
        ]);

        factory(TeamRequirement::class)->create([
            'id' => 1,
            'team_id' => $team ['id'],
            'requirement' => '车型'
        ]);
        factory(TeamRequirement::class)->create([
            'id' => 2,
            'team_id' => $team ['id'],
            'requirement' => '排量'
        ]);

        $this->startSession();
        $this->actingAs($user)->post('/community/team/requirement/update', [
            '_token' => csrf_token(),
            'join_type' => 0,
        ]);
        $this->seeJsonContains([
            'code' => 0
        ]);

        $this->seeInDatabase('teams', [
            'id'        => $team->id,
            'join_type' => 0,
        ]);
    }

    public function testSuccessfulUpdateRequirements_NeedHandle()
    {
        $this->expectsJobs(\Jihe\Jobs\TeamSearchIndexRefreshJob::class);

        $user = $this->createUser();
        $city = $this->createCity();
        $team = $this->createTeam($city, $user);

        factory(TeamRequirement::class)->create([
                'id' => 1,
                'team_id' => $team ['id'],
                'requirement' => '车型'
        ]);
        factory(TeamRequirement::class)->create([
                'id' => 2,
                'team_id' => $team ['id'],
                'requirement' => '排量'
        ]);

        $this->startSession();
        $this->actingAs($user)->post('/community/team/requirement/update', [
                '_token' => csrf_token(),
                'join_type' => 1,
                'requirements' => [
                        [
                                'id' => 1,
                                'requirement' => '车型'
                        ],
                        [
                                'requirement' => '出厂日期'
                        ]
                ]
        ]);
        $this->seeJsonContains([
                'code' => 0
        ]);

        $this->seeInDatabase('teams', [
            'id'        => $team->id,
            'join_type' => 1,
        ]);
    }
    
    // =========================================
    // requestCertifications
    // =========================================
    public function testSuccessfulRequestCertifications()
    {
        $this->expectsJobs(\Jihe\Jobs\SendMessageToUserJob::class);
        
        factory(\Jihe\Models\Team::class)->create([ 
                'id' => 1,
                'city_id' => 1,
                'creator_id' => 1,
                'email' => 'email@email.com',
                'logo_url' => 'http://domain/tmp/20150635/20152685.jpg' 
        ]);
        factory(\Jihe\Models\City::class)->create([ 
                'id' => 1 
        ]);
        $user = factory(\Jihe\Models\User::class)->create([ 
                'id' => 1 
        ]);
        
        factory(TeamCertification::class)->create([ 
                'id' => 1,
                'certification_url' => 'http://domain/certifications1.jpg',
                'type' => 1 
        ]);
        factory(TeamCertification::class)->create([ 
                'id' => 2,
                'certification_url' => 'http://domain/certifications2.jpg',
                'type' => 2 
        ]);
        
        $this->startSession();
        $this->mockStorageService('http://domain/tmp/20150635/20152685.jpg');
        $this->actingAs($user)->ajaxPost('/community/team/certification/request', [ 
                '_token' => csrf_token(),
                'team' => 1,
                'certifications' => [ 
                        [ 
                                'id' => 1,
                                'certification_id' => 'http://domain/tmp/certification1.jpg',
                                'type' => 1 
                        ],
                        [ 
                                'certification_id' => 'http://domain/tmp/certification3.jpg',
                                'type' => 0 
                        ],
                        [ 
                                'certification_id' => 'http://domain/tmp/certification4.jpg',
                                'type' => 2 
                        ] 
                ] 
        ]);
        $this->seeJsonContains([ 
                'code' => 0 
        ]);
    }
    
    // =========================================
    // downloadQrcode
    // =========================================
    public function testSuccessfulDownloadQrcode()
    {
        factory(\Jihe\Models\Team::class)->create([ 
                'id' => 1,
                'city_id' => 1,
                'creator_id' => 1,
                'email' => 'email@email.com',
                'logo_url' => __DIR__ . '/test-data/panda.jpg' 
        ]);
        factory(\Jihe\Models\City::class)->create([ 
                'id' => 1 
        ]);
        $user = factory(\Jihe\Models\User::class)->create([ 
                'id' => 1 
        ]);
        
        $this->actingAs($user)->get('/community/team/qrcode/download?team=1&size=10');
        
        $this->assertResponseOk();
        $this->assertTrue($this->response->headers->has('Content-Disposition'));
    }
    
    // =========================================
    // send team notice
    // =========================================
    public function testSuccessfulSendTeamNotice_ToAllAndSms()
    {
        $this->expectsJobs(\Jihe\Jobs\SendMessageToTeamMemberJob::class);
        
        $user = $this->createUser();
        $city = $this->createCity();
        $team = $this->createTeam($city, $user);
        
        $this->startSession();
        $this->actingAs($user)->ajaxPost('/community/team/notice/send', [ 
                '_token' => csrf_token(),
                'to_all' => true,
                'send_way' => 'sms',
                'content' => 'smscontent' 
        ]);
        $this->seeJsonContains([ 
                'code' => 0 
        ]);
    }
    public function testSuccessfulSendTeamNotice_NotToAllAndSms()
    {
        $this->expectsJobs(\Jihe\Jobs\SendMessageToTeamMemberJob::class);
        
        $user = $this->createUser();
        $city = $this->createCity();
        $team = $this->createTeam($city, $user);
        
        $phones = [ ];
        for($i = 0; $i < 23; $i ++ ) {
            $newPhone = '12345678' . (111 + $i);
            array_push($phones, $newPhone);
            
            $newUser = factory(\Jihe\Models\User::class)->create([ 
                    'mobile' => '12345678' . (111 + $i) 
            ]);
            
            factory(\Jihe\Models\TeamMember::class)->create([ 
                    'team_id' => $team->id,
                    'user_id' => $newUser->id 
            ]);
        }
        
        $this->startSession();
        $this->actingAs($user)->ajaxPost('/community/team/notice/send', [ 
                '_token' => csrf_token(),
                'to_all' => false,
                'phones' => $phones,
                'send_way' => 'sms',
                'content' => 'smscontent' 
        ]);
        $this->seeJsonContains([ 
                'code' => 0 
        ]);
    }
    
    // =========================================
    // listNotices
    // =========================================
    public function testSuccessfullListNotices()
    {
        $user = $this->createUser();
        $city = $this->createCity();
        factory(\Jihe\Models\Team::class)->create([ 
                'id' => 1,
                'city_id' => $city->id,
                'creator_id' => $user->id 
        ]);
        
        factory(\Jihe\Models\Message::class)->create([ 
                'team_id' => 1,
                'activity_id' => null,
                'user_id' => null 
        ]);
        factory(\Jihe\Models\Message::class)->create([ 
                'team_id' => 1,
                'activity_id' => null,
                'user_id' => null 
        ]);
        factory(\Jihe\Models\Message::class)->create([ 
                'team_id' => 1,
                'activity_id' => null,
                'user_id' => 1 
        ]);
        factory(\Jihe\Models\Message::class)->create([ 
                'team_id' => null,
                'activity_id' => null,
                'user_id' => null 
        ]);
        factory(\Jihe\Models\Message::class)->create([ 
                'team_id' => 1,
                'activity_id' => 1,
                'user_id' => null 
        ]);
        
        $this->actingAs($user)->ajaxGet("/community/team/notices/list?page=1&size=10");
        $this->seeJsonContains([ 
                'code' => 0 
        ]);
        
        $result = json_decode($this->response->getContent());
        
        $this->assertObjectHasAttribute('pages', $result);
        $this->assertObjectHasAttribute('messages', $result);
        $this->assertEquals(1, $result->pages);
        $this->assertCount(3, $result->messages);
        
        $message = $result->messages [0];
        $this->assertObjectHasAttribute('id', $message);
        $this->assertObjectHasAttribute('content', $message);
        $this->assertObjectHasAttribute('notified_type', $message);
        $this->assertObjectHasAttribute('created_at', $message);
    }

    private function mockPhotoService()
    {
        $photoService = \Mockery::mock(\Jihe\Services\Photo\PhotoService::class);
        $photoService->shouldReceive('crop')->withAnyArgs()->andReturn(true);
        $this->app [\Jihe\Services\Photo\PhotoService::class] = $photoService;

        return $photoService;
    }

    private function mockStorageService($return = 'http://domain/tmp/key.png')
    {
        $storageService = \Mockery::mock(\Jihe\Services\StorageService::class);
        $storageService->shouldReceive('isTmp')->withAnyArgs()->andReturn(true);
        $storageService->shouldReceive('storeAsFile')->withAnyArgs()->andReturn($return);
        $storageService->shouldReceive('storeAsImage')->withAnyArgs()->andReturn($return);
        $storageService->shouldReceive('remove')->withAnyArgs()->andReturn(false);
        $this->app [\Jihe\Services\StorageService::class] = $storageService;
        
        return $storageService;
    }
    
    /**
     *
     * @return \Jihe\Models\User
     */
    private function createUser()
    {
        return factory(\Jihe\Models\User::class)->create();
    }
    
    /**
     *
     * @return \Jihe\Models\City
     */
    private function createCity()
    {
        return factory(\Jihe\Models\City::class)->create();
    }
    
    /**
     *
     * @return \Jihe\Models\Team
     */
    private function createTeam($city = null, $creator = null)
    {
        $team = [ ];
        
        if(null != $city) {
            $team ['city_id'] = $city ['id'];
        }
        
        if(null != $creator) {
            $team ['creator_id'] = $creator ['id'];
        }
        
        return factory(\Jihe\Models\Team::class)->create($team);
    }
}
