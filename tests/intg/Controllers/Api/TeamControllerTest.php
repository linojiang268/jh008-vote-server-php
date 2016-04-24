<?php
namespace intg\Jihe\Controllers\Api;

use Illuminate\Foundation\Testing\DatabaseTransactions;

use intg\Jihe\TestCase;
use intg\Jihe\RequestSignCheck;
use intg\Jihe\AuthDeviceCheck;

class TeamControllerTest extends TestCase
{
    use DatabaseTransactions;
    use RequestSignCheck;
    use AuthDeviceCheck;
    
    //=========================================
    //                teams
    //=========================================
    public function testSuccessfulTeams()
    {
        $user = factory(\Jihe\Models\User::class)->create();
        factory(\Jihe\Models\City::class)->create([
            'id' => 1,
        ]);
        
        factory(\Jihe\Models\Team::class)->create([
            'id'      => 1,
            'city_id' => 1,
        ]);

        $this->startSession();
    
        $this->actingAs($user)
             ->get('/api/team/list?city=1');
        
        $this->seeJsonContains([ 'code' => 0 ]);

        $result = json_decode($this->response->getContent());
        
        $this->assertObjectHasAttribute('pages', $result);
        $this->assertObjectHasAttribute('teams', $result);
        
        $team = $result->teams[0];
        $this->assertObjectHasAttribute('id', $team);
        $this->assertObjectHasAttribute('name', $team);
        $this->assertObjectHasAttribute('introduction', $team);
        $this->assertObjectHasAttribute('logo_url', $team);
        $this->assertObjectHasAttribute('qr_code_url', $team);
        $this->assertObjectHasAttribute('activity_num', $team);
        $this->assertObjectHasAttribute('member_num', $team);
        $this->assertObjectHasAttribute('joined', $team);
    }
    
    public function testSuccessfulTeams_Multi()
    {
        $user = factory(\Jihe\Models\User::class)->create();
        factory(\Jihe\Models\City::class)->create([
            'id' => 1,
        ]);
    
        factory(\Jihe\Models\Team::class)->create([
            'id'      => 1,
            'city_id' => 1,
        ]);
        
        factory(\Jihe\Models\Team::class)->create([
            'id'      => 2,
            'city_id' => 1,
        ]);
    
        $this->startSession();
    
        $this->actingAs($user)
             ->get('/api/team/list?city=1');
    
        $this->seeJsonContains([ 'code' => 0 ]);
    
        $result = json_decode($this->response->getContent());
    
        $this->assertObjectHasAttribute('pages', $result);
        $this->assertObjectHasAttribute('teams', $result);
    
        $team = $result->teams[0];
        $this->assertObjectHasAttribute('id', $team);
        $this->assertObjectHasAttribute('name', $team);
        $this->assertObjectHasAttribute('introduction', $team);
        $this->assertObjectHasAttribute('logo_url', $team);
        $this->assertObjectHasAttribute('qr_code_url', $team);
        $this->assertObjectHasAttribute('activity_num', $team);
        $this->assertObjectHasAttribute('member_num', $team);
        $this->assertObjectHasAttribute('joined', $team);
    }
    
