<?php
use Jihe\Entities\User as UserEntity;
use Jihe\Entities\Team as TeamEntity;
use Jihe\Entities\Activity as ActivityEntity;
use Jihe\Entities\TeamRequest as TeamApplicationEntity;
use Jihe\Entities\TeamCertification as TeamCertificationEntity;
use Jihe\Entities\ActivityAlbumImage as ActivityAlbumImageEntity;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/

//================================================================
//                    User
//================================================================
$factory->define(Jihe\Models\User::class, function ($faker) {
    return [
        'mobile'         => '1' . $faker->numerify('##########'),
        'type'           => UserEntity::TYPE_SELF,
        'salt'           => str_random(16),
        'password'       => str_random(32),
        'remember_token' => str_random(10),
        'nick_name'      => 'zhangsan',
        'gender'         => UserEntity::GENDER_MALE,
        'birthday'       => '1990-01-01',
        'signature'      => 'jihe',
        'avatar_url'     => str_random(64),
        'status'         => UserEntity::STATUS_NORMAL,
    ];
});

$factory->define(Jihe\Models\UserTag::class, function ($faker) {
    return [
        'name'         => 'sport',
        'resource_url' => str_random(64),
    ];
});

$factory->define(Jihe\Models\City::class, function ($faker) {
    return [
        'name' => str_random(32),
    ];
});

$factory->define(Jihe\Models\Verification::class, function ($faker) {
    return [
        'mobile'     => '1' . $faker->numerify('##########'),
        'code'       => $faker->numerify('####'),
        'expired_at' => date('Y-m-d H:i:s', strtotime('120 seconds')),
    ];
});

//==================================================================
//                      Team
//==================================================================
$factory->define(Jihe\Models\Team::class, function ($faker) {
    return [
        'creator_id'    => $faker->randomNumber,
        'city_id'       => $faker->randomNumber,
        'name'          => str_random(32),
        'email'         => str_random(64),
        'logo_url'      => "http://dev.image.jhla.com.cn/default/team_logo.png",
        'address'       => $faker->address,
        'contact_phone' => $faker->phoneNumber,
        'contact'       => str_random(32),
        'introduction'  => str_random(200),
        'certification' => TeamEntity::UN_CERTIFICATION,
        'qr_code_url'   => str_random(255),
        'join_type'     => TeamEntity::JOIN_TYPE_ANY,
        'status'        => TeamEntity::STATUS_NORMAL,
    ];
});

$factory->define(Jihe\Models\TeamRequest::class, function ($faker) {
    return [
        'team_id'       => null,
        'initiator_id'  => $faker->randomNumber,
        'city_id'       => $faker->randomNumber,
        'name'          => str_random(32),
        'email'         => str_random(64),
        'logo_url'      => "http://dev.image.jhla.com.cn/default/team_logo.png",
        'address'       => $faker->address,
        'contact_phone' => $faker->phoneNumber,
        'contact'       => str_random(32),
        'introduction'  => str_random(200),
        'status'        => TeamApplicationEntity::STATUS_PENDING,
        'read'          => 0,
        'memo'          => str_random(128),
    ];
});

$factory->define(Jihe\Models\TeamRequirement::class, function ($faker) {
    return [
        'team_id'     => $faker->randomNumber,
        'requirement' => str_random(32),
    ];
});

$factory->define(Jihe\Models\TeamCertification::class, function ($faker) {
    return [
        'team_id'           => $faker->randomNumber,
        'certification_url' => str_random(255),
        'type'              => TeamCertificationEntity::TYPE_BUSSINESS_CERTIFICATES,
    ];
});

$factory->define(Jihe\Models\TeamGroup::class, function ($faker) {
    return [
        'name'    => str_random(32),
        'team_id' => $faker->randomNumber,
    ];
});


