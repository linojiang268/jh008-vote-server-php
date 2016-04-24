<?php
namespace spec\Jihe\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Jihe\Contracts\Repositories\TeamRepository;
use Jihe\Contracts\Repositories\UserRepository;
use Jihe\Contracts\Repositories\NewsRepository;
use Jihe\Contracts\Repositories\ActivityRepository;
use Jihe\Services\StorageService;
use Jihe\Entities\User as UserEntity;
use Jihe\Entities\Activity as ActivityEntity;
use Jihe\Entities\Team as TeamEntity;
use Jihe\Entities\News as NewsEntity;
use PhpSpec\Laravel\LaravelObjectBehavior;
use Prophecy\Argument;

class NewsServiceSpec extends LaravelObjectBehavior
{

    function let(NewsRepository $newsRepository,
                 ActivityRepository $activityRepository,
                 UserRepository $userRepository,
                 TeamRepository $teamRepository,
                 StorageService $storageService)
    {
        $this->beAnInstanceOf(\Jihe\Services\NewsService::class, [
            $newsRepository, $activityRepository,
            $userRepository, $teamRepository,
            $storageService
        ]);

    }

    //=========================================================================
    //        Publish news
    //=========================================================================
    function it_accepts_request_for_publish_with_no_picture(NewsRepository $newsRepository,
                                            ActivityRepository $activityRepository,
                                            UserRepository $userRepository,
                                            TeamRepository $teamRepository,
                                            StorageService $storageService)
    {
        $content = '<p><a href="http://baidu.com" target="_self">adfadsfasdf</a>' .
            '<embed type="application/x-shockwave-flash" class="edui-faked-music" ' .
            'pluginspage="http://www.macromedia.com/go/getflashplayer" ' .
            'src="http://box.baidu.com/widget/flash/bdspacesong.swf width="400" height="95" align="none"' .
            ' wmode="transparent" play="true" loop="false" menu="false" allowscriptaccess="never" ' .
            'allowfullscreen="true"/><img src="http://img.baidu.com/hi/jx2/j_0015.gif"/>' .
            '<img style="vertical-align: middle; margin-right: 2px;" ' .
            'src="http://ueditor.baidu.com/ueditor/dialogs/attachment/fileTypeImages/icon_jpg.gif"/>' .
            '<a style="font-size:12px; color:#0066cc;" ' .
            'href="http://dev.image.jhla.com.cn/tmp/20150803/20150803162452765480.jpg" '.
            'title="demo.jpg">demo.jpg</a></p>';

        $activityRepository->findActivityById(1)->willReturn((new ActivityEntity)->setId(1));
        $userRepository->findById(3)->willReturn((new UserEntity)->setId(3));
        $teamRepository->findTeam(2, array('creator'))
            ->willReturn((new TeamEntity)->setId(2)->setCreator((new UserEntity)->setId(3)));
        $storageService->isTmp(Argument::cetera())->willReturn(false);
        $storageService->storeAsImage(Argument::cetera())->shouldNotBeCalled();
        $newsRepository->add(Argument::that(function (NewsEntity $news) use ($content) {
            return $this->isNewsEqual(null, 'test title', $content, 1, 2, 3, $news);
        }))->willReturn(5);
        $teamRepository->updateNotifiedAt(2, ['news'])->shouldBeCalledTimes(1)->willReturn(true);

        $this->publishNews('test title', $content, 'http://demo/test.jpg', 1, 2, 3)
             ->shouldBe(5);
    }