    //=========================================
    //              relate teams
    //=========================================
    public function testSuccessfulRelateTeams()
    {
        $teamAttributes = [];
        for ($i = 0; $i < 5; $i++) {
            array_push($teamAttributes, [
                'id'            => $i + 1,
                'creator_id'    => $i + 1,
                'name'          => 'team' . ($i + 1),
                'introduction'  => 'team' . ($i + 1),
                'certification' => true,
                'logo_url'      => 'logo_url' . ($i + 1),
                'qr_code_url'   => 'qr_code_url' . ($i + 1),
            ]);
        }
        $this->mockSearchService($teamAttributes);
        
        $user = factory(\Jihe\Models\User::class)->create([
            'id' => 1,
            'mobile'  => '13800138000',
        ]);
        factory(\Jihe\Models\City::class)->create([
            'id' => 1,
        ]);
    
        factory(\Jihe\Models\Team::class)->create([
            'id'      => 1,
            'city_id' => 1,
            'status'  => \Jihe\Entities\Team::STATUS_NORMAL,
        ]);
        
        factory(\Jihe\Models\Team::class)->create([
            'id'      => 2,
            'city_id' => 1,
            'status'  => \Jihe\Entities\Team::STATUS_NORMAL,
        ]);
        
        factory(\Jihe\Models\TeamMember::class)->create([
            'id'      => 1,
            'team_id' => 1,
            'user_id' => 1,
        ]);
        
        factory(\Jihe\Models\TeamMemberEnrollmentRequest::class)->create([
            'id'           => 1,
            'team_id'      => 1,
            'initiator_id' => 1,
            'status'       => \Jihe\Entities\TeamMemberEnrollmentRequest::STATUS_PENDING,
        ]);
        
        factory(\Jihe\Models\TeamMemberEnrollmentPermission::class)->create([
            'mobile'  => '13800138000',
            'team_id' => 2,
            'status'  => \Jihe\Entities\TeamMemberEnrollmentPermission::STATUS_PERMITTED,
        ]);
    
        $this->startSession();
    
        $this->actingAs($user)
             ->ajaxGet('/api/team/relate/list?city=1');
    
        $this->seeJsonContains([ 'code' => 0 ]);
    
        $result = json_decode($this->response->getContent());
    
        $this->assertObjectHasAttribute('enrolled_teams', $result);
        $this->assertObjectHasAttribute('requested_teams', $result);
        $this->assertObjectHasAttribute('recommended_teams', $result);
        $this->assertObjectHasAttribute('invited_teams', $result);
        $this->assertCount(1, $result->enrolled_teams);
        $this->assertCount(1, $result->requested_teams);
        $this->assertCount(5, $result->recommended_teams);
        $this->assertCount(1, $result->invited_teams);
    }
    
    public function testSuccessfulRelateTeams_ReturnOnlyCount()
    {
        $teamAttributes = [];
        for ($i = 0; $i < 5; $i++) {
            array_push($teamAttributes, [
                'id'            => $i + 1,
                'creator_id'    => $i + 1,
                'name'          => 'team' . ($i + 1),
                'introduction'  => 'team' . ($i + 1),
                'certification' => true,
                'logo_url'      => 'logo_url' . ($i + 1),
                'qr_code_url'   => 'qr_code_url' . ($i + 1),
            ]);
        }
        $this->mockSearchService($teamAttributes);
    
        $user = factory(\Jihe\Models\User::class)->create([
            'id' => 1,
            'mobile' => '13800138000',
        ]);
        factory(\Jihe\Models\City::class)->create([
            'id' => 1,
        ]);
    
        factory(\Jihe\Models\Team::class)->create([
            'id'      => 1,
            'city_id' => 1,
            'status'  => \Jihe\Entities\Team::STATUS_NORMAL,
        ]);
        
        factory(\Jihe\Models\Team::class)->create([
            'id'      => 2,
            'city_id' => 1,
            'status'  => \Jihe\Entities\Team::STATUS_NORMAL,
        ]);
    
        factory(\Jihe\Models\TeamMember::class)->create([
            'id'      => 1,
            'team_id' => 1,
            'user_id' => 1,
        ]);
    
        factory(\Jihe\Models\TeamMemberEnrollmentRequest::class)->create([
            'id'           => 1,
            'team_id'      => 1,
            'initiator_id' => 1,
            'status'       => \Jihe\Entities\TeamMemberEnrollmentRequest::STATUS_PENDING,
        ]);
        
        factory(\Jihe\Models\TeamMemberEnrollmentPermission::class)->create([
            'mobile'  => '13800138000',
            'team_id' => 2,
            'status'  => \Jihe\Entities\TeamMemberEnrollmentPermission::STATUS_PERMITTED,
        ]);
    
        $this->startSession();
    
        $this->actingAs($user)
        ->ajaxGet('/api/team/relate/list?city=1&only_count=1');
    
        $this->seeJsonContains([ 'code' => 0 ]);
    
        $result = json_decode($this->response->getContent());
    
        $this->assertObjectHasAttribute('enrolled_teams', $result);
        $this->assertObjectHasAttribute('requested_teams', $result);
        $this->assertObjectHasAttribute('recommended_teams', $result);
        $this->assertObjectHasAttribute('invited_teams', $result);
        $this->assertEquals(1, $result->enrolled_teams);
        $this->assertEquals(1, $result->requested_teams);
        $this->assertEquals(5, $result->recommended_teams);
        $this->assertEquals(1, $result->invited_teams);
    }
    
