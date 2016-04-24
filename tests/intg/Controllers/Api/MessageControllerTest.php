<?php
namespace intg\Jihe\Controllers\Api;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use intg\Jihe\TestCase;
use intg\Jihe\RequestSignCheck;
use intg\Jihe\AuthDeviceCheck;
use Jihe\Entities\Team as TeamEntity;

class MessageControllerTest extends TestCase
{
    use DatabaseTransactions;
    use RequestSignCheck;
    use AuthDeviceCheck;
    
    // =========================================
    //             listMessages
    // =========================================
    public function testSuccessfulMessagesOfSystem()
    {
        $user = factory(\Jihe\Models\User::class)->create([ 
            'id' => 1 
        ]);
        factory(\Jihe\Models\City::class)->create([ 
            'id' => 1 
        ]);
        
        factory(\Jihe\Models\Message::class)->create([ 
            'team_id' => null,
            'activity_id' => null,
            'user_id' => null,
            'content' => 'system msg',
            'created_at' => '2015-08-03 00:00:00' 
        ]);
        factory(\Jihe\Models\Message::class)->create([ 
            'created_at' => '2015-07-30 00:00:00' 
        ]);
        factory(\Jihe\Models\Message::class)->create([ 
            'team_id' => null,
            'activity_id' => null,
            'user_id' => 1,
            'content' => 'user system msg',
            'created_at' => '2015-08-03 00:00:00' 
        ]);
        factory(\Jihe\Models\Message::class)->create([ 
            'team_id' => null,
            'activity_id' => null,
            'user_id' => 2,
            'content' => 'user system msg',
            'created_at' => '2015-08-03 00:00:00' 
        ]);
        
        $this->startSession();
        
        $this->actingAs($user)->ajaxGet("/api/message/list?type=1&last_requested_time=2015-08-01 00:00:00");
        $this->seeJsonContains([ 
            'code' => 0 
        ]);
        
        $result = json_decode($this->response->getContent());
        
        $this->assertObjectHasAttribute('last_requested_time', $result);
        $this->assertObjectHasAttribute('total_num', $result);
        $this->assertObjectHasAttribute('messages', $result);
        $this->assertCount(2, $result->messages);
        
        $systemMsg = $result->messages [0];
        $this->assertObjectHasAttribute('id', $systemMsg);
        $this->assertObjectHasAttribute('content', $systemMsg);
        $this->assertObjectHasAttribute('type', $systemMsg);
        $this->assertObjectHasAttribute('attributes', $systemMsg);
        $this->assertObjectHasAttribute('created_at', $systemMsg);
    }
    
    public function testSuccessfulMessagesOfNotices()
    {
        $user = factory(\Jihe\Models\User::class)->create([ 
            'id' => 1 
        ]);
        factory(\Jihe\Models\City::class)->create([ 
            'id' => 1 
        ]);
        
        factory(\Jihe\Models\Team::class)->create([ 
            'id' => 1,
            'city_id' => 1,
            'name' => 'shetuan',
            'status' => TeamEntity::STATUS_NORMAL 
        ]);
        factory(\Jihe\Models\TeamMember::class)->create([
            'id'      => 1,
            'team_id' => 1,
            'user_id' => 1,
        ]);
        
        factory(\Jihe\Models\Message::class)->create([ 
            'team_id' => 1,
            'created_at' => '2015-08-03 00:00:00' 
        ]);
        factory(\Jihe\Models\Message::class)->create([ 
            'team_id' => 1,
            'user_id' => 1,
            'created_at' => '2015-08-02 00:00:00' 
        ]);
        factory(\Jihe\Models\Message::class)->create([ 
            'team_id' => 1,
            'user_id' => 1,
            'created_at' => '2015-07-30 00:00:00' 
        ]);
        factory(\Jihe\Models\Message::class)->create([ 
            'team_id' => 1,
            'user_id' => 2,
            'created_at' => '2015-08-03 00:00:00' 
        ]);
        
        $this->startSession();
        
        $this->actingAs($user)->ajaxGet('/api/message/list?type=2&last_requested_time=2015-08-01 00:00:00');
        
        $this->seeJsonContains([ 
            'code' => 0 
        ]);
        
        $result = json_decode($this->response->getContent());
        
        $this->assertObjectHasAttribute('last_requested_time', $result);
        $this->assertObjectHasAttribute('total_num', $result);
        $this->assertObjectHasAttribute('messages', $result);
        $this->assertCount(2, $result->messages);
        
        $message = $result->messages [0];
        $this->assertObjectHasAttribute('id', $message);
        $this->assertObjectHasAttribute('content', $message);
        $this->assertObjectHasAttribute('type', $message);
        $this->assertObjectHasAttribute('attributes', $message);
        $this->assertObjectHasAttribute('created_at', $message);
        $this->assertObjectHasAttribute('team_name', $message);
        $this->assertNotEmpty($message->team_name);
    }
    