    //=========================================================================
    //        Publish news
    //=========================================================================
    function it_accepts_request_for_publish_with_one_picture(NewsRepository $newsRepository,
                                            ActivityRepository $activityRepository,
                                            UserRepository $userRepository,
                                            TeamRepository $teamRepository,
                                            StorageService $storageService)
    {
        $testTmpImagePath = 'http://dev.image.jhla.com.cn/tmp/20150803/20150803162452765480.jpg';
        $testNoneTmpImagePath = 'http://dev.image.jhla.com.cn/20150804/20150804111654785610.jpg';
        $contentTemplate = '<p><img src="%s" title="" alt="20150803162452765480.jpg"/>asdfasdfasdf sdf' .
            '<a href="http://baidu.com" target="_self">adfadsfasdf</a>' .
            '<embed type="application/x-shockwave-flash" class="edui-faked-music" ' .
            'pluginspage="http://www.macromedia.com/go/getflashplayer" ' .
            'src="http://box.baidu.com/widget/flash/bdspacesong.swf width="400" height="95" align="none"' .
            ' wmode="transparent" play="true" loop="false" menu="false" allowscriptaccess="never" ' .
            'allowfullscreen="true"/><img src="http://img.baidu.com/hi/jx2/j_0015.gif"/>' .
            '<img style="vertical-align: middle; margin-right: 2px;" ' .
            'src="http://ueditor.baidu.com/ueditor/dialogs/attachment/fileTypeImages/icon_jpg.gif"/>' .
            '<a style="font-size:12px; color:#0066cc;" ' .
            'href="http://dev.image.jhla.com.cn/tmp/20150803/sdfsaf.jpg" '.
            'title="demo.jpg">demo.jpg</a></p>';
        $tmpContent = sprintf($contentTemplate, $testTmpImagePath);
        $content = sprintf($contentTemplate, $testNoneTmpImagePath);

        $activityRepository->findActivityById(1)->willReturn((new ActivityEntity)->setId(1));
        $userRepository->findById(3)->willReturn((new UserEntity)->setId(3));
        $teamRepository->findTeam(2, array('creator'))
            ->willReturn((new TeamEntity)->setId(2)->setCreator((new UserEntity)->setId(3)));
        $storageService->isTmp($testTmpImagePath)->willReturn(true);
        $storageService->isTmp(Argument::cetera())->willReturn(false);
        $storageService->storeAsImage($testTmpImagePath)->willReturn($testNoneTmpImagePath);
        $newsRepository->add(Argument::that(function (NewsEntity $news) use ($content) {
            return $this->isNewsEqual(null, 'test title', $content, 1, 2, 3, $news);
        }))->willReturn(5);
        $teamRepository->updateNotifiedAt(2, ['news'])->shouldBeCalledTimes(1)->willReturn(true);

        $this->publishNews('test title', $tmpContent, 'http://demo/test.jpg', 1, 2, 3)
            ->shouldBe(5);
    }

    //=========================================================================
    //        Publish news
    //=========================================================================
    function it_accepts_request_for_publish_with_two_same_picture(NewsRepository $newsRepository,
                                                             ActivityRepository $activityRepository,
                                                             UserRepository $userRepository,
                                                             TeamRepository $teamRepository,
                                                             StorageService $storageService)
    {
        $testTmpImagePath = 'http://dev.image.jhla.com.cn/tmp/20150803/20150803162452765480.jpg';
        $testNoneTmpImagePath = 'http://dev.image.jhla.com.cn/20150804/20150804111654785610.jpg';
        $contentTemplate = '<p><img src="%s" title="" alt="20150803162452765480.jpg"/>asdfasdfasdf sdf' .
            '<p><img src="%s" title="" alt="sdfsdf.jpg"/>asdfasdfasdf sdf' .
            '<a href="http://baidu.com" target="_self">adfadsfasdf</a>' .
            '<embed type="application/x-shockwave-flash" class="edui-faked-music" ' .
            'pluginspage="http://www.macromedia.com/go/getflashplayer" ' .
            'src="http://box.baidu.com/widget/flash/bdspacesong.swf width="400" height="95" align="none"' .
            ' wmode="transparent" play="true" loop="false" menu="false" allowscriptaccess="never" ' .
            'allowfullscreen="true"/><img src="http://img.baidu.com/hi/jx2/j_0015.gif"/>' .
            '<img style="vertical-align: middle; margin-right: 2px;" ' .
            'src="http://ueditor.baidu.com/ueditor/dialogs/attachment/fileTypeImages/icon_jpg.gif"/>' .
            '<a style="font-size:12px; color:#0066cc;" ' .
            'href="http://dev.image.jhla.com.cn/tmp/20150803/sdfsaf.jpg" '.
            'title="demo.jpg">demo.jpg</a></p>';
        $tmpContent = sprintf($contentTemplate, $testTmpImagePath, $testTmpImagePath);
        $content = sprintf($contentTemplate, $testNoneTmpImagePath, $testNoneTmpImagePath);

        $activityRepository->findActivityById(1)->willReturn((new ActivityEntity)->setId(1));
        $userRepository->findById(3)->willReturn((new UserEntity)->setId(3));
        $teamRepository->findTeam(2, array('creator'))
            ->willReturn((new TeamEntity)->setId(2)->setCreator((new UserEntity)->setId(3)));
        $storageService->isTmp($testTmpImagePath)->willReturn(true);
        $storageService->isTmp(Argument::cetera())->willReturn(false);
        $storageService->storeAsImage($testTmpImagePath)->willReturn($testNoneTmpImagePath);
        $newsRepository->add(Argument::that(function (NewsEntity $news) use ($content) {
            return $this->isNewsEqual(null, 'test title', $content, 1, 2, 3, $news);
        }))->willReturn(5);
        $teamRepository->updateNotifiedAt(2, ['news'])->shouldBeCalledTimes(1)->willReturn(true);

        $this->publishNews('test title', $tmpContent, 'http://demo/test.jpg', 1, 2, 3)
            ->shouldBe(5);
    }