//==================================================================
//                      Team Member
//==================================================================
$factory->define(Jihe\Models\TeamMember::class, function ($faker) {
    return [
        'user_id'  => $faker->randomNumber,
        'team_id'  => $faker->randomNumber,
        'group_id' => $faker->randomNumber,
        'role'     => \Jihe\Entities\TeamMember::ROLE_ORDINARY,
        'memo'     => null,
        'status'   => \Jihe\Entities\TeamMember::STATUS_NORMAL,
    ];
});
$factory->define(Jihe\Models\TeamMemberEnrollmentPermission::class, function ($faker) {
    return [
        'mobile'  => str_random(11),
        'team_id' => $faker->randomNumber,
        'memo'    => null,
        'status'  => \Jihe\Entities\TeamMemberEnrollmentPermission::STATUS_PROHIBITED,
    ];
});
$factory->define(Jihe\Models\TeamMemberEnrollmentRequest::class, function ($faker) {
    return [
        'initiator_id' => $faker->randomNumber,
        'team_id'      => $faker->randomNumber,
        'memo'         => null,
        'name'         => str_random(15),
        'status'       => \Jihe\Entities\TeamMemberEnrollmentRequest::STATUS_PENDING,
        'reason'       => null,
    ];
});
$factory->define(Jihe\Models\TeamMemberEnrollmentRequirement::class, function ($faker) {
    return [
        'request_id'     => $faker->randomNumber,
        'requirement_id' => $faker->randomNumber,
        'value'          => str_random(),
    ];
});
$factory->define(Jihe\Models\TeamMemberRequirement::class, function ($faker) {
    return [
        'member_id'      => $faker->randomNumber,
        'requirement_id' => $faker->randomNumber,
        'value'          => str_random(),
    ];
});

//==================================================================
//                      Activity
//==================================================================
$factory->define(Jihe\Models\Activity::class, function ($faker) {
    return [
        'city_id'           => $faker->randomNumber,
        'team_id'           => $faker->randomNumber,
        'title'             => '成都荧光跑',
        'qr_code_url'       => null,
        'begin_time'        => date('Y-m-d H:i:s', strtotime('120 seconds')),
        'end_time'          => date('Y-m-d H:i:s', strtotime('1200 seconds')),
        'contact'           => '不认识',
        'telephone'         => '13801380000',
        'cover_url'         => 'http://dev.image.com.cn/default/activity1.png',
        'address'           => '四川省成都市高新区萃华路xxx号',
        'brief_address'     => '花样年：香年广场',
        'detail'            => '我不知道写什么 …… ……',
        'enroll_begin_time' => date('Y-m-d H:i:s', strtotime('240 seconds')),
        'enroll_end_time'   => date('Y-m-d H:i:s', strtotime('720 seconds')),
        'enroll_type'       => \Jihe\Entities\Activity::ENROLL_ALL,
        'enroll_limit'      => 1000,
        'enroll_fee_type'   => \Jihe\Entities\Activity::ENROLL_FEE_TYPE_PAY,
        'enroll_fee'        => 998,
        'enroll_attrs'      => '["手机号", "姓名", "年龄"]',
        'status'            => \Jihe\Entities\Activity::STATUS_PUBLISHED,
        'location'          => [12.5, 34.3434],
        'roadmap'           => [[15, 45], [49.4535434, 88]],
        'publish_time'      => date('Y-m-d H:i:s', strtotime('-720 seconds')),
        'auditing'          => 1,
        'update_step'       => 1,
        'essence'           => 0,
        'top'               => 0,
        'images_url'        => '["http://dev.image.com.cn/default/activity1.png",
                                "http://dev.image.com.cn/default/activity2.png",
                                "http://dev.image.com.cn/default/activity3.png",
                                "http://dev.image.com.cn/default/activity4.png"]',
        'has_album'         => 0,
        'organizers'        => null,

    ];
});

$factory->define(Jihe\Models\ActivityEnrollPayment::class, function ($faker) {
    return [
        'activity_id' => $faker->randomNumber,
        'user_id'     => $faker->randomNumber,
        'fee'         => 1,
        'channel'     => \Jihe\Entities\ActivityEnrollPayment::CHANNEL_ALIPAY,
        'order_no'    => str_random(32),
        'trade_no'    => str_random(32),
        'payed_at'    => date('Y-m-d H:i:s', strtotime('-120 seconds')),
        'status'      => \Jihe\Entities\ActivityEnrollPayment::STATUS_SUCCESS,
    ];
});

