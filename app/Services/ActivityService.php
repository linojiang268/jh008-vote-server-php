<?php
namespace Jihe\Services;

use Jihe\Contracts\Repositories\ActivityApplicantRepository;
use Jihe\Contracts\Repositories\ActivityFileRepository;
use Jihe\Contracts\Repositories\ActivityRepository;
use Jihe\Contracts\Services\Search\SearchService;
use Jihe\Dispatches\DispatchesActivityPublishRemindToExceptTeamMember;
use Jihe\Dispatches\DispatchesMessage;
use Jihe\Entities\Activity;
use Jihe\Services\StorageService;
use Jihe\Contracts\Repositories\ActivityAlbumRepository;
use Jihe\Entities\ActivityAlbumImage;
use Jihe\Entities\ActivityApplicant;
use Jihe\Entities\ActivityFile;
use Jihe\Entities\Message;
use Jihe\Entities\User;
use Jihe\Contracts\Repositories\ActivityMemberRepository;
use Jihe\Models\ActivityMember;
use Jihe\Services\Admin\UserService as AdminUserService;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Jihe\Dispatches\DispatchesSearchIndexRefresh;
use Jihe\Dispatches\DispatchesPushMessage;
use Jihe\Contracts\Services\Qrcode\QrcodeService;
use Jihe\Services\Excel\ExcelReader;
use Jihe\Utils\PushTemplate;
use Jihe\Entities\Team;
use Jihe\Contracts\Repositories\ActivityPlanRepository;

class ActivityService
{
    use DispatchesJobs, DispatchesSearchIndexRefresh, DispatchesPushMessage, DispatchesMessage;
    use DispatchesActivityPublishRemindToExceptTeamMember;

    const RECOMMEND_NUM = 5;

    const ACTIVITY_QR_CODE_SIZE = 500;

    const MY_ACTIVITIES_TYPE = ['All', 'NotBeginning', 'WaitPay', 'End', 'Auditing', 'Beginning', 'Enrolling'];
    /**
     * @var max album num user can requestd in a activity
     */
    // const MAX_ALLOWED_USER_ALBUM_REQUESTED = 100;

    /**
     * @var boolean   true if album is just visible when it's be approved,
     *                false album is visible at once when it's requestd
     */
    const ALBUM_REQUESTED_NEED_REQUIRED = false;

    const ATTR_MOBILE = '手机号';
    const ATTR_NAME = '姓名';

    /**
     * @var max file num user can upload in a activity
     */
    // const MAX_ALLOWED_FILE_UPLOADED = 15;

    /**
     * @var \Jihe\Contracts\Repositories\ActivityRepository
     */
    private $activities;

    /**
     * @var \Jihe\Services\TeamMemberService
     */
    private $teamMemberService;

    /**
     * @var \Jihe\Services\TeamService
     */
    private $teamService;

    /**
     * @var \Jihe\Services\StorageService
     */
    private $storageService;

    /**
     * @var \Jihe\Contracts\Repositories\ActivityAlbumRepository
     */
    private $albumRepository;

    /**
     * @var \Jihe\Contracts\Repositories\ActivityMemberRepository
     */
    private $activityMemberRepository;


    /**
     * @var \Jihe\Services\Admin\UserService
     */
    private $adminUserService;

    /**
     * @var \Jihe\Contracts\Services\Search\SearchService
     */
    private $searchService;

    /**
     * @var \Jihe\Contracts\Services\Qrcode\QrcodeService
     */
    private $qrcodeService;

    /**
     * @var \Jihe\Contracts\Repositories\ActivityPlanRepository
     */
    private $activityPlans;

    /**
     * @var \Jihe\Contracts\Repositories\ActivityFileRepository
     */
    private $fileRepository;

    /**
     * @var \Jihe\Services\UserService
     */
    private $userService;

    /**
     * @var \Jihe\Contracts\Repositories\ActivityApplicantRepository
     */
    private $activityApplicantRepository;

    public function __construct(ActivityRepository $activityRepository,
                                TeamMemberService $teamMemberService,
                                TeamService $teamService,
                                StorageService $storageService,
                                ActivityAlbumRepository $activityAlbumRepository,
                                ActivityMemberRepository $activityMemberRepository,
                                AdminUserService $adminUserService,
                                SearchService $searchService,
                                QrcodeService $qrcodeService,
                                ActivityFileRepository $activityFileRepository,
                                ActivityPlanRepository $activityPlanRepository,
                                UserService $userService,
                                ActivityApplicantRepository $activityApplicantRepository)
    {
        $this->activities = $activityRepository;
        $this->teamMemberService = $teamMemberService;
        $this->teamService = $teamService;
        $this->storageService = $storageService;
        $this->albumRepository = $activityAlbumRepository;
        $this->activityMemberRepository = $activityMemberRepository;
        $this->adminUserService = $adminUserService;
        $this->searchService = $searchService;
        $this->qrcodeService = $qrcodeService;
        $this->activityPlans = $activityPlanRepository;
        $this->fileRepository = $activityFileRepository;
        $this->userService = $userService;
        $this->activityApplicantRepository = $activityApplicantRepository;
    }

