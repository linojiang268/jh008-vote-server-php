<?php
namespace intg\Jihe\Repositories;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use intg\Jihe\TestCase;
use Jihe\Models\News;
use Jihe\Models\User;
use Jihe\Models\City;
use Jihe\Models\NewsRequirement;
use Jihe\Entities\News as NewsEntity;
use Jihe\Entities\City as CityEntity;
use Jihe\Entities\User as UserEntity;
use Jihe\Entities\NewsRequirement as NewsRequirementEntity;

class NewsRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    //===========================================
    //       findTeamNews
    //===========================================
    public function testFindTeamNews_NoNews()
    {
        self::assertEquals(0, $this->getRepository()->findTeamNews(100000, null, 1, 15)->total());
    }

    //===========================================
    //       findTeamNews
    //===========================================
    public function testFindTeamNews_OneNewsOfTeam()
    {
        factory(News::class)->create([
            'team_id'   => 100000,
        ]);

        self::assertEquals(1, $this->getRepository()->findTeamNews(100000, null, 1, 15)->total());
    }

    //===========================================
    //       findAll
    //===========================================
    public function testFindAll_Exists()
    {
        factory(News::class)->create([
            'id'        => 1,
            'team_id'   => 100000,
        ]);
        $new = $this->getRepository()->findNewsById(1);
        $new->setClickNum(123);
        $this->getRepository()->update($new);
        $newses = $this->getRepository()->findAll();
        self::assertEquals(123, $newses[0]->getClickNum());
        self::assertCount(1, $newses);
    }

    /**
     * @return \Jihe\Contracts\Repositories\NewsRepository
     */
    private function getRepository()
    {
        return $this->app[\Jihe\Contracts\Repositories\NewsRepository::class];
    }
}
