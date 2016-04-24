<?php
namespace intg\Jihe\Controllers\Backstage;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Jihe\Models\City;
use Jihe\Models\News;
use Jihe\Models\Team;
use Jihe\Models\User;
use intg\Jihe\TestCase;
use intg\Jihe\RequestSignCheck;
use intg\Jihe\AuthDeviceCheck;

class NewsControllerTest extends TestCase
{
    use DatabaseTransactions;
    use RequestSignCheck;
    use AuthDeviceCheck;

    //=========================================
    //          GetTeamNews
    //=========================================
    public function testGetTeamNews_NoNewsAtAll()
    {
        $user = factory(User::class)->create();
        factory(City::class)->create([
            'id' => 1
        ]);
        factory(Team::class)->create([
            'id' => 1,
            'city_id' => 1,
            'creator_id' => $user->id
        ]);

        $this->actingAs($user)->ajaxGet('/community/news')
            ->seeJsonContains([ 'code' => 0, 'pages' => 0, 'news' => [] ]);
    }

    public function testGetTeamNews_NonExistingTeam()
    {
        $user = factory(User::class)->create();
        factory(City::class)->create([
            'id' => 1
        ]);
        factory(Team::class)->create([
            'id' => 1,
            'city_id' => 1,
            'creator_id' => $user->id
        ]);
        $this->actingAs($user)->ajaxGet('/community/news')
            ->seeJsonContains([ 'code' => 0, 'pages' => 0, 'news' => [] ]);
    }

    public function testGetTeamNews_WithSomeNews()
    {
        $user = factory(User::class)->create();
        factory(City::class)->create([
            'id' => 1
        ]);
        factory(Team::class)->create([
            'id' => 1,
            'city_id' => 1,
            'creator_id' => $user->id
        ]);
        factory(\Jihe\Models\News::class)->create([
            'id'           => 1,
            'title'        => 'first news',
            'content'      => 'this is the first news',
            'team_id'      => 1,
            'activity_id'  => null,
            'created_at'   => '2015-07-21 10:20:02',
        ]);
        factory(\Jihe\Models\News::class)->create([
            'id'           => 2,
            'title'        => 'second news',
            'content'      => 'this is the second news',
            'team_id'      => 1,
            'activity_id'  => 1,
            'created_at'   => '2015-07-18 08:10:32',
        ]);
        $this->actingAs($user)->ajaxGet('/community/news');

        $response = json_decode($this->response->getContent());
        self::assertEquals(0, $response->code);
        self::assertEquals(1, $response->pages);
        self::assertCount(2, $response->news);

        self::assertEquals(1, $response->news[0]->id);
        self::assertEquals(2, $response->news[1]->id);


        $this->actingAs($user)->ajaxGet('/community/news?activity_id=-1');

        $response = json_decode($this->response->getContent());
        self::assertEquals(0, $response->code);
        self::assertEquals(1, $response->pages);
        self::assertCount(1, $response->news);

        self::assertEquals(1, $response->news[0]->id);

        $this->actingAs($user)->ajaxGet('/community/news?activity_id=0');

        $response = json_decode($this->response->getContent());
        self::assertEquals(0, $response->code);
        self::assertEquals(1, $response->pages);
        self::assertCount(1, $response->news);

        self::assertEquals(2, $response->news[0]->id);


        $this->actingAs($user)->ajaxGet('/community/news?activity_id=1');

        $response = json_decode($this->response->getContent());
        self::assertEquals(0, $response->code);
        self::assertEquals(1, $response->pages);
        self::assertCount(1, $response->news);

        self::assertEquals(2, $response->news[0]->id);
    }

    //=========================================
    //           Get News Detail
    //=========================================
    public function testGetNewsDetail()
    {
        $user = factory(User::class)->create();
        factory(City::class)->create([
            'id' => 1
        ]);
        factory(Team::class)->create([
            'id' => 1,
            'city_id' => 1,
            'creator_id' => $user->id
        ]);
        factory(News::class)->create([
            'id'      => 1,
            'team_id' => 1
        ]);

//        $this->actingAs($user)->get('/community/news/1');
//
//        self::assertViewHas('key', 'news');
//
//        $news = $this->response->original->news;
//        self::assertEquals(1, $news->getId());
    }

    public function testGetNewsDetail_BadNewsId()
    {
        $user = factory(User::class)->create();
        factory(City::class)->create([
            'id' => 1
        ]);
        factory(Team::class)->create([
            'id' => 1,
            'city_id' => 1,
            'creator_id' => $user->id
        ]);
        factory(News::class)->create([
            'id'      => 1,
            'team_id' => 1
        ]);

        $this->actingAs($user)->get('/community/news/a')
             ->seeStatusCode(404);
    }

    //=========================================
    //           publishNews
    //=========================================
    public function testSuccessfulPublishToTeam()
    {
        $user = factory(User::class)->create();
        factory(City::class)->create([
            'id' => 1
        ]);
        factory(Team::class)->create([
            'id' => 1,
            'city_id' => 1,
            'creator_id' => $user->id
        ]);
        $this->startSession();

        $this->actingAs($user)->ajaxPost('/community/news', [
            '_token'    => csrf_token(),
            'title'     => 'test title',
            'content'   => 'test content',
            'cover_url' => 'http://test.com/cover.jpg',
        ]);
        $this->seeJsonContains([ 'code' => 0 ]);
    }

    //=========================================
    //           Update News
    //=========================================
    public function testUpdateNews()
    {
        $user = factory(User::class)->create();
        factory(City::class)->create([
            'id' => 1
        ]);
        factory(Team::class)->create([
            'id' => 1,
            'city_id' => 1,
            'creator_id' => $user->id
        ]);
        factory(News::class)->create([
            'id' => 1,
            'team_id' => 1
        ]);
        $this->mockStorageService();
        $this->startSession();

        $this->actingAs($user)->put('/community/news/1', [
            '_token'    => csrf_token(),
            'title'     => 'test title',
            'content'   => 'test content',
            'cover_url' => 'http://test.com/cover.jpg',
        ]);
        $this->seeJsonContains([ 'code' => 0 ]);
    }

    //=========================================
    //           requestForDeleteNews
    //=========================================
    public function testSuccessfulRequestForDeleteNews()
    {
        $user = factory(User::class)->create();
        factory(City::class)->create([
            'id' => 1
        ]);
        factory(Team::class)->create([
            'id' => 1,
            'city_id' => 1,
            'creator_id' => $user->id
        ]);
        factory(News::class)->create([
            'id' => 1,
            'team_id' => 1
        ]);
        $this->startSession();

        $this->actingAs($user)->delete('/community/news/1', [
            '_token'    => csrf_token()
        ]);
        $this->seeJsonContains([ 'code' => 0 ]);
    }

    private function mockStorageService($return = 'http://domain/tmp/key.png')
    {
        $storageService = \Mockery::mock(\Jihe\Services\StorageService::class);
        $storageService->shouldReceive('isTmp')->withAnyArgs()->andReturn(true);
        $storageService->shouldReceive('storeAsFile')->withAnyArgs()->andReturn($return);
        $storageService->shouldReceive('storeAsImage')->withAnyArgs()->andReturn($return);
        $storageService->shouldReceive('remove')->withAnyArgs()->andReturn(false);
        $this->app [\Jihe\Services\StorageService::class] = $storageService;

        return $storageService;
    }
}