    /**
     * check whether the team is owned by given creator
     *
     * @param  int      $user Activities in the user ID
     * @param  int|null $team Activities in the team ID
     *
     * @return bool    If it is true, it can be operated, otherwise no permission Operation
     * @throws \Exception
     */
    private function canManipulate($user, $team = null)
    {
        $userEntity = $this->adminUserService->getUser($user);
        if ($userEntity) {
            if ($userEntity->isAdmin() || $userEntity->isOperator()) {
                return true;
            }
        }
        if (is_null($team)) {
            return false;
        }
        if (null === $team = $this->teamService->getTeam($team)) {
            throw new \Exception('非法操作：社团不存在！');
        }
        // only team's creator can manipulate his/her team
        return $team->getCreator()->getId() == $user;
    }

    private function ignoreStateManipulate($user)
    {
        $userEntity = $this->adminUserService->getUser($user);
        if ($userEntity) {
            if ($userEntity->isAdmin() || $userEntity->isOperator()) {
                return true;
            }
        }
        return false;
    }

    /**
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     *                      backstage
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     *
     * @param  int $teamId Activities in the team ID
     * @param  int $userId Activities in the team ID
     * @param  int $page   The current page number
     * @param  int $size   The number of data per page
     *
     * @return array
     * @throws \Exception
     */
    public function getActivityListByTeam($teamId, $userId, $page, $size)
    {
        if ($this->canManipulate($userId, $teamId)) {
            return $this->activities->findActivitiesInTeam($teamId, $page, $size);
        } else {
            throw new \Exception('非法操作：无操查看该社团活动的权限！');
        }
    }

    /**
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     *                      API
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     *
     * @param  int $teamId Activities in the team ID
     * @param  int $userId Activities in the team ID
     * @param  int $page   The current page number
     * @param  int $size   The number of data per page
     *
     * @return array
     * @throws \Exception
     */
    public function getMangeActivityListByTeam($teamId, $userId, $page, $size)
    {
        if ($this->canManipulate($userId, $teamId)) {
            return $this->activities->findPublishedActivitiesInTeam($teamId, $page, $size);
        } else {
            throw new \Exception('非法操作：无操查看该社团活动的权限！');
        }
    }

    /**
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     *                      API
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     *
     * @param  int $teamId Activities in the team ID
     * @param  int $page   The current page number
     * @param  int $size   The number of data per page
     *
     * @return array
     */
    public function getPublishedActivityListByTeam($teamId, $page, $size)
    {
        return $this->activities->findPublishedActivitiesInTeam($teamId, $page, $size);
    }

    /**
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     *                      API
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     *
     * @param  int $city city id
     * @param  int $page page number
     * @param  int $size page size
     *
     * @return array
     */
    public function getPublishedActivitiesInCity($city, $page, $size)
    {
        return $this->activities->findPublishedActivitiesInCity($city, $page, $size);
    }

    /**
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     *                      API
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     *
     * @param  string $keyword keyword in the activity name
     * @param  int    $city    city id
     * @param  int    $page    page number
     * @param  int    $size    page size
     *
     * @return array
     */
    public function searchActivities($keyword, $city, $page, $size)
    {
        return $this->activities->searchCityActivitiesByTitle($keyword, $city, $page, $size);
    }

    /**
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     *                      backstage
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     *
     * @param  string $keyword keyword in the activity name
     * @param  int    $userId  Activities in the user ID
     * @param  int    $teamId  Activities in the team ID
     * @param  int    $page    The current page number
     * @param  int    $size    The number of data per page
     *
     * @return array
     * @throws \Exception
     */
    public function getActivitiesByTeamAndName($keyword, $userId, $teamId, $page, $size)
    {
        if ($this->canManipulate($userId, $teamId)) {
            return $this->activities->searchTeamActivitiesByTitle($keyword, $teamId, $page, $size);
        } else {
            throw new \Exception('非法操作：无搜索该社团活动的权限！');
        }
    }

    /**
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     *                      API
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     *
     * @param  int $id Activities  ID
     *
     * @return \Jihe\Entities\Activity
     */
    public function getPublishedActivityById($id)
    {
        return $this->activities->findPublishedActivityById($id);
    }

    /**
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     *                      API
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     *
     * @param  array $ids Activities  ID
     *
     * @return array
     */
    public function getEndOfYesterdayActivitiesByIds($ids)
    {
        return $this->activities->findEndOfYesterdayActivitiesByIds($ids);
    }

    /**
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     *                      backstage
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     *
     * @param  int      $id     Activities  ID
     * @param  int|null $userId login user  ID
     *
     * @return \Jihe\Entities\Activity
     * @throws \Exception
     */
    public function getActivityById($id, $userId = null)
    {
        $activityDB = $this->activities->findActivityById($id);
        if ($activityDB && ($userId == null || $this->canManipulate($userId, $activityDB->getTeam()->getId()))) {
            return $activityDB;
        } else {
            throw new \Exception('非法操作：无操作权限');
        }
    }


    /**
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     *                      API
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     *
     * @param  array $point               Latitude and longitude coordinates
     * @param  int   $dist                Distance from the active site to
     *                                    the current user
     * @param  int   $page                The current page number
     * @param  int   $size                The number of data per page
     *
     * @return array
     */
    public function getNearbyActivities($point, $dist = 5, $page = 1, $size = 10)
    {
        return $this->activities->findActivitiesByPoint($point, $dist, $page, $size);
    }

