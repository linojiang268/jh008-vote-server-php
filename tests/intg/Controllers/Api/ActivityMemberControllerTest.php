<?php
namespace intg\Jihe\Controllers\Api;

use Illuminate\Foundation\Testing\DatabaseTransactions;

use intg\Jihe\TestCase;
use intg\Jihe\RequestSignCheck;
use intg\Jihe\AuthDeviceCheck;
use Jihe\Entities\Activity as ActivityEntity;

class ActivityMemberControllerTest extends TestCase
{
    use DatabaseTransactions;
    use RequestSignCheck;
    use AuthDeviceCheck;
    
    //=========================================
    //                score
    //=========================================
    public function testSuccessfulScore()
    {
        $user = factory(\Jihe\Models\User::class)->create([
            'id' => 1,
        ]);
        
        factory(\Jihe\Models\Activity::class)->create([
            'id'       => 1,
            'city_id'  => 1,
            'team_id'  => 1,
            'end_time' => date('Y-m-d H:i:s', strtotime('1990-12-01 00:00:00')),
            'status'   => ActivityEntity::STATUS_PUBLISHED,
        ]);
        
        factory(\Jihe\Models\City::class)->create([
            'id' => 1,
        ]);
        
        factory(\Jihe\Models\Team::class)->create([
            'id' => 1,
        ]);
        
        factory(\Jihe\Models\ActivityMember::class)->create([
            'activity_id' => 1,
            'user_id'     => 1,
        ]);
        
        $this->startSession();
        
        $this->actingAs($user)->ajaxPost('/api/activity/member/score', [
            '_token'     => csrf_token(),
            'activity'   => 1,
            'score'      => 3,
            'attributes' => [
                                '组织混乱',
                                '交通不便',
                            ],
            'memo'       => '活动有虚假信息。',
        ])->seeJsonContains([ 'code' => 0 ]);
    }
}
