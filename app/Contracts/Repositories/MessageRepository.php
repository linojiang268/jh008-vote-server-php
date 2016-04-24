<?php
namespace Jihe\Contracts\Repositories;

interface MessageRepository
{
    /**
     * add message of system message|team message|activity message
     * 
     * @param \Jihe\Entities\Message $message
     * @return \Jihe\Entities\Message
     */
    public function add(\Jihe\Entities\Message $message);
    
    /**
     * find messages of user sended by system
     * 
     * @param int $user               id of user
     * @param int $page               index of page
     * @param int $size               size of page
     * @param String $lastCreatedAt   created time of last message 
     * @return array      array of \Jihe\Entities\Message
     */
    public function findSystemMessagesOf($user, $page, $size, $lastCreatedAt = null);
    
    /**
     * find messages of user that created from user's teams or user's activity 
     * 
     * @param id|array $teams         ids of teams
     * @param id|array $activities    ids of activities
     * @param id $user                id of user
     * @param String $lastCreatedAt   created time of last message
     * @return array                  array of \Jihe\Entities\Message
     */
    public function findUserMessages($teams, $activities, $user, $page, $size, $lastCreatedAt = null);
    
    /**
     * find messages of user that created from given team
     * 
     * @param int $team               id of team
     * @param id|array $activities    ids of activities
     * @param int $user               id of user
     * @param int $page               index of page
     * @param int $size               size of page
     * @param String $lastCreatedAt   created time of last message 
     * @return array                  array of \Jihe\Entities\Message        
     */
    public function findUserMessagesOf($team, $activities, $user, $page, $size, $lastCreatedAt = null);
    
    /**
     * find messages sended by system
     *
     * @param int $page               index of page
     * @param int $size               size of page
     * @return array      array of \Jihe\Entities\Message
     */
    public function findSystemMessages($page, $size);
    
    /**
     * find messages sended by team
     *
     * @param int $team         id of team
     * @param int $page         index of page
     * @param int $size         size of page
     * @return array            array of \Jihe\Entities\Message
    */
    public function findTeamMessages($team, $page, $size);
    
    /**
     * find messages sended by activity
     *
     * @param int $activity     id of activity
     * @param int $page         index of page
     * @param int $size         size of page
     * @return array            array of \Jihe\Entities\Message
     */
    public function findActivityMessages($activity, $page, $size);

    /**
     * find messages of user that created from user's teams or user's activity or system
     *
     * @param id $user                id of user
     * @param id|array $teams         ids of teams
     * @param id|array $activities    ids of activities
     * @param String $lastCreatedAt   created time of last message
     * @param $onlyCount              whether return only count, true default, otherwise false
     * @return array                  array of \Jihe\Entities\Message
     */
    public function findMessagesOfUser($user, array $teams = [], array $activities = [], $lastCreatedAt, $onlyCount = true);

    /**
     * @param       $activity  id of activity
     * @param array $options   options, keys taken:
     *                          - only_mass (boolean)false default
     * @return mixed
     */
    public function countActivityNotices($activity, $options = []);
}