    /**
     * search team activity by activity time
     *
     * @param  int    $userId Activities in the user ID
     * @param  string $start  activity begin time
     * @param  string $end    activity end time
     * @param  int    $team   team id
     * @param  int    $page   The current page number
     * @param  int    $size   The number of data per page
     *
     * @return array
     * @throws \Exception
     */
    public function searchTeamActivitiesByActivityTime($userId, $start, $end, $team, $page = 1, $size = 15)
    {
        if ($this->canManipulate($userId, $team)) {
            return $this->activities->searchTeamActivitiesByActivityTime($start, $end, $team, $page, $size);
        } else {
            throw new \Exception('非法操作：无操作权限');
        }
    }

    /**
     * search team or all activity by activity time
     *
     * @param  string $start activity begin time
     * @param  string $end   activity end time
     * @param  int    $page  The current page number
     * @param  int    $size  The number of data per page
     *
     * @return array
     */
    public function searchActivitiesByActivityTime($start, $end, $page = 1, $size = 15)
    {
        return $this->activities->searchTeamActivitiesByActivityTime($start, $end, null, $page, $size);
    }


    /**
     * create Activity
     *
     * @param  array $activity
     *
     * @return int
     */
    public function createActivity($activity)
    {
        $activity = $this->processImageFields($activity);
        return $this->activities->add($activity);
    }

    /**
     * update activity by id
     *
     * @param int   $id       activity id
     * @param int   $userId   user id
     * @param array $activity update fields and value
     *
     * @return bool
     * @throws \Exception
     */
    public function updateActivity($id, $userId, $activity)
    {
        $activityDB = $this->getActivityById($id, $userId);
        if ($activityDB) {
            if (time() < strtotime($activityDB->getBeginTime()) || $this->ignoreStateManipulate($userId)) {
                if (isset($activity['images_url']) && !empty($activity['images_url']) && !empty($activityDB->getImagesUrl())) {
                    $this->removeImages($activityDB->getImagesUrl(), json_decode($activity['images_url'], true));
                }
                /* Processing images URL*/
                $activity = $this->processImageFields($activity);

                return $this->activities->updateOnce($id, $activity);
            } else {
                throw new \Exception('非法操作：活动不可修改！');
            }
        } else {
            throw new \Exception('非法操作：非登录用户创建！');
        }
    }

    /**
     * update activity by id
     *
     * @param int   $id       activity id
     * @param int   $userId   user id
     * @param array $activity update organizers and value
     *
     * @return bool
     * @throws \Exception
     */
    public function updateActivityOrganizers($id, $userId, $activity)
    {
        $activityDB = $this->getActivityById($id, $userId);
        if ($activityDB) {
            if (Activity::STATUS_PUBLISHED == $activityDB->getStatus()) {
                return $this->activities->updateOnce($id, $activity);
            } else {
                throw new \Exception('非法操作：活动信息不可修改！');
            }
        } else {
            throw new \Exception('非法操作：非登录用户创建！');
        }
    }

    /**
     * Processing images URL
     *
     * @param  array $activity activity data array
     *
     * @return array
     */
    private function processImageFields($activity)
    {
        if (isset($activity['cover_url'])) {
            $activity['cover_url'] = $this->copyUploadImage($activity['cover_url']);
        }
        if (isset($activity['images_url'])) {
            $activity['images_url'] = json_encode($this->copyUploadImage(json_decode($activity['images_url'], true)));
        }
        return $activity;
    }

    /**
     * if user delete image ,we will delete oss image
     *
     * @param  array $oldImages image url array in the db
     * @param  array $nowImages image url array by post data
     *
     * @return array
     */
    private function removeImages($oldImages, $nowImages)
    {
        $diff = array_diff($oldImages, $nowImages);
        if ($diff) {
            foreach ($diff as $key => $val) {
                $this->storageService->remove($val);
            }
        }
    }

    /**
     * if have 'tmp/' copy
     *
     * @param  array $images image url array
     *
     * @return array
     */
    private function copyUploadImage($images)
    {
        if ($images) {
            $string = false;
            if (!is_array($images)) {
                $images = [$images];
                $string = true;
            }
            foreach ($images as $key => $val) {
                if ($this->storageService->isTmp($val)) {
                    $images[$key] = $this->storageService->storeAsImage($val);
                }
            }
            if ($string) {
                $images = $images[0];
            }
        }
        return $images;
    }

    /**
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     *                      backstage
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * Activity check
     *
     * @param  int $id activity id
     *
     * @return bool
     */
    public function checkActivityExists($id)
    {
        return $this->activities->exists($id);
    }

    /**
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     *                      backstage
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * Activity delete
     *
     * @param  int $id     activity id
     * @param  int $userId user id
     *
     * @return bool
     */
    public function activityDelete($id, $userId)
    {
        $ret = $this->checkActivityOwner($id, $userId);
        if ($ret) {
            $result = $this->activities->deleteActivityById($id);
            if ($result) {
                $this->dispatchActivitySearchIndexRefresh($id);
            }
            return $result;
        }
        return false;
    }

    /**
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     *                      backstage
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * Activity restore
     *
     * @param  int $id     activity id
     * @param  int $userId user id
     *
     * @return bool
     */
    public function activityRestore($id, $userId)
    {
        $activityDB = $this->activities->find($id);
        if (!empty($activityDB) && ($userId == null || $this->canManipulate($userId, $activityDB->getTeam()->getId()))) {
            $result = $this->activities->restoreActivityById($id);
            if ($result) {
                $this->dispatchActivitySearchIndexRefresh($id);
            }
            return $result;
        } else {
            throw new \Exception('非法操作：无查看该社团活动的权限！');
        }
    }

