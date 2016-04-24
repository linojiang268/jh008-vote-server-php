<?php
namespace Jihe\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Jihe\Contracts\Repositories\NewsRepository as NewsRepositoryContract;
use Jihe\Contracts\Repositories\the;
use Jihe\Entities\EntityPage;
use Jihe\Models\News;
use Jihe\Entities\News as NewsEntity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;

class NewsRepository implements NewsRepositoryContract
{

    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Repositories\NewsRepository::findTeamNews()
     */
    public function findTeamNews($teamId, $activityId = null, $pageIndex = 1, $pageSize = 15)
    {
        /* @var $query \Illuminate\Database\Eloquent\Builder */
        $query =  News::orderBy('created_at', 'desc');
        if (!empty($teamId)) {
            $query->where('team_id', $teamId);
        }
        if (!is_null($activityId)) {
            if ($activityId == -1) {
                $query->whereNull('activity_id');
            } else if ($activityId == 0) {
                $query->whereNotNull('activity_id');
            } else {
                $query->where('activity_id', $activityId);
            }
        }

        /* @var $result LengthAwarePaginatorContract */
        $result = $query->paginate($pageSize);

        return new LengthAwarePaginator($this->convertToEntities($result->items()),
                                        $result->total(), $result->perPage(), $result->currentPage());
    }
    
    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Repositories\NewsRepository::findNewsById()
     */
    public function findNewsById($newsId, $relations = [])
    {
        $query = News::where('id', $newsId);
        if (!empty($relations)) {
            $query->with($relations);
        }
        $news = $query->first();

        return is_null($news) ? null : $this->convertToEntity($news);
    }

    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Repositories\NewsRepository::add()
     */
    public function add(NewsEntity $news)
    {
        return News::create($this->morphToAttrs($news))->id;
    }

    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\NewsRepository::update()
     */
    public function update($news)
    {
        $newsId = $news->getId();
        $news->setId(null);
        return 1 == News::where('id', $newsId)
                        ->update($this->morphToAttrs($news));
    }

    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\NewsRepository::update()
     */
    public function delete($newsId)
    {
        return 1 == News::where('id', $newsId)->delete();
    }

    /**
     *
     * @param \Jihe\Entities\News $news
     * @return array
     */
    private function morphToAttrs($news)
    {
        $data = [
            'id'            => $news->getId(),
            'title'         => $news->getTitle(),
            'cover_url'     => $news->getCoverUrl(),
            'content'       => $news->getContent(),
            'publisher_id'  => $news->getPublisher()->getId(),
            'team_id'       => $news->getTeam()->getId(),
            'click_num'     => $news->getClickNum(),
            'activity_id'   => is_null($news->getActivity()) ? null : $news->getActivity()->getId()
        ];
        return array_filter($data);
    }

    /**
     *
     * @param \Jihe\Models\News $newsModel
     * @return \Jihe\Entities\News
     */
    private function convertToEntity($newsModel)
    {
        return $newsModel->toEntity();
    }

    /**
     *
     * @param array $newsModels  array of \Jihe\Models\News
     * @return array             array of \Jihe\Entities\News
     */
    private function convertToEntities($newsModels)
    {
        return array_map([ $this, 'convertToEntity' ], $newsModels);
    }

    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\NewsRepository::findAll()
     */
    public function findAll()
    {
        $news =  News::orderBy('created_at', 'desc')->get()->all();

        return array_map([ $this, 'convertToEntity' ], $news);
    }
}
