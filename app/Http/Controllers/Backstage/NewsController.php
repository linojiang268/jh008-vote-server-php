<?php
namespace Jihe\Http\Controllers\Backstage;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Jihe\Services\StorageService;
use Jihe\Entities\News;
use Jihe\Http\Controllers\Controller;
use Jihe\Services\NewsService;
use Jihe\Services\WechatService;
use Jihe\Services\TeamService;
use Jihe\Entities\NewsRequest;
use Cache;

class NewsController extends Controller
{
    /**
     * get one page of news
     */
    public function getTeamNews(Request $request, NewsService $newsService)
    {
        $this->validate($request, [
            'activity_id'  => 'integer|min:-1',
            'page'         => 'integer|min:1',
            'size'         => 'integer|min:1'
        ], [
            'activity_id.integer'   => '活动数据错误',
            'activity_id.min'       => '活动数据错误',
            'page.integer'          => '页码数据错误',
            'page.min'              => '页码数据错误',
            'size.integer'          => '分页数据错误',
            'size.min'              => '分页数据错误',
        ]);

        list($page, $size) = $this->sanePageAndSize($request->input('page'), $request->input('size'));

        try {
            $result = $newsService->getTeamNews($request->input('team')->getId(),
                                                $request->input('activity_id'),
                                                $page, $size);

            return $this->json([
                'pages'     => $result->lastPage(),
                'news'      => array_map(function (News $news) {
                                    return [
                                        'id'          => $news->getId(),
                                        'title'       => $news->getTitle(),
                                        'publishTime' => $news->getPublishTime()->format('Y-m-d H:i:s')
                                    ];
                                }, $result->items()),
            ]);

        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    /**
     * get news detail
     */
    public function getNews(Request $request, NewsService $newsService, $newsId)
    {
       $news = $newsService->getNewsDetail($newsId);
       return view('backstage.news.detail', [
           'key'  => 'news',
           'news' => $news,
       ]);
    }

    /**
     * get news detail
     */
    public function getNewsForUpdate(Request $request, NewsService $newsService, $newsId)
    {
        $news = $newsService->getNewsDetail($newsId);
        return view('backstage.activity.newsPublish', [
            'key'  => 'news',
            'news' => $news,
        ]);
    }

    /**
     * add the news
     */
    public function publishNews(Request $request, Guard $auth,
                                NewsService $newsService, StorageService $storageService)
    {
        $this->validate($request, [
            'title'        => 'between:1,32|required',
            'content'      => 'between:5,20000|required',
            'activity_id'  => 'integer|min:1',
            'cover_url'    => 'required',
        ], [
            'title.between'         => '请正确输入标题',
            'title.required'        => '请正确输入标题',
            'content.between'       => '请正确输入正文',
            'content.required'      => '请正确输入正文',
            'activity_id.integer'   => '请正确选择活动',
            'activity_id.min'       => '请正确选择活动',
            'cover_url.required'    => '资讯封面未设置',
        ]);

        $coverUrl = $request->input('cover_url');
        if ($storageService->isTmp($coverUrl)) {
            $coverUrl = $storageService->storeAsImage($coverUrl);
        }

        try {
            $newsService->publishNews($request->input('title'),
                                      $request->input('content'),
                                      $coverUrl,
                                      $request->input('activity_id'),
                                      $request->input('team')->getId(),
                                      $auth->user()->getAuthIdentifier());
            return $this->json('发布资讯成功');

        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    /**
     * update the news
     */
    public function updateNews(Request $request, Guard $auth,
                               NewsService $newsService, StorageService $storageService, $newsId)
    {
        $this->validate($request, [
            'title'   => 'between:1,32|required',
            'content' => 'between:5,20000|required',
        ], [
            'title.between'    => '请正确输入标题',
            'title.required'   => '请正确输入标题',
            'content.between'  => '请正确输入正文',
            'content.required' => '请正确输入正文',
        ]);

        // cover url can be optional
        $coverUrl = $request->input('cover_url');
        if ($coverUrl && $storageService->isTmp($coverUrl)) {
            $coverUrl = $storageService->storeAsImage($coverUrl);
        }

        try {
            $newsService->updateNews($newsId,
                                     $request->input('title'),
                                     $request->input('content'),
                                     $coverUrl,
                                     $auth->user()->getAuthIdentifier());
            return $this->json('修改资讯成功');

        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    /**
     * delete the news
     */
    public function deleteNews(NewsService $newsService, Guard $auth, $newsId)
    {
        try {
            $newsService->deleteNews($newsId, $auth->user()->getAuthIdentifier());
            return $this->json('删除资讯成功');

        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    /**
     * render detail wap page
     */
    public function detail(Request $request, NewsService $newsService, TeamService $team, WechatService $wechatService)
    {
        $this->validate($request, [
            'news_id'          => 'required|integer',
        ], [
            'news_id.required'     => 'id非法',
        ]);

        try {
            $newsId = $request->input('news_id');
            $news = $newsService->getNewsDetail($newsId);
            $team = $team->getTeam($news->getTeam()->getId());
        } catch (\Exception $ex) {
            return view('backstage.wap.news', [
                'news' => null,
                'sign_package' => null,
                'team' => null,
                'app_installed' => 1 == $request->input('isappinstalled'),
            ])->withErrors('该资讯不存在');
        }

        try {
            $url = url("wap/news/detail?news_id={$request->input('news_id')}");
            $package = $wechatService->getJsSignPackage($url);
        } catch (\Exception $ex) {
            $package = null;
        }

        $key = md5(News::CLICK_NUM_CACHE_KEY.$newsId);
        if (Cache::has($key)) {
            Cache::increment($key, 1);
        } else {
            Cache::forever($key, 1);
        }

        return view('backstage.wap.news', [
            'news' => $news,
            'sign_package' => $package,
            'team' => $team,
            'app_installed' => 1 == $request->input('isappinstalled'),
        ]);
    }
}