    /**
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     *                      backstage
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * Activity publish
     *
     * @param  int $id activity id
     *
     * @return bool
     */
    public function publishing($id, $userId)
    {
        $ret = $this->checkActivityOwner($id, $userId);
        $activity = $this->checkActivityData($id, $userId);
        if ($ret) {
            $qrcode = $this->generateActivityQrcode($id);
            $result = $this->activities->updateStatusPublish($id, $qrcode);
            if ($result) {
                // notify team
                $this->teamService->notify($activity->getTeam()->getId(), ['activities']);

                $this->dispatchActivitySearchIndexRefresh($id);
                $this->pushActivityPublishMessageInTeam($activity);
                $this->pushActivityPublishToTeamCreator($activity);
                if ($activity->getEnrollType() == Activity::ENROLL_ALL) {
                    $this->dispatchActivityPublishRemindToExceptTeamMember($activity->getTeam()->getId(), $activity);
                }
            }
            return $result;
        }
        return false;
    }

    private function pushActivityPublishMessageInTeam(Activity $activity)
    {
        $this->sendToTeamMembers($activity->getTeam(), null, [
            'title'      => $activity->getTeam()->getName(),
            'content'    => PushTemplate::generalMessage(PushTemplate::ACTIVITY_PUBLISHED_BY_ENROLLED_TEAM, $activity->getTitle()),
            'type'       => Message::TYPE_ACTIVITY,
            'attributes' => ['activity_id' => $activity->getId()],
        ], [
            'push' => true,
        ]);
    }

    private function pushActivityPublishToTeamCreator(Activity $activity)
    {
        $this->sendToUsers([$activity->getTelephone()], [
            'title'      => $activity->getTeam()->getName(),
            'content'    => PushTemplate::generalMessage(PushTemplate::ACTIVITY_PUBLISHED_BY_SELF_TEAM,
                $activity->getTitle()),
            'type'       => Message::TYPE_ACTIVITY,
            'attributes' => ['activity_id' => $activity->getId()],
        ], [
            'push' => true,
        ]);
    }


    /**
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     *                      API
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     *
     * @param  int $teamId   team id
     *
     * @param  int $page     The current page number
     * @param  int $pageSize The number of data per page
     *
     * @return array
     */
    public function getHasAlbumActivities($teamId, $page = 1, $pageSize = 10)
    {
        return $this->activities->findActivitiesHasAlbum($teamId, $page, $pageSize);
    }

    public function checkActivityOwner($id, $userId)
    {
        $activity = $this->findAllStatusActivitiesByIds($id, $userId);
        if (empty($activity)) {
            throw new \Exception('非法操作：活动不存在或无操作权限！');
        }
        return true;
    }

    private function checkActivityData($id, $userId)
    {
        $activity = $this->getActivityById($id, $userId);
        if (empty($activity)) {
            throw new \Exception('非法操作：活动不存在！');
        }

        if (empty($activity->getCity())) {
            throw new \Exception('非法操作：所在城市数据不完整！');
        }

        if (empty($activity->getTeam())) {
            throw new \Exception('非法操作：所在社团数据不完整！');
        }

        if (empty($activity->getContact())) {
            throw new \Exception('非法操作：联系人数据不完整！');
        }
//
//        if (empty($activity->getTelephone())) {
//            throw new \Exception('非法操作：联系电话数据不完整！');
//        }

        if (empty($activity->getBeginTime())) {
            throw new \Exception('非法操作：活动开始时间数据不完整！');
        }

        if (empty($activity->getEndTime())) {
            throw new \Exception('非法操作：活动结束时间数据不完整！');
        }

        if (empty($activity->getCoverUrl())) {
            throw new \Exception('非法操作：活动封面数据不完整！');
        }

        if (empty($activity->getAddress())) {
            throw new \Exception('非法操作：活动地址数据不完整！');
        }

        if (empty($activity->getBriefAddress())) {
            throw new \Exception('非法操作：活动简写地址数据不完整！');
        }

        if (empty($activity->getTitle())) {
            throw new \Exception('非法操作：活动标题数据不完整！');
        }

        if (empty($activity->getLocation())) {
            throw new \Exception('非法操作：活动坐标数据不完整！');
        }

        if (empty($activity->getDetail())) {
            throw new \Exception('非法操作：活动内容数据不完整！');
        }

        if (empty($activity->getEnrollBeginTime())) {
            throw new \Exception('非法操作：活动报名开始时间数据不完整！');
        }

        if (empty($activity->getEnrollEndTime())) {
            throw new \Exception('非法操作：活动报名结束时间数据不完整！');
        }

        if (empty($activity->getEnrollType())) {
            throw new \Exception('非法操作：活动报名人员类型数据不完整！');
        }

        if (is_null($activity->getEnrollLimit())) {
            throw new \Exception('非法操作：活动报名人数数据不完整！');
        }

        if (empty($activity->getEnrollFeeType())) {
            throw new \Exception('非法操作：活动报名收费类型数据不完整！');
        }

        if (is_null($activity->getEnrollFee())) {
            throw new \Exception('非法操作：活动报名费数据不完整！');
        }

        if (empty($activity->getEnrollAttrs())) {
            throw new \Exception('非法操作：活动报名资料数据不完整！');
        }

        if (is_null($activity->getAuditing())) {
            throw new \Exception('非法操作：活动是否审核数据不完整！');
        }

        return $activity;
    }