    //=========================================================================
    //        Publish news
    //=========================================================================
    function it_accepts_request_for_publish_with_two_different_picture(NewsRepository $newsRepository,
                                                                       ActivityRepository $activityRepository,
                                                                       UserRepository $userRepository,
                                                                       TeamRepository $teamRepository,
                                                                       StorageService $storageService)
    {
        $testTmpImagePath1 = 'http://dev.image.jhla.com.cn/tmp/20150803/20150803162452765480.jpg';
        $testTmpImagePath2 = 'http://dev.image.jhla.com.cn/tmp/20150803/20150803162452765481.jpg';
        $testNoneTmpImagePath1 = 'http://dev.image.jhla.com.cn/20150804/20150804111654785610.jpg';
        $testNoneTmpImagePath2 = 'http://dev.image.jhla.com.cn/20150804/20150804111654785611.jpg';
        $contentTemplate = '<p><img src="%s" title="" alt="sdf.jpg"/>asdfasdfasdf sdf' .
            '<p><img src="%s" title="" alt="sdfsdf.jpg"/>asdfasdfasdf sdf' .
            '<a href="http://baidu.com" target="_self">adfadsfasdf</a>' .
            '<embed type="application/x-shockwave-flash" class="edui-faked-music" ' .
            'pluginspage="http://www.macromedia.com/go/getflashplayer" ' .
            'src="http://box.baidu.com/widget/flash/bdspacesong.swf width="400" height="95" align="none"' .
            ' wmode="transparent" play="true" loop="false" menu="false" allowscriptaccess="never" ' .
            'allowfullscreen="true"/><img src="http://img.baidu.com/hi/jx2/j_0015.gif"/>' .
            '<img style="vertical-align: middle; margin-right: 2px;" ' .
            'src="http://ueditor.baidu.com/ueditor/dialogs/attachment/fileTypeImages/icon_jpg.gif"/>' .
            '<a style="font-size:12px; color:#0066cc;" ' .
            'href="http://dev.image.jhla.com.cn/tmp/20150803/sdfsaf.jpg" '.
            'title="demo.jpg">demo.jpg</a></p>';
        $tmpContent = sprintf($contentTemplate, $testTmpImagePath1, $testTmpImagePath2);
        $content = sprintf($contentTemplate, $testNoneTmpImagePath1, $testNoneTmpImagePath2);

        $activityRepository->findActivityById(1)->willReturn((new ActivityEntity)->setId(1));
        $userRepository->findById(3)->willReturn((new UserEntity)->setId(3));
        $teamRepository->findTeam(2, array('creator'))
            ->willReturn((new TeamEntity)->setId(2)->setCreator((new UserEntity)->setId(3)));
        $storageService->isTmp($testTmpImagePath1)->willReturn(true);
        $storageService->isTmp($testTmpImagePath2)->willReturn(true);
        $storageService->isTmp(Argument::cetera())->willReturn(false);
        $storageService->storeAsImage($testTmpImagePath1)->willReturn($testNoneTmpImagePath1);
        $storageService->storeAsImage($testTmpImagePath2)->willReturn($testNoneTmpImagePath2);
        $newsRepository->add(Argument::that(function (NewsEntity $news) use ($content) {
            return $this->isNewsEqual(null, 'test title', $content, 1, 2, 3, $news);
        }))->willReturn(5);
        $teamRepository->updateNotifiedAt(2, ['news'])->shouldBeCalledTimes(1)->willReturn(true);

        $this->publishNews('test title', $tmpContent, 'http://demo/test.jpg', 1, 2, 3)
            ->shouldBe(5);
    }