    public function testSuccessfulMessagesOfNotices_NoTeam_And_NoActivity()
    {
        $user = factory(\Jihe\Models\User::class)->create([
            'id' => 1
        ]);
        factory(\Jihe\Models\City::class)->create([
            'id' => 1
        ]);
    
        factory(\Jihe\Models\Team::class)->create([
            'id'      => 1,
            'city_id' => 1,
            'name'    => 'shetuan',
            'status'  => TeamEntity::STATUS_NORMAL
        ]);
        factory(\Jihe\Models\Team::class)->create([
            'id'      => 2,
            'city_id' => 1,
            'name'    => 'shetuan2',
            'status'  => TeamEntity::STATUS_NORMAL
        ]);
    
        factory(\Jihe\Models\Message::class)->create([
            'id'         => 1,
            'team_id'    => 1,
            'created_at' => '2015-08-03 00:00:00'
        ]);
        factory(\Jihe\Models\Message::class)->create([
            'team_id'    => 1,
            'user_id'    => 1,
            'created_at' => '2015-08-02 00:00:00'
        ]);
        factory(\Jihe\Models\Message::class)->create([
            'team_id'    => 1,
            'user_id'    => 1,
            'created_at' => '2015-07-30 00:00:00'
        ]);
        factory(\Jihe\Models\Message::class)->create([
            'team_id'    => 1,
            'user_id'    => 2,
            'created_at' => '2015-08-03 00:00:00'
        ]);
    
        $this->startSession();
    
        $this->actingAs($user)->ajaxGet('/api/message/list?type=2&last_requested_time=2015-08-01 00:00:00');
    
        $this->seeJsonContains([
                'code' => 0
        ]);
    
        $result = json_decode($this->response->getContent());
    
        $this->assertObjectHasAttribute('last_requested_time', $result);
        $this->assertObjectHasAttribute('total_num', $result);
        $this->assertObjectHasAttribute('messages', $result);
        $this->assertCount(1, $result->messages);
    }

    // =========================================
    //            listMessagesOfUser
    // =========================================
    public function testSuccessfulListMessagesOfUser_NoMessages()
    {
        $user = factory(\Jihe\Models\User::class)->create([
            'id' => 1
        ]);

        $this->startSession();

        $this->actingAs($user)->ajaxGet('/api/message/new?last_requested_time=2015-08-01 00:00:00');

        $this->seeJsonContains([
            'code' => 0
        ]);

        $result = json_decode($this->response->getContent());

        $this->assertObjectHasAttribute('last_requested_time', $result);
        $this->assertObjectHasAttribute('new_messages', $result);
        $this->assertCount(0, $result->new_messages);
    }

    // =========================================
    //                checkNew
    // =========================================
    public function testSuccessfulCheckNew_NoMessages()
    {
        $user = factory(\Jihe\Models\User::class)->create([
            'id' => 1
        ]);

        $this->startSession();

        $this->actingAs($user)->ajaxGet('/api/check_new?last_requested_time=2015-08-01 00:00:00');

        $this->seeJsonContains([
            'code' => 0
        ]);

        $result = json_decode($this->response->getContent());

        $this->assertObjectHasAttribute('last_requested_time', $result);
        $this->assertObjectHasAttribute('new_messages', $result);
        $this->assertEquals(0, $result->new_messages);
    }
}
