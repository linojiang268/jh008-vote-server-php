<?php
namespace spec\Jihe\Services;

use Bus;
use Jihe\Contracts\Repositories\ActivityApplicantRepository;
use Jihe\Contracts\Repositories\ActivityPlanRepository;
use Jihe\Contracts\Services\Search\SearchService;
use Jihe\Services\UserService;
use PhpSpec\Laravel\LaravelObjectBehavior;
use Prophecy\Argument;
use Jihe\Contracts\Repositories\ActivityRepository;
use Jihe\Services\TeamMemberService;
use Jihe\Services\TeamService;
use Jihe\Services\StorageService;
use Jihe\Contracts\Repositories\ActivityAlbumRepository;
use Jihe\Entities\ActivityAlbumImage as ActivityAlbumImageEntity;
use Jihe\Entities\Activity as ActivityEntity;
use Jihe\Entities\User as UserEntity;
use Jihe\Entities\Team as TeamEntity;
use Jihe\Contracts\Repositories\ActivityMemberRepository;
use Jihe\Services\Admin\UserService as AdminUserService;
use Jihe\Contracts\Services\Qrcode\QrcodeService;
use Jihe\Contracts\Repositories\ActivityFileRepository;

class ActivityServiceSpec extends LaravelObjectBehavior
{
    function let(ActivityRepository $activities,
                 TeamMemberService $members,
                 TeamService $teams,
                 StorageService $storages,
                 ActivityAlbumRepository $albums,
                 ActivityMemberRepository $activityMembers,
                 AdminUserService $adminUserService,
                 SearchService $searchService,
                 QrcodeService $qrcodeService,
                 ActivityFileRepository $files,
                 ActivityPlanRepository $activityPlanRepository,
                 UserService $userService,
                 ActivityApplicantRepository $activityApplicantRepository
                )
    {
        $this->beAnInstanceOf(\Jihe\Services\ActivityService::class, [
            $activities,
            $members,
            $teams,
            $storages,
            $albums,
            $activityMembers,
            $adminUserService,
            $searchService,
            $qrcodeService,
            $files,
            $activityPlanRepository,
            $userService,
            $activityApplicantRepository,
        ]);
        
        Bus::shouldReceive('dispatch')->with(\Mockery::on(function ($job) {
            return ($job instanceof \Jihe\Jobs\ActivitySearchIndexRefreshJob);
        }))->andReturn(null);
        Bus::shouldReceive('dispatch')->with(\Mockery::on(function ($job) {
            return ($job instanceof \Jihe\Jobs\SendMessageToActivityMemberJob);
        }))->andReturn(null);
    }
    
    //=============================================
    //               addAlbumImage
    //=============================================
    function it_adds_ablum_image_successful_if_creator_type_is_sponsor_and_user_is_activity_creator(
            ActivityRepository $activities, ActivityAlbumRepository $albums, TeamService $teams, ActivityMemberRepository $activityMembers)
    {
        $requestedImage = [
            'activity' => $this->createActivity(1, 1),
            'creator_type' => ActivityAlbumImageEntity::SPONSOR,
            'creator'      => 1,
            'image_id'    => 'http://domain/image.png',
        ];
        
        $image = (new ActivityAlbumImageEntity())
                    ->setId(1)
                    ->setActivity($this->createActivity(1, 1))
                    ->setCreatorType(ActivityAlbumImageEntity::SPONSOR)
                    ->setCreator((new UserEntity())->setId(1))
                    ->setImageUrl('http://domain/image.png')
                    ->setStatus(ActivityAlbumImageEntity::STATUS_APPROVED);
        
        $teams->getTeamsByCreator(1)->shouldBeCalled()->willReturn([(new TeamEntity())->setId(1)]);
        $activityMembers->exists(Argument::cetera())->shouldNotBeCalled();
        $activities->updateOnce(1, ['has_album' => 1])->shouldBeCalled()->willReturn(true);
        $albums->addImage(Argument::that(function (ActivityAlbumImageEntity $image) {
            return 1 == $image->getActivity()->getId() && 
                   ActivityAlbumImageEntity::SPONSOR == $image->getCreatorType() && 
                   1 == $image->getCreator()->getId() && 
                   'http://domain/image.png' == $image->getImageUrl();
        }))->shouldBeCalled()->willReturn($image);
        $teams->notify(1, ['albums'])->shouldBeCalledTimes(1)->willReturn(1);
        
        $this->addAlbumImage($requestedImage)->shouldBe($image);
    }
    