    //=========================================================================
    //        Publish news when activity not exists
    //=========================================================================
    function it_accepts_request_for_publish_when_activity_not_exists(NewsRepository $newsRepository,
                                            UserRepository $userRepository,
                                            TeamRepository $teamRepository,
                                            StorageService $storageService)
    {
        $testTmpImagePath = 'http://dev.image.jhla.com.cn/tmp/20150803/20150803162452765480.jpg';
        $testNoneTmpImagePath = 'http://dev.image.jhla.com.cn/20150804/20150804111654785610.jpg';
        $contentTemplate = '<p><img src="%s" title="" alt="20150803162452765480.jpg"/>asdfasdfasdf sdf' .
            '<a href="http://baidu.com" target="_self">adfadsfasdf</a>' .
            '<embed type="application/x-shockwave-flash" class="edui-faked-music" ' .
            'pluginspage="http://www.macromedia.com/go/getflashplayer" ' .
            'src="http://box.baidu.com/widget/flash/bdspacesong.swf width="400" height="95" align="none"' .
            ' wmode="transparent" play="true" loop="false" menu="false" allowscriptaccess="never" ' .
            'allowfullscreen="true"/><img src="http://img.baidu.com/hi/jx2/j_0015.gif"/>' .
            '<img style="vertical-align: middle; margin-right: 2px;" ' .
            'src="http://ueditor.baidu.com/ueditor/dialogs/attachment/fileTypeImages/icon_jpg.gif"/>' .
            '<a style="font-size:12px; color:#0066cc;" ' .
            'href="http://dev.image.jhla.com.cn/tmp/20150803/sdfsaf.jpg" '.
            'title="demo.jpg">demo.jpg</a></p>';
        $tmpContent = sprintf($contentTemplate, $testTmpImagePath);
        $content = sprintf($contentTemplate, $testNoneTmpImagePath);

        $userRepository->findById(3)->shouldBeCalledTimes(1)->willReturn((new UserEntity)->setId(3));
        $teamRepository->findTeam(2, ['creator'])->shouldBeCalledTimes(1)
            ->willReturn((new TeamEntity)->setId(2)->setCreator((new UserEntity)->setId(3)));
        $storageService->isTmp($testTmpImagePath)->willReturn(true);
        $storageService->isTmp(Argument::cetera())->willReturn(false);
        $storageService->storeAsImage($testTmpImagePath)->willReturn($testNoneTmpImagePath);
        $newsRepository->add(Argument::that(function (NewsEntity $news) use ($content) {
            return $this->isNewsEqual(null, 'test title', $content, null, 2, 3, $news);
        }))->willReturn(5);
        $teamRepository->updateNotifiedAt(2, ['news'])->shouldBeCalledTimes(1)->willReturn(true);

        $this->publishNews('test title', $tmpContent, 'http://demo/test.jpg', 1, 2, 3)
             ->shouldBe(5);
    }

