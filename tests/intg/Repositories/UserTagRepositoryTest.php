<?php
namespace intg\Jihe\Repositories;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use intg\Jihe\TestCase;

class UserTagRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    //==============================
    //      findAll
    //==============================
    public function testFindAllFound()
    {
        factory(\Jihe\Models\UserTag::class)->create([
            'id'    => 1,
            'name'  => 'sports',
        ]);
        factory(\Jihe\Models\UserTag::class)->create([
            'id'    => 2,
            'name'  => 'culture & art',
        ]);

        $tags = $this->getRepository()->findAll();
        self::assertCount(2, $tags);
        self::assertEquals(1, $tags[0]->getId());
        self::assertEquals('sports', $tags[0]->getName());
        self::assertEquals(2, $tags[1]->getId());
        self::assertEquals('culture & art', $tags[1]->getName());
    }

    /**
     * @return \Jihe\Contracts\Repositories\UserTagRepository
     */
    private function getRepository()
    {
        return $this->app[\Jihe\Contracts\Repositories\UserTagRepository::class];
    }
}
