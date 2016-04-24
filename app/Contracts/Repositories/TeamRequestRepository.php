<?php
namespace Jihe\Contracts\Repositories;

use Jihe\Entities\TeamRequest;

interface TeamRequestRepository
{
    /**
     * check whether there is a pending request for team enrollment
     *
     * @param int $initiator   who initiates this request
     * @return boolean         true if pending enrollment request exists. false otherwise.
     */
    public function hasPendingEnrollmentRequest($initiator);
    
    /**
     * check whether there is a pending request for team update
     *
     * @param int $team   id of the team
     * @return boolean    true if pending update request exists. false otherwise
     */
    public function hasPendingUpdateRequest($team);

    /**
     * get the number of times a team has been updated ever before
     *
     * @param int $team     id of the team
     *
     * @return int          the number of updated times
     */
    public function getNumberOfUpdatedTimes($team);
    
    /**
     * add a new team request (either enrollment or update request)
     *
     * @param \Jihe\Entities\TeamRequest $request
     * @return int      id of new record
     */
    public function add(TeamRequest $request);
    
    /**
     * find request by its id
     *
     * @param int $id       id of request
     * @return \Jihe\Entities\TeamRequest|null
     */
    public function findRequest($id);
    
    /**
     * update status of pending request to approved state
     *
     * @param int $id       id of request
     * @param string $memo  memo why update the request to approved
     * @return bool         true if update successfully. false otherwise
     */
    public function updatePendingRequestToApproved($request, $memo = null);
    
    /**
     * update status of pending request to rejected state
     *
     * @param int $request     id of request
     * @return bool            true if update successfully. false otherwise
     */
    public function updatePendingRequestToRejected($request, $memo = null);
    
    /**
     * find pending enrollment request
     *
     * @param int $initiator                    id of the request initiator
     * @param array $relations|[]               array of model relatisonship, keys:
     *                                             - city
     *                                             - creator
     * @return \Jihe\Entities\TeamRequest|null
     */
    public function findPendingEnrollmentRequest($initiator, $relations = []);
    
    /**
     * find pending update request of given team
     *
     * @param int $team   id of the team
     * @param array $relations|[]               array of model relatisonship, keys:
     *                                             - city
     *                                             - creator
     *                                             - team
     * @return \Jihe\Entities\TeamRequest|null
     */
    public function findPendingUpdateRequest($team, $relations = []);
    
    /**
     * find pending requests
     * 
     * @param int $page                         index of page
     * @param int $size                         size of page
     * @param array $relations|[]               array of model relatisonship, keys:
     *                                             - city
     *                                             - creator
     *                                             - team
     * @return array \Jihe\Entities\TeamRequest
     */
    public function findPendingRequests($page, $size, $relations = []);
    
    /**
     * find request by given initiator that user has not inspected
     *
     * @param int $team   id of the request initiator
     * @return array \Jihe\Entities\TeamRequest
     */
    public function findUninspectedRequests($initiator);
    
    /**
     * update request to inspected which request is uninspected
     *
     * @param int $request  id of team request
     * @return boolean      true if update successfully, otherwise false
     */
    public function updateRequestToInspected($request);
}