    /**
     * Marked activity has a photo album
     *
     * @param  \Jihe\Entities\Activity $activity
     *
     * @return bool
     */
    public function activityHasAlbum(Activity $activity)
    {
        if ($activity && Activity::HAS_NO_ALBUM == $activity->getHasAlbum()) {
            $result = $this->activities->updateOnce($activity->getId(), ['has_album' => 1]);
            if ($result) {
                $this->dispatchActivitySearchIndexRefresh($activity->getId());
            }
            return $result;
        }
    }

    /**
     * add album image of activity
     *
     * @param array $image                     detail of album image, keys taken:
     *                                         - activity      (Activity) entity of activity
     *                                         - creator_type  (int) role of initator
     *                                         - creator       (int) id of creator
     *                                         - image_id      (int) url of image
     *
     * @return \Jihe\Entities\ActivityAlbumImage
     */
    public function addAlbumImage(array $image)
    {
        $image = $this->morphAlbumImage($image);

        $this->ensureRequestAcceptable($image);

        // not need to check if request by sponsor
        if ($image->isSponsor()) {
            $image->setStatus(ActivityAlbumImage::STATUS_APPROVED);

            $this->notifyOnAlbumUpdated($image->getActivity());
        } else {
            $image->setStatus(
                self::ALBUM_REQUESTED_NEED_REQUIRED ? ActivityAlbumImage::STATUS_PENDING :
                    ActivityAlbumImage::STATUS_APPROVED);
        }

        return $this->albumRepository->addImage($image);
    }

    public function notifyOnAlbumUpdated(Activity $activity)
    {
        $this->teamService->notify($activity->getTeam()->getId(), ['albums']);

        if (Activity::HAS_NO_ALBUM == $activity->getHasAlbum()) {
            $this->activityHasAlbum($activity);

            // not need to notice
//            $this->sendToActivityMembers($activity, null, [
//                'content' => PushTemplate::generalMessage(PushTemplate::ACTIVITY_ALBUM_UPDATED, $activity->getTitle()),
//            ], [
//                'push' => true,
//            ]);
        }
    }

    /**
     * ensure initator could add album image,
     *   rule:
     *    - initator should be sponsor of the activity if creatorType is sponsor
     *    - initator should be member of the activity if creatorType is user
     *
     * @param ActivityAlbumImage $image
     *
     * @throws \Exception
     */
    private function ensureRequestAcceptable(ActivityAlbumImage $image)
    {
        if ($image->isSponsor()) {
            if (!$this->isCreator($image->getActivity(), $image->getCreator()->getId())) {
                throw new \Exception('非活动发布者');
            }
        } else {
            if (!$this->enrolled($image->getActivity(), $image->getCreator()->getId())) {
                throw new \Exception('非活动成员');
            }
        }
    }

    /**
     *
     * @param \Jihe\Entities\Activity $activity
     * @param int                     $user
     *
     * @return boolean
     */
    private function isCreator(Activity $activity, $user)
    {
        $teams = $this->teamService->getTeamsByCreator($user);
        if (empty($teams)) {
            return false;
        }

        if ($teams[0]->getId()
            != $activity->getTeam()->getId()
        ) {
            return false;
        }
        return true;
    }

    /**
     *
     * @param \Jihe\Entities\Activity $activity
     * @param int                     $user
     *
     * @return boolean
     */
    private function enrolled(Activity $activity, $user)
    {
        return $this->activityMemberRepository->exists($activity->getId(), $user);
    }

    private function morphAlbumImage(array $image)
    {
        return (new ActivityAlbumImage())
            ->setActivity(array_get($image, 'activity'))
            ->setCreatorType(array_get($image, 'creator_type'))
            ->setCreator((new User())->setId(array_get($image, 'creator')))
            ->setImageUrl(array_get($image, 'image_id'));
    }

    /**
     * get pending album images of given activity that user uploaded
     *
     * @param Activity $activity      entity of activity
     * @param int      $page          index of page
     * @param int      $size          size of page
     * @param string   $lastCreatedAt last created time of album
     * @param int      $lastId        last id of album
     */
    public function getPendingAlbumImages($activity, $page, $size, $lastCreatedAt = null, $lastId = null)
    {
        return $this->getAlbumImages(
            $activity->getId(),
            $page,
            $size,
            ActivityAlbumImage::USER,
            null,
            ActivityAlbumImage::STATUS_PENDING,
            $lastCreatedAt,
            $lastId);
    }

    /**
     * get approved album images of given activity that user uploaded
     *
     * @param \Jihe\Entities\Activity $activity
     * @param int                     $page          index of page
     * @param int                     $size          size of page
     * @param string                  $lastCreatedAt last created time of image
     * @param int                     $lastId        last id of image
     */
    public function getApprovedAlbumImages($activity, $page, $size, $lastCreatedAt = null, $lastId = null)
    {
        return $this->getAlbumImages(
            $activity->getId(),
            $page,
            $size,
            ActivityAlbumImage::USER,
            null,
            ActivityAlbumImage::STATUS_APPROVED,
            $lastCreatedAt,
            $lastId);
    }

