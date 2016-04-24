<?php
namespace intg\Jihe\Controllers\Api;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use intg\Jihe\AuthDeviceCheck;
use intg\Jihe\RequestSignCheck;
use intg\Jihe\TestCase;
use Jihe\Entities\Activity as ActivityEntity;
use Jihe\Entities\ActivityAlbumImage;
use Jihe\Entities\ActivityAlbumImage as ActivityAlbumImageEntity;
use Jihe\Entities\Team as TeamEntity;
use Jihe\Models\Activity;
use Jihe\Models\ActivityApplicant;
use Jihe\Models\ActivityMember;
use Jihe\Models\ActivityPlan;
use Jihe\Models\City;
use Jihe\Models\Team;
use Jihe\Models\TeamMember;
use Jihe\Models\User;
use Jihe\Models\UserTag;
use Mockery;

class ActivityControllerTest extends TestCase
{
    use DatabaseTransactions, RequestSignCheck, AuthDeviceCheck;

    //=========================================
    //          findActivitiesInCity
    //=========================================
    public function testSuccessfulFindActivitiesInCity()
    {
        $response = $this->prepareTestDataAndRequestGET('/api/activity/city/list?city=1');
        self::assertDataStructure($response);
        self::assertDataListFields($response->activities);
        self::assertActivityListFields($response->activities[2]);
        self::assertEquals(1, $response->activities[2]->enrolled_num);
    }

    //=========================================
    //                activityById
    //=========================================
    public function testSuccessfulActivityById()
    {
        $response = $this->prepareTestDataAndRequestGET('/api/activity/info?activity=1');
        self::assertDataStructure($response, ['activity']);
        self::assertDataListFields([$response->activity], 'detail');
        self::assertActivityDetailFields($response->activity);
    }

    //=========================================
    //                getNewDetail
    //=========================================
    public function testSuccessfulGetNewDetail()
    {
        $response = $this->prepareTestDataAndRequestGET('/api/activity/detail?activity=1');
        self::assertDataStructure($response, ['activity']);
        self::assertDataListFields([$response->activity], 'detail');
        self::assertActivityDetailFields($response->activity);
        self::assertEquals(1, $response->activity->activity_members_count);
        self::assertEquals(0, $response->activity->activity_album_count);
    }

    //=========================================
    //                activitiesListByName
    //=========================================
    public function testSuccessfulActivitiesListByName()
    {
        $response = $this->prepareTestDataAndRequestGET('/api/activity/search/name?city=1&keyword=%');
        self::assertDataStructure($response);
        self::assertDataListFields($response->activities);
        self::assertActivityListFields($response->activities[2]);
    }

    //=========================================
    //                activitiesListByTeam
    //=========================================
    public function testSuccessfulActivitiesListByTeam()
    {
        $response = $this->prepareTestDataAndRequestGET('/api/activity/team/list?team=1');
        self::assertDataStructure($response);
        self::assertDataListFields($response->activities);
        self::assertActivityListFields($response->activities[2]);
    }

    //=========================================
    //                activitiesListByPoint
    //=========================================
    public function testSuccessfulActivitiesListByPoint()
    {
        $response = $this->prepareTestDataAndRequestGET('/api/activity/search/point?lat=16&lng=20');
        self::assertDataStructure($response);
        self::assertDataListFields($response->activities);
        self::assertActivityListFields($response->activities[0]);
    }

    //=========================================
    //                listActivitiesByHasAlbum
    //=========================================
    public function testSuccessfulListActivitiesByHasAlbum()
    {
        $response = $this->prepareTestDataAndRequestGET('/api/activity/hasalbum/list?team=1');
        self::assertDataStructure($response);
        self::assertDataListFields($response->activities, 'simpleList');
        self::assertActivitySimpleListFields($response->activities[0]);
    }

    //=========================================
    //       getCurrentUserNoScoreActivities
    //=========================================
    public function testSuccessfulGetCurrentUserNoScoreActivities()
    {
        $response = $this->prepareTestDataAndRequestGET('/api/activity/no/score');
        self::assertDataStructure($response, ['activities']);
        self::assertDataListFields($response->activities, 'simpleList');
        $activity = $response->activities[0];
        self::assertEquals(2, $activity->id);
        self::assertEquals('已发布测试活动－－\/%跑步', $activity->title);
        self::assertEquals(date('Y-m-d 00:00:00', strtotime('-2 day')), $activity->begin_time);
        self::assertEquals(date('Y-m-d 00:00:00', strtotime('-1 day')), $activity->end_time);
        self::assertEquals(date('Y-m-d 00:00:00', strtotime('-5 day')), $activity->publish_time);
        self::assertEquals(116.00211, $activity->location[0]);
        self::assertEquals(95, $activity->location[1]);
    }