    //=========================================
    //                team
    //=========================================
    public function testSuccessfulTeam()
    {
        $user = factory(\Jihe\Models\User::class)->create([
            'mobile' => '13800001111',
        ]);
        factory(\Jihe\Models\Team::class)->create([
            'id'         => 1,
            'creator_id' => 1,
            'city_id'    => 1,
            'join_type'  => 1,
        ]);
        
        factory(\Jihe\Models\City::class)->create([
            'id' => 1,
        ]);
        
        factory(\Jihe\Models\User::class)->create([
            'id' => 1,
        ]);
        
        factory(\Jihe\Models\TeamRequirement::class)->create([
            'id'          => 1,
            'team_id'     => 1,
            'requirement' => '车型',
        ]);

        factory(\Jihe\Models\TeamMemberEnrollmentPermission::class)->create([
            'team_id' => 1,
            'status'  => 0,
            'mobile'  => '13800001111',
        ]);
    
        $this->startSession();
    
        $this->actingAs($user)
             ->get('/api/team/info?team=1');
        
        $this->seeJsonContains([ 'code' => 0 ]);
        
        $team = json_decode($this->response->getContent());
        $this->assertObjectHasAttribute('id', $team);
        $this->assertObjectHasAttribute('name', $team);
        $this->assertObjectHasAttribute('introduction', $team);
        $this->assertObjectHasAttribute('logo_url', $team);
        $this->assertObjectHasAttribute('qr_code_url', $team);
        $this->assertObjectHasAttribute('join_type', $team);
        $this->assertObjectHasAttribute('join_requirements', $team);
        $this->assertObjectHasAttribute('member_num', $team);
        $this->assertObjectHasAttribute('joined', $team);
        $this->assertObjectHasAttribute('activities_updated_at', $team);
        $this->assertObjectHasAttribute('members_updated_at', $team);
        $this->assertObjectHasAttribute('news_updated_at', $team);
        $this->assertObjectHasAttribute('albums_updated_at', $team);
        $this->assertObjectHasAttribute('notices_updated_at', $team);

        $requirements = $team->join_requirements;
        $this->assertCount(1, $requirements);
        
        $requirement = $requirements[0];
        $this->assertObjectHasAttribute('id', $requirement);
        $this->assertObjectHasAttribute('requirement', $requirement);
    }

    //=========================================
    //             teams of user
    //=========================================
    public function testSuccessfulTeamsOfUser()
    {
        $user = factory(\Jihe\Models\User::class)->create([
            'id' => 1,
            'mobile'  => '13800138000',
        ]);
        factory(\Jihe\Models\City::class)->create([
            'id' => 1,
        ]);

        factory(\Jihe\Models\Team::class)->create([
            'id'      => 1,
            'city_id' => 1,
            'status'  => \Jihe\Entities\Team::STATUS_NORMAL,
        ]);

        factory(\Jihe\Models\Team::class)->create([
            'id'      => 2,
            'city_id' => 1,
            'status'  => \Jihe\Entities\Team::STATUS_NORMAL,
        ]);

        factory(\Jihe\Models\Team::class)->create([
            'id'      => 3,
            'city_id' => 1,
            'status'  => \Jihe\Entities\Team::STATUS_NORMAL,
        ]);

        factory(\Jihe\Models\Team::class)->create([
            'id'      => 4,
            'city_id' => 1,
            'status'  => \Jihe\Entities\Team::STATUS_NORMAL,
        ]);

        factory(\Jihe\Models\Team::class)->create([
            'id'      => 5,
            'city_id' => 1,
            'status'  => \Jihe\Entities\Team::STATUS_NORMAL,
        ]);

        factory(\Jihe\Models\Team::class)->create([
            'id'      => 6,
            'city_id' => 1,
            'status'  => \Jihe\Entities\Team::STATUS_NORMAL,
        ]);

        factory(\Jihe\Models\Team::class)->create([
            'id'      => 7,
            'city_id' => 1,
            'status'  => \Jihe\Entities\Team::STATUS_NORMAL,
        ]);

        factory(\Jihe\Models\TeamMember::class)->create([
            'id'      => 1,
            'team_id' => 1,
            'user_id' => 1,
        ]);

        factory(\Jihe\Models\TeamMemberEnrollmentRequest::class)->create([
            'id'           => 1,
            'team_id'      => 2,
            'initiator_id' => 1,
            'status'       => \Jihe\Entities\TeamMemberEnrollmentRequest::STATUS_PENDING,
        ]);

        factory(\Jihe\Models\TeamMemberEnrollmentPermission::class)->create([
            'mobile'  => '13800138000',
            'team_id' => 3,
            'status'  => \Jihe\Entities\TeamMemberEnrollmentPermission::STATUS_PERMITTED,
        ]);

        factory(\Jihe\Models\Activity::class)->create([
            'id'           => 1,
            'team_id'      => 4,
            'city_id'      => 2,
            'title'        => '已发布测试活动－－_\%跑步',
            'status'       => \Jihe\Entities\Activity::STATUS_PUBLISHED,
            'end_time'     => date('Y-m-d 00:00:00', strtotime('+2 days')),
            'begin_time'   => date('Y-m-d 00:00:01', strtotime('+2 days')),
            'publish_time' => date('Y-m-d 10:11:11', strtotime('-10 days')),
        ]);

        factory(\Jihe\Models\Activity::class)->create([
            'id'           => 2,
            'team_id'      => 6,
            'city_id'      => 2,
            'title'        => '已发布测试活动－－_\%跑步',
            'status'       => \Jihe\Entities\Activity::STATUS_PUBLISHED,
            'end_time'     => date('Y-m-d 00:00:00', strtotime('+2 days')),
            'begin_time'   => date('Y-m-d 00:00:01', strtotime('+2 days')),
            'publish_time' => date('Y-m-d 10:11:11', strtotime('-10 days')),
        ]);

        factory(\Jihe\Models\ActivityMember::class)->create([
            'id'          => 1,
            'activity_id' => 1,
            'user_id'     => 1,
        ]);

        factory(\Jihe\Models\ActivityMember::class)->create([
            'id' => 2,
            'activity_id' => 2,
            'user_id'     => 1,
        ]);

        factory(\Jihe\Models\Activity::class)->create([
            'id'           => 3,
            'team_id'      => 7,
            'city_id'      => 2,
            'title'        => '已发布测试活动－－_\%跑步',
            'status'       => \Jihe\Entities\Activity::STATUS_PUBLISHED,
            'end_time'     => date('Y-m-d 00:00:00', strtotime('+2 days')),
            'begin_time'   => date('Y-m-d 00:00:01', strtotime('+2 days')),
            'publish_time' => date('Y-m-d 10:11:11', strtotime('-10 days')),
        ]);

        factory(\Jihe\Models\ActivityApplicant::class)->create([
            'id'          => 1,
            'activity_id' => 3,
            'user_id'     => 1,
        ]);

        $this->startSession();

        $this->actingAs($user)
             ->ajaxGet('/api/team/self/list');

        $this->seeJsonContains([ 'code' => 0 ]);

        $result = json_decode($this->response->getContent(), true);

        $this->assertArrayHasKey('teams', $result);
        $this->assertCount(6, $result['teams']);

        $team = $result['teams'][0];
        $this->assertArrayHasKey('joined', $team);
        $this->assertArrayHasKey('requested', $team);
        $this->assertArrayHasKey('in_whitelist', $team);
    }
    
    private function mockSearchService($return = '')
    {
        $searchService = \Mockery::mock(\Jihe\Contracts\Services\Search\SearchService::class);
        $searchService->shouldReceive('getRecommendActivity')->withAnyArgs()->andReturn($return);
        $searchService->shouldReceive('getRecommendTeam')->withAnyArgs()->andReturn($return);
        $this->app[\Jihe\Contracts\Services\Search\SearchService::class] = $searchService;
    
        return $searchService;
    }
}