$factory->define(Jihe\Models\ActivityEnrollIncome::class, function ($faker) {
    return [
        'team_id'         => $faker->randomNumber,
        'activity_id'     => $faker->randomNumber,
        'total_fee'       => 100,
        'transfered_fee'  => 0,
        'enroll_end_time' => '2015-08-01 10:00:00',
        'status'          => \Jihe\Entities\ActivityEnrollIncome::STATUS_WAIT,
    ];
});

$factory->define(Jihe\Models\News::class, function ($faker) {
    return [
        'title'        => 'auto generated title',
        'content'      => 'auto generated content',
        'cover_url'    => 'http://domain/cover.jpg',
        'team_id'      => $faker->randomNumber,
        'activity_id'  => $faker->randomNumber,
        'click_num'  => $faker->randomNumber,
        'publisher_id' => $faker->randomNumber,
    ];
});

$factory->define(Jihe\Models\ActivityAlbumImage::class, function ($faker) {
    return [
        'activity_id'  => $faker->randomNumber,
        'creator_type' => ActivityAlbumImageEntity::USER,
        'creator_id'   => $faker->randomNumber,
        'image_url'    => str_random(255),
        'status'       => ActivityAlbumImageEntity::STATUS_PENDING,
    ];
});

$factory->define(Jihe\Models\ActivityMember::class, function ($faker) {
    return [
        'activity_id' => $faker->randomNumber,
        'user_id'     => $faker->randomNumber,
        'mobile'      => '1' . $faker->numerify('##########'),
        'name'        => str_random(10),
        'attrs'       => '[{"key":"年龄","value":"25"}]',
    ];
});

$factory->define(Jihe\Models\ActivityApplicant::class, function () {
    return [
        'order_no'    => str_random(32),
        'activity_id' => 1,
        'user_id'     => 1,
        'mobile'      => '13800138000',
        'name'        => '张三',
        'attrs'       => json_encode([]),
        'expire_at'   => date('Y-m-d H:i:s', strtotime(\Jihe\Entities\Activity::PAYMENT_TIMEOUT . ' seconds')),
        'status'      => \Jihe\Models\ActivityApplicant::STATUS_NORMAL,
    ];
});

$factory->define(Jihe\Models\ActivityCheckIn::class, function () {
    return [
        'user_id'     => 1,
        'activity_id' => 1,
        'step'        => 1,
        'process_id'  => 0,
    ];
});

$factory->define(Jihe\Models\ActivityCheckInQRCode::class, function () {
    return [
        'activity_id' => 1,
        'step'        => 1,
        'url'         => 'http://domain/qrcode',
    ];
});

$factory->define(Jihe\Models\UserTag::class, function () {
    return [
        'resource_url' => 'http://domain/tag/icons/1.jpg',
    ];
});

//================================================================
//                    Admin User
//================================================================
$factory->define(Jihe\Models\Admin\User::class, function ($faker) {
    return [
        'user_name'      => '1' . $faker->numerify('##########'),
        'salt'           => str_random(16),
        'password'       => str_random(32),
        'remember_token' => str_random(10),
        'role'           => \Jihe\Entities\Admin\User::ROLE_ADMIN,
        'status'         => \Jihe\Entities\Admin\User::STATUS_NORMAL,
    ];
});

//================================================================
//                       Message
//================================================================
$factory->define(Jihe\Models\Message::class, function () {
    return [
        'content' => str_random(255),
        'type'    => \Jihe\Entities\Message::TYPE_TEXT,
    ];
});

$factory->define(Jihe\Models\ActivityFile::class, function ($faker) {
    return [
        'activity_id' => $faker->randomNumber,
        'name'        => str_random(128),
        'memo'        => str_random(255),
        'size'        => $faker->randomNumber,
        'extension'   => str_random(32),
        'url'         => str_random(255),
    ];
});