    //=========================================
    //       getMyActivities
    //=========================================
    public function testSuccessfulGetMyActivities()
    {
        $response = $this->prepareTestDataAndRequestGET('/api/activity/my');
        self::assertDataStructure($response);
        self::assertDataListFields($response->activities);
        //self::assertActivityListFields($response->activities[2]);
    }

    public function testSuccessfulGetMyActivities_NotBeginning()
    {
        $response = $this->prepareTestDataAndRequestGET('/api/activity/my?type=NotBeginning');
        self::assertDataStructure($response);
        self::assertEquals(1, count($response->activities));
    }

    public function testSuccessfulGetMyActivities_waitPay()
    {
        $response = $this->prepareTestDataAndRequestGET('/api/activity/my?type=WaitPay');
        self::assertDataStructure($response);
        self::assertEquals(0, count($response->activities));
    }

    public function testSuccessfulGetMyActivities_EndOf()
    {
        $response = $this->prepareTestDataAndRequestGET('/api/activity/my?type=End');
        self::assertDataStructure($response);
        self::assertEquals(0, count($response->activities));
    }

    public function testSuccessfulGetMyActivities_Enrolling()
    {
        $response = $this->prepareTestDataAndRequestGET('/api/activity/my?type=Enrolling');
        self::assertDataStructure($response);
        self::assertEquals(0, count($response->activities));
    }

    //=========================================
    //       getMyActivitiesCount
    //=========================================
    public function testSuccessfulGetMyActivitiesCount()
    {
        $response = $this->prepareTestDataAndRequestGET('/api/activity/my/count');
        self::assertDataStructure($response, ['count']);
        self::assertEquals(1, $response->count->all);
        self::assertEquals(1, $response->count->notBeginning);
        self::assertEquals(0, $response->count->waitPay);
        self::assertEquals(0, $response->count->end);
    }
    //=========================================
    //       getHomePageMyActivities
    //=========================================
    public function testSuccessfulGetHomePageMyActivities()
    {
        $user = factory(User::class)->create(['id' => 1]);
        $this->prepareTestData();
        factory(ActivityApplicant::class)->create([
            'user_id'     => 1,
            'activity_id' => 23,
            'status'      => ActivityApplicant::STATUS_SUCCESS,
        ]);
        factory(Activity::class)->create([
            'id'           => 23,
            'team_id'      => 1,
            'city_id'      => 1,
            'title'        => '已发布测试活动－－\/%跑步',
            'status'       => ActivityEntity::STATUS_PUBLISHED,
            'has_album'    => 0,
            'end_time'     => date('Y-m-d 00:00:00', strtotime('-1 day')),
            'begin_time'   => date('Y-m-d 00:00:00', strtotime('-2 day')),
            'publish_time' => date('Y-m-d 00:00:00', strtotime('-5 day')),
            'location'     => [116.00211, 95],
        ]);
        factory(Activity::class)->create([
            'id'           => 21,
            'team_id'      => 1,
            'city_id'      => 1,
            'title'        => '已发布测试活动－－\/%跑步1',
            'status'       => ActivityEntity::STATUS_PUBLISHED,
            'has_album'    => 0,
            'end_time'     => date('Y-m-d 00:00:00', strtotime('+115 day')),
            'begin_time'   => date('Y-m-d 00:00:00', strtotime('+12 day')),
            'publish_time' => date('Y-m-d 00:00:00', strtotime('-20 day')),
            'enroll_begin_time' => date('Y-m-d 08:00:00', strtotime('-10 day')),
            'enroll_end_time'   => date('Y-m-d 10:00:00', strtotime('+5 day')),
            'location'     => [116.00211, 95],
        ]);
        factory(ActivityApplicant::class)->create([
            'user_id'     => 1,
            'activity_id' => 21,
            'status'      => ActivityApplicant::STATUS_PAY,
        ]);

        $this->actingAs($user)
            ->ajaxGet('/api/activity/home/my')
            ->seeJsonContains(['code' => 0]);
        $response = json_decode($this->response->getContent());
        self::assertEquals(2, $response->activities[0]->sub_status);
        self::assertEquals(3, $response->activities[1]->sub_status);
        self::assertEquals(5, $response->activities[2]->sub_status);
        self::assertCount(3, $response->activities);
    }


    //=========================================
    //       getMyTopics
    //=========================================
    public function testSuccessfulGetMyTopics()
    {
        $response = $this->prepareTestDataAndRequestGET('/api/activity/my/topics');
        self::assertDataStructure($response, ['topic_list']);
        self::assertEquals(6, count($response->topic_list));
        self::assertEquals('topic_activity_1', $response->topic_list[0]);
        self::assertEquals('topic_activity_3', $response->topic_list[2]);
        self::assertEquals('topic_team_2', $response->topic_list[4]);
    }