    /**
     * get album images of given activity that sponsor uploaded
     *
     * @param \Jihe\Entities\Activity $activity
     * @param int                     $page          index of page
     * @param int                     $size          size of page
     * @param string                  $lastCreatedAt last created time of image
     * @param int                     $lastId        last id of image
     */
    public function getAlbumImagesOfSponsor($activity, $page, $size, $lastCreatedAt = null, $lastId = null)
    {
        return $this->getAlbumImages(
            $activity->getId(),
            $page,
            $size,
            ActivityAlbumImage::SPONSOR,
            null,
            null,
            $lastCreatedAt,
            $lastId);
    }

    /**
     * get album images of given activity(and given creator) that user uploaded
     *
     * @param \Jihe\Entities\Activity $activity
     * @param int                     $page          index of page
     * @param int                     $size          size of page
     * @param int                     $creator       id of creator
     * @param string                  $lastCreatedAt last created time of image
     * @param int                     $lastId        last id of image
     */
    public function getUserAlbumImages($activity, $page, $size, $creator = null)
    {
        return $this->getAlbumImages(
            $activity->getId(),
            $page,
            $size,
            ActivityAlbumImage::USER,
            $creator,
            null,
            null,
            null);
    }

    private function getAlbumImages($activity, $page, $size, $creatorType = null, $creator = null, $status = null, $lastCreatedAt = null, $lastId = null)
    {
        return $this->albumRepository
            ->findImages(
                $activity, $page, $size,
                $creatorType,
                $creator,
                $status,
                $lastCreatedAt,
                $lastId);
    }

    /**
     * approve album images of activity
     *
     * @param \Jihe\Entities\Activity $activity id of activity
     * @param array                   $images   ids of images
     *
     * @return boolean
     */
    public function approveAlbumImages($activity, array $images)
    {
        $result = $this->albumRepository->updateImageStatusToApproved($activity->getId(), $images);
        if ($result) {
            $this->notifyOnAlbumUpdated($activity);
        }

        return $result;
    }

    /**
     * remove album images of activity
     *
     * @param \Jihe\Entities\Activity $activity
     * @param array                   $images                 ids of images
     * @param                         $creator                id of creator
     *
     * @return boolean
     */
    public function removeAlbumImages($activity, array $images, $creator = null)
    {
        return $this->albumRepository->removeImages($activity->getId(), $images, $creator);
    }

    /**
     * stat activity's pending album images
     *
     * @param array $teams team ids
     *
     * @return array
     */
    public function statPendingAlbumImages(array $activities = null)
    {
        return $this->albumRepository->statPendingImages($activities);
    }

    /**
     * count album images of given team
     *
     * @return int
     */
    public function countApprovedAlbumImagesOfTeam(Team $team)
    {
        return $this->albumRepository->countAlbumImages([
            'team'   => $team->getId(),
            'status' => ActivityAlbumImage::STATUS_APPROVED,
        ]);
    }

    /**
     * count album images of given activity
     *
     * @return int
     */
    public function countApprovedAlbumImagesOfActivity(Activity $activity)
    {
        return $this->albumRepository->countAlbumImages([
            'activity' => $activity->getId(),
            'status'   => ActivityAlbumImage::STATUS_APPROVED,
        ]);
    }

    /**
     * Count activity in team
     *
     * @param int $team
     *
     * @return int
     */
    public function getTeamActivitiesCount($team)
    {
        return $this->activities->getTeamActivitiesCount($team);
    }

    /**
     * @param string $keyword search activities title
     * @param bool   $tags    search no tags or all activities
     * @param bool   $status  search delete or all activities
     *
     * @return array
     */
    public function searchActivityTitleByTagsAndStatus($keyword, $tags, $status, $page = 1, $size = 20)
    {
        return $this->activities->searchActivityTitleByTagsAndStatus($keyword, $tags, $status, $page, $size);
    }

    /**
     * @param   int    $id
     * @param null|int $userId
     *
     * @return Activity
     * @throws \Exception
     */
    public function findAllStatusActivitiesByIds($id, $userId = null)
    {
        $activityDB = $this->activities->findActivitiesByIds([$id], null, false);
        $activityDB = isset($activityDB[0]) ? $activityDB[0] : null;
        if ($activityDB && ($userId == null || $this->canManipulate($userId, $activityDB->getTeam()->getId()))) {
            return $activityDB;
        } else {
            throw new \Exception('非法操作：无操作权限');
        }
    }


    /**
     * @param $ids
     *
     * @return mixed
     */
    public function findActivitiesByIds($ids)
    {
        if (empty($ids)) {
            return [];
        }
        return $this->activities->findActivitiesByIds($ids);
    }

    /**
     * @param $ids
     *
     * @return mixed
     */
    public function findNotEndActivitiesByIds($ids)
    {
        return $this->activities->findActivitiesByIds($ids);
    }

    public function findUserActivities($userId, $type, $page, $size)
    {
        if (!in_array($type, self::MY_ACTIVITIES_TYPE)) {
            throw new \Exception('非法的请求类型！');
        }
        return $this->activities->findUserActivities($userId, $type, $page, $size);
    }

    public function findHomePageUserActivities($userId)
    {
        return $this->activities->findHomPageUserActivities($userId, Activity::SHOW_END_ACTIVITY_DELAY);
    }

    public function getTeamsActivitiesCount($teamIds, $includeEnd = false)
    {
        return $this->activities->getTeamsActivitiesCount($teamIds, $includeEnd);
    }

    /**
     * search activities by tags
     *
     * @param int   $cityId  login user in the city
     * @param array $tagsags login user tags
     *
     * @return array
     */
    public function searchMyRecommendActivities($cityId, $tags)
    {
        return $this->searchService->getRecommendActivity($cityId, $tags, self::RECOMMEND_NUM);
    }

