<?php
namespace Jihe\Repositories;

use Jihe\Contracts\Repositories\ActivityRepository as ActivityRepositoryContract;
use Jihe\Entities\Activity as ActivityEntity;
use Jihe\Entities\TeamMember;
use Jihe\Models\Activity;
use Jihe\Models\ActivityApplicant;
use Jihe\Utils\SqlUtil;
use DB;

class ActivityRepository implements ActivityRepositoryContract
{
    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Repositories\ActivityRepository::getCityActivitiesCount()
     */
    public function getCityActivitiesCount($cityId)
    {
        return Activity::where('city_id', $cityId)
            ->where('status', ActivityEntity::STATUS_PUBLISHED)
            ->count();
    }

    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Repositories\ActivityRepository::getTeamActivitiesCount()
     */
    public function getTeamActivitiesCount($teamId)
    {
        return Activity::where('team_id', $teamId)
            ->where('status', ActivityEntity::STATUS_PUBLISHED)
            ->count();
    }

    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Repositories\ActivityRepository::getTeamsActivitiesCount()
     */
    public function getTeamsActivitiesCount($teamIds, $includeEnd = false)
    {
        $query = Activity::where('status', ActivityEntity::STATUS_PUBLISHED);
        if (!$includeEnd) {
            $query->where('end_time', '>', date('Y-m-d H:i:s'));
        }

        if (is_array($teamIds)) {
            $result = array_fill_keys($teamIds, 0);

            $activities = $query->whereIn('team_id', $teamIds)
                ->groupBy('team_id')
                ->addSelect(DB::raw('team_id, count(id) as total'))
                ->get(['team_id', 'total']);

            foreach ($activities as $activity) {
                $result[$activity->team_id] = $activity->total;
            }

            return $result;
        } else {
            return $query->where('team_id', $teamIds)->count();
        }
    }

    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Repositories\ActivityRepository::searchTeamActivitiesByActivityTime()
     */
    public function searchTeamActivitiesByActivityTime($start, $end, $team = null, $page = 1, $size = 20)
    {
        return $this->findByConditions([
            'begin_time' => ['>=', $start],
            'end_time'   => ['<=', $end],
            'team'       => $team,
            'page'       => $page,
            'size'       => $size,
        ]);
    }

    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Repositories\ActivityRepository::searchEndActivityByTime()
     */
    public function searchEndActivityByTime($start, $end, $page = 1, $size = 20)
    {
        $query = Activity::addSelectGeometryColumn()
            ->with('city', 'team')
            ->where('status', ActivityEntity::STATUS_PUBLISHED)
            ->where('end_time', '>=', $start)
            ->where('end_time', '<=', $end)
            ->orderBy('end_time', 'asc')
            ->forPage($page, $size);
        $activities = $query->get()->all();
        $activities = array_map([$this, 'convertToEntity'], $activities);

        return $activities;
    }

    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Repositories\ActivityRepository::findPublishedActivitiesInTeam()
     */
    public function findPublishedActivitiesInTeam($team, $page = 1, $size = 20)
    {
        return $this->findByConditions([
            'team'      => $team,
            'published' => true,
            'page'      => $page,
            'size'      => $size,
        ]);
    }


    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Repositories\ActivityRepository::findActivitiesInTeam()
     */
    public function findActivitiesInTeam($team, $page = 1, $size = 20)
    {
        return $this->findByConditions([
            'team' => $team,
            'page' => $page,
            'size' => $size,
        ]);
    }

    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Repositories\ActivityRepository::findPublishedActivitiesInCity()
     */
    public function findPublishedActivitiesInCity($city, $page = 1, $size = 20)
    {
        return $this->findByConditions([
            'city'   => $city,
            'status' => ActivityEntity::STATUS_PUBLISHED,
            'page'   => $page,
            'size'   => $size,
        ], ['end_time' => 'desc']);
    }

    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Repositories\searchPublishedActivitiesByTitle::searchPublishedActivitiesByName()
     */
    public function searchPublishedActivitiesByTitle($keyword, $page = 1, $size = 20)
    {
        return $this->findByConditions([
            'keyword'   => $keyword,
            'published' => true,
            'page'      => $page,
            'size'      => $size,
        ]);
    }

    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Repositories\ActivityRepository::searchTeamActivitiesByTitle()
     */
    public function searchTeamActivitiesByTitle($keyword, $team, $page = 1, $size = 20)
    {
        return $this->findByConditions([
            'keyword' => $keyword,
            'team'    => $team,
            'page'    => $page,
            'size'    => $size,
        ]);
    }

    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Repositories\ActivityRepository::searchCityActivitiesByTitle()
     */
    public function searchCityActivitiesByTitle($keyword, $city, $page = 1, $size = 20)
    {
        return $this->findByConditions([
            'keyword'   => $keyword,
            'published' => true,
            'city'      => $city,
            'page'      => $page,
            'size'      => $size,
        ]);
    }

    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Repositories\ActivityRepository::findActivitiesHasAlbum()
     */
    public function findActivitiesHasAlbum($team, $page = 1, $size = 10)
    {
        return $this->findByConditions([
            'hasAlbum'  => ActivityEntity::HAS_ALBUM,
            'published' => true,
            'team'      => $team,
            'page'      => $page,
            'size'      => $size,
        ]);
    }

    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Repositories\ActivityRepository::findActivitiesByPoint()
     */
    public function findActivitiesByPoint($point, $distance = 5, $page = 1, $size = 10)
    {
        return $this->findByConditions([
            'point'     => $point,
            'distance'  => $distance,
            'published' => true,
            'page'      => $page,
            'size'      => $size,
        ]);
    }

    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Repositories\ActivityRepository::findActivityById()
     */
    public function findActivityById($id)
    {
        return $this->findByConditions(['id' => $id]);
    }

    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Repositories\ActivityRepository::findPublishedActivityById()
     */
    public function findPublishedActivityById($id)
    {
        return $this->findByConditions(['id' => $id, 'published' => true]);
    }

    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Repositories\ActivityRepository::findEndOfYesterdayActivitiesByIds()
     */
    public function findEndOfYesterdayActivitiesByIds($ids)
    {
        $query = Activity::addSelectGeometryColumn()
            ->with('city', 'team')
            ->whereIn('id', $ids)
            ->where('status', '!=', ActivityEntity::STATUS_DELETE)
            ->where('end_time', '<', date('Y-m-d 00:00:00'))
            ->orderBy('end_time', 'asc');
        $activities = $query->get()->all();
        $activities = array_map([$this, 'convertToEntity'], $activities);

        return $activities;
    }

    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Repositories\ActivityRepository::findNotEndActivitiesByIds()
     */
    public function findNotEndActivitiesByIds($ids)
    {
        $query = Activity::whereIn('id', $ids)->where('end_time', '<', date('Y-m-d H:i:s'));
        $query->orderBy('end_time', 'asc');
        $activities = $query->get()->all();
        $activities = array_map([$this, 'convertToEntity'], $activities);

        return $activities;
    }


    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Repositories\ActivityRepository::findActivitiesByIds()
     */
    public function findActivitiesByIds($ids, $orderBy = null, $excludeDelete = true)
    {
        $query = Activity::with('city', 'team')->whereIn('id', $ids);
        if ($excludeDelete) {
            $query->where('status', '!=', ActivityEntity::STATUS_DELETE);
        }
        if ($orderBy) {
            foreach ($orderBy as $value) {
                $query->orderBy($value[0], $value[1]);
            }
        } else {
            $query->orderBy('end_time', 'asc');
        }
        $activities = $query->get()->all();
        $activities = array_map([$this, 'convertToEntity'], $activities);

        return $activities;
    }

    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Repositories\ActivityRepository::searchActivityTitleByTagsAndStatus()
     */
    public function searchActivityTitleByTagsAndStatus($keyword, $tags = 0, $status = 0, $page = 1, $size = 20)
    {
        if ($status == 1) {
            $status = ActivityEntity::STATUS_PUBLISHED;
        } elseif ($status == 2) {
            $status = ActivityEntity::STATUS_DELETE;
        } else {
            $status = ['<>', ActivityEntity::STATUS_NOT_PUBLISHED];
        }
        $conditions = [
            'status' => $status,
            'page'   => $page,
            'size'   => $size,
        ];
        if ($tags == 1) {
            $conditions['tags'] = 'not null';
        } elseif ($tags == 2) {
            $conditions['tags'] = 'null';
        }
        if ($keyword != null) {
            $conditions['keyword'] = $keyword;
        }

        return $this->findByConditions($conditions);
    }

    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Repositories\ActivityRepository::findHomPageUserActivities()
     */
    public function findHomPageUserActivities($userId, $endDelay = 7)
    {
        $query = Activity::with('city', 'team');
        $query->join('activity_applicants', 'activity_applicants.activity_id', '=', 'activities.id')
            ->where('activity_applicants.user_id', '=', $userId)
            ->where('activities.status', '=', ActivityEntity::STATUS_PUBLISHED);
        $query->where(function ($query) {
            //WaitPay
            $query->orWhere(function ($query) {
                $this->QueryWaitPay($query);
            });
            //Auditing
            $query->orWhere(function ($query) {
                $this->queryAuditing($query);
            });
            //NotBeginning
            $query->orWhere(function ($query) {
                $this->QueryNotBeginning($query);
            });
            //Beginning
            $query->orWhere(function ($query) {
                $this->queryBeginning($query);
            });
            //End
            $query->orWhere(function ($query) {
                $delayDateTime = date('Y-m-d H:i:s', strtotime('-7 day'));
                $this->queryEndDelayShow($query, $delayDateTime);
            });
        });
        $query->orderBy('activities.begin_time', 'desc')
            ->select('activities.*', 'activity_applicants.status as applicants_status');
        $activities = $query->get()->all();
        $activities = array_map([$this, 'convertToEntity'], $activities);

        return $activities;
    }

    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Repositories\ActivityRepository::findUserActivities()
     */
    public function findUserActivities($userId, $type = 'All', $page = 1, $size = 20)
    {
        $query = Activity::with('city', 'team');
        $query->join('activity_applicants', 'activity_applicants.activity_id', '=', 'activities.id')
            ->where('activity_applicants.user_id', '=', $userId)
            ->where('activities.status', '=', ActivityEntity::STATUS_PUBLISHED);

        if ($type == 'All') {
            $query->where(function ($query) {
                //NotBeginning
                $query->orWhere(function ($query) {
                    $this->QueryNotBeginning($query);
                });
                //WaitPay
                $query->orWhere(function ($query) {
                    $this->QueryWaitPay($query);
                });
                //End
                $query->orWhere(function ($query) {
                    $this->queryEnd($query);
                });
                //Auditing
                $query->orWhere(function ($query) {
                    $this->queryAuditing($query);
                });
                //Beginning
                $query->orWhere(function ($query) {
                    $this->queryBeginning($query);
                });
            });
        } elseif ($type == 'NotBeginning') {
            $this->QueryNotBeginning($query);
        } elseif ($type == 'WaitPay') {
            $query->where(function ($query) {
                $this->QueryWaitPay($query);
            });
        } elseif ($type == 'End') {
            $this->queryEnd($query);
        } elseif ($type == 'Auditing') {
            $this->queryAuditing($query);
        } elseif ($type == 'Beginning') {
            $this->queryBeginning($query);
        } elseif ($type == 'Enrolling') {
            $query->where(function ($query) {
                // activity applicant auditing
                $query->orWhere(function ($query) {
                    $this->queryAuditing($query);
                });
                $this->QueryWaitPay($query);
            });
        }

        $query->orderBy('activities.begin_time', 'desc')->select('activities.*', 'activity_applicants.status as applicants_status');
        $count = $query->count();
        $activities = $query->forPage($page, $size)->get()->all();
        $activities = array_map([$this, 'convertToEntity'], $activities);

        return [$count, $activities];
    }