    //=========================================
    //       recommendActivities
    //=========================================
    public function testSuccessfulRecommendActivities()
    {
        $this->mockSearchService([
            [
                'id'              => 1,
                'title'           => '已发布测试活动－－%越野跑',
                'publish_time'    => date('Y-m-d 10:00:00', strtotime('-30 day')),
                'begin_time'      => date('Y-m-d 08:00:00', strtotime('-20 day')),
                'end_time'        => date('Y-m-d 18:00:00', strtotime('-20 day')),
                'team_id'         => 1,
                'sub_status'      => 5,
                'cover_url'       => 'http://dev.image.com.cn/default/activity1.png' . ActivityEntity::THUMBNAIL_STYLE_FOR_COVER,
                'qr_code_url'     => NULL,
                'address'         => '四川省成都市高新区萃华路xxx号',
                'brief_address'   => '花样年：香年广场',
                'enroll_fee_type' => 3,
                'enroll_fee'      => '9.98',
                'essence'         => 0,
                'city'            =>
                    [
                        'id'   => 1,
                        'name' => '8Dd87HtdqTlHHtc8vlrlIsD0lNyZ3GBO',
                    ],
                'team'            =>
                    [
                        'id'           => 1,
                        'name'         => '2aUtbXk4ewOEIr50TgouDTpGtAEzggGW',
                        'logo_url'     => 'http://dev.image.jhla.com.cn/default/team_logo.png',
                        'introduction' => 'zjYOpD0Zy3bqiJafKlRa0UQBqgZ3J2qVOFATJNkwq9MD1Qa8D6bLAKD7XMwKeED2VJyGe61pKwk86l6xzf8SYWZrR5Us0jv5VCJJcwvuNawg2XaBWHy5KZBVMZKl3xFb2pG9uArQTqi5XwTFj3JE7tDevpaR0quZwlqBqYj14tOYB0Itjx4oliHOa07byhcQkem0K5FX',
                    ],
                'location'        =>
                    [
                        0 => '16.00211',
                        1 => '20',
                    ],
                'status'          => 1,
            ],
        ]);
        $response = $this->prepareTestDataAndRequestGET('/api/activity/recommend?city=1');
        self::assertDataStructure($response, ['activities']);
        self::assertDataListFields($response->activities);
        self::assertActivityListFields($response->activities[0]);
    }

    //=========================================
    //       getCheckInActivityDetail
    //=========================================
    public function testSuccessfulGetCheckInActivityDetail()
    {
        $response = $this->prepareTestDataAndRequestGET('/api/activity/checkin/info?activity=1');
        self::assertDataStructure($response, ['activity']);
        self::assertActivityDetailFields($response->activity);
        self::assertEquals(1, $response->activity->activity_members_count);
        self::assertEquals(0, $response->activity->activity_album_count);
    }

    //=========================================
    //       getPaymentTimeoutSeconds
    //=========================================
    public function testSuccessfulGetPaymentTimeoutSeconds()
    {
        $response = $this->prepareTestDataAndRequestGET('/api/activity/pay/timeout/seconds?activity=1');
        self::assertDataStructure($response, ['timeout_seconds']);
        self::assertLessThanOrEqual(\Jihe\Entities\Activity::PAYMENT_TIMEOUT, $response->timeout_seconds);
    }

    /**
     * http method GET request API uri
     *
     * @param string $uri     API uri
     * @param int    $code    API return code
     * @param string $message throw exception message
     * @param int    $user    user id
     *
     * @return array        Api return json to array
     */
    private function prepareTestDataAndRequestGET($uri, $code = 0, $message = null, $user = 1)
    {
        $user = factory(User::class)->create(['id' => $user]);
        $this->prepareTestData();
        $this->actingAs($user)
            ->ajaxGet($uri)
            ->seeJsonContains(['code' => $code]);
        $response = json_decode($this->response->getContent());
        if ($code != 0) {
            self::assertEquals($message, $response->message);
            return null;
        }

        return $response;
    }

    private static function assertDataStructure($response, $fields = ['pages', 'activities'])
    {
        if ($fields) {
            foreach ($fields as $field) {
                self::assertObjectHasAttribute($field, $response);
            }
        }
    }

    private static function assertDataListFields($activities, $type = 'list')
    {
        for ($i = 0; $i < count($activities); $i++) {
            if ($type == 'list') {
                self::assertAnElementInList($activities[$i]);
            } elseif ($type == 'simpleList') {
                self::assertAnElementInSimpleList($activities[$i]);
            } else {
                self::assertElementInInfo($activities[$i]);
                self::assertAnElementInList($activities[$i]);
            }
        }
    }

    /**
     * assert simple activities list by element
     *
     * @param $activity
     */
    private static function assertAnElementInSimpleList($activity)
    {
        self::assertObjectHasAttribute('id', $activity);
        self::assertObjectHasAttribute('title', $activity);
        self::assertObjectHasAttribute('begin_time', $activity);
        self::assertObjectHasAttribute('end_time', $activity);
    }

