<?php
namespace Jihe\Repositories;

use Jihe\Contracts\Repositories\TeamRequestRepository as TeamRequestRepositoryContract;
use Jihe\Entities\TeamRequest as TeamRequestEntity;
use Jihe\Entities\City as CityEntity;
use Jihe\Entities\Team as TeamEntity;
use Jihe\Entities\User as UserEntity;
use Jihe\Models\TeamRequest;
use Jihe\Models\User;
use Jihe\Models\Team;
use Jihe\Models\City;

class TeamRequestRepository implements TeamRequestRepositoryContract
{
    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\TeamRequestRepository::hasPendingEnrollmentRequest()
     */
    public function hasPendingEnrollmentRequest($initiator)
    {
        return null != TeamRequest::where('status', TeamRequestEntity::STATUS_PENDING)
                                  ->where('initiator_id', $initiator)
                                  ->whereNull('team_id')
                                  ->value('id');
    }
    
    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\TeamRequestRepository::hasPendingUpdateRequest()
     */
    public function hasPendingUpdateRequest($team)
    {
        return null != TeamRequest::where('status', TeamRequestEntity::STATUS_PENDING)
                                  ->where('team_id', $team)
                                  ->value('id');
    }
    
    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\TeamRequestRepository::findPendingEnrollmentRequest()
     */
    public function findPendingEnrollmentRequest($initiator, $relations = [])
    {
        $query = TeamRequest::where('status', TeamRequestEntity::STATUS_PENDING)
                              ->where('initiator_id', $initiator)
                              ->whereNull('team_id');

        if (!empty($relations)) {
            $query->with($relations);
        }

        return $this->morphToTeamRequestEntity($query->first());
    }
    
    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\TeamRequestRepository::findPendingUpdateRequest()
     */
    public function findPendingUpdateRequest($team, $relations = [])
    {
        $query = TeamRequest::where('status', TeamRequestEntity::STATUS_PENDING)
                              ->where('team_id', $team);
        
        if (!empty($relations)) {
            $query->with($relations);
        }
        
        return $this->morphToTeamRequestEntity($query->first());
    }
    
    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\TeamRequestRepository::findPendingRequests()
     */
    public function findPendingRequests($page, $size, $relations = [])
    {
        $query = TeamRequest::where('status', TeamRequestEntity::STATUS_PENDING);
        
        if (!empty($relations)) {
            $query->with($relations);
        }
        
        $query->orderBy('created_at', 'desc');
        $query->orderBy('id', 'desc');
        
        $total = $query->getCountForPagination()->count();
        $pages = ceil($total / $size);
        if ($page > $pages) {
            $page = $pages;
        }
        
        return [
                $pages,
                array_map(
                        [$this, 'morphToTeamRequestEntity'],
                        $query->forPage($page, $size)->get()->all()),
        ];
    }
    
    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\TeamRequestRepository::findUninspectedRequests()
     */
    public function findUninspectedRequests($initiator, $relations = [])
    {
        $query = TeamRequest::where('initiator_id', $initiator)
                            ->where('read', TeamRequest::UN_READ);
        
        if (!empty($relations)) {
            $query->with($relations);
        }
        
        return array_map([ $this, 'morphToTeamRequestEntity' ], $query->get()->all());
    }
    
    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\TeamRequestRepository::updateRequestToInspected()
     */
    public function updateRequestToInspected($request)
    {
        return 1 == TeamRequest::where('id', $request)
                                ->where('status', '<>', TeamRequestEntity::STATUS_PENDING)
                                ->where('read', TeamRequest::UN_READ)
                                ->update(['read' => TeamRequest::READ]);
    }
    
    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\TeamRequestRepository::getApplyCountOfTeamUpdated()
     */
    public function getNumberOfUpdatedTimes($team)
    {
        return TeamRequest::where('status', TeamRequestEntity::STATUS_APPROVED)
                          ->where('team_id', $team)
                          ->count();
    }
    
    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\TeamRequestRepository::add()
     */
    public function add(TeamRequestEntity $request)
    {
        return TeamRequest::create($this->convertToModelAttributes($request))->id;
    }
    
    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\TeamRequestRepository::getApplication()
     */
    public function findRequest($id)
    {
        $request = TeamRequest::with(['city', 'team', 'initiator'])
                              ->where('id', $id)
                              ->first();

        return $this->morphToTeamRequestEntity($request);
    }

    /**
     *
     * @param \Jihe\Entities\TeamRequest $request
     * @return array
     */
    private function convertToModelAttributes(TeamRequestEntity $request)
    {
        return array_filter([
            'team_id'       => (is_null($request->getTeam())) ? null : $request->getTeam()->getId(),
            'initiator_id'  => $request->getInitiator()->getId(),
            'city_id'       => $request->getCity()->getId(),
            'name'          => $request->getName(),
            'email'         => $request->getEmail(),
            'logo_url'      => $request->getLogoUrl(),
            'address'       => $request->getAddress(),
            'contact_phone' => $request->getContactPhone(),
            'contact'       => $request->getContact(),
            'contact_hidden' => $request->getContactHidden(),
            'introduction'  => $request->getIntroduction(),
            'status'        => $request->getStatus(),
            'read'          => $request->isRead() ? TeamRequest::READ : TeamRequest::UN_READ,
            'memo'          => $request->getMemo(),
        ]);
    }
    
    /**
     *
     * @param \Jihe\Models\TeamRequest $request
     * @return \Jihe\Entities\TeamRequest|null
     */
    private function morphToTeamRequestEntity(TeamRequest $request = null)
    {
        if ($request == null) {
            return null;
        }

        return $request->toEntity();
    }

    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\TeamRequestRepository::updateStatusToApproved()
     */
    public function updatePendingRequestToApproved($request, $memo = null)
    {
        return $this->updateStatus($request, TeamRequestEntity::STATUS_PENDING,
                                        TeamRequestEntity::STATUS_APPROVED, $memo);
    }
    
    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\TeamRequestRepository::updateStatusToRejected()
     */
    public function updatePendingRequestToRejected($request, $memo = null)
    {
        return $this->updateStatus($request, TeamRequestEntity::STATUS_PENDING,
                                             TeamRequestEntity::STATUS_REJECTED, $memo);
    }
    
    private function updateStatus($id, $oldStatus, $newStatus, $memo = null)
    {
        $updateAttributes = ['status' => $newStatus];
        if (!is_null($memo)) {
            $updateAttributes['memo'] = $memo;
        }
        
        return 1 == TeamRequest::where('id', $id)
                               ->where('status', $oldStatus)
                               ->update($updateAttributes);
    }
}
