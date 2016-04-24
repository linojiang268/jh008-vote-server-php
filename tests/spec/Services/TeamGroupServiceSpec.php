<?php
namespace spec\Jihe\Services;

use Jihe\Contracts\Repositories\TeamGroupRepository;
use Jihe\Entities\Team as TeamEntity;
use Jihe\Services\TeamMemberService;
use Jihe\Services\TeamService;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class TeamGroupServiceSpec extends ObjectBehavior
{
    function let(TeamGroupRepository $teamGroupRepository,
                 TeamService $teamService,
                 TeamMemberService $teamMemberService)
    {
        $this->beAnInstanceOf(\Jihe\Services\TeamGroupService::class,
            [
                $teamGroupRepository,
                $teamService,
                $teamMemberService,
            ]);
    }
    
    //======================================
    //          add
    //======================================
    function it_adds_non_existing_group(TeamGroupRepository $teamGroupRepository,
                                        TeamService $teamService)
    {
        $teamService->getTeam(1)->willReturn((new TeamEntity)->setId(1));
        $teamGroupRepository->exists('VIP', 1)->willReturn(false);
        $teamGroupRepository->add('VIP', 1)->willReturn(1);

        $this->add('VIP', 1)->shouldBe(1);
    }

    function it_refuses_adding_existing_group(TeamGroupRepository $teamGroupRepository,
                                              TeamService $teamService)
    {
        $teamService->getTeam(1)->willReturn((new TeamEntity)->setId(1));
        $teamGroupRepository->exists('VIP', 1)->willReturn(true);
        $teamGroupRepository->add(Argument::cetera())->shouldNotBeCalled();

        $this->shouldThrow(new \Exception('社团分组已存在'))
             ->duringAdd('VIP', 1);
    }

    function it_refuses_adding_a_group_with_bad_team(TeamGroupRepository $teamGroupRepository,
                                                     TeamService $teamService)
    {
        $teamService->getTeam(1)->willReturn(null);
        $teamGroupRepository->exists(Argument::cetera())->shouldNotBeCalled();
        $teamGroupRepository->add(Argument::cetera())->shouldNotBeCalled();

        $this->shouldThrow(new \Exception('非法社团'))
             ->duringAdd('VIP', 1);
    }

    function it_refuses_adding_a_group_with_reserved_name(TeamGroupRepository $teamGroupRepository,
                                                          TeamService $teamService)
    {
        $teamService->getTeam(1)->willReturn((new TeamEntity)->setId(1));
        $teamGroupRepository->exists(Argument::cetera())->shouldNotBeCalled();
        $teamGroupRepository->add(Argument::cetera())->shouldNotBeCalled();

        $this->shouldThrow(new \Exception('社团分组已存在'))
             ->duringAdd('未分组', 1);
    }

}
