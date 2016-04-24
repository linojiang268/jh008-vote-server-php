<?php
namespace intg\Jihe\Controllers\Backstage;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use intg\Jihe\TestCase;
use intg\Jihe\RequestSignCheck;
use intg\Jihe\AuthDeviceCheck;

class ActivityMemberControllerTest extends TestCase
{
    use DatabaseTransactions;
    use RequestSignCheck;
    use AuthDeviceCheck;

    //=========================================
    //            exportMembers
    //=========================================
    public function testExportMembers()
    {
        $user = factory(\Jihe\Models\User::class)->create([
            'id'         => 1,
            'nick_name'  => 'First',
        ]);
        factory(\Jihe\Models\Team::class)->create([
            'id' => 1,
            'city_id' => 1,
            'creator_id' => 1,
        ]);
        factory(\Jihe\Models\City::class)->create([
            'id'   => 1,
            'name' => '成都',
        ]);
        factory(\Jihe\Models\Activity::class)->create([
            'id'           => 3,
            'team_id'      => 1,
            'city_id'      => 1,
            'title'        => '已发布测试活动－－_\%跑步',
            'status'       => \Jihe\Entities\Activity::STATUS_PUBLISHED,
            'end_time'     => date('Y-m-d 00:00:00', strtotime('+2 days')),
            'begin_time'   => date('Y-m-d 00:00:01', strtotime('+2 days')),
            'publish_time' => date('Y-m-d 10:11:11', strtotime('-10 days')),
        ]);
        factory(\Jihe\Models\User::class)->create([
            'id'         => 2,
            'nick_name'  => 'Second',
        ]);
        factory(\Jihe\Models\User::class)->create([
            'id'         => 3,
            'nick_name'  => 'Second',
        ]);
        factory(\Jihe\Models\User::class)->create([
            'id'         => 4,
            'nick_name'  => 'Second',
        ]);
        factory(\Jihe\Models\User::class)->create([
            'id'         => 5,
            'nick_name'  => 'Second',
        ]);
        factory(\Jihe\Models\ActivityMember::class)->create([
            'id' => 1,
            'activity_id' => 3,
            'user_id'     => 1,
            'mobile'     => '13800138001',
        ]);
        factory(\Jihe\Models\ActivityMember::class)->create([
            'id' => 2,
            'activity_id' => 3,
            'user_id'     => 2,
            'mobile'     => '13800138002',
        ]);
        factory(\Jihe\Models\ActivityMember::class)->create([
            'id' => 3,
            'activity_id' => 3,
            'user_id'     => 3,
            'mobile'     => '13800138003',
        ]);
        factory(\Jihe\Models\ActivityMember::class)->create([
            'id' => 4,
            'activity_id' => 3,
            'user_id'     => 4,
            'mobile'     => '13800138004',
        ]);
        factory(\Jihe\Models\ActivityMember::class)->create([
            'id' => 5,
            'activity_id' => 3,
            'user_id'     => 5,
            'mobile'     => '13800138005',
        ]);

        $this->actingAs($user)->get('/community/activity/member/export?activity=3');
        self::assertStringStartsWith('attachment', $this->response->headers->get('Content-Disposition'));
    }


}
