<?php
namespace spec\Jihe\Services\Search;

use Elastica\Client;
use Elastica\Request;
use Elastica\Response;
use Jihe\Contracts\Repositories\TeamRepository;
use Jihe\Contracts\Repositories\ActivityRepository;
use Jihe\Entities\Activity as ActivityEntity;
use Jihe\Entities\City as CityEntity;
use Jihe\Entities\Team as TeamEntity;
use Jihe\Services\Search\ElasticSearchService;
use PhpSpec\Laravel\LaravelObjectBehavior;
use Prophecy\Argument;

class ElasticSearchServiceSpec extends LaravelObjectBehavior
{

    function let(Client $elasticaClient,
                 TeamRepository $teamRepository,
                 ActivityRepository $activityRepository)
    {
        $this->beAnInstanceOf(\Jihe\Services\Search\ElasticSearchService::class, [
            $elasticaClient, $teamRepository, $activityRepository,
        ]);

    }

    //=========================================================================
    //        indexTeam
    //=========================================================================
    function it_accepts_request_for_index_team(Client $elasticaClient, TeamRepository $teamRepository)
    {
        $team = (new TeamEntity)->setId(1)->setCity((new CityEntity)->setId(2));
        $teamRepository->findTeam(1, ['city'])->willReturn($team);
        $elasticaClient->addDocuments(Argument::that(function (array $teamDocumentArray) {
            return $this->isTeamEqual(1, 2, $teamDocumentArray[0]->getData());
        }));
        $elasticaClient->deleteIds([1], ElasticSearchService::TEAM_TYPE_NAME, ElasticSearchService::INDEX_NAME);
        $this->indexTeam(1);
    }

    //=========================================================================
    //        indexActivity
    //=========================================================================
    function it_accepts_request_for_index_activity(Client $elasticaClient, ActivityRepository $activityRepository)
    {
        $activity = (new ActivityEntity)->setId(1)
            ->setCity((new CityEntity)->setId(2))
            ->setTeam((new TeamEntity)->setId(3));
        $activityRepository->findActivitiesByIds([1], null, false)->willReturn([$activity]);
        $elasticaClient->addDocuments(Argument::that(function (array $teamDocumentArray) {
            return $this->isActivityEqual(1, 2, 3, $teamDocumentArray[0]->getData());
        }));
        $elasticaClient->deleteIds([1], ElasticSearchService::TEAM_TYPE_NAME, ElasticSearchService::INDEX_NAME);
        $this->indexActivity(1);
    }

    //=========================================================================
    //        recommendTeam
    //=========================================================================
    function it_accepts_request_for_recommend_team(Client $elasticaClient, ActivityRepository $activityRepository)
    {
        $elasticaClient->request(Argument::cetera())->willReturn(new Response('', 200));
        $this->getRecommendActivity(1, ['测试']);
    }

    //=========================================================================
    //        recommendActivity
    //=========================================================================
    function it_accepts_request_for_recommend_activity(Client $elasticaClient, ActivityRepository $activityRepository)
    {
        $elasticaClient->request(Argument::cetera())->willReturn(new Response('', 200));
        $this->getRecommendActivity(1, ['测试']);
    }

    private function isTeamEqual($expectedId, $expectedCityId, array $teamArray)
    {
        return $expectedId == $teamArray['id'] &&
        $expectedCityId == $teamArray['city']['id'];

    }

    private function isActivityEqual($expectedId, $expectedCityId, $expectedTeamId, array $teamArray)
    {
        return $expectedId == $teamArray['id'] &&
        $expectedCityId == $teamArray['city']['id'] &&
        $expectedCityId == $teamArray['team']['id'];

    }
}
