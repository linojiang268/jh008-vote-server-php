<?php
namespace Jihe\Repositories;

use Jihe\Contracts\Repositories\TeamMemberRepository as TeamMemberRepositoryContract;
use Jihe\Entities\TeamGroup as TeamGroupEntity;
use Jihe\Models\TeamMember;
use Jihe\Models\TeamMemberRequirement;
use Jihe\Entities\TeamMember as TeamMemberEntity;
use Jihe\Entities\Team as TeamEntity;
use Jihe\Utils\SqlUtil;
use Illuminate\Support\Facades\DB;

class TeamMemberRepository implements TeamMemberRepositoryContract
{
    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Repositories\TeamMemberRepository::add()
     */
    public function add($user, $team, array $requests = null)
    {
        $requests = $requests ?: [];
        $requests['user_id'] = $user;
        $requests['team_id'] = $team;
        $requests['group_id'] = array_get($requests, 'group', TeamGroupEntity::UNGROUPED);
        $requests['status'] = TeamMemberEntity::STATUS_NORMAL;

        $requirements = array_get($requests, 'requirements');
        array_forget($requests, ['group', 'requirements']);

        // save member
        $member = TeamMember::create($requests);
        // save requirements if needed
        if (!empty($requirements)) {
            $member->requirements()->saveMany($this->morphRequirements($member, $requirements));
        }

        return $member->id;
    }

    // morph key-value paired requirements to TemMemberRequirement instance
    private function morphRequirements(TeamMember $member, array $requirements = [])
    {
        $instances = [];
        foreach ($requirements as $requirement => $answer) {
            // $requirement corresponds the identifier of the question/requirement
            // which is required by the team leader as a requirement to enroll its members
            array_push($instances, new TeamMemberRequirement([
                'member_id'      => $member->id,
                'requirement_id' => $requirement,
                'value'          => $answer,
            ]));
        }

        return $instances;
    }

    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Repositories\TeamMemberRepository::exists()
     */
    public function exists($user, array $teams = [])
    {
        $existingTeams = TeamMember::where('user_id', $user)
            ->whereIn('team_id', $teams)
            ->get(['team_id']);

        $exists = array_fill_keys($teams, false);
        foreach ($existingTeams as $team) {
            $exists[$team->team_id] = true;
        }

        return $exists;
    }

    /**
     * @inheritdoc
     * @see \Jihe\Contracts\Repositories\TeamMemberRepository::findTeamMember()
     */
    public function findTeamMember($user, $team)
    {
        $member = TeamMember::where('user_id', $user)
            ->where('team_id', $team)
            ->first();

        return $member ? $member->toEntity() : null;
    }

    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Repositories\TeamMemberRepository::updateGroup()
     */
    public function updateGroup($team, $member, $toGroup)
    {
        $members = (array)$member;
        if (empty($members)) {
            return true;
        }

        return TeamMember::where('team_id', $team)
            ->whereIn('user_id', $members)
            ->update([
                'group_id' => $toGroup,
            ]) > 0;
    }

    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Repositories\TeamMemberRepository::updateGroupOfGroupedMembers()
     */
    public function updateGroupOfGroupedMembers($team, $fromGroup, $toGroup)
    {
        return TeamMember::where('team_id', $team)
            ->where('group_id', $fromGroup)
            ->update([
                'group_id' => $toGroup,
            ]) > 0;
    }

    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Repositories\TeamMemberRepository::delete()
     */
    public function delete($team, $member)
    {
        return 1 == TeamMember::where('team_id', $team)
            ->where('user_id', $member)
            ->delete();
    }

    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Repositories\TeamMemberRepository::listMembers()
     */
    public function listMembers($team, $page, $size, array $criteria = [])
    {
        $query = TeamMember::with('user', 'group', 'requirements')->where('team_id', $team);

        /* @var $query \Illuminate\Database\Eloquent\Builder */
        // apply criteria
        if (array_has($criteria, 'visibility')) {
            $query->where('visibility', $criteria['visibility']);
        }
        if (array_get($criteria, 'group')) {
            $query->where('group_id', $criteria['group']);
        }
        if (array_get($criteria, 'name')) {
            $query->join('users', 'users.id', '=', 'team_members.user_id')
                ->where('nick_name', 'LIKE', '%' . SqlUtil::escape($criteria['name']) . '%')
                ->orderBy('team_members.created_at', 'asc')
                ->orderBy('users.id', 'asc');
        } else if (array_get($criteria, 'mobile')) {
            $query->join('users', 'users.id', '=', 'team_members.user_id')
                ->where('mobile', $criteria['mobile'])
                ->orderBy('team_members.created_at', 'asc')
                ->orderBy('users.id', 'asc');
        } else {
            $query->orderBy('created_at', 'asc');
        }


        $query->orderBy('name', 'desc');

        $total = $query->getCountForPagination()->count();
        $pages = ceil($total / $size);
        if ($page > $pages) {
            $page = $pages;
        }

        return [$pages, array_map(function (TeamMember $member) {
            return $member->toEntity();
        }, $query->forPage($page, $size)->get()->all())];
    }

    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\TeamMemberRepository::listEnrolledTeams()
     */
    public function listEnrolledTeams($user, $page = null, $size = null, array $option = [])
    {
        $query = TeamMember::where('user_id', $user)
            ->orderBy('team_members.created_at', 'asc')
            ->orderBy('team_members.id', 'asc');

        $query->join('teams', 'teams.id', '=', 'team_members.team_id')
            ->where('teams.status', TeamEntity::STATUS_NORMAL);

        $ret = [];

        if (array_get($option, 'paging', false)) {
            $total = $query->getCountForPagination()->count();
            $pages = ceil($total / $size);
            if ($page > $pages) {
                $page = $pages;
            }

            $ret[] = $pages;
        }

        $ret[] = array_get($option, 'only_id', true) ?
            array_map(function (TeamMember $teamMember) {
                return $teamMember->team->id;
            }, $query->get()->all())
            :
            array_map(function (TeamMember $teamMember) {
                return $teamMember->team->toEntity();
            }, $query->get()->all());
        return count($ret) > 1 ? $ret : $ret[0];
    }

    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Repositories\TeamMemberRepository::countMembers()
     */
    public function countMembers($team, $visibility = null)
    {
        $query = TeamMember::with('user');
        if (!is_null($visibility)) {
            $query->where('visibility', $visibility);
        }
        
        if (is_array($team)) {
            $result = array_fill_keys($team, 0);
            $members = $query->whereIn('team_id', $team)
                             ->groupBy('team_id')
                             ->addSelect(DB::raw('team_id, count(team_members.id) as total'))
                             ->get(['team_id', 'total']);
            
            foreach ($members as $member) {
                $result[$member->team_id] = $member->total;
            }
            return $result;
        } else {
            return $query->where('team_id', $team)->count();
        }
    }

    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Repositories\TeamMemberRepository::update()
     */
    public function update($member, $team, array $updates)
    {
        return 1 == TeamMember::where('team_id', $team)
            ->where('user_id', $member)
            ->update($updates);
    }
}