    private function generateActivityQrcode($activityId)
    {
        $qrcode = $this->qrcodeService->generate(
            sprintf(url('/wap/activity/detail?activity=%d'), $activityId),
            [
                'size' => self::ACTIVITY_QR_CODE_SIZE,
            ]);
        return $this->uploadQrcode($qrcode);
    }

    private function uploadQrcode($qrcode)
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'team_' . uniqid() . time() . '.png';
        file_put_contents($path, $qrcode);
        $ret = $this->storageService->storeAsImage($path);
        @unlink($path);
        return $ret;
    }

    /**
     * @param \Jihe\Entities\Activity $activity
     * @param string                  $content
     * @param bool                    $toAll
     * @param array                   $phones
     * @param string                  $sendWay
     *
     * @throws \Exception
     */
    public function pushActivityNotice($activity, $content, $toAll, $phones, $sendWay)
    {
        if (!$toAll) {
            if (empty($phones)) {
                throw new \Exception('成员未指定');
            }
        }
        $message = ['title' => $activity->getTeam()->getName(), 'content' => $content];
        $option = [];
        $option['record'] = true;
        if ('sms' == $sendWay) {
            $option['sms'] = true;
        } else {
            $option['push'] = true;
        }
        $this->sendToActivityMembers($activity, $phones, $message, $option);
    }

    /**
     * add file of activity
     *
     * @param Activity $activity                    entity of activity file
     * @param array    $file                        detail of file, keys taken:
     *                                              - name        (string)
     *                                              - memo        (string)
     *                                              - size        (int)
     *                                              - extension   (string)
     *                                              - file_id     (string)url of file
     *
     *
     * @return \Jihe\Entities\ActivityFile
     */
    public function addFile(Activity $activity, array $file)
    {
        return $this->fileRepository->add($this->morphFile($activity, $file));
    }

    private function morphFile(Activity $activity, array $file)
    {
        return (new ActivityFile())
            ->setActivity($activity)
            ->setName(array_get($file, 'name'))
            ->setMemo(array_get($file, 'memo'))
            ->setSize(array_get($file, 'size'))
            ->setExtension(array_get($file, 'extension'))
            ->setUrl(array_get($file, 'file_id'));
    }

    /**
     * list files of given activity
     *
     * @param Activity $activity entity of activity
     * @param int      $page     index of page
     * @param int      $size     size of page
     */
    public function listFiles(Activity $activity, $page, $size)
    {
        return $this->fileRepository->find($activity->getId(), $page, $size);
    }

    /**
     * remove files of activity
     *
     * @param \Jihe\Entities\Activity $activity id of activity
     * @param array                   $files    ids of files
     *
     * @return boolean
     */
    public function removeFiles($activity, array $files)
    {
        return $this->fileRepository->remove($activity->getId(), $files);
    }

    /**
     * count files of given activity
     *
     * @return int
     */
    public function countFilesOfActivity(Activity $activity)
    {
        return $this->fileRepository->count($activity->getId());
    }


    /**
     * find activity plans by activity id
     *
     * @param int $activityId
     * @param int $teamId
     * @param int $userId
     *
     * @return array
     * @throws \Exception
     */
    public function findActivityPlanByActivityId($activityId)
    {
        return $this->activityPlans->findActivityPlanByActivityId($activityId);
    }

    /**
     * create $activity plan
     *
     * @param array $activityPlans
     * @param int   $activityId
     * @param int   $userId
     * @param int   $teamId
     *
     * @return bool
     * @throws \Exception
     */
    public function createActivityPlan($activityPlans, $activityId, $userId, $teamId)
    {
        if ($this->canManipulate($userId, $teamId)) {
            $this->activityPlans->deleteActivityPlanByActivityId($activityId);
            foreach ($activityPlans as $activityPlan) {
                $ret = $this->activityPlans->add($activityPlan);
                if (!$ret) {
                    throw new \Exception('部分数据添加失败，请重新操作！');
                }
            }
        } else {
            throw new \Exception('非法操作：无操作该社团活动的权限！');
        }
    }

    /**
     * import activity members
     *
     * @param string           $excel
     * @param Activity         $activity
     * @param array            $options
     * @param ExcelReader|null $reader
     *
     * @return array
     */
    public function importActivityMembers($excel, Activity $activity, array $options = [], ExcelReader $reader = null)
    {
        $failedRows = [];  // rows failed to import
        $reader = $reader ?: new ExcelReader();
        // merge options with default value
        $options = array_merge([
            'from_row'      => 2,       // skip the first row, which is a header
            'on_error_stop' => false,
            'batch_size'    => 50,
        ], $options);
        $stopOnError = array_get($options, 'on_error_stop');
        $reader->read($excel, function ($data, $row) use (&$failedRows, $stopOnError, $activity) {
            list($mobiles, $names, $joinData, $mobileData, $errors) = $this->makeExcelData($data, $activity, $row);
            $failedRows = array_merge($failedRows, $errors);
            list($usersData, $addMobilesFailed) = $this->checkAndAddUser($mobiles);
            if (!empty($addMobilesFailed)) {
                foreach ($addMobilesFailed as $addMobileFailed) {
                    if (isset($joinData[$addMobileFailed])) {
                        $mobileData[$addMobileFailed]['message'] = '服务器错误，请稍后重试';
                        $failedRows[] = $mobileData[$addMobileFailed];
                        unset($joinData[$addMobileFailed]);
                        $key = array_search($addMobileFailed, $mobiles);
                        unset($mobiles[$key]);
                    }
                }
            }
            if (!empty($joinData)) {
                $ret = $this->checkAndAddActivityApplicant($activity, $mobiles, $usersData, $names, $joinData);
                if ($ret) {
                    $this->checkAndAddActivityMembers($activity, $mobiles, $usersData, $names, $joinData);
                }
            }
        }, $options);

        return $failedRows;
    }

    /**
     * insert activity members
     *
     * @param  Activity $activity
     * @param  array    $mobiles
     * @param  array    $usersData
     * @param  array    $names
     * @param  array    $joinData
     */
    private function checkAndAddActivityMembers(Activity $activity, $mobiles, $usersData, $names, $joinData)
    {
        $activityMembers = [];
        $result = $this->activityMemberRepository->getActivityMembersByMobiles($activity->getId(), $mobiles);
        if (!empty($result)) {
            foreach ($result as $mobile => $id) {
                if (is_null($id)) {
                    $members = [
                        'activity_id' => $activity->getId(),
                        'user_id'     => $usersData[$mobile],
                        'name'        => $names[$mobile],
                        'mobile'      => $mobile,
                        'attrs'       => $joinData[$mobile],
                        'group_id'    => ActivityMember::UNGROUPED,
                        'role'        => ActivityMember::ROLE_NORMAL,
                    ];
                    $activityMembers[] = $members;
                }
            }
            $ret = $this->activityMemberRepository->batchAdd($activityMembers);
            return $ret;
        }
        return false;
    }

    /**
     * insert activity applicants
     *
     * @param  Activity $activity
     * @param  array    $mobiles
     * @param  array    $usersData
     * @param  array    $names
     * @param  array    $joinData
     *
     * @return bool|mixed
     */
    private function checkAndAddActivityApplicant(Activity $activity, $mobiles, $usersData, $names, $joinData)
    {
        $applicants = [];
        $result = $this->activityApplicantRepository->getActivityApplicantsByMobiles($activity->getId(), $mobiles);
        if (!empty($result)) {
            foreach ($result as $mobile => $id) {
                if (is_null($id)) {
                    $applicant = [];
                    $applicant['name'] = $names[$mobile];
                    $applicant['mobile'] = $mobile;
                    $applicant['attrs'] = $joinData[$mobile];
                    $applicant['activity_id'] = $activity->getId();
                    $applicant['user_id'] = $usersData[$mobile];
                    $applicant['channel'] = ActivityApplicant::CHANNEL_BATCH_VIP;
                    $applicant['status'] = ActivityApplicant::STATUS_SUCCESS;
                    $applicants[] = $applicant;
                }
            }
            $ret = $this->activityApplicantRepository->multipleAddApplicantInfo($applicants);
            return $ret;
        }
        return false;
    }

    /**
     * add users
     *
     * @param $mobiles
     *
     * @return array
     */
    private function checkAndAddUser($mobiles)
    {
        $addMobiles = [];
        $usersData = $this->userService->fetchUsersForMessagePush($mobiles);
        if ($usersData) {
            foreach ($usersData as $mobile => $userId) {
                if (is_null($userId)) {
                    $addMobiles[] = $mobile;
                }
            }
            $ret = $this->userService->multipleRegisterWithoutProfile($addMobiles);
            if (!$ret) {
                return [$usersData, $addMobiles];
            } else {
                $usersData = $this->userService->fetchUsersForMessagePush($mobiles);
                return [$usersData, []];
            }
        }

        return [$usersData, []];
    }

    /**
     * init import data
     *
     * @param array    $data
     * @param Activity $activity
     * @param int      $row
     *
     * @return array
     */
    private function makeExcelData($data, Activity $activity, $row)
    {
        $titles = $activity->getEnrollAttrs();
        $ids = $names = $joinData = $errors = $mobileData = [];
        if (!empty($data)) {
            foreach ($data as $key => $val) {
                $row++;
                $mobile = trim(strval($val[0]));
                $val[0] = $mobile = trim($mobile, "'");
                $val[1] = $name = trim(strval($val[1]));
                $mobileData[$mobile] = ['data' => $val, 'row'  => $row+1];
                if (!preg_match('/^1[34578]\d{9}$/', $mobile)) {
                    $errors[] = [
                        'data'    => $val,
                        'message' => ActivityApplicantService::ATTR_MOBILE . '格式错误',
                        'row'     => $row+1,
                    ];
                    continue;
                }
                if(empty($name)){
                    $errors[] = [
                        'data'    => $val,
                        'message' => ActivityApplicantService::ATTR_NAME . '格式错误',
                        'row'     => $row+1,
                    ];
                    continue;
                }
                $ids[] = $mobile;
                $names[$mobile] = $name;
                $tmp = [];
                if ($val) {
                    foreach ($val as $k => $v) {
                        if (isset($titles[$k]) && $titles[$k] != ActivityApplicantService::ATTR_MOBILE && $titles[$k] != ActivityApplicantService::ATTR_NAME) {
                            $tmp[] = ['key' => $titles[$k], 'value' => trim(strval($v))];
                        }
                    }
                }
                $joinData[$mobile] = json_encode($tmp);
            }
        }

        return [$ids, $names, $joinData, $mobileData, $errors];
    }

}