    /**
     * assert common activities list by element
     *
     * @param $activity
     */
    private static function assertAnElementInList($activity)
    {
        self::assertObjectHasAttribute('id', $activity);
        self::assertObjectHasAttribute('title', $activity);
        self::assertObjectHasAttribute('sub_status', $activity);
        self::assertObjectHasAttribute('cover_url', $activity);
        self::assertObjectHasAttribute('address', $activity);
        self::assertObjectHasAttribute('brief_address', $activity);
        self::assertObjectHasAttribute('enroll_fee_type', $activity);
        self::assertObjectHasAttribute('enroll_fee', $activity);
    }

    /**
     * assert activity detail by element
     *
     * @param $activity
     */
    private static function assertElementInInfo($activity)
    {
        self::assertObjectHasAttribute('begin_time', $activity);
        self::assertObjectHasAttribute('end_time', $activity);
        self::assertObjectHasAttribute('enroll_begin_time', $activity);
        self::assertObjectHasAttribute('enroll_end_time', $activity);
        self::assertObjectHasAttribute('enroll_type', $activity);
        self::assertObjectHasAttribute('enroll_limit', $activity);
        self::assertObjectHasAttribute('enroll_attrs', $activity);
        self::assertObjectHasAttribute('roadmap', $activity);
        self::assertObjectHasAttribute('location', $activity);
        self::assertObjectHasAttribute('logo_url', $activity->team);
        self::assertObjectHasAttribute('introduction', $activity->team);
        self::assertEquals(2, count($activity->location));
    }

    private static function assertActivitySimpleListFields($activity)
    {
        self::assertEquals(1, $activity->id);
        self::assertEquals('已发布测试活动－－%越野跑', $activity->title);
        self::assertEquals(date('Y-m-d 08:00:00', strtotime('-20 day')), $activity->begin_time);
        self::assertEquals(date('Y-m-d 18:00:00', strtotime('-20 day')), $activity->end_time);
    }

    private static function assertActivityListFields($activity)
    {
        self::assertEquals(1, $activity->id);
        self::assertEquals('已发布测试活动－－%越野跑', $activity->title);
        self::assertEquals('http://dev.image.com.cn/default/activity1.png' . ActivityEntity::THUMBNAIL_STYLE_FOR_COVER, $activity->cover_url);
        self::assertEquals('四川省成都市高新区萃华路xxx号', $activity->address);
        self::assertEquals('花样年：香年广场', $activity->brief_address);
        self::assertEquals(3, $activity->enroll_fee_type);
        self::assertEquals(9.98, $activity->enroll_fee);
        self::assertEquals(1, $activity->team->id);
        self::assertEquals(1, $activity->city->id);
        self::assertEquals(date('Y-m-d 08:00:00', strtotime('-20 day')), $activity->begin_time);
        self::assertEquals(date('Y-m-d 18:00:00', strtotime('-20 day')), $activity->end_time);
        self::assertEquals(date('Y-m-d 10:00:00', strtotime('-30 day')), $activity->publish_time);
        self::assertEquals(0, $activity->essence);
    }

    private static function assertActivityDetailFields($activity)
    {
        self::assertEquals(1, $activity->id);
        self::assertEquals('已发布测试活动－－%越野跑', $activity->title);
        self::assertEquals('http://dev.image.com.cn/default/activity1.png' . ActivityEntity::THUMBNAIL_STYLE_FOR_COVER, $activity->cover_url);
        self::assertEquals('四川省成都市高新区萃华路xxx号', $activity->address);
        self::assertEquals('花样年：香年广场', $activity->brief_address);
        self::assertEquals(3, $activity->enroll_fee_type);
        self::assertEquals(9.98, $activity->enroll_fee);
        self::assertEquals(1, $activity->team->id);
        self::assertEquals(1, $activity->city->id);
        self::assertEquals('13801380000', $activity->telephone);
        self::assertEquals('不认识', $activity->contact);
        self::assertEquals('我不知道写什么 …… ……', $activity->detail);
        self::assertEquals(date('Y-m-d 08:00:00', strtotime('-20 day')), $activity->begin_time);
        self::assertEquals(date('Y-m-d 18:00:00', strtotime('-20 day')), $activity->end_time);
        self::assertEquals(date('Y-m-d 08:00:00', strtotime('-25 day')), $activity->enroll_begin_time);
        self::assertEquals(date('Y-m-d 10:00:00', strtotime('-25 day')), $activity->enroll_end_time);
        self::assertEquals(1, $activity->enroll_type);
        self::assertEquals(1000, $activity->enroll_limit);
        self::assertEquals(3, count($activity->enroll_attrs));
        self::assertEquals('手机号', $activity->enroll_attrs[0]);
        self::assertEquals(2, count($activity->location));
        self::assertEquals(16.00211, $activity->location[0]);
        self::assertEquals(20, $activity->location[1]);
        self::assertEquals(49.4535434, $activity->roadmap[1][0]);
        self::assertEquals(88, $activity->roadmap[1][1]);
        self::assertEquals(date('Y-m-d 10:00:00', strtotime('-30 day')), $activity->publish_time);
        self::assertEquals(5, $activity->update_step);
        self::assertEquals(0, $activity->essence);
        self::assertEquals(4, count($activity->images_url));
        self::assertEquals(2, $activity->applicant_status);

    }

