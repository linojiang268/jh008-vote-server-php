<?php
namespace intg\Jihe\Repositories;

use intg\Jihe\TestCase;
use Jihe\Services\ActivityApplicantService;
use \PHPUnit_Framework_Assert as Assert;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Jihe\Entities\Activity as ActivityEntity;

class ActivityMemberRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    //=======================================
    //             Update Score
    //=======================================
    public function testUpdateScore()
    {
        $scoreAttributes = [
            '交通不方便',
            '组织混乱',
        ];
        
        $scoreMemo = '团长不够专业，现场很混乱。';
        
        factory(\Jihe\Models\ActivityMember::class)->create([
            'id' => 1,
            'activity_id' => 1,
            'user_id'     => 1,
            'score'       => null,
        ]);
        
        factory(\Jihe\Models\ActivityMember::class)->create([
            'id' => 11,
            'activity_id' => 11,
            'user_id'     => 1,
            'score'       => null,
        ]);
        
        factory(\Jihe\Models\ActivityMember::class)->create([
            'id' => 12,
            'activity_id' => 12,
            'user_id'     => 1,
            'score'       => null,
        ]);
        
        factory(\Jihe\Models\ActivityMember::class)->create([
            'id' => 13,
            'activity_id' => 13,
            'user_id'     => 1,
            'score'       => null,
        ]);
        
        factory(\Jihe\Models\ActivityMember::class)->create([
            'id' => 21,
            'activity_id' => 21,
            'user_id'     => 1,
            'score'       => null,
        ]);
        
        factory(\Jihe\Models\ActivityMember::class)->create([
            'id' => 22,
            'activity_id' => 22,
            'user_id'     => 1,
            'score'       => null,
        ]);
        
        factory(\Jihe\Models\ActivityMember::class)->create([
            'id' => 31,
            'activity_id' => 31,
            'user_id'     => 1,
            'score'       => 3,
        ]);
        
        // true if params not empty
        Assert::assertTrue($this->getRepository()->updateScore(
            1, 1,
            [
                'score' => 3,
                'score_attributes' => $scoreAttributes,
                'score_memo' => $scoreMemo,
            ]
        ));
        
        // ture if only score_attributes not exists
        Assert::assertTrue($this->getRepository()->updateScore(
            11, 1,
            [
                'score' => 3,
                'score_memo' => $scoreMemo,
            ]
        ));
        
        // ture if only score_attributes is null
        Assert::assertTrue($this->getRepository()->updateScore(
            12, 1,
            [
                'score' => 3,
                'score_attributes' => null,
                'score_memo' => $scoreMemo,
            ]
        ));
        
        // ture if only score_attributes is empty
        Assert::assertTrue($this->getRepository()->updateScore(
            13, 1,
            [
                'score' => 3,
                'score_attributes' => [],
                'score_memo' => $scoreMemo,
            ]
        ));
        
        // ture if only score_memo not exists
        Assert::assertTrue($this->getRepository()->updateScore(
            21, 1,
            [
                'score' => 3,
                'score_attributes' => $scoreAttributes,
            ]
        ));
        
        // ture if only score_memo is null
        Assert::assertTrue($this->getRepository()->updateScore(
            22, 1,
            [
                'score' => 3,
                'score_attributes' => $scoreAttributes,
                'score_memo' => null,
            ]
        ));
        
        // false if given user has scored in the activity
        Assert::assertFalse($this->getRepository()->updateScore(
            31, 1,
            [
                'score' => 3,
                'score_attributes' => $scoreAttributes,
                'score_memo' => $scoreMemo,
            ]
        ));
        
        // false if given user not activity's member
        Assert::assertFalse($this->getRepository()->updateScore(
            32, 1,
            [
                'score' => 3,
                'score_attributes' => $scoreAttributes,
                'score_memo' => $scoreMemo,
            ]
        ));
    
        $this->seeInDatabase('activity_members', [
            'activity_id'      => 1,
            'user_id'          => 1,
            'score'            => 3,
            'score_attributes' => json_encode(array_values($scoreAttributes)),
            'score_memo'       => $scoreMemo,
        ]);
    }
    
    //=======================================
    //                Scored
    //=======================================
    public function testScored()
    {
        factory(\Jihe\Models\ActivityMember::class)->create([
            'id' => 1,
            'activity_id' => 1,
            'user_id'     => 1,
            'score'       => 1,
        ]);
    
        factory(\Jihe\Models\ActivityMember::class)->create([
            'id' => 2,
            'activity_id' => 2,
            'user_id'     => 1,
            'score'       => null,
        ]);
    
        Assert::assertTrue($this->getRepository()->scored(1, 1));
        Assert::assertFalse($this->getRepository()->scored(1, 2));
        Assert::assertFalse($this->getRepository()->scored(1, 3));
    }
    
    //=======================================
    //             Count Members
    //=======================================
    public function testCountMembers()
    {
        factory(\Jihe\Models\ActivityMember::class)->create([
            'id' => 1,
            'activity_id' => 1,
            'user_id'     => 1,
            'score'       => 1,
        ]);
        
        factory(\Jihe\Models\ActivityMember::class)->create([
            'id' => 2,
            'activity_id' => 1,
            'user_id'     => 2,
            'score'       => null,
        ]);
        
        factory(\Jihe\Models\ActivityMember::class)->create([
            'id' => 3,
            'activity_id' => 2,
            'user_id'     => 5,
            'score'       => null,
        ]);
        
        Assert::assertEquals(2, $this->getRepository()->countMembers(1));
    }
    
    //=======================================
    //          Count Scored Members
    //=======================================
    public function testCountScoredMembers()
    {
        factory(\Jihe\Models\ActivityMember::class)->create([
            'id' => 1,
            'activity_id' => 1,
            'user_id'     => 1,
            'score'       => 1,
        ]);
    
        factory(\Jihe\Models\ActivityMember::class)->create([
            'id' => 2,
            'activity_id' => 1,
            'user_id'     => 2,
            'score'       => null,
        ]);
    
        factory(\Jihe\Models\ActivityMember::class)->create([
            'id' => 3,
            'activity_id' => 2,
            'user_id'     => 5,
            'score'       => 1,
        ]);
    
        Assert::assertEquals(1, $this->getRepository()->countScoredMembers(1));
    }
    
    //=======================================
    //               Sum Scored
    //=======================================
    public function testSumScored()
    {
        factory(\Jihe\Models\ActivityMember::class)->create([
            'id' => 1,
            'activity_id' => 1,
            'user_id'     => 1,
            'score'       => 3,
        ]);
    
        factory(\Jihe\Models\ActivityMember::class)->create([
            'id' => 2,
            'activity_id' => 1,
            'user_id'     => 2,
            'score'       => 4,
        ]);
        
        factory(\Jihe\Models\ActivityMember::class)->create([
            'id' => 3,
            'activity_id' => 1,
            'user_id'     => 3,
            'score'       => null,
        ]);
        
        factory(\Jihe\Models\ActivityMember::class)->create([
            'id' => 4,
            'activity_id' => 2,
            'user_id'     => 4,
            'score'       => 5,
        ]);
    
        Assert::assertEquals(7, $this->getRepository()->sumScored(1));
    }

    //=======================================
    //               getMemberCount
    //=======================================
    public function testGetMemberCount()
    {
        factory(\Jihe\Models\ActivityMember::class)->create([
            'id' => 1,
            'activity_id' => 1,
            'user_id'     => 1,
            'score'       => 1,
        ]);

        factory(\Jihe\Models\ActivityMember::class)->create([
            'id' => 2,
            'activity_id' => 2,
            'user_id'     => 1,
            'score'       => null,
        ]);

        factory(\Jihe\Models\ActivityMember::class)->create([
            'id' => 3,
            'activity_id' => 2,
            'user_id'     => 1,
            'score'       => null,
        ]);

        $ret = $this->getRepository()->getMemberCount([1, 2, 3]);

        Assert::assertCount(2, $ret);
        Assert::assertEquals(1, $ret[0]['activity_id']);
        Assert::assertEquals(1, $ret[0]['total']);
        Assert::assertEquals(2, $ret[1]['activity_id']);
        Assert::assertEquals(2, $ret[1]['total']);
    }

    //=======================================
    //        getTeamsOfJoinedActivities
    //=======================================
    public function testGetTeamsOfJoinedActivities()
    {
        factory(\Jihe\Models\Team::class)->create(['id' => 1, 'status' => \Jihe\Entities\Team::STATUS_NORMAL,]);
        factory(\Jihe\Models\Team::class)->create(['id' => 2, 'status' => \Jihe\Entities\Team::STATUS_NORMAL,]);

        factory(\Jihe\Models\Activity::class)->create([
            'id'           => 1,
            'team_id'      => 1,
            'city_id'      => 2,
            'title'        => '已发布测试活动－－_\%跑步',
            'status'       => \Jihe\Entities\Activity::STATUS_PUBLISHED,
            'end_time'     => date('Y-m-d 00:00:00', strtotime('+2 days')),
            'begin_time'   => date('Y-m-d 00:00:01', strtotime('+2 days')),
            'publish_time' => date('Y-m-d 10:11:11', strtotime('-10 days')),
        ]);

        factory(\Jihe\Models\Activity::class)->create([
            'id'           => 2,
            'team_id'      => 2,
            'city_id'      => 2,
            'title'        => '已发布测试活动－－_\%跑步',
            'status'       => \Jihe\Entities\Activity::STATUS_PUBLISHED,
            'end_time'     => date('Y-m-d 00:00:00', strtotime('+2 days')),
            'begin_time'   => date('Y-m-d 00:00:01', strtotime('+2 days')),
            'publish_time' => date('Y-m-d 10:11:11', strtotime('-10 days')),
        ]);

        factory(\Jihe\Models\Activity::class)->create([
            'id'           => 3,
            'team_id'      => 2,
            'city_id'      => 2,
            'title'        => '已发布测试活动－－_\%跑步',
            'status'       => \Jihe\Entities\Activity::STATUS_PUBLISHED,
            'end_time'     => date('Y-m-d 00:00:00', strtotime('+2 days')),
            'begin_time'   => date('Y-m-d 00:00:01', strtotime('+2 days')),
            'publish_time' => date('Y-m-d 10:11:11', strtotime('-10 days')),
        ]);

        factory(\Jihe\Models\ActivityMember::class)->create([
            'id' => 1,
            'activity_id' => 1,
            'user_id'     => 1,
        ]);

        factory(\Jihe\Models\ActivityMember::class)->create([
            'id' => 2,
            'activity_id' => 2,
            'user_id'     => 1,
        ]);

        factory(\Jihe\Models\ActivityMember::class)->create([
            'id' => 3,
            'activity_id' => 3,
            'user_id'     => 1,
        ]);

        Assert::assertEquals([1, 2], $this->getRepository()->findTeamsOfJoinedActivities(1));
    }

    //=======================================
    //        markAsCheckin
    //=======================================
    public function testMarkAsCheckinSuccessfully()
    {
        factory(\Jihe\Models\ActivityMember::class)->create([
            'id' => 3,
            'activity_id' => 3,
            'user_id'     => 1,
            'checkin'     => 0,
        ]);

        Assert::assertEquals(1, $this->getRepository()->markAsCheckin(1, 3));

        $this->seeInDatabase('activity_members', [
            'id'        => 3,
            'user_id'   => 1,
            'checkin'   => 1,       // aready checkin
        ]);
    }

    //=======================================
    //        getCheckinList
    //=======================================
    public function testGetCheckinListSuccessfully()
    {
        $this->genActivityMemberData(1, 5, ['checkin' => 0]);
        $this->genActivityMemberData(8, 10, ['checkin' => 1]);

        list(
            $waitingTotal, $waitingData
        )= $this->getRepository()->getCheckinList(1, 0, 2, 2);
        list(
            $doneTotal, $doneData
        ) = $this->getRepository()->getCheckinList(1, 1, 1, 2);

        Assert::assertEquals(5, $waitingTotal);
        Assert::assertCount(2, $waitingData);
        Assert::assertEquals(3, $waitingData[0]->getId());
        Assert::assertCount(0, $waitingData[0]->getCheckins());
        Assert::assertEquals(4, $waitingData[1]->getId());

        Assert::assertEquals(3, $doneTotal);
        Assert::assertCount(2, $doneData);
        Assert::assertEquals(8, $doneData[0]->getId());
        Assert::assertCount(1, $doneData[0]->getCheckins());
        Assert::assertEquals(1, $doneData[0]->getCheckins()[0]->getStep());
    }

    //=======================================
    //        searchCheckinInfo
    //=======================================
    public function testSearchCheckinInfoSuccessfully_UseMobile()
    {
        $this->genActivityMemberData(1, 1, [
            'mobile'    => '13800138000',
            'name'      => '尼斯',
            'checkin'   => 0
        ]);

        $checkins = $this->getRepository()->searchCheckinInfo(1, '13800138000', null);

        Assert::assertInstanceOf(\Illuminate\Support\Collection::class, $checkins);
        Assert::assertEquals(1, $checkins->count());
        Assert::assertEquals('13800138000', $checkins->first()->getMobile());
        Assert::assertCount(0, $checkins->first()->getCheckins());
    }

    public function testSearchCheckinInfoSuccessfully_UseName()
    {
        $this->genActivityMemberData(1, 1, [
            'mobile'    => '13800138000',
            'name'      => 'lld尼斯',
            'checkin'   => 1 
        ]);

        $checkins = $this->getRepository()->searchCheckinInfo(1, null, '尼');

        Assert::assertInstanceOf(\Illuminate\Support\Collection::class, $checkins);
        Assert::assertEquals(1, $checkins->count());
        Assert::assertEquals('13800138000', $checkins->first()->getMobile());
        Assert::assertCount(1, $checkins->first()->getCheckins());
        Assert::assertEquals(1, $checkins->first()->getCheckins()->first()->getStep());
        Assert::assertEquals(100, $checkins->first()->getCheckins()->first()->getProcessId());
    }

    private function genActivityMemberData($beginId, $stopId, $config)
    {
        foreach (range($beginId, $stopId) as $id) {
            $conf = array_merge([
                'id'            => $id,
                'activity_id'   => 1,
            ], $config);
            $member = factory(\Jihe\Models\ActivityMember::class)->create($conf);
            factory(\Jihe\Models\ActivityCheckIn::class)->create([
                'activity_id'   => $config['checkin'] ? $member->activity_id : 12345,
                'user_id'       => $member->user_id,
                'step'          => 1,
                'process_id'    => 100,
            ]);
            factory(\Jihe\Models\ActivityCheckIn::class)->create([
                'activity_id'   => $member->activity_id,
                'user_id'       => $member->user_id,
                'step'          => 2,
                'process_id'    => 100,
            ]);
        }
    }

    //=======================================
    //    getActivityMembersByMobiles
    //=======================================
    public function testGetActivityMembersByMobiles()
    {
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
        $result = $this->getRepository()->getActivityMembersByMobiles(3, [
            '13800138001',
            '13800138002',
            '13800138003',
            '13800138004',
            '13800138005',
        ]);
        self::assertEquals('13800138004', array_search(4, $result));
    }

    //=======================================
    //    listActivityMembers
    //=======================================
    public function testListActivityMembers()
    {
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
        for($i=1;$i<=5;$i++){
            list($count, $result) = $this->getRepository()->listActivityMembers(3, $i, 1);
            self::assertEquals(5, $count);
            self::assertEquals('1380013800'.$i, $result[0][0]);
        }
    }

    /**
     * @return \Jihe\Contracts\Repositories\ActivityMemberRepository
     */
    private function getRepository()
    {
        return $this->app[\Jihe\Contracts\Repositories\ActivityMemberRepository::class];
    }
}