    private function QueryNotBeginning($query)
    {
        $query->where('activity_applicants.status', ActivityApplicant::STATUS_SUCCESS)
            ->where('activities.begin_time', '>', date('Y-m-d H:i:s'));
    }

    private function QueryWaitPay($query)
    {
        // auditing payment end time is activity begin_time
        $query->orWhere(function ($query) {
            $query->where('activities.auditing', ActivityEntity::AUDITING)
                ->where('activity_applicants.status', ActivityApplicant::STATUS_PAY)
                ->where('activities.begin_time', '>', date('Y-m-d H:i:s'));
        });
        // not auditing payment end time is activity expire_at
        $query->orWhere(function ($query) {
            $query->where('activities.auditing', ActivityEntity::NOT_AUDITING)
                ->where('activity_applicants.status', ActivityApplicant::STATUS_PAY)
                ->where('activity_applicants.expire_at', '>', date('Y-m-d H:i:s'));
        });
    }

    private function queryEnd($query)
    {
        $query->where('activity_applicants.status', ActivityApplicant::STATUS_SUCCESS)
            ->where('activities.end_time', '<', date('Y-m-d H:i:s'));
    }

    private function queryEndDelayShow($query, $delayDateTime)
    {
        $query->where('activity_applicants.status', ActivityApplicant::STATUS_SUCCESS)
            ->where('activities.end_time', '>', $delayDateTime);
    }

