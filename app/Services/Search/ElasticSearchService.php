<?php
namespace Jihe\Services\Search;

use Elastica\Client;
use Elastica\Document;
use Elastica\Filter\Term;
use Elastica\Filter\Terms;
use Elastica\Query;
use Elastica\Query\Filtered;
use Elastica\Filter\Bool;
use Elastica\Search;
use Jihe\Entities\Team as TeamEntity;
use Jihe\Entities\Activity as ActivityEntity;
use Jihe\Contracts\Repositories\ActivityRepository;
use Jihe\Contracts\Repositories\TeamRepository;
use Jihe\Contracts\Services\Search\SearchService;

class ElasticSearchService implements SearchService
{

    const INDEX_NAME_V1 = 'jhla_v1';
    const INDEX_NAME = 'jhla';
    const TEAM_TYPE_NAME = 'team';
    const ACTIVITY_TYPE_NAME = 'activity';

    /**
     * @var \Elastica\Client
     */
    private $elasticaClient;

    /**
     * @var \Jihe\Contracts\Repositories\TeamRepository
     */
    private $teamRepository;

    /**
     * @var \Jihe\Contracts\Repositories\ActivityRepository
     */
    private $activityRepository;

    function __construct(Client $elasticaClient,
                         TeamRepository $teamRepository,
                         ActivityRepository $activityRepository)
    {
        $this->elasticaClient = $elasticaClient;
        $this->teamRepository = $teamRepository;
        $this->activityRepository = $activityRepository;
    }

    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Services\Search\SearchService::init()
     */
    public function init($deleteIfExists = false, $numberOfShards = 1, $numberOfReplicas = 0)
    {
        $elasticaIndex = $this->elasticaClient->getIndex(static::INDEX_NAME_V1);
        $elasticaIndex->create(
            [
                'settings' => [
                    'number_of_shards'   => $numberOfShards,
                    'number_of_replicas' => $numberOfReplicas,
                ],
                'mappings' => [
                    '_default_' => [
                        'dynamic' => false,
                        '_all'    => [
                            'enabled' => false,
                        ],
                    ],
                    'team'      => [
                        'properties' => [
                            'title' => [
                                'type'     => 'string',
                                'index'    => 'analyzed',
                                'analyzer' => 'ik',
                            ],
                            'tags'  => [
                                'type'  => 'string',
                                'index' => 'not_analyzed',
                            ],
                            'city'  => [
                                'type'       => 'object',
                                'properties' => [
                                    'id' => [
                                        'type' => 'long',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'activity'  => [
                        'properties' => [
                            'city' => [
                                'type'       => 'object',
                                'properties' => [
                                    'id' => [
                                        'type' => 'long',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            $deleteIfExists
        );
        $elasticaIndex->addAlias(static::INDEX_NAME, true);
    }

    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Services\Search\SearchService::indexTeam()
     */
    public function indexTeam($teamId)
    {
        $team = $this->teamRepository->findTeam($teamId, ['city']);
        if (is_null($team)) {
            return null;
        }

        $this->elasticaClient->deleteIds([$teamId],
            static::TEAM_TYPE_NAME,
            static::INDEX_NAME);

        $document = new Document($teamId,
            $this->getTeamArrayToIndex($team),
            static::TEAM_TYPE_NAME,
            static::INDEX_NAME);
        return $this->elasticaClient->addDocuments([$document]);
    }

    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Services\Search\SearchService::indexActivity()
     */
    public function indexActivity($activityId)
    {
        $activities = $this->activityRepository->findActivitiesByIds([$activityId], null, false);

        if (empty($activities)) {
            return null;
        }
        $activity = $activities[0];

        $this->elasticaClient->deleteIds([$activityId],
            static::ACTIVITY_TYPE_NAME,
            static::INDEX_NAME);

        if ($activity->getStatus() == ActivityEntity::STATUS_DELETE) {
            return null;
        }

        $document = new Document($activityId,
            $this->getActivityArrayToIndex($activity),
            static::TEAM_TYPE_NAME,
            static::INDEX_NAME);
        return $this->elasticaClient->addDocuments([$document]);
    }

    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Services\Search\SearchService::getRecommendTeam()
     */
    public function getRecommendTeam($cityId, $tags, $resultNum = 10)
    {
        $tagsFilter = new Terms('tags', $tags);
        $cityIdFilter = new Term(['city.id' => $cityId]);
        $filter = (new Bool)->addMust($cityIdFilter)->addMust($tagsFilter);
        $query = (new Filtered(null, $filter));
        $search = (new Search($this->elasticaClient))
            ->setQuery($query)
            ->setOption('size', $resultNum)
            ->addIndex(static::INDEX_NAME)
            ->addType(static::TEAM_TYPE_NAME);

        $results = [];
        $original = $search->search();
        if ($original->getTotalHits() > 0) {
            foreach ($original->getResults() as $originalItem) {
                $results[] = $originalItem->getData();
            }
        }
        return $results;
    }

    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Services\Search\SearchService::getRecommendActivity()
     */
    public function getRecommendActivity($cityId, $tags, $resultNum = 10)
    {
        $tagsFilter = new Terms('tags', $tags);
        $cityIdFilter = new Term(['city.id' => $cityId]);
        $filter = (new Bool)->addMust($cityIdFilter)->addMust($tagsFilter);
        $query = (new Filtered(null, $filter));
        $search = (new Search($this->elasticaClient))
            ->setQuery($query)
            ->setOption('size', $resultNum)
            ->addIndex(static::INDEX_NAME)
            ->addType(static::ACTIVITY_TYPE_NAME);

        $results = [];
        $originalResult = $search->search();
        if ($originalResult->getTotalHits() > 0) {
            foreach ($originalResult->getResults() as $originalResultItem) {
                $results[] = $originalResultItem->getData();
            }
        }
        return $results;
    }

    private function getTeamArrayToIndex(TeamEntity $team)
    {
        return [
            'id'            => $team->getId(),
            'name'          => $team->getName(),
            'introduction'  => $team->getIntroduction(),
            'tags'          => json_decode($team->getTags()),
            'certification' => $team->getCertification(),
            'logo_url'      => $team->getLogoUrl(),
            'qr_code_url'   => $team->getQrCodeUrl(),
            'city'          => [
                'id'   => $team->getCity()->getId(),
                'name' => $team->getCity()->getName(),
            ]];
    }

    private function getActivityArrayToIndex(ActivityEntity $activity)
    {
        return [
            'id'                => $activity->getId(),
            'title'             => $activity->getTitle(),
            'qr_code_url'       => $activity->getQrCodeUrl(),
            'publish_time'      => $activity->getPublishTime(),
            'begin_time'        => $activity->getBeginTime(),
            'end_time'          => $activity->getEndTime(),
            'tags'              => json_decode($activity->getTags()),
            'contact'           => $activity->getContact(),
            'telephone'         => $activity->getTelephone(),
            'detail'            => $activity->getDetail(),
            'auditing'          => $activity->getAuditing(),
            'images_url'        => $activity->getImagesUrl(),
            'enroll_begin_time' => $activity->getEnrollBeginTime(),
            'enroll_end_time'   => $activity->getEnrollEndTime(),
            'enroll_type'       => $activity->getEnrollType(),
            'enroll_limit'      => $activity->getEnrollLimit(),
            'enroll_attrs'      => $activity->getEnrollAttrs(),
            'roadmap'           => $activity->getRoadmap(),
            'update_step'       => $activity->getUpdateStep(),
            'status'            => $activity->getStatus(),
            'team_id'           => $activity->getTeam()->getId(),
            'sub_status'        => $activity->getSubStatus(),
            'cover_url'         => $activity->getCoverUrl(),
            'address'           => $activity->getaddress(),
            'brief_address'     => $activity->getBriefAddress(),
            'enroll_fee_type'   => $activity->getEnrollFeeType(),
            'enroll_fee'        => $activity->getEnrollFee(),
            'essence'           => $activity->getEssence(),
            'city'              => [
                'id'   => $activity->getCity()->getId(),
                'name' => $activity->getCity()->getName(),
            ],
            'team'              => [
                'id'           => $activity->getTeam()->getId(),
                'name'         => $activity->getTeam()->getName(),
                'logo_url'     => $activity->getTeam()->getLogoUrl(),
                'introduction' => $activity->getTeam()->getIntroduction(),
            ],
            'location'          => $activity->getLocation(),
        ];
    }
}