    //=========================================================================
    //        Publish news when team not exists
    //=========================================================================
    function it_rejects_request_for_publish_because_team_not_exists(NewsRepository $newsRepository,
                                            ActivityRepository $activityRepository,
                                            UserRepository $userRepository,
                                            TeamRepository $teamRepository)
    {
        $activityRepository->findActivityById(1)->willReturn((new ActivityEntity)->setId(1));
        $userRepository->findById(3)->willReturn((new UserEntity)->setId(3));
        $teamRepository->findTeam(2, ['creator'])->willReturn(null);

        $this->shouldThrow(new \Jihe\Exceptions\AppException('社团信息不正确'))
             ->duringPublishNews('test title', 'test content', 'http://demo/test.jpg', 1, 2, 3);
    }

    //=========================================================================
    //        Publish news when team not belong to user
    //=========================================================================
    function it_rejects_request_for_publish_because_team_not_belong_to_user(NewsRepository $newsRepository,
                                                                    ActivityRepository $activityRepository,
                                                                    UserRepository $userRepository,
                                                                    TeamRepository $teamRepository)
    {
        $activityRepository->findActivityById(1)->willReturn((new ActivityEntity)->setId(1));
        $userRepository->findById(3)->willReturn((new UserEntity)->setId(3));
        $teamRepository->findTeam(2, ['creator'])
            ->willReturn((new TeamEntity)->setId(2)
                                         ->setCreator((new UserEntity)->setId(4)));

        $this->shouldThrow(new \Jihe\Exceptions\AppException('社团信息不正确'))
            ->duringPublishNews('test title', 'test content', 'http://demo/test.jpg', 1, 2, 3);
    }

    //=========================================================================
    //        Publish news in case that user not exists
    //=========================================================================
    function it_rejects_request_for_publish_because_user_not_exists(NewsRepository $newsRepository,
                                                                   ActivityRepository $activityRepository,
                                                                   UserRepository $userRepository,
                                                                   TeamRepository $teamRepository)
    {
        $activityRepository->findActivityById(1)->willReturn((new ActivityEntity)->setId(1));
        $userRepository->findById(3)->willReturn(null);
        $teamRepository->findTeam(2, ['creator'])
            ->willReturn((new TeamEntity)->setId(2)
                                         ->setCreator((new UserEntity)->setId(3)));

        $this->shouldThrow(new \Jihe\Exceptions\AppException('用户信息不正确'))
            ->duringPublishNews('test title', 'test content', 'http://demo/test.jpg', 1, 2, 3);
    }

    //=========================================================================
    //        Get one normal page of news of the team and activity
    //=========================================================================
    function it_accepts_request_for_get_one_normal_page_of_news_of_the_team_and_activity(NewsRepository $newsRepository)
    {
        $testPageContent = array();
        for ($i =0; $i < 15; $i++) {
            $testPageContent[] = (new NewsEntity())->setId($i);
        }
        $testPage = new LengthAwarePaginator($testPageContent, 100, 15, 1);
        $newsRepository->findTeamNews(2, 1, 1, 15)->willReturn($testPage);

        $result = $this->getTeamNews(2, 1);
        $result->shouldBeAnInstanceOf('Illuminate\Contracts\Pagination\LengthAwarePaginator');
        $result->items()->shouldHaveCount(15);
        $result->total()->shouldBe(100);
        $result->lastPage()->shouldBe(7);
    }

    //=========================================================================
    //        Get one page of news of the team
    //=========================================================================
    function it_accepts_request_for_get_one_page_of_news_of_the_team(NewsRepository $newsRepository)
    {
        $testPage = new LengthAwarePaginator(array(), 0, 10, 1);
        $newsRepository->findTeamNews(2, null, 2, 10)->willReturn($testPage);

        $result = $this->getTeamNews(2, null, 2, 10);
        $result->shouldBeAnInstanceOf('Illuminate\Contracts\Pagination\LengthAwarePaginator');
        $result->items()->shouldHaveCount(0);
        $result->total()->shouldBe(0);
        $result->lastPage()->shouldBe(0);
        $result->currentPage()->shouldBe(1);
    }

