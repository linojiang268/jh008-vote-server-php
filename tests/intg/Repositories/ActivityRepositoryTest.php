<?php
namespace intg\Jihe\Repositories;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use intg\Jihe\TestCase;
use Jihe\Entities\Activity as ActivityEntity;
use Jihe\Entities\Team as TeamEntity;
use Jihe\Models\Activity;
use Jihe\Models\ActivityApplicant;
use Jihe\Models\ActivityMember;
use Jihe\Models\City;
use Jihe\Models\Team;
use Jihe\Models\TeamMember;

class ActivityRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    //===========================================
    //            getTeamActivitiesCount
    //===========================================
    public function testGetTeamActivitiesCount_OneActivityEXists()
    {
        factory(Activity::class)->create([
            'team_id' => 1,
            'status'  => ActivityEntity::STATUS_PUBLISHED,
        ]);
        self::assertEquals(1, $this->getRepository()
            ->getTeamActivitiesCount(1));
    }

    public function testGetTeamActivitiesCount_ActivityNotExists()
    {
        self::assertEmpty($this->getRepository()
            ->getTeamActivitiesCount(1));
    }

    //===========================================
    //            getCityActivitiesCount
    //===========================================
    public function testGetCityActivitiesCount_OneActivityEXists()
    {
        factory(Activity::class)->create([
            'city_id' => 1,
            'status'  => ActivityEntity::STATUS_PUBLISHED,
        ]);
        self::assertEquals(1, $this->getRepository()
            ->getCityActivitiesCount(1));
    }

    public function testGetCityActivitiesCount_ActivityNotExists()
    {
        self::assertEmpty($this->getRepository()
            ->getCityActivitiesCount(1));
    }

    //===========================================
    //            findPublishedActivitiesInTeam
    //===========================================
    public function testFindPublishedActivitiesInTeam_ActivitiesNotExists()
    {
        list($total, $activities) = $this->getRepository()
            ->findPublishedActivitiesInTeam(1);
        self::assertEquals(0, $total);
        self::assertEmpty($activities);
    }

    public function testFindPublishedActivitiesInTeam_ActivitiesExists()
    {
        $this->cretateEveryOneStatusData();
        list($total, $activities) = $this->getRepository()
            ->findPublishedActivitiesInTeam(1);
        self::assertEquals(2, $total);
        self::assertPublishedActivityEquals($activities[1]);
    }

    //===========================================
    //            findActivitiesInTeam
    //===========================================
    public function testFindActivitiesInTeam_ActivitiesNotExists()
    {
        list($total, $activities) = $this->getRepository()
            ->findActivitiesInTeam(1);
        self::assertEquals(0, $total);
        self::assertEmpty($activities);
    }

    public function testFindActivitiesInTeam_ActivitiesExists()
    {
        $this->cretateEveryOneStatusData();
        list($total, $activities) = $this->getRepository()
            ->findActivitiesInTeam(1);
        self::assertEquals(3, $total);
        self::assertTowActivitiesEquals([$activities[1], $activities[2]]);
    }

    //===========================================
    //         findPublishedActivitiesInCity
    //===========================================

    public function testFindPublishedActivitiesInCity_ActivitiesNotExists()
    {
        list($total, $activities) = $this->getRepository()
            ->findPublishedActivitiesInCity(1);
        self::assertEquals(0, $total);
        self::assertEmpty($activities);
    }

    public function testFindPublishedActivitiesInCity_ActivitiesExists()
    {
        $this->cretateEveryOneStatusData();
        list($total, $activities) = $this->getRepository()
            ->findPublishedActivitiesInCity(2);
        self::assertEquals(5, $total);
        self::assertPublishedActivityEquals($activities[4]);
    }

    //===========================================
    //     searchPublishedActivitiesByTitle
    //===========================================
    public function testSearchPublishedActivitiesByTitle_ActivitiesNotExists()
    {
        $this->createDeleteStatusData();
        list($total, $activities) = $this->getRepository()
            ->searchPublishedActivitiesByTitle('活动');
        self::assertEquals(0, $total);
        self::assertEmpty($activities);
    }

    public function testSearchPublishedActivitiesByTitle_ActivitiesExists()
    {
        $this->cretateEveryOneStatusData();
        list($total, $activities) = $this->getRepository()
            ->searchPublishedActivitiesByTitle('_');
        self::assertEquals(1, $total);
        self::assertPublishedActivityEquals($activities[0]);
    }

    //===========================================
    //            searchTeamActivitiesByTitle
    //===========================================
    public function testSearchTeamActivitiesByTitle_ActivitiesNotExists()
    {
        list($total, $activities) = $this->getRepository()
            ->searchTeamActivitiesByTitle('活动', 1);
        self::assertEquals(0, $total);
        self::assertEmpty($activities);
    }

    public function testSearchTeamActivitiesByTitle_ActivitiesExists()
    {
        $this->cretateEveryOneStatusData();
        list($total, $activities) = $this->getRepository()
            ->searchTeamActivitiesByTitle('活动', 1);
        self::assertEquals(3, $total);
        self::assertTowActivitiesEquals([$activities[1], $activities[2]]);
    }

    //===========================================
    //            findActivityById
    //===========================================
    public function testFindActivityById_ActivitiesNotExists()
    {
        self::assertEmpty($this->getRepository()->findActivityById(1));
        $this->createDeleteStatusData();
        self::assertEmpty($this->getRepository()->findActivityById(1));
    }

    public function testFindActivityById_ActivitiesExists()
    {
        $this->cretateEveryOneStatusData();
        $activities = $this->getRepository()->findActivityById(2);
        self::assertPublishedActivityEquals($activities);
    }

    //===========================================
    //            update
    //===========================================
    public function testUpdate_ActivitiesNotExists()
    {
        self::assertFalse($this->getRepository()
            ->updateOnce(1,
                ['city_id'  => 2,
                 'location' => [123.5, -133.5],
                ])
        );
    }

    public function testUpdate_ActivitiesExists()
    {
        $this->cretateEveryOneStatusData();
        self::assertTrue($this->getRepository()
            ->updateOnce(1,
                ['city_id'  => 2,
                 'location' => [56.5, -78.5],
                 'title'    => '已发布测试活动－－_\%跑步',
                 'status'   => ActivityEntity::STATUS_PUBLISHED,
                ])
        );
        $activities = $this->getRepository()->findActivityById(1);
        self::assertPublishedActivityEquals($activities);
        $point = $activities->getLocation();
        self::assertEquals(56.5, $point[0]);
        self::assertEquals(-78.5, $point[1]);
    }

    public function testUpdateHasAlbum_ActivitiesExists()
    {
        $this->cretateEveryOneStatusData();
        self::assertTrue($this->getRepository()->updateHasAlbum(1));
    }

    //===========================================
    //            findActivitiesByPoint
    //===========================================
    public function testFindActivitiesByPoint_ActivitiesNotExists()
    {
        list($total, $activities) = $this->getRepository()
            ->findActivitiesByPoint([12, 34], 1000);
        self::assertEquals(0, $total);
        self::assertEmpty($activities);
    }

    public function testFindActivitiesByPoint_ActivitiesExists()
    {
        factory(City::class)->create([
            'id'   => 1,
            'name' => '成都',
        ]);

        factory(Team::class)->create([
            'id'      => 1,
            'city_id' => 2,
            'status'  => TeamEntity::STATUS_NORMAL,
        ]);

        factory(City::class)->create([
            'id'   => 2,
            'name' => '北京',
        ]);

        factory(Activity::class)->create([
            'id'       => 1,
            'team_id'  => 1,
            'city_id'  => 2,
            'title'    => '已发布测试活动－－_\%跑步',
            'status'   => ActivityEntity::STATUS_PUBLISHED,
            'location' => [15.99716, 20],
        ]);

        factory(Activity::class)->create([
            'id'       => 2,
            'team_id'  => 1,
            'city_id'  => 1,
            'title'    => '已发布测试活动－－_\%皮划艇',
            'status'   => ActivityEntity::STATUS_PUBLISHED,
            'location' => [14.5, 20],
        ]);
        list($total, $activities) = $this->getRepository()
            ->findActivitiesByPoint([16, 20], 5);
        self::assertEquals(1, $total);
        self::assertPublishedActivityEquals($activities[0]);
    }

    //===========================================
    //            deleteActivityById
    //===========================================
    public function testDeleteActivityById_ActivitiesNotExists()
    {
        self::assertEmpty($this->getRepository()->deleteActivityById(1));
    }

    public function testDeleteActivityById_ActivitiesExists()
    {
        $this->cretateEveryOneStatusData();
        self::assertNotEmpty($this->getRepository()->deleteActivityById(1));
        self::assertEmpty($this->getRepository()->findActivityById(1));
    }

    //===========================================
    //        findEndOfYesterdayActivitiesByIds
    //===========================================
    public function testFindEndOfYesterdayActivitiesByIds_ActivitiesNotExists()
    {
        self::assertEmpty($this->getRepository()->findEndOfYesterdayActivitiesByIds([2, 4]));
    }

    //===========================================
    //        findUserActivities
    //===========================================
    public function testFindUserActivities_ActivitiesExists()
    {
        $this->cretateEveryOneStatusData();
        $ret = $this->getRepository()->findUserActivities(1);
        self::assertNotEmpty($ret[1]);
        $ret = $this->getRepository()->findUserActivities(1, 'NotBeginning');
        self::assertNotEmpty($ret[1]);
        $ret = $this->getRepository()->findUserActivities(1, 'WaitPay');
        self::assertEmpty($ret[1]);
        $ret = $this->getRepository()->findUserActivities(1, 'End');
        self::assertEmpty($ret[1]);
        $ret = $this->getRepository()->findUserActivities(1, 'Enrolling');
        self::assertEmpty($ret[1]);
    }

    //===========================================
    //        getTeamsActivitiesCount
    //===========================================
    public function testGetTeamsActivitiesCount_ActivitiesExists()
    {
        $this->cretateEveryOneStatusData();
        $response = $this->getRepository()->getTeamsActivitiesCount([1, 2, 3]);
        self::assertEquals(3, count($response));
        self::assertEquals(1, $response[1]);
        self::assertEquals(1, $response[2]);
        self::assertEquals(2, $response[3]);
    }

    //===========================================
    //        activityAdvanceRemind
    //===========================================
    public function testActivityAdvanceRemind_ActivitiesExists()
    {
        factory(City::class)->create([
            'id'   => 2,
            'name' => '成都',
        ]);
        factory(Team::class)->create([
            'id'     => 1,
            'status' => TeamEntity::STATUS_NORMAL,
        ]);
        factory(Activity::class)->create([
            'id'           => 1,
            'team_id'      => 1,
            'city_id'      => 2,
            'title'        => '已发布测试活动－－_\%跑步',
            'status'       => ActivityEntity::STATUS_PUBLISHED,
            'end_time'     => date('Y-m-d 00:00:00', strtotime('+2 days')),
            'begin_time'   => date('Y-m-d 00:00:01', strtotime('+2 days')),
            'publish_time' => date('Y-m-d 10:11:11', strtotime('-10 days')),
        ]);
        factory(Activity::class)->create([
            'id'           => 2,
            'team_id'      => 1,
            'city_id'      => 2,
            'title'        => '已发布测试活动－－_\%游泳',
            'status'       => ActivityEntity::STATUS_PUBLISHED,
            'end_time'     => date('Y-m-d 00:00:00', strtotime('+2 days')),
            'begin_time'   => date('Y-m-d 00:00:00', strtotime('+1 days')),
            'publish_time' => date('Y-m-d 10:11:11', strtotime('-10 days')),
        ]);
        $response = $this->getRepository()->activityAdvanceRemind(1);
        self::assertEquals(1, count($response));

    }
    //===========================================
    //   findParticipatedInTeamOfActivitiesUser
    //===========================================
    public function testFindParticipatedInTeamOfActivitiesUser_ActivitiesExists()
    {
        factory(\Jihe\Models\User::class)->create(['id' => 1, 'mobile' => '17256716371']);
        factory(\Jihe\Models\User::class)->create(['id' => 2, 'mobile' => '17256716372']);
        factory(\Jihe\Models\User::class)->create(['id' => 3, 'mobile' => '17256716373']);
        factory(\Jihe\Models\User::class)->create(['id' => 4, 'mobile' => '17256716374']);
        factory(\Jihe\Models\User::class)->create(['id' => 5, 'mobile' => '17256716375']);
        factory(TeamMember::class)->create(['user_id' => 1, 'team_id' => 1, 'status' => 0]);
        factory(TeamMember::class)->create(['user_id' => 2, 'team_id' => 1, 'status' => 0]);
        factory(TeamMember::class)->create(['user_id' => 3, 'team_id' => 1, 'status' => 0]);
        factory(City::class)->create(['id' => 2, 'name' => '成都',]);
        factory(Team::class)->create(['id' => 1, 'status' => TeamEntity::STATUS_NORMAL,]);
        factory(Activity::class)->create([
            'id'           => 1,
            'team_id'      => 1,
            'city_id'      => 2,
            'title'        => '已发布测试活动－－_\%跑步',
            'status'       => ActivityEntity::STATUS_PUBLISHED,
            'end_time'     => date('Y-m-d 00:00:00', strtotime('+2 days')),
            'begin_time'   => date('Y-m-d 00:00:01', strtotime('+2 days')),
            'publish_time' => date('Y-m-d 10:11:11', strtotime('-10 days')),
        ]);
        factory(Activity::class)->create([
            'id'           => 2,
            'team_id'      => 1,
            'city_id'      => 2,
            'title'        => '已发布测试活动－－_\%游泳',
            'status'       => ActivityEntity::STATUS_PUBLISHED,
            'end_time'     => date('Y-m-d 00:00:00', strtotime('+2 days')),
            'begin_time'   => date('Y-m-d 00:00:00', strtotime('+1 days')),
            'publish_time' => date('Y-m-d 10:11:11', strtotime('-10 days')),
        ]);
        factory(ActivityMember::class)->create(['activity_id' => 1, 'user_id' => 1, 'mobile' => '17256716371']);
        factory(ActivityMember::class)->create(['activity_id' => 1, 'user_id' => 2, 'mobile' => '17256716372']);
        factory(ActivityMember::class)->create(['activity_id' => 1, 'user_id' => 3, 'mobile' => '17256716373']);
        factory(ActivityMember::class)->create(['activity_id' => 1, 'user_id' => 4, 'mobile' => '17256716374']);
        factory(ActivityMember::class)->create(['activity_id' => 1, 'user_id' => 5, 'mobile' => '17256716375']);

        factory(ActivityMember::class)->create(['activity_id' => 2, 'user_id' => 1, 'mobile' => '17256716371']);
        factory(ActivityMember::class)->create(['activity_id' => 2, 'user_id' => 2, 'mobile' => '17256716372']);
        factory(ActivityMember::class)->create(['activity_id' => 2, 'user_id' => 3, 'mobile' => '17256716373']);
        factory(ActivityMember::class)->create(['activity_id' => 2, 'user_id' => 4, 'mobile' => '17256716374']);
        factory(ActivityMember::class)->create(['activity_id' => 2, 'user_id' => 5, 'mobile' => '17256716375']);

        $response = $this->getRepository()->findParticipatedInTeamOfActivitiesUser(1);
        self::assertEquals('17256716374', $response[0]);
        self::assertEquals('17256716375', $response[1]);
    }

    //===========================================
    //        find
    //===========================================
    public function testFind_ActivitiesExists()
    {
        $this->cretateEveryOneStatusData();
        $activities = $this->getRepository()->find(2);
        self::assertNotEmpty($activities);
        self::assertPublishedActivityEquals($activities);
        $activities = $this->getRepository()->find(1);
        self::assertNotEmpty($activities);
        $activities = $this->getRepository()->find(3);
        self::assertNotEmpty($activities);
    }

    public function testFind_ActivitiesNotExists()
    {
        $activities = $this->getRepository()->find(3);
        self::assertEmpty($activities);
    }

    //===========================================
    //        findHomPageUserActivities
    //===========================================
    public function testFindHomPageUserActivities_ActivitiesExists()
    {
        $this->cretateEveryOneStatusData();
        factory(Activity::class)->create([
            'id'                => 22,
            'team_id'           => 1,
            'city_id'           => 2,
            'title'             => '已发布测试活动－－_\%跑步',
            'status'            => ActivityEntity::STATUS_PUBLISHED,
            'begin_time'        => date('Y-m-d 00:00:00', strtotime('-2 day')),
            'end_time'          => date('Y-m-d 10:11:11', strtotime('-3 day')),
            'publish_time'      => date('Y-m-d 00:00:00', strtotime('-10 day')),
            'enroll_begin_time' => date('Y-m-d 00:00:00', strtotime('-5 day')),
            'enroll_end_time'   => date('Y-m-d 10:11:11', strtotime('-6 day')),
        ]);
        factory(Activity::class)->create([
            'id'                => 23,
            'team_id'           => 1,
            'city_id'           => 2,
            'title'             => '已发布测试活动－－_\%跑步3',
            'status'            => ActivityEntity::STATUS_PUBLISHED,
            'begin_time'        => date('Y-m-d 00:00:00', strtotime('-2 day')),
            'end_time'          => date('Y-m-d 10:11:11', strtotime('-3 day')),
            'publish_time'      => date('Y-m-d 00:00:00', strtotime('-10 day')),
            'enroll_begin_time' => date('Y-m-d 00:00:00', strtotime('-5 day')),
            'enroll_end_time'   => date('Y-m-d 10:11:11', strtotime('-6 day')),
        ]);

        factory(ActivityApplicant::class)->create([
            'id'          => 22,
            'user_id'     => 1,
            'activity_id' => 2,
            'status'      => ActivityApplicant::STATUS_SUCCESS,
        ]);

        factory(ActivityApplicant::class)->create([
            'user_id'     => 1,
            'activity_id' => 23,
            'status'      => ActivityApplicant::STATUS_SUCCESS,
        ]);

        $activities = $this->getRepository()->findHomPageUserActivities(1);
        self::assertEquals(3, count($activities));
        self::assertEquals(4, $activities[0]->getId());
        self::assertEquals(1, $activities[0]->getSubStatus());
        self::assertEquals(5, $activities[1]->getSubStatus());
        self::assertEquals(5, $activities[2]->getSubStatus());
    }

    public function testFindEndOfYesterdayActivitiesByIds_ActivitiesExists()
    {
        $this->cretateEveryOneStatusData();
        $activities = $this->getRepository()->findEndOfYesterdayActivitiesByIds([2, 4]);
        self::assertEquals(1, count($activities));
        self::assertEquals(2, $activities[0]->getId());
        self::assertEquals(1, $activities[0]->getStatus());
        self::assertEquals('已发布测试活动－－_\%跑步', $activities[0]->getTitle());
    }

    //===========================================
    //        searchEndActivityByTime
    //===========================================
    public function testSearchEndActivityByTime_ActivitiesExists()
    {
        $this->cretateEveryOneStatusData();
        $start = date('Y-m-d 00:00:00', strtotime('-60 day'));
        $end = date('Y-m-d 00:00:00', strtotime('-2 day'));
        $activities = $this->getRepository()
            ->searchEndActivityByTime($start, $end, 1, 10000);
        self::assertEquals(2, $activities[0]->getId());
        self::assertPublishedActivityEquals($activities[0]);
    }

    private static function assertTowActivitiesEquals($activities)
    {
        self::assertCount(2, $activities);
        //select order by id desc
        self::assertEquals(ActivityEntity::STATUS_PUBLISHED, $activities[0]->getStatus());
        self::assertEquals(ActivityEntity::STATUS_NOT_PUBLISHED, $activities[1]->getStatus());

        //STATUS_PUBLISHED
        self::assertEquals(1, $activities[0]->getTeam()->getId());
        self::assertEquals(2, $activities[0]->getCity()->getId());
        self::assertEquals('已发布测试活动－－_\%跑步', $activities[0]->getTitle());

        //STATUS_NOT_PUBLISHED
        self::assertEquals(1, $activities[1]->getTeam()->getId());
        self::assertEquals(1, $activities[1]->getCity()->getId());
        self::assertEquals('未发布测试活动－－游泳', $activities[1]->getTitle());
    }

    private static function assertPublishedActivityEquals($activity, Array $options = [])
    {
        if (!empty($options)) {
            foreach ($options as $field => $value) {
                self::assertEquals($value[0], $activity->$value[1]());
            }
        } else {
            self::assertEquals('已发布测试活动－－_\%跑步', $activity->getTitle());
            self::assertEquals(ActivityEntity::STATUS_PUBLISHED, $activity->getStatus());
            self::assertEquals(1, $activity->getTeam()->getId());
            self::assertEquals(2, $activity->getCity()->getId());
        }


    }

    private function createDeleteStatusData()
    {
        factory(City::class)->create([
            'id'   => 1,
            'name' => '成都',
        ]);

        factory(Team::class)->create([
            'id'     => 1,
            'status' => TeamEntity::STATUS_NORMAL,
        ]);

        factory(Activity::class)->create([
            'team_id' => 1,
            'city_id' => 1,
            'title'   => '已删除测试活动－－自行车',
            'status'  => ActivityEntity::STATUS_DELETE,
        ]);
    }

    private function cretateEveryOneStatusData()
    {
        factory(City::class)->create([
            'id'   => 1,
            'name' => '成都',
        ]);

        factory(City::class)->create([
            'id'   => 2,
            'name' => '北京',
        ]);

        factory(Team::class)->create([
            'id'     => 1,
            'name'   => '集合啦',
            'status' => TeamEntity::STATUS_NORMAL,
        ]);

        factory(Team::class)->create([
            'id'      => 2,
            'name'    => '不集合啦',
            'city_id' => 2,
            'status'  => TeamEntity::STATUS_NORMAL,
        ]);

        factory(Team::class)->create([
            'id'      => 3,
            'name'    => '还不集合啦',
            'city_id' => 2,
            'status'  => TeamEntity::STATUS_NORMAL,
        ]);

        factory(ActivityMember::class)->create([
            'activity_id' => 2,
            'user_id'     => 1,
        ]);

        factory(ActivityMember::class)->create([
            'activity_id' => 4,
            'user_id'     => 1,
        ]);

        factory(ActivityApplicant::class)->create([
            'id'          => 12,
            'user_id'     => 1,
            'activity_id' => 2,
            'status'      => ActivityApplicant::STATUS_PAY,
        ]);

        factory(ActivityApplicant::class)->create([
            'user_id'     => 1,
            'activity_id' => 4,
            'status'      => ActivityApplicant::STATUS_SUCCESS,
        ]);

        factory(Activity::class)->create([
            'id'      => 1,
            'team_id' => 1,
            'city_id' => 1,
            'title'   => '未发布测试活动－－游泳',
            'status'  => ActivityEntity::STATUS_NOT_PUBLISHED,
        ]);

        factory(Activity::class)->create([
            'id'                => 2,
            'team_id'           => 1,
            'city_id'           => 2,
            'title'             => '已发布测试活动－－_\%跑步',
            'status'            => ActivityEntity::STATUS_PUBLISHED,
            'begin_time'        => date('Y-m-d 00:00:00', strtotime('-2 day')),
            'end_time'          => date('Y-m-d 10:11:11', strtotime('-3 day')),
            'publish_time'      => date('Y-m-d 00:00:00', strtotime('-10 day')),
            'enroll_begin_time' => date('Y-m-d 00:00:00', strtotime('-5 day')),
            'enroll_end_time'   => date('Y-m-d 10:11:11', strtotime('-6 day')),
        ]);

        factory(Activity::class)->create([
            'id'      => 3,
            'team_id' => 1,
            'city_id' => 2,
            'title'   => '已删除测试活动－－自行车',
            'status'  => ActivityEntity::STATUS_DELETE,
        ]);

        factory(Activity::class)->create([
            'id'           => 4,
            'team_id'      => 1,
            'city_id'      => 2,
            'title'        => '已发布测试活动－－\%跑步',
            'status'       => ActivityEntity::STATUS_PUBLISHED,
            'end_time'     => date('Y-m-d H:i:s', strtotime('+3 day')),
            'begin_time'   => date('Y-m-d H:i:s', strtotime('+2 day')),
            'publish_time' => date('Y-m-d 10:10:10', strtotime('-10 day')),

        ]);

        factory(Activity::class)->create([
            'id'           => 5,
            'team_id'      => 2,
            'city_id'      => 2,
            'title'        => '已发布测试活动－－\%跑步5',
            'status'       => ActivityEntity::STATUS_PUBLISHED,
            'end_time'     => date('Y-m-d H:i:s', strtotime('+2 day')),
            'begin_time'   => date('Y-m-d H:i:s', strtotime('+1 day')),
            'publish_time' => date('Y-m-d 10:10:10', strtotime('-10 day')),
        ]);

        factory(Activity::class)->create([
            'id'           => 6,
            'team_id'      => 3,
            'city_id'      => 2,
            'title'        => '已发布测试活动－－\%跑步6',
            'status'       => ActivityEntity::STATUS_PUBLISHED,
            'end_time'     => date('Y-m-d H:i:s', strtotime('+2 day')),
            'begin_time'   => date('Y-m-d H:i:s', strtotime('+1 day')),
            'publish_time' => date('Y-m-d 10:10:10', strtotime('-10 day')),
        ]);

        factory(Activity::class)->create([
            'id'           => 7,
            'team_id'      => 3,
            'city_id'      => 2,
            'title'        => '已发布测试活动－－\%跑步7',
            'status'       => ActivityEntity::STATUS_PUBLISHED,
            'end_time'     => date('Y-m-d H:i:s', strtotime('+2 day')),
            'begin_time'   => date('Y-m-d H:i:s', strtotime('+1 day')),
            'publish_time' => date('Y-m-d 10:10:10', strtotime('-10 day')),
        ]);
    }

    /**
     * @return \Jihe\Contracts\Repositories\CityRepository
     */
    private function getRepository()
    {
        return $this->app[\Jihe\Contracts\Repositories\ActivityRepository::class];
    }
}