    /**
     * 
     * @param int $activity
     * @param int $team
     * @return \Jihe\Entities\Activity
     */
    private function createActivity($activity, $team)
    {
        return (new ActivityEntity())
                ->setId($activity)
                ->setTeam((new TeamEntity())->setId($team));
    }
    
    function it_adds_album_image_successful_if_creator_type_is_user_and_user_is_activty_member(
            ActivityAlbumRepository $albums, TeamService $teams, ActivityMemberRepository $activityMembers)
    {
        $requestedAlbum = [
            'activity' => $this->createActivity(1, 1),
            'creator_type' => ActivityAlbumImageEntity::USER,
            'creator'      => 1,
            'image_id'    => 'http://domain/image.png',
        ];
        
        $album = (new ActivityAlbumImageEntity())
                    ->setId(1)
                    ->setActivity($this->createActivity(1, 1))
                    ->setCreatorType(ActivityAlbumImageEntity::USER)
                    ->setCreator((new UserEntity())->setId(1))
                    ->setImageUrl('http://domain/image.png')
                    ->setStatus(ActivityAlbumImageEntity::STATUS_APPROVED);
        
        $teams->getTeamsByCreator(Argument::cetera())->shouldNotBeCalled();
        $activityMembers->exists(1, 1)->shouldBeCalled()->willReturn(true);
        
        $albums->addImage(Argument::that(function (ActivityAlbumImageEntity $album) {
            return 1 == $album->getActivity()->getId() &&
                   ActivityAlbumImageEntity::USER == $album->getCreatorType() &&
                   1 == $album->getCreator()->getId() &&
                   'http://domain/image.png' == $album->getImageUrl();
        }))->shouldBeCalled()->willReturn($album);
        
        $this->addAlbumImage($requestedAlbum)->shouldBe($album);
    }
    
    function it_adds_ablum_image_throw_exception_if_creator_type_is_sponsor_but_user_is_not_activity_creator(
            ActivityAlbumRepository $albums, TeamService $teams, ActivityMemberRepository $activityMembers)
    {
        $requestedImage = [
            'activity'     => $this->createActivity(1, 1),
            'creator_type' => ActivityAlbumImageEntity::SPONSOR,
            'creator'      => 1,
            'image_id'     => 'http://domain/image.png',
        ];
        
        $teams->getTeamsByCreator(1)->shouldBeCalled()->willReturn([]);
        $activityMembers->exists(Argument::cetera())->shouldNotBeCalled();
        $albums->addImage(Argument::cetera())->shouldNotBeCalled();
    
        $this->shouldThrow(new \Exception('非活动发布者'))
             ->duringAddAlbumImage($requestedImage);
    }
    
    function it_adds_ablum_image_throw_exception_if_creator_type_is_user_but_user_is_not_enrolled_activity(
            ActivityAlbumRepository $albums, TeamService $teams, ActivityMemberRepository $activityMembers)
    {
        $requestedImage = [
            'activity'     => $this->createActivity(1, 1),
            'creator_type' => ActivityAlbumImageEntity::USER,
            'creator'      => 1,
            'image_id'     => 'http://domain/image.png',
        ];
        
        $teams->getTeamsByCreator(Argument::cetera())->shouldNotBeCalled();
        $activityMembers->exists(1, 1)->shouldBeCalled()->willReturn(false);
        $albums->addImage(Argument::cetera())->shouldNotBeCalled();
    
        $this->shouldThrow(new \Exception('非活动成员'))
             ->duringAddAlbumImage($requestedImage);
    }
}