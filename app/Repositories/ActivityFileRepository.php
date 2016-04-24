<?php
namespace Jihe\Repositories;

use Jihe\Contracts\Repositories\ActivityFileRepository as ActivityFileRepositoryContract;
use Jihe\Entities\ActivityFile as ActivityFileEntity;
use Jihe\Models\ActivityFile;

class ActivityFileRepository implements ActivityFileRepositoryContract
{
    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\ActivityFileRepository::add()
     */
    public function add(ActivityFileEntity $file)
    {
        return ActivityFile::create($this->morphToAttributes($file))->toEntity();
    }
    
    /**
     * 
     * @param \Jihe\Entities\ActivityFile  $file
     * @return array
     */
    private function morphToAttributes(ActivityFileEntity $file)
    {
        return array_filter(
            [
                'activity_id'  => $file->getActivity()->getId(),
                'name'         => $file->getName(),
                'memo'         => $file->getMemo(),
                'size'         => $file->getSize(),
                'extension'    => $file->getExtension(),
                'url'          => $file->getUrl(),
            ]);
    }

    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\ActivityFileRepository::find()
     */
    public function find($activity, $page, $size)
    {
        $query = ActivityFile::where('activity_id', $activity)
                             ->orderBy('created_at', 'desc')
                             ->orderBy('id', 'desc');

        $total = $query->getCountForPagination()->count();
        $pages = ceil($total / $size);
        if ($page > $pages) {
            $page = $pages;
        }
        
        return [ $total, array_map(function (ActivityFile $activityFile) {
            return $activityFile->toEntity();
        }, $query->forPage($page, $size)->get()->all()) ];
    }
    
    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\ActivityFileRepository::remove()
     */
    public function remove($activity, array $files)
    {
        return count($files) == ActivityFile::where('activity_id', $activity)
                                            ->whereIn('id', $files)
                                            ->delete();
    }

    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\ActivityFileRepository::count()
     */
    public function count($activity)
    {
        return ActivityFile::where('activity_id', $activity)->count();
    }
}