    private function getUserRepository()
    {
        return $this->app[\Jihe\Contracts\Repositories\UserRepository::class];
    }

    /**
     * inti data
     */
    private function prepareTestData()
    {
        factory(ActivityPlan::class)->create([
            'id'          => 1,
            'activity_id' => 1,
            'begin_time'  => date('Y-m-d H:i:s', strtotime('240 seconds')),
            'end_time'    => date('Y-m-d H:i:s', strtotime('1440 seconds')),
            'plan_text'   => '东方热哦陪外婆玩儿玩儿问',
        ]);

        factory(ActivityPlan::class)->create([
            'id'          => 2,
            'activity_id' => 1,
            'begin_time'  => date('Y-m-d H:i:s', strtotime('480 seconds')),
            'end_time'    => date('Y-m-d H:i:s', strtotime('2880 seconds')),
            'plan_text'   => '东方',
        ]);

        factory(City::class)->create([
            'id' => 1,
        ]);

        factory(UserTag::class)->create([
            'id'   => 1,
            'name' => '琴棋',
        ]);

        factory(UserTag::class)->create([
            'id'   => 2,
            'name' => '书画',
        ]);

        $this->getUserRepository()->updateProfile(1, [
            'tags' => [1, 2],
        ]);

        factory(Team::class)->create([
            'id'      => 1,
            'city_id' => 1,
            'status'  => TeamEntity::STATUS_NORMAL,
        ]);

        factory(Team::class)->create([
            'id'      => 2,
            'city_id' => 1,
            'status'  => TeamEntity::STATUS_NORMAL,
        ]);

        factory(Team::class)->create([
            'id'      => 3,
            'city_id' => 1,
            'status'  => TeamEntity::STATUS_NORMAL,
        ]);

        factory(ActivityMember::class)->create([
            'activity_id' => 1,
            'user_id'     => 1,
            'score'       => 5,
        ]);

        factory(ActivityMember::class)->create([
            'activity_id' => 2,
            'user_id'     => 1,
            'score'       => null,
        ]);

        factory(ActivityMember::class)->create([
            'activity_id' => 3,
            'user_id'     => 1,
            'score'       => null,
        ]);

        factory(ActivityApplicant::class)->create([
            'user_id'     => 1,
            'activity_id' => 1,
            'status'      => ActivityApplicant::STATUS_PAY,
        ]);

        factory(ActivityApplicant::class)->create([
            'user_id'     => 2,
            'activity_id' => 2,
            'status'      => ActivityApplicant::STATUS_SUCCESS,
        ]);

        factory(ActivityApplicant::class)->create([
            'user_id'     => 1,
            'activity_id' => 3,
            'status'      => ActivityApplicant::STATUS_SUCCESS,
        ]);

        factory(ActivityApplicant::class)->create([
            'user_id'     => 1,
            'activity_id' => 4,
            'status'      => ActivityApplicant::STATUS_PAY,
        ]);

        factory(TeamMember::class)->create([
            'user_id' => 1,
            'team_id' => 1,
            'status'  => 1,
        ]);

        factory(TeamMember::class)->create([
            'user_id' => 1,
            'team_id' => 2,
            'status'  => 1,
        ]);

        factory(TeamMember::class)->create([
            'user_id' => 1,
            'team_id' => 3,
            'status'  => 1,
        ]);

        factory(Activity::class)->create([
            'id'                => 1,
            'team_id'           => 1,
            'city_id'           => 1,
            'title'             => '已发布测试活动－－%越野跑',
            'status'            => ActivityEntity::STATUS_PUBLISHED,
            'begin_time'        => date('Y-m-d 08:00:00', strtotime('-20 day')),
            'end_time'          => date('Y-m-d 18:00:00', strtotime('-20 day')),
            'enroll_begin_time' => date('Y-m-d 08:00:00', strtotime('-25 day')),
            'enroll_end_time'   => date('Y-m-d 10:00:00', strtotime('-25 day')),
            'publish_time'      => date('Y-m-d 10:00:00', strtotime('-30 day')),
            'update_step'       => 5,
            'location'          => [16.00211, 20],
            'has_album'         => 1,
        ]);

        factory(Activity::class)->create([
            'id'           => 2,
            'team_id'      => 1,
            'city_id'      => 1,
            'title'        => '已发布测试活动－－\/%跑步',
            'status'       => ActivityEntity::STATUS_PUBLISHED,
            'has_album'    => 0,
            'end_time'     => date('Y-m-d 00:00:00', strtotime('-1 day')),
            'begin_time'   => date('Y-m-d 00:00:00', strtotime('-2 day')),
            'publish_time' => date('Y-m-d 00:00:00', strtotime('-5 day')),
            'location'     => [116.00211, 95],
        ]);

        factory(Activity::class)->create([
            'id'                => 3,
            'team_id'           => 1,
            'city_id'           => 1,
            'title'             => '已发布测试活动－－%自行车',
            'status'            => ActivityEntity::STATUS_PUBLISHED,
            'has_album'         => 0,
            'begin_time'        => date('Y-m-d 08:00:00', strtotime('+20 day')),
            'end_time'          => date('Y-m-d 18:00:00', strtotime('+20 day')),
            'enroll_begin_time' => date('Y-m-d 08:00:00', strtotime('-30 day')),
            'enroll_end_time'   => date('Y-m-d 10:00:00', strtotime('-35 day')),
        ]);

        factory(Activity::class)->create([
            'id'                => 4,
            'team_id'           => 1,
            'city_id'           => 1,
            'title'             => '已发布测试活动－－%越野跑',
            'status'            => ActivityEntity::STATUS_PUBLISHED,
            'begin_time'        => date('Y-m-d 08:00:00', strtotime('-20 day')),
            'end_time'          => date('Y-m-d 18:00:00', strtotime('-20 day')),
            'enroll_begin_time' => date('Y-m-d 08:00:00', strtotime('-25 day')),
            'enroll_end_time'   => date('Y-m-d 10:00:00', strtotime('-25 day')),
            'publish_time'      => date('Y-m-d 10:00:00', strtotime('-30 day')),
            'update_step'       => 5,
            'location'          => [16.00211, 20],
            'has_album'         => 1,
        ]);

    }

