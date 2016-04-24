<?php
namespace Jihe\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Jihe\Entities\News;

interface NewsRepository
{
    /**
     * get news by page
     *
     * @param int $teamId team id the news belong to
     * @param integer|null $activityId belong activity id, 0 means belong to any activity, -1 means belong to none activity, null means don't care
     * @param int $pageIndex the index of the page
     * @param int $pageSize number of news per page
     * @return LengthAwarePaginatorContract filled with \Jihe\Entities\News
     */
    public function findTeamNews($teamId, $activityId = null, $pageIndex, $pageSize);

    /**
     * get the news by id
     *
     * @param int $newsId the id of the news
     * @param array $relations|[]      array of model relatisonship, keys:
     *                                   - publisher
     *                                   - activity
     *                                   - team
     * @return \Jihe\Entities\News
     */
    public function findNewsById($newsId, $relations = []);

    /**
     * add news
     * @param \Jihe\Entities\News $news created news
     * @return int id of the newly added user
     */
    public function add(News $news);

    /**
     * update news
     *
     * @param \Jihe\Entities\News $news created news
     * @return boolean true if success, false if not found
     */
    public function update($news);

    /**
     * delete news
     *
     * @param int $newsId
     * @return boolean the result, true means success, false means not found
     */
    public function delete($newsId);

    /**
     * all news
     *
     * @return array with \Jihe\Entities\News
     */
    public function findAll();

}