//================================================================
//                    ActivityPlan
//================================================================
$factory->define(Jihe\Models\ActivityPlan::class, function ($faker) {
    return [
        'activity_id' => $faker->randomNumber,
        'begin_time'  => date('Y-m-d H:i:s', strtotime('240 seconds')),
        'end_time'    => date('Y-m-d H:i:s', strtotime('1440 seconds')),
        'plan_text'   => str_random(240),
    ];
});

//================================================================
//                       Banner
//================================================================
$factory->define(Jihe\Models\Banner::class, function ($faker) {
    return [
        'city_id'   => $faker->randomNumber,
        'image_url' => str_random(255),
        'type'      => \Jihe\Entities\Banner::TYPE_URL,
        'begin_time'  => date('Y-m-d H:i:s', strtotime('240 seconds')),
        'end_time'    => date('Y-m-d H:i:s', strtotime('1440 seconds')),
    ];
});


//================================================================
//                       Wechat
//================================================================
$factory->define(Jihe\Models\WechatToken::class, function ($faker) {
    return [
        'openid'    => 'o6_bmjrPTlm6_2sgVt7hMZOPfL2M',
    ];
});

$factory->define(Jihe\Models\WechatUser::class, function ($faker) {
    return [
        'openid'    => 'o6_bmjrPTlm6_2sgVt7hMZOPfL2M',
        'nick_name' => 'nick_name',
        'gender'    => 0,
        'subscribe' => 0,
    ];
});

//================================================================
//                       Vote
//================================================================
$factory->define(Jihe\Models\Vote::class, function ($faker) {
    return [
        'voter'   => $faker->randomNumber,
        'user_id' => str_random(255),
        'type'    => 1,
    ];
});

//================================================================
//                       PlaneModelVote
//================================================================
$factory->define(Jihe\Models\PlaneModelVote::class, function ($faker) {
    return [
        'voter'   => $faker->randomNumber,
        'user_id' => str_random(255),
        'type'    => 1,
    ];
});

//================================================================
//                       ShiYangVote
//================================================================
$factory->define(Jihe\Models\ShiYangVote::class, function ($faker) {
    return [
        'voter'   => $faker->randomNumber,
        'user_id' => str_random(255),
        'type'    => 1,
    ];
});

//================================================================
//                       GaoXinVote
//================================================================
$factory->define(Jihe\Models\GaoXinVote::class, function ($faker) {
    return [
        'voter'   => $faker->randomNumber,
        'user_id' => str_random(255),
        'type'    => 1,
    ];
});

//================================================================
//                       Questions
//================================================================
$factory->define(Jihe\Models\Question::class, function ($faker) {
    return [
        'content'   => $faker->randomNumber,
        'type' => 1,
        'source'    => 1,
        'relate_id'    => 1,
        'pid'    => 0,
    ];
});

//================================================================
//                       XinNianVote
//================================================================
$factory->define(Jihe\Models\XinNianVote::class, function ($faker) {
    return [
        'voter'   => $faker->randomNumber,
        'user_id' => str_random(255),
        'type'    => 1,
    ];
});

$factory->define(Jihe\Models\YoungSingle::class, function ($faker) {
    return [
        'order_id'      => 0,
        'name'          => str_random(16),
        'id_number'     => str_random(18),
        'gender'        => mt_rand(1, 2),
        'date_of_birth' => mt_rand(1995, 2000) . '年' . mt_rand(1, 12) . '月',
        'height'        => mt_rand(140, 190),
        'graduate_university'  => str_random(128),
        'degree'        => mt_rand(1, 4),
        'yearly_salary' => mt_rand(1, 4),
        'work_unit'     => str_random(128),
        'mobile'        => str_random(11),
        'cover_url'     => 'http://image.jpg',
        'images_url'    => json_encode(['http://image1.jpg', 'http://image2.jpg']),
        'talent'        => str_random(128),
        'mate_choice'   => str_random(128),
        'status'        => \Jihe\Models\YoungSingle::STATUS_PENDING,
    ];
});