    private function queryAuditing($query)
    {
        $query->where('activity_applicants.status', ActivityApplicant::STATUS_AUDITING)
            ->where('activities.enroll_end_time', '>', date('Y-m-d H:i:s'));
    }

    private function queryBeginning($query)
    {
        $query->where('activity_applicants.status', ActivityApplicant::STATUS_SUCCESS)
            ->where('activities.begin_time', '<', date('Y-m-d H:i:s'))
            ->where('activities.end_time', '>', date('Y-m-d H:i:s'));
    }


    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Repositories\ActivityRepository::add()
     */
    public function add($activity)
    {
        if ($activity == null) {
            return 0;
        }
        $activity['status'] = ActivityEntity::STATUS_NOT_PUBLISHED;
        return Activity::create($activity)->id;
    }

    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Repositories\ActivityRepository::updateOnce()
     */
    public function updateOnce($id, $activity)
    {
        // Activity::updated(function() use($activity){
        //     echo 'update:';
        //     var_dump($activity);
        // });
        $activityDb = Activity::addSelectGeometryColumn()
            ->where('id', $id)
            ->first();
        if (null == $activityDb || empty($activity)) {
            return false;
        }
        if ($activity) {
            foreach ($activity as $field => $value) {
                $activityDb->$field = $value;
            }
        }
        return $activityDb->save();
    }

    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Repositories\ActivityRepository::updateMultiple()
     */
    public function updateMultiple($conditions, $activity)
    {
        if (isset($activity['location'])) {
            unset($activity['location']);
        }
        if (isset($activity['roadmap'])) {
            unset($activity['roadmap']);
        }
        $query = Activity::addSelectGeometryColumn();
        if ($conditions) {
            foreach ($conditions as $key => $value) {
                if (is_array($value) && count($value) == 2) {
                    $query->where($key, $value[0], $value[1]);
                } elseif (is_string($value)) {
                    $query->where($key, $value);
                } else {
                    return false;
                }
            }
        } else {
            return false;
        }
        return $query->update($activity) > 0 ? true : false;
    }

