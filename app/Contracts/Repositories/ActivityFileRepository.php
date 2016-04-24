<?php
namespace Jihe\Contracts\Repositories;

use Jihe\Entities\ActivityFile;

interface ActivityFileRepository
{
    /**
     * add actvity file
     * 
     * @param \Jihe\Entities\ActivityFile   $file   activity file entity
     * @return \Jihe\Entities\ActivityFile  $file   activity file entity
     */
    public function add(ActivityFile $file);
    
    /**
     * find files of activity
     * 
     * @param int $activity      id of actvity
     * @param int $page          index of page
     * @param int $size          size of each page
     * @return array             array of \Jihe\Entities\ActivityFile
     */
    public function find($activity, $page, $size);
    
    /**
     * remove given activity files
     * 
     * @param int $activity   id of activity
     * @param array $files    ids of activity file
     * @return boolean
     */
    public function remove($activity, array $files);

    /**
     * count activity files
     *
     * @param int $activity   id of activity
     */
    public function count($activity);
}
