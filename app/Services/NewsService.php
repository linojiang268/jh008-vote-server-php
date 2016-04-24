<?php
namespace Jihe\Services;

use Jihe\Contracts\Repositories\NewsRepository;
use Jihe\Contracts\Repositories\UserRepository;
use Jihe\Contracts\Repositories\ActivityRepository;
use Jihe\Contracts\Repositories\TeamRepository;
use Jihe\Services\StorageService;
use Jihe\Entities\News;
use Jihe\Exceptions\AppException;
use Jihe\Exceptions\News\NewsNotExistException;

class NewsService
{
    /**
     * @var \Jihe\Contracts\Repositories\NewsRepository
     */
    private $newsRepository;

    /**
     * @var \Jihe\Contracts\Repositories\ActivityRepository
     */
    private $activityRepository;

    /**
     * @var \Jihe\Contracts\Repositories\UserRepository
     */
    private $userRepository;

    /**
     * @var \Jihe\Contracts\Repositories\TeamRepository
     */
    private $teamRepository;

    /* @var \Jihe\Services\StorageService */
    private $storageService;

    public function __construct(NewsRepository $newsRepository,
                                ActivityRepository $activityRepository,
                                UserRepository $userRepository,
                                TeamRepository $teamRepository,
                                StorageService $storageService)
    {
        $this->newsRepository = $newsRepository;
        $this->activityRepository = $activityRepository;
        $this->userRepository = $userRepository;
        $this->teamRepository = $teamRepository;
        $this->storageService = $storageService;
    }

    /**
     * get array of available cities
     *
     * @param null|int        $teamId     team id the news belong to
     * @param null|int|string $activityId activity id the news belong to, 0 means belong to any activity, -1 means belong to none activity, null means don't care
     * @param int             $pageIndex  the index of the page, start from 1, if larger than max, return empty, if smaller
     *                                    than 1, return the first page
     * @param int             $pageSize   number of news per page, if smaller than 1 or larger than 1000 use default 15
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator filled with Jihe\Entities\News
     */
    public function getTeamNews($teamId = null, $activityId = null, $pageIndex = 1, $pageSize = 15)
    {
        return $this->newsRepository->findTeamNews($teamId, $activityId, $pageIndex, $pageSize);
    }

    /**
     * get detail of news by its id
     *
     * @param int $newsId id of the news
     *
     * @return \Jihe\Entities\News|null
     * @throws NewsNotExistException
     */
    public function getNewsDetail($newsId)
    {
        $news = $this->newsRepository->findNewsById($newsId);
        if (is_null($news)) {
            throw new NewsNotExistException();
        }
        return $news;
    }

    /**
     * publish a piece of news
     *
     * @param string      $title      title of the news
     * @param string      $content    content of the news
     * @param string      $coverUrl   cover's url
     * @param string|null $activity id of the activity which the news belong to
     * @param string      $teamId     id of the team which the news belong to
     * @param string      $publisherId  id of the user which the news belong to
     *
     * @throws AppException throw when data is not legal
     * @return int the new news's id
     */
    public function publishNews($title, $content, $coverUrl, $activity = null, $teamId, $publisherId)
    {
        if (!is_null($activity)) {
            $activity = $this->activityRepository->findActivityById($activity);
        }

        $team = $this->teamRepository->findTeam($teamId, ['creator']); //不可为空
        if (is_null($team) || $publisherId != $team->getCreator()->getId()) {
            throw new AppException("社团信息不正确");
        }

        //判断用户是否存在，是否有权限
        $user = $this->userRepository->findById($publisherId); //不可为空
        if (is_null($user)) {
            throw new AppException("用户信息不正确");
        }

        $content = $this->replaceTmpImage($content);

        $news = (new News())
            ->setTitle($title)
            ->setContent($content)
            ->setCoverUrl($coverUrl)
            ->setActivity($activity)
            ->setPublisher($user)
            ->setTeam($team);

        $result = $this->newsRepository->add($news);
        if($result) {
            // notify team when news published
            $this->teamRepository->updateNotifiedAt($news->getTeam()->getId(), ['news']);
        }

        return $result;
    }