    //=========================================================================
    //        Get exists news
    //=========================================================================
    function it_accepts_request_for_get_exists_news(NewsRepository $newsRepository)
    {
        $news = (new NewsEntity())->setId(5);
        $newsRepository->findNewsById(5)->willReturn($news);

        $this->getNewsDetail(5)->shouldBeEqualTo($news);
    }

    //=========================================================================
    //        Get not exists news
    //=========================================================================
    function it_accepts_request_for_get_not_exists_news(NewsRepository $newsRepository)
    {
        $newsRepository->findNewsById(5)->willReturn(null);
        $this->shouldThrow(new \Jihe\Exceptions\News\NewsNotExistException())
             ->duringGetNewsDetail(5);
    }

    //=========================================================================
    //        Update not exists news
    //=========================================================================
    function it_rejects_request_for_update_not_exists_news_with_no_picture(NewsRepository $newsRepository)
    {
        $newsRepository->findNewsById(5, ['team'])->willReturn(null);
        $this->shouldThrow(new \Jihe\Exceptions\News\NewsNotExistException())
             ->duringUpdateNews(5, 'test title', 'test content', null, 3);
    }

    //=========================================================================
    //        Update exists news
    //=========================================================================
    function it_accepts_request_for_update_exists_news_with_changed_picture(NewsRepository $newsRepository,
                                                                            TeamRepository $teamRepository,
                                                                            StorageService $storageService)
    {

        $testTmpImagePath = 'http://dev.image.jhla.com.cn/tmp/20150803/20150803162452765480.jpg';
        $testNoneTmpImagePath = 'http://dev.image.jhla.com.cn/20150804/20150804111654785610.jpg';
        $testOldImagePath = 'http://dev.image.jhla.com.cn/20150804/20150804111654785612.jpg';
        $contentTemplate = '<p><img src="%s" title="" alt="sdf.jpg"/>asdfasdfasdf sdf' .
            '<p><img src="http://dev.image.jhla.com.cn/20150804/20150804111654785611.jpg" ' .
            'title="" alt="sdfsdf.jpg"/>asdfasdfasdf sdf' .
            '<a href="http://baidu.com" target="_self">adfadsfasdf</a>' .
            '<embed type="application/x-shockwave-flash" class="edui-faked-music" ' .
            'pluginspage="http://www.macromedia.com/go/getflashplayer" ' .
            'src="http://box.baidu.com/widget/flash/bdspacesong.swf width="400" height="95" align="none"' .
            ' wmode="transparent" play="true" loop="false" menu="false" allowscriptaccess="never" ' .
            'allowfullscreen="true"/><img src="http://img.baidu.com/hi/jx2/j_0015.gif"/>' .
            '<img style="vertical-align: middle; margin-right: 2px;" ' .
            'src="http://ueditor.baidu.com/ueditor/dialogs/attachment/fileTypeImages/icon_jpg.gif"/>' .
            '<a style="font-size:12px; color:#0066cc;" ' .
            'href="http://dev.image.jhla.com.cn/tmp/20150803/sdfsaf.jpg" '.
            'title="demo.jpg">demo.jpg</a></p>';
        $newTmpContent = sprintf($contentTemplate, $testTmpImagePath);
        $newContent = sprintf($contentTemplate, $testNoneTmpImagePath);
        $oldContent = sprintf($contentTemplate, $testOldImagePath);

        $existingNews = (new NewsEntity)->setId(5)
                                        ->setContent($oldContent)
                                        ->setTeam((new TeamEntity)->setId(2)
                                            ->setCreator((new UserEntity)->setId(3)));
        $newsRepository->findNewsById(5, ['team'])->willReturn($existingNews);
        $storageService->remove($testOldImagePath)->shouldBeCalledTimes(1);
        $storageService->isTmp($testTmpImagePath)->willReturn(true);
        $storageService->isTmp(Argument::cetera())->willReturn(false);
        $storageService->storeAsImage($testTmpImagePath)->willReturn($testNoneTmpImagePath);
        $newsRepository->update(Argument::that(function (NewsEntity $news) use ($newContent) {
            return $this->isNewsEqual(5, 'test title', $newContent, null, 2, null, $news);
        }))->willReturn(true);

        $this->updateNews(5, 'test title', $newTmpContent, null, 3)
            ->shouldBeEqualTo(true);
    }

