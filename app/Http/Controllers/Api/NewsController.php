<?php
namespace Jihe\Http\Controllers\Api;

use Illuminate\Http\Request;
use Jihe\Entities\News;
use Jihe\Http\Controllers\Controller;
use Jihe\Services\NewsService;

class NewsController extends Controller
{
    /**
     * get team's news
     */
    public function getTeamNews(Request $request, NewsService $newsService)
    {
        $this->validate($request, [
            'team_id'     => 'integer|required|min:1',
            'activity_id' => 'integer|min:1',
            'page'        => 'integer|min:1',
            'size'        => 'integer|min:1'
        ], [
            'team_id.integer'     => '社团数据错误',
            'team_id.required'    => '社团数据错误',
            'team_id.min'         => '社团数据错误',
            'activity_id.integer' => '活动数据错误',
            'activity_id.min'     => '活动数据错误',
            'page.integer'        => '页码数据错误',
            'page.min'            => '页码数据错误',
            'size.integer'        => '分页数据错误',
            'size.min'            => '分页数据错误',
        ]);

        list($page, $size) = $this->sanePageAndSize($request->input('page'), $request->input('size'));

        try {
            $result = $newsService->getTeamNews($request->input('team_id'),
                                                $request->input('activity_id'),
                                                $page, $size);
            return $this->json([
                'pages' => $result->lastPage(),
                'news'  => array_map([$this, 'morphNews'], $result->items()),
            ]);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    private function morphNews(News $news)
    {
        return [
            'id'           => $news->getId(),
            'title'        => $news->getTitle(),
            'cover_url'    => $news->getCoverUrl(),
            'publish_time' => $news->getPublishTime()->format('Y-m-d H:i:s'),
        ];
    }
}
