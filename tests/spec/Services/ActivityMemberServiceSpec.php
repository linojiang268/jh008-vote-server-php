<?php
namespace spec\Jihe\Services;

use PhpSpec\Laravel\LaravelObjectBehavior;
use Prophecy\Argument;

use Jihe\Contracts\Repositories\ActivityMemberRepository;
use Jihe\Services\ActivityService;
use Jihe\Repositories\ActivityGroupRepository;
use Jihe\Entities\Activity as ActivityEntity;
use Jihe\Entities\User as UserEntity;

class ActivityMemberServiceSpec extends LaravelObjectBehavior
{
    function let(ActivityMemberRepository $activityMemberRepository,
                 ActivityGroupRepository $activityGroupRepository)
    {
        $this->beAnInstanceOf(\Jihe\Services\ActivityMemberService::class, 
                             [
                                 $activityMemberRepository,
                                 $activityGroupRepository,
                             ]);
    }
    
    //======================================
    //                Score
    //======================================
    function it_scored_successful(ActivityMemberRepository $activityMemberRepository)
    {
        $activity = (new ActivityEntity())
                        ->setId(1)
                        ->setStatus(ActivityEntity::STATUS_PUBLISHED)
                        ->setEndTime(date('Y-m-d H:i:s', strtotime('1990-01-01 00:00:00')));
        
        $activityMemberRepository->exists(1, 1)
                                 ->shouldBeCalled()
                                 ->willReturn(true);
        $activityMemberRepository->updateScore(1, 1, [
                                        'score' => 3,
                                    ])
                                 ->shouldBeCalled()
                                 ->willReturn(true);
        
        $this->score($activity, 1, ['score' => 3])->shouldBe(true);
    }
    
    function it_fail_to_score_if_today_is_not_next_day(ActivityMemberRepository $activityMemberRepository)
    {
        $activity = (new ActivityEntity())
                        ->setId(1)
                        ->setStatus(ActivityEntity::STATUS_PUBLISHED)
                        ->setEndTime(date('Y-m-d H:i:s', strtotime("+1 day")));
    
        $activityMemberRepository->exists(Argument::cetera())->shouldNotBeCalled();
        $activityMemberRepository->updateScore(Argument::cetera())->shouldNotBeCalled();
    
        $this->shouldThrow(new \Exception('活动结束后一天才能评分'))
             ->duringScore($activity, 1, ['score' => 3]);
    }
    
    function it_fail_to_score_if_user_is_not_member(ActivityMemberRepository $activityMemberRepository)
    {
        $activity = (new ActivityEntity())
                        ->setId(1)
                        ->setStatus(ActivityEntity::STATUS_PUBLISHED);
    
        $activityMemberRepository->exists(1, 1)
                                 ->shouldBeCalled()
                                 ->willReturn(false);
        $activityMemberRepository->updateScore(Argument::cetera())->shouldNotBeCalled();
    
        $this->shouldThrow(new \Exception('非活动成员不能评分'))
             ->duringScore($activity, 1, ['score' => 3]);
    }
    
    //======================================
    //            Get AverageScore
    //======================================
    function it_gets_average_score_successful(ActivityMemberRepository $activityMemberRepository)
    {
        $activity = (new ActivityEntity())
                        ->setId(1)
                        ->setStatus(ActivityEntity::STATUS_PUBLISHED);
        
        $activityMemberRepository->countMembers(1)
                                 ->shouldBeCalled()
                                 ->willReturn(6);
        $activityMemberRepository->countScoredMembers(1)
                                 ->shouldBeCalled()
                                 ->willReturn(3);
        $activityMemberRepository->sumScored(1)
                                 ->shouldBeCalled()
                                 ->willReturn(13);
    
        $this->getAverageScore($activity)->shouldBe(4.6);
    }
}