    /**
     * change the title or content of the news
     *
     * @param int    $newsId
     * @param string $title
     * @param string $content
     * @param string $coverUrl   cover's url
     * @param int    $operatorId the delete operator
     *
     * @throws NewsNotExistException throw when the news is not exists or not belong to the operator
     * @return boolean true when success and false when fail
     */
    public function updateNews($newsId, $title, $content, $coverUrl, $operatorId)
    {
        $news = $this->getNewsForUpdateOrDelete($newsId, $operatorId);
        if (is_null($news)) {
            throw new NewsNotExistException();
        }

        if ($news->getCoverUrl() != $coverUrl && !empty($news->getCoverUrl())) {
            $this->storageService->remove($news->getCoverUrl());
        }

        $this->removeNotUsedImage($news->getContent(), $content);
        $content = $this->replaceTmpImage($content);

        $news->setTitle($title)->setContent($content)->setCoverUrl($coverUrl);

        return $this->newsRepository->update($news);
    }

    /**
     * delet the news
     *
     * @param int $newsId     the news deleted
     * @param int $operatorId the delete operator
     *
     * @throws NewsNotExistException throw when the news is not exists or not belong to the operator
     * @return boolean true when success and false when fail
     */
    public function deleteNews($newsId, $operatorId)
    {
        if (is_null($this->getNewsForUpdateOrDelete($newsId, $operatorId))) {
            throw new NewsNotExistException();
        }
        return $this->newsRepository->delete($newsId);
    }

    /**
     * judge whether the operator can delete or update the news
     *
     * @param int $newsId     deleted or updated news
     * @param int $operatorId the operator
     *
     * @return null|\Jihe\Entities\News return null if not found or can not operate
     */
    protected function getNewsForUpdateOrDelete($newsId, $operatorId)
    {
        $news = $this->newsRepository->findNewsById($newsId, ['team']);
        if (is_null($news)) {
            return null;
        }
        if ($operatorId != $news->getTeam()->getCreator()->getId()) {
            return null;
        }
        return $news;
    }

    /**
     * remove the image not used anymore in the old content
     * @param string $oldContent the old content pre edit
     * @param string $newContent the new content changed to
     */
    private function removeNotUsedImage($oldContent, $newContent)
    {
        $oldImageSrcs = $this->getAllImageSrc($oldContent);
        $newImageSrcs = $this->getAllImageSrc($newContent);
        foreach ($oldImageSrcs as $oldImageSrc) {
            // the old file is not used in new content, remove it
            if (false === array_search($oldImageSrc, $newImageSrcs)) {
                $this->storageService->remove($oldImageSrc);
            }
        }

    }

    /**
     * copy the tmp image in the content to none tmp folder
     * @param  string $content the content find image in
     * @return string the new content with no tmp image
     */
    private function replaceTmpImage($content)
    {
        $imageSrcs = $this->getAllImageSrc($content);
        foreach ($imageSrcs as $imageSrc) {
            // found the tmp image, copy it and change to the new path in content
            if ($this->storageService->isTmp($imageSrc)) {
                $newImageSrc = $this->storageService->storeAsImage($imageSrc);
                $content = str_replace($imageSrc, $newImageSrc, $content);
            }
        }
        return $content;
    }

    /**
     * get all image src path from the html content
     * @param string $content the content with format of html
     * @return array the path of all image
     */
    private function getAllImageSrc($content)
    {
        if (preg_match_all('/(?:src|href)=(["\'])([^"\']+)\1/', $content, $matches, PREG_PATTERN_ORDER)) {
            return array_unique(array_values($matches[2]));
        }
        return [];

    }

}
