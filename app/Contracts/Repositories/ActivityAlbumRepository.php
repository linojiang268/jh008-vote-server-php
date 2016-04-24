<?php
namespace Jihe\Contracts\Repositories;

use Jihe\Entities\ActivityAlbumImage;

interface ActivityAlbumRepository
{
    /**
     * add actvity album image
     * 
     * @param \Jihe\Entities\ActivityAlbumImage   $image   album image entity    
     * @return \Jihe\Entities\ActivityAlbumImage  $image   album image entity
     */
    public function addImage(ActivityAlbumImage $image);
    
    /**
     * find album images of activity
     * 
     * @param int $activity      id of actvity
     * @param int $page          index of page
     * @param int $size          size of each page
     * @param int $creatorType   role of uploaded user
     * @param int $creator       id of uploaded user
     * @param int $status        status of album
     * @param int $lastCreatedAt created time of last album
     * @param int $lastId        id of last album image
     * @return array             array of \Jihe\Entities\ActivityAlbumImage
     */
    public function findImages($activity, $page, $size, $creatorType = null, $creator = null, $status = null, $lastCreatedAt = null, $lastId = null);
    
    /**
     * update given album images status to approved
     * 
     * @param int $activity       id of activity
     * @param array $images       ids of album images
     * @return boolean
     */
    public function updateImageStatusToApproved($activity, array $images);
    
    /**
     * remove given album images
     * 
     * @param int $activity   id of activity
     * @param array $images   ids of album images
     * @param int $creator   id of creator
     * @return boolean
     */
    public function removeImages($activity, array $images, $creator = null);
    
    /**
     * stat activity's pending album images
     *
     * @param array $teams  team ids
     *
     * @return array
     */
    public function statPendingImages(array $activities = null);
    
    /**
     * count album images
     * 
     * @param array $criteria   keys taken:
     *                           - team           id of team
     *                           - activity       id of activity
     *                           - creator_id     id of user
     *                           - creator_type
     *                           - status
     */
    public function countAlbumImages($criteria = []);

    /**
     * @param array $activities
     * @param int $creatorType
     * @param int $status
     *
     * @return array
     */
    public function countActivityImages($activities, $creatorType, $status);
}
