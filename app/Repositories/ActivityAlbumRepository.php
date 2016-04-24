<?php
namespace Jihe\Repositories;

use Jihe\Contracts\Repositories\ActivityAlbumRepository as ActivityAlbumRepositoryContract;
use Jihe\Entities\ActivityAlbumImage as ActivityAlbumImageEntity;
use Jihe\Models\ActivityAlbumImage;
use Jihe\Entities\Activity as ActivityEntity;
use DB;

class ActivityAlbumRepository implements ActivityAlbumRepositoryContract
{
    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\ActivityAlbumRepository::addImage()
     */
    public function addImage(ActivityAlbumImageEntity $image)
    {
        return ActivityAlbumImage::create($this->morphToImage($image))
            ->toEntity();
    }

    /**
     *
     * @param \Jihe\Entities\ActivityAlbumImage $image
     *
     * @return array                             array of \Jihe\Entities\ActivityAlbumImage
     */
    private function morphToImage(ActivityAlbumImageEntity $image)
    {
        return [
            'activity_id'  => $image->getActivity()->getId(),
            'creator_type' => $image->getCreatorType(),
            'creator_id'   => $image->getCreator()->getId(),
            'image_url'    => $image->getImageUrl(),
            'status'       => $image->getStatus(),
        ];
    }

    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\ActivityAlbumRepository::findImages()
     */
    public function findImages($activity, $page, $size, $creatorType = null, $creator = null, $status = null, $lastCreatedAt = null, $lastId = null)
    {
        $query = ActivityAlbumImage::where('activity_id', $activity);
        if (!is_null($creatorType)) {
            $query->where('creator_type', $creatorType);
        }
        if (!is_null($creator)) {
            $query->where('creator_id', $creator);
        }
        if (!is_null($status)) {
            $query->where('status', $status);
        }

        // asc when find pending user album images, otherwise desc
        if (is_null($creatorType) || ActivityAlbumImageEntity::USER != $creatorType ||
            is_null($status) || ActivityAlbumImageEntity::STATUS_PENDING != $status
        ) {
            $query->orderBy('created_at', 'desc');
            $query->orderBy('id', 'desc');

            // get new page by last id of album image
            if (!is_null($lastCreatedAt) && !is_null($lastId)) {
                $query->where('created_at', '<', $lastCreatedAt);
                $query->where('id', '<', $lastId);
                $page = 1;
            }
        } else {
            $query->orderBy('created_at', 'asc');
            $query->orderBy('id', 'asc');

            // get new page by last id of album
            if (!is_null($lastCreatedAt) && !is_null($lastId)) {
                $query->where('created_at', '>', $lastCreatedAt);
                $query->where('id', '>', $lastId);
                $page = 1;
            }
        }

        $total = $query->getCountForPagination()->count();
        $pages = ceil($total / $size);
        if ($page > $pages) {
            $page = $pages;
        }

        return [
            $pages,
            array_map(function (ActivityAlbumImage $activityAlbumImage) {
                return $activityAlbumImage->toEntity();
            },
                $query->forPage($page, $size)
                    ->get()
                    ->all()),
        ];
    }

    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\ActivityAlbumRepository::updateImageStatusToApproved()
     */
    public function updateImageStatusToApproved($activity, array $images)
    {
        return count($images) == ActivityAlbumImage::where('activity_id', $activity)
            ->whereIn('id', $images)
            ->where('status', ActivityAlbumImageEntity::STATUS_PENDING)
            ->update([
                'status' => ActivityAlbumImageEntity::STATUS_APPROVED]);
    }

    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\ActivityAlbumRepository::removeImages()
     */
    public function removeImages($activity, array $images, $creator = null)
    {
        $query = ActivityAlbumImage::where('activity_id', $activity)
            ->whereIn('id', $images);

        if (!is_null($creator)) {
            $query->where('creator_type', ActivityAlbumImageEntity::USER)
                ->where('creator_id', $creator);
        }

        return count($images) == $query->delete();
    }

    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\ActivityAlbumRepository::statPendingImages()
     */
    public function statPendingImages(array $activities = null)
    {
        $query = \DB::table('activity_album_images')
            ->where('status', ActivityAlbumImageEntity::STATUS_PENDING)
            ->select('activity_id', \DB::raw('SUM(1) as pending_images'))
            ->groupBy('activity_id');

        if (!empty($activities)) {
            $query->whereIn('activity_id', $activities);
        }

        $pendingImages = $query->get();
        if (empty($pendingImages)) {
            return [];
        }

        $images = [];
        foreach ($pendingImages as $image) {
            $images[$image->activity_id] = $image->pending_images;
        }

        $activities = \DB::table('activities')
            ->where('status', ActivityEntity::STATUS_PUBLISHED)
            ->whereIn('id', array_keys($images))
            ->select('id', 'title', 'telephone')
            ->get();
        $stats = [];
        foreach ($activities as $activity) {
            $stats[$activity->id] = [
                'activity'       => [
                    'id'        => $activity->id,
                    'title'     => $activity->title,
                    'telephone' => $activity->telephone,
                ],
                'pending_images' => $images[$activity->id],
            ];
        }

        return $stats;
    }

    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\ActivityAlbumRepository::countAlbumImages()
     */
    public function countAlbumImages($criteria = [])
    {
        $query = ActivityAlbumImage::join('activities', 'activities.id', '=', 'activity_album_images.activity_id')
            ->where('activities.status', ActivityEntity::STATUS_PUBLISHED);

        if ($activity = array_get($criteria, 'activity')) {
            $query->where('activity_id', $activity);
        }

        if ($creator = array_get($criteria, 'creator')) {
            $query->where('creator_id', $creator);
        }

        if ($creatorType = array_get($criteria, 'creator_type')) {
            $query->where('creator_type', $creatorType);
        }

        if (null !== ($status = array_get($criteria, 'status'))) {
            $query->where('activity_album_images.status', $status);
        }

        if ($team = array_get($criteria, 'team')) {
            $query->where('activities.team_id', $team);
        }

        return $query->count();
    }

    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\ActivityAlbumRepository::countActivityImages()
     */
    public function countActivityImages($activities, $creatorType, $status)
    {
        $activitiesImageCount = DB::table('activity_album_images')
            ->leftJoin('activities', 'activities.id', '=', 'activity_album_images.activity_id')
            ->where('activities.status', ActivityEntity::STATUS_PUBLISHED)
            ->whereIn('activity_album_images.activity_id', $activities)
            ->where('activity_album_images.creator_type', $creatorType)
            ->where('activity_album_images.status', $status)
            ->groupBy('activity_album_images.activity_id')
            ->select(DB::raw('count(activity_album_images.image_url) as ci , activity_album_images.activity_id'))
            ->get();
        $result = [];
        if($activitiesImageCount){
            foreach ($activitiesImageCount as $activityImageCount) {
                $result[$activityImageCount->activity_id] = $activityImageCount->ci;
            }
        }

        return $result;
    }

}