    //=========================================
    //            list album images
    //=========================================
    public function testSuccessfulListSponsorAlbumImages()
    {
        $user = factory(\Jihe\Models\User::class)->create(['id' => 1]);
        factory(\Jihe\Models\City::class)->create([
            'id' => 1,
        ]);

        factory(Team::class)->create([
            'id'      => 1,
            'city_id' => 1,
            'status'  => TeamEntity::STATUS_NORMAL,
        ]);

        factory(Activity::class)->create([
            'id'        => 1,
            'team_id'   => 1,
            'city_id'   => 1,
            'title'     => '活动1',
            'status'    => ActivityEntity::STATUS_PUBLISHED,
            'location'  => [15.99716, 20],
            'has_album' => 1,
        ]);

        factory(ActivityMember::class)->create([
            'activity_id' => 1,
            'user_id'     => 1,
        ]);

        factory(\Jihe\Models\ActivityAlbumImage::class)->create([
            'activity_id'  => 1,
            'creator_type' => ActivityAlbumImageEntity::SPONSOR,
        ]);

        $this->startSession();

        $this->actingAs($user)
            ->ajaxGet('/api/activity/album/image/list?activity=1&creator_type=0&page=1&size=3');

        $this->seeJsonContains(['code' => 0]);

        $result = json_decode($this->response->getContent());

        $this->assertObjectHasAttribute('pages', $result);
        $this->assertEquals(1, $result->pages);
        $this->assertObjectHasAttribute('images', $result);
        $this->assertCount(1, $result->images);

        $image = $result->images[0];
        $this->assertObjectHasAttribute('id', $image);
        $this->assertObjectHasAttribute('image_url', $image);
    }

    public function testSuccessfulListUserAlbumImages()
    {
        $user = factory(\Jihe\Models\User::class)->create(['id' => 1]);
        factory(\Jihe\Models\City::class)->create([
            'id' => 1,
        ]);

        factory(Team::class)->create([
            'id'      => 1,
            'city_id' => 1,
            'status'  => TeamEntity::STATUS_NORMAL,
        ]);

        factory(Activity::class)->create([
            'id'        => 1,
            'team_id'   => 1,
            'city_id'   => 1,
            'title'     => '活动1',
            'status'    => ActivityEntity::STATUS_PUBLISHED,
            'location'  => [15.99716, 20],
            'has_album' => 1,
        ]);

        factory(ActivityMember::class)->create([
            'activity_id' => 1,
            'user_id'     => 1,
        ]);

        factory(\Jihe\Models\ActivityAlbumImage::class)->create([
            'activity_id'  => 1,
            'creator_type' => ActivityAlbumImageEntity::USER,
            'status'       => ActivityAlbumImage::STATUS_APPROVED,
        ]);

        $this->startSession();

        $this->actingAs($user)
            ->ajaxGet('/api/activity/album/image/list?activity=1&creator_type=1&page=1&size=3');

        $this->seeJsonContains(['code' => 0]);

        $result = json_decode($this->response->getContent());

        $this->assertObjectHasAttribute('pages', $result);
        $this->assertEquals(1, $result->pages);
        $this->assertObjectHasAttribute('images', $result);
        $this->assertCount(1, $result->images);

        $image = $result->images[0];
        $this->assertObjectHasAttribute('id', $image);
        $this->assertObjectHasAttribute('image_url', $image);
    }