    /**
     * {@inheritdoc}
     * @return ActivityEntity | null
     */
    private function convertToEntity(Activity $activity)
    {
        if ($activity == null) {
            return null;
        }
        return $activity->toEntity();
    }

    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Repositories\ActivityRepository::updateStatusPublish()
     */
    public function updateStatusPublish($id, $qrCodeUrl)
    {
        return $this->updateOnce($id, [
            'status'       => ActivityEntity::STATUS_PUBLISHED,
            'qr_code_url'  => $qrCodeUrl,
            'publish_time' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Repositories\ActivityRepository::deleteActivityById()
     */
    public function deleteActivityById($id)
    {
        return $this->updateOnce($id, [
            'status'     => ActivityEntity::STATUS_DELETE,
            'deleted_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Repositories\ActivityRepository::restoreActivityById()
     */
    public function restoreActivityById($id)
    {
        return $this->updateOnce($id, [
            'status' => ActivityEntity::STATUS_PUBLISHED,
        ]);
    }


    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Repositories\ActivityRepository::updateHasAlbum()
     */
    public function updateHasAlbum($id)
    {
        return $this->updateOnce($id, ['has_album' => 1,]);
    }

    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Repositories\ActivityRepository::exists()
     */
    public function exists($id)
    {
        if(Activity::find($id) == null){
            return false;
        }
        return true;
    }

    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Repositories\ActivityRepository::find()
     */
    public function find($id)
    {
        $activity = Activity::addSelectGeometryColumn()
            ->with('city', 'team')
            ->where('id', $id)
            ->first();
        if (empty($activity)) {
            return null;
        }

        return $this->convertToEntity($activity);
    }

    /**
     * find activities by conditions
     *
     * @param  array $conditions              [id]        activity id
     *                                        [published] To filter the activities that are
     *                                        published
     *                                        [team]      team id
     *                                        [city]      city id
     *                                        [title]     activity name
     *                                        [hasAlbum]  activity have album
     *                                        [point]     user coordinate
     *                                        [distance]  search radius. unit km  default
     *                                        5km
     *                                        [page]      page number
     *                                        [size]      page size
     *
     * @return array [0]  total number of activities met the criteria
     *               [1]  array of activities
     */
    private function findByConditions(array $conditions = [], array $orderBy = [])
    {
        $id = array_get($conditions, 'id', null);
        $published = array_get($conditions, 'published', null);
        $team = array_get($conditions, 'team', null);
        $city = array_get($conditions, 'city', null);
        $keyword = array_get($conditions, 'keyword', null);
        $hasAlbum = array_get($conditions, 'hasAlbum', null);
        $point = array_get($conditions, 'point', null);
        $distance = array_get($conditions, 'distance', 5);
        $page = array_get($conditions, 'page', 1);
        $size = array_get($conditions, 'size', 15);
        $beginTime = array_get($conditions, 'begin_time', null);
        $endTime = array_get($conditions, 'end_time', null);
        $status = array_get($conditions, 'status', null);
        $tags = array_get($conditions, 'tags', 'tags');


        $query = Activity::addSelectGeometryColumn()->with('city', 'team');
        if (!is_null($id)) {
            $query->where('id', $id);
        }
        if (is_null($status)) {
            if ($published) {
                $query->where('status', ActivityEntity::STATUS_PUBLISHED);
                $orderBy = array_merge(['publish_time' => 'desc'], $orderBy);
            } else {
                $query->where('status', '!=', ActivityEntity::STATUS_DELETE);
                $orderBy = array_merge(['id' => 'desc'], $orderBy);
            }
        } else {
            if (is_array($status)) {
                $query->where('status', $status[0], $status[1]);
            } else {
                $query->where('status', $status);
            }
        }
        if (!is_null($team)) {
            $query->where('team_id', $team);
        }
        if (!is_null($city)) {
            $query->where('city_id', $city);
        }
        if (!is_null($hasAlbum)) {
            $query->where('has_album', $hasAlbum);
            $orderBy = array_merge(['end_time' => 'desc'], $orderBy);
        }
        if (!is_null($keyword)) {
            $query->where('title', 'like', '%' . SqlUtil::escape(trim($keyword)) . '%');
        }
        if (!is_null($point)) {
            $query->withInDistance($distance, $point, 'location');
        }
        if (!is_null($beginTime)) {
            $query->where('begin_time', $beginTime[0], $beginTime[1]);
        }
        if (!is_null($endTime)) {
            $query->where('end_time', $endTime[0], $endTime[1]);
        }
        if ($tags != 'tags') {
            if ($tags == 'null') {
                $query->whereNull('tags');
            } else {
                $query->whereNotNull('tags');
            }
        }
        if (!is_null($id)) {
            $activity = $query->first();
            if (null == $activity) {
                return null;
            }
            return $this->convertToEntity($activity);
        } else {
            $count = $query->count();
            if ($orderBy) {
                foreach ($orderBy as $field => $sort) {
                    $query->orderBy($field, $sort);
                }
            }
            $activities = $query->forPage($page, $size)->get()->all();
            $activities = array_map([$this, 'convertToEntity'], $activities);
            return [$count, $activities];
        }

    }

    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Repositories\ActivityRepository::activityAdvanceRemind()
     */
    public function activityAdvanceRemind($days)
    {
        $start = date('Y-m-d 00:00:00', strtotime('+' . $days . ' days'));
        $end = date('Y-m-d 00:00:00', strtotime('+' . ($days + 1) . ' days'));
        $activities = Activity::with('city', 'team')
            ->where('status', ActivityEntity::STATUS_PUBLISHED)
            ->where('begin_time', '>=', $start)
            ->where('begin_time', '<', $end)
            ->get()->all();
        $activities = array_map([$this, 'convertToEntity'], $activities);

        return $activities;
    }

    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Repositories\ActivityRepository::findParticipatedInTeamOfActivitiesUser()
     */
    public function  findParticipatedInTeamOfActivitiesUser($teamId)
    {
        $teamMembers = DB::table('team_members')
            ->leftJoin('users', 'users.id', '=', 'team_members.user_id')
            ->where('team_members.team_id', $teamId)
            ->where('team_members.status', TeamMember::STATUS_NORMAL)
            ->select('users.mobile')
            ->get();
        $teamMemberMobiles = array_map(function ($item) {
            return $item->mobile;
        }, $teamMembers);
        sort($teamMemberMobiles);

        $activityMembers = DB::table('activity_members')
            ->leftJoin('activities', 'activities.id', '=', 'activity_members.activity_id')
            ->where('activities.team_id', $teamId)
            ->where('activities.status', ActivityEntity::STATUS_PUBLISHED)
            ->select(DB::raw('distinct(activity_members.mobile)'))
            ->get();
        $activityMemberMobiles = array_map(function ($item) {
            return $item->mobile;
        }, $activityMembers);
        sort($activityMemberMobiles);

        return array_values(array_diff($activityMemberMobiles, $teamMemberMobiles));
    }


}