    //=========================================================================
    //        Update news unauthorized
    //=========================================================================
    function it_rejects_request_for_update_unauthorized_news(NewsRepository $newsRepository, TeamRepository $teamRepository)
    {
        $beforeNews = (new NewsEntity())->setId(5)
                                        ->setTeam((new TeamEntity())
                                            ->setId(2)
                                            ->setCreator((new UserEntity())->setId(4)));
        $newsRepository->findNewsById(5, ['team'])->willReturn($beforeNews);
        $this->shouldThrow(new \Jihe\Exceptions\News\NewsNotExistException())
             ->duringUpdateNews(5, 'test title', 'test content', null, 3);
    }

    //=========================================================================
    //        Delete exists news
    //=========================================================================
    function it_accepts_request_for_delete_exists_news(NewsRepository $newsRepository, TeamRepository $teamRepository)
    {
        $beforeNews = (new NewsEntity())->setId(5)
                                        ->setTeam((new TeamEntity())
                                            ->setId(2)
                                            ->setCreator((new UserEntity())->setId(3)));
        $newsRepository->findNewsById(5, ['team'])->willReturn($beforeNews);
        $newsRepository->delete(5)->willReturn(true);

        $this->deleteNews(5, 3)->shouldBeEqualTo(true);
    }

    //=========================================================================
    //        Delete not exists news
    //=========================================================================
    function it_rejects_request_for_delete_not_exists_news(NewsRepository $newsRepository)
    {
        $newsRepository->findNewsById(5, ['team'])->willReturn(null);
        $this->shouldThrow(new \Jihe\Exceptions\News\NewsNotExistException())
             ->duringDeleteNews(5, 3);
    }

    //=========================================================================
    //        Delete news unauthorized
    //=========================================================================
    function it_rejects_request_for_delete_unauthorized_news(NewsRepository $newsRepository, TeamRepository $teamRepository)
    {
        $deletedNews = (new NewsEntity())->setId(5)
                                         ->setTeam((new TeamEntity())
                                             ->setId(2)
                                             ->setCreator((new UserEntity())->setId(4)));
        $newsRepository->findNewsById(5, ['team'])->willReturn($deletedNews);
        $this->shouldThrow(new \Jihe\Exceptions\News\NewsNotExistException())
             ->duringDeleteNews(5, 3);
    }

    private function isNewsEqual($expectedId, $expectedTitle, $expectedContent,
                                 $expectedActivityId, $expectedTeamId, $expectedPublisherId,
                                 NewsEntity $news)
    {
        return $news->getId() == $expectedId &&
               $news->getTitle() == $expectedTitle &&
               $news->getContent() == $expectedContent &&
               $this->isActivityEqual($expectedActivityId, $news->getActivity()) &&
               $this->isPublisherEqual($expectedPublisherId, $news->getPublisher()) &&
               $this->isTeamEqual($expectedTeamId, $news->getTeam());
    }

    private function isActivityEqual($expectedId, ActivityEntity $activity)
    {
        if ($activity == null) {
            return is_null($expectedId);
        }

        return $activity->getId() == $expectedId;
    }

    private function isPublisherEqual($expectedId, UserEntity $publisher)
    {
        if ($publisher == null) {
            return is_null($expectedId);
        }

        return $publisher->getId() == $expectedId;
    }

    private function isTeamEqual($expectedId, TeamEntity $team)
    {
        if ($team == null) {
            return is_null($expectedId);
        }

        return $team->getId() == $expectedId;
    }
}