    public function testSuccessfulListAlbumImagesOfUser()
    {
        $user = factory(\Jihe\Models\User::class)->create(['id' => 1]);
        factory(\Jihe\Models\City::class)->create([
            'id' => 1,
        ]);

        factory(Team::class)->create([
            'id'      => 1,
            'city_id' => 1,
            'status'  => TeamEntity::STATUS_NORMAL,
        ]);

        factory(Activity::class)->create([
            'id'        => 1,
            'team_id'   => 1,
            'city_id'   => 1,
            'title'     => '活动1',
            'status'    => ActivityEntity::STATUS_PUBLISHED,
            'location'  => [15.99716, 20],
           'has_album' => 1,
        ]);

        factory(ActivityMember::class)->create([
            'activity_id' => 1,
            'user_id'     => 1,
        ]);

        factory(\Jihe\Models\ActivityAlbumImage::class)->create([
            'activity_id'  => 1,
            'creator_type' => ActivityAlbumImageEntity::USER,
            'creator_id'   => 1,
            'status'       => ActivityAlbumImage::STATUS_PENDING,
        ]);

        factory(\Jihe\Models\ActivityAlbumImage::class)->create([
            'activity_id'  => 1,
            'creator_type' => ActivityAlbumImageEntity::USER,
            'creator_id'   => 2,
            'status'       => ActivityAlbumImage::STATUS_APPROVED,
        ]);

        factory(\Jihe\Models\ActivityAlbumImage::class)->create([
            'activity_id'  => 1,
            'creator_type' => ActivityAlbumImageEntity::USER,
            'creator_id'   => 1,
            'status'       => ActivityAlbumImage::STATUS_APPROVED,
        ]);

        $this->startSession();

        $this->actingAs($user)
             ->ajaxGet('/api/activity/album/image/self/list?activity=1&page=1&size=3');

        $this->seeJsonContains(['code' => 0]);

        $result = json_decode($this->response->getContent());

        $this->assertObjectHasAttribute('pages', $result);
        $this->assertEquals(1, $result->pages);
        $this->assertObjectHasAttribute('images', $result);
        $this->assertCount(2, $result->images);

        $image = $result->images[0];
        $this->assertObjectHasAttribute('id', $image);
        $this->assertObjectHasAttribute('image_url', $image);
    }

    //=========================================
    //            add album image
    //=========================================
    public function testSuccessfulAddAlbumImage()
    {
        $user = factory(\Jihe\Models\User::class)->create(['id' => 1]);
        factory(\Jihe\Models\City::class)->create([
            'id' => 1,
        ]);

        factory(Team::class)->create([
            'id'      => 1,
            'city_id' => 1,
            'status'  => TeamEntity::STATUS_NORMAL,
        ]);

        factory(Activity::class)->create([
            'id'        => 1,
            'team_id'   => 1,
            'city_id'   => 1,
            'title'     => '活动1',
            'status'    => ActivityEntity::STATUS_PUBLISHED,
            'location'  => [15.99716, 20],
            'has_album' => 1,
        ]);

        factory(ActivityMember::class)->create([
            'activity_id' => 1,
            'user_id'     => 1,
        ]);

        $this->mockStorageService();
        $this->mockImageForUploading(__DIR__ . '/test-data/panda.jpg');
        $this->startSession();

        $this->actingAs($user)->call('POST', '/api/activity/album/image/add', [
            '_token'   => csrf_token(),
            'activity' => 1,
        ], [], [
            'image' => $this->makeUploadFile($this->getMockedImagePath('panda.jpg')),
        ]);

        $this->seeJsonContains(['code' => 0]);

        $result = json_decode($this->response->getContent());
        self::assertObjectHasAttribute('id', $result);
        self::assertObjectHasAttribute('image_url', $result);
    }

