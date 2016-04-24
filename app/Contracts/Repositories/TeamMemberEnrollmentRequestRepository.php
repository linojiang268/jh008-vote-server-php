<?php
namespace Jihe\Contracts\Repositories;

interface TeamMemberEnrollmentRequestRepository
{
    /**
     * add a new team member request
     *
     * @param int $initiator       the user who issued this request
     * @param int $team            team the user requests to be added to
     * @param array $requests      enrollment requirements. including:
     *                             - name         (Optional) nick name in the team
     *                             - memo         (Optional) memo
     *                             - requirements (Optional) of array type. contains answer to team requirements,
     *                                            which is keyed by team requirement id and valued by corresponding
     *                                            answer
     *                             - group        (Optional) group of given team, if not given, TeamGroup::UNGROUPED
     *                                            will be used
     *
     * @return int                id of the newly added request
     */
    public function add($initiator, $team, array $requests = null);

    /**
     * remove finished (un-pending) requests of given user
     *
     * @param int $initiator    id of initiator
     */
    public function removeFinishedRequestsOf($initiator);

    /**
     * find pending request by its id
     *
     * @param int|array $request  id of the request
     * @param int $team           team id
     * @return \Jihe\Entities\TeamMemberEnrollmentRequest|null
     */
    public function findPendingRequest($request, $team);

    /**
     * find request by its id
     *
     * @param int $request        id of the request
     * @param int $team           team id
     * @return \Jihe\Entities\TeamMemberEnrollmentRequest|null
     */
    public function findRequest($request, $team);


    /**
     * update given request
     *
     * @param int   $request
     * @param array $updates
     *
     * @return bool            true on success, false otherwise
     */
    public function update($request, array $updates);

    /**
     * find pending request by its id
     *
     * @param int $request        id of the request
     * @param int $team           team id
     * @return \Jihe\Entities\TeamMemberEnrollmentRequest|null
     */
    public function findRejectedRequest($request, $team);

    /**
     * find pending requests of given team
     *
     * @param int $initiator  initiator's id
     * @return array          array of pending requests
     */
    public function findPendingRequestsForInitiator($initiator);

    /**
     * find pending requests of given team
     *
     * @param int $team   team's id
     * @param int $page   page number
     * @param int $size   page size
     * @return array      [0] total pages
     *                    [1] array of pending requests
     */
    public function findPendingRequestsForTeam($team, $page = 1, $size = 15);

    /**
     * find rejected requests of given team
     *
     * @param int $team   team's id
     * @param int $page   page number
     * @param int $size   page size
     * @return array     [0] total pages
     *                   [1] array of rejected requests
     */
    public function findRejectedRequestsForTeam($team, $page = 1, $size = 15);

    /**
     * check whether given pending request exists
     *
     * @param int $user    user's id
     * @param int $team    team's id
     * @return bool        true if pending request exists. false otherwise.
     */
    public function pendingRequestExists($user, $team);

    /**
     * stat team's pending enrollment requests
     *
     * @param array $teams  team ids
     *
     * @return array
     */
    public function statPendingEnrollmentRequests(array $teams = null);

    /**
     * update pending request to rejected state
     *
     * @param array|int $request      request to update
     * @param string $reason    the reason why the request is rejected
     *
     * @return bool             true on success, false otherwise
     */
    public function updateStatusToRejected($request, $reason);

    /**
     * update pending request to approved state
     *
     * @param int|array $request      request to update
     *
     * @return bool                   true on success, false otherwise
     */
    public function updateStatusToApproved($request);


    /**
     * find request permission set for given user in some team
     *
     * @param string $mobile   mobile of the user
     * @param int $team        team id
     *
     * @return \Jihe\Entities\TeamMemberEnrollmentPermission|null
     */
    public function findPermission($mobile, $team);
    
    /**
     * find teams that adding the user in their whitelist
     *
     * @param string $mobile  user's mobile
     * 
     * @return array array of teams
     */
    public function findTeamsWhitelistedUser($mobile);

    /**
     * add a new request permission
     *
     * @param array $permission     detail of an enrollment permission, fields are:
     *                              - mobile    (mandatory) mobile
     *                              - team      (mandatory) team id
     *                              - name      (optional)  name in the team
     *                              - memo      (optional)  memo
     *                              - status    (optional) TeamMemberEnrollmentPermission::STATUS_PERMITTED or
     *                                                     TeamMemberEnrollmentPermission::STATUS_PROHIBITED
     *
     * @return int            id of the newly created permission
     */
    public function addPermission(array $permission);

    /**
     * update given permission
     *
     * @param int $permission  permission id
     * @param array $updates   things to update, keys taken:
     *                         - memo
     *                           memo to the user(denoted by his/her mobile)
     *                         - status
     *                           should be one of TeamMemberRequestPermission::STATUS_PERMITTED
     *                           or STATUS_PROHIBITED
     *
     * @return bool           true if updated successfully. false otherwise.
     */
    public function updatePermission($permission, array $updates);

    /**
     * delete given permission
     *
     * @param int $permission  permission's id
     *
     * @return bool            true if the deleted. false otherwise
     */
    public function deletePermission($permission);

    /**
     * find all prohibited permissions for given team
     * @param int $team   team id
     * @param int $page   page number
     * @param int $size   page size
     * @return array      [0] total pages
     *                    [1] whitelist in given page
     */
    public function findProhibitedPermissionsFor($team, $page = 1, $size = 15);

    /**
     * find all permitted permissions for given team
     * @param int $team   team id
     * @param int $page   page number
     * @param int $size   page size
     * @return array      [0] total pages
     *                    [1] blacklist in given page
     */
    public function findPermittedPermissionsFor($team, $page = 1, $size = 15);
    
    /**
     * list pending teams given user requested
     * 
     * @param int $user        id of user
     * @param array $options   options, keys taken:
     *                           - only_id false(default)
     * @return array \Jihe\Entities\Team
     */
    public function findPendingRequestedTeams($user, array $options = []);
}
