<?php
namespace Jihe\Services\Search;

use Jihe\Contracts\Services\Search\SearchService;

class DummySearchService implements SearchService
{

    /**
     * init the search engine
     *
     * @param boolean $deleteIfExists   whether delete the index if exists before create, default is false
     * @param int     $numberOfShards   the number of shards, default is 1
     * @param int     $numberOfReplicas the number of replicas, default is 0
     */
    public function init($deleteIfExists = false, $numberOfShards = 1, $numberOfReplicas = 0)
    {
        // TODO: Implement init() method.
    }

    /**
     * create or replace index of team
     *
     * @param null|int $teamId team's id, will index all team if null
     *
     * @return array indexed document
     */
    public function indexTeam($teamId)
    {
        return [];
    }

    /**
     * create or replace index of team
     *
     * @param null|int $teamId team's id, will index all team if null
     *
     * @return array indexed document
     */
    public function indexActivity($teamId)
    {
        return [];
    }

    /**
     * get recommended team of the tags
     *
     * @param int   $cityId    city id
     * @param array $tags      the tags search with
     * @param int   $resultNum the max result number
     *
     * @return array the recommended team
     */
    public function getRecommendTeam($cityId, $tags, $resultNum = 10)
    {
        return [];
    }

    /**
     * get recommended activity of the tags
     *
     * @param int   $cityId    city id
     * @param array $tags      the tags search with
     * @param int   $resultNum the max result number
     *
     * @return array the recommended activity
     */
    public function getRecommendActivity($cityId, $tags, $resultNum = 10)
    {
        return [];
    }
}