    //=========================================
    //       remove album images of user
    //=========================================
    public function testSuccessfulRemoveAlbumImagesOfUser()
    {
        $user = factory(\Jihe\Models\User::class)->create(['id' => 1]);
        factory(\Jihe\Models\City::class)->create([
            'id' => 1,
        ]);

        factory(Team::class)->create([
            'id'      => 1,
            'city_id' => 1,
            'status'  => TeamEntity::STATUS_NORMAL,
        ]);

        factory(Activity::class)->create([
            'id'        => 1,
            'team_id'   => 1,
            'city_id'   => 1,
            'title'     => '活动1',
            'status'    => ActivityEntity::STATUS_PUBLISHED,
            'location'  => [15.99716, 20],
            'has_album' => 1,
        ]);

        factory(\Jihe\Models\ActivityAlbumImage::class)->create([
            'id'           => 1,
            'activity_id'  => 1,
            'creator_type' => ActivityAlbumImageEntity::USER,
            'status'       => ActivityAlbumImageEntity::STATUS_PENDING,
        ]);

        factory(\Jihe\Models\ActivityAlbumImage::class)->create([
            'id'           => 2,
            'activity_id'  => 1,
            'creator_type' => ActivityAlbumImageEntity::USER,
            'status'       => ActivityAlbumImageEntity::STATUS_APPROVED,
        ]);

        $this->startSession();

        $this->actingAs($user)->ajaxPost('/api/activity/album/image/self/remove', [
            'activity' => 1,
            'images'   => [1, 2],
        ]);

        $this->seeJsonContains(['code' => 0]);
    }

    //=========================================
    //              list files
    //=========================================
    public function testSuccessfulListFiles()
    {
        $user = factory(\Jihe\Models\User::class)->create(['id' => 1]);
        factory(\Jihe\Models\City::class)->create([
            'id' => 1,
        ]);

        factory(Team::class)->create([
            'id'      => 1,
            'city_id' => 1,
            'status'  => TeamEntity::STATUS_NORMAL,
        ]);

        factory(Activity::class)->create([
            'id'        => 1,
            'team_id'   => 1,
            'city_id'   => 1,
            'title'     => '活动1',
            'status'    => ActivityEntity::STATUS_PUBLISHED,
            'location'  => [15.99716, 20],
            'has_album' => 1,
        ]);

        factory(\Jihe\Models\ActivityFile::class)->create([
            'activity_id' => 1,
        ]);

        factory(\Jihe\Models\ActivityFile::class)->create([
            'activity_id' => 1,
        ]);

        factory(\Jihe\Models\ActivityFile::class)->create([
            'activity_id' => 2,
        ]);

        $this->startSession();

        $this->actingAs($user)
            ->ajaxGet('/api/activity/file/list?activity=1&page=1&size=1');

        $this->seeJsonContains(['code' => 0]);

        $result = json_decode($this->response->getContent());

        $this->assertObjectHasAttribute('pages', $result);
        $this->assertEquals(2, $result->pages);
        $this->assertObjectHasAttribute('files', $result);
        $this->assertCount(1, $result->files);

        $file = $result->files[0];
        $this->assertObjectHasAttribute('id', $file);
        $this->assertObjectHasAttribute('name', $file);
        $this->assertObjectHasAttribute('memo', $file);
        $this->assertObjectHasAttribute('size', $file);
        $this->assertObjectHasAttribute('extension', $file);
        $this->assertObjectHasAttribute('url', $file);
        $this->assertObjectHasAttribute('created_at', $file);
    }

    /**
     * mock image file for uploading
     *
     * @param string $path file path to existing image file
     * @param string $name name in the mocked file system (virtual file system, exactly)
     */
    private function mockImageForUploading($path, $name = null)
    {
        // populate name if not given
        !$name && $name = pathinfo($path, PATHINFO_BASENAME);

        // put excel files in 'teammember' directory - the root directory
        // NOTE: 'teammember' is also used by getMockedExcelUrl() below
        $this->mockFiles('imageupload', [
            $name => file_get_contents($path),
        ]);
        // no need to register mime type guessers, as the default FileinfoMimeTypeGuesser
        // works on images
    }

    /**
     * get mocked image file's full path (including scheme and root directory)
     *
     * @param $path      path to mocked image file
     *
     * @return string    full path of the mocked image file
     */
    private function getMockedImagePath($path)
    {
        return $this->getMockedFilePath(sprintf('imageupload/%s', ltrim($path, '/')));
    }

    private function mockStorageService($return = 'http://domain/tmp/key.png')
    {
        $storageService = \Mockery::mock(\Jihe\Services\StorageService::class);
        $storageService->shouldReceive('isTmp')->withAnyArgs()->andReturn(true);
        $storageService->shouldReceive('storeAsFile')->withAnyArgs()->andReturn('http://domain/image.png');
        $storageService->shouldReceive('storeAsImage')->withAnyArgs()->andReturn('http://domain/image.png');
        $storageService->shouldReceive('remove')->withAnyArgs()->andReturn(false);
        $this->app [\Jihe\Services\StorageService::class] = $storageService;

        return $storageService;
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
