<?php
namespace intg\Jihe\Repositories;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use intg\Jihe\TestCase;
use Jihe\Models\GaoXinVote as Vote;
use Jihe\Repositories\GaoXinVoteRepository as VoteRepository;

class GaoXinVoteRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    //===========================================
    //            add
    //===========================================
    public function testAdd()
    {
        $ret = [
            'voter' => '12345678912',
            'type' => Vote::TYPE_APP,
            'user_id' => 40988,
        ];
        $insertID = $this->getRepository()->add($ret);
        self::assertNotEquals(0, $insertID);
        self::seeInDatabase('gaoxin_votes', ['voter' => '12345678912', 'user_id' => 40988]);
    }

    //===========================================
    //            findAllVoteCount
    //===========================================
    public function testFindAllVoteCount()
    {
        $this->createData();
        $result = $this->getRepository()->findAllVoteCount();
        self::assertEquals(6, $result);
    }

    //===========================================
    //            findTodayUserVotes
    //===========================================
    public function testFindTodayUserVotes()
    {
        $this->createData();
        $result = $this->getRepository()->findTodayVoteCountByVoter('12345678912');
        self::assertEquals(6, $result);
    }

    //===========================================
    //            findUserVoteCount
    //===========================================
    public function testFindUserVoteCount()
    {
        $this->createData();
        $result = $this->getRepository()->findUserVoteCount([48888, 48887, 48886]);
        self::assertEquals(4, $result[48888]);
        self::assertEquals(1, $result[48887]);
        self::assertEquals(0, $result[48886]);
    }

    //===========================================
    //            findVoteCountSort
    //===========================================
    public function testFindVoteCountSort()
    {
        $this->createData();
        $result = $this->getRepository()->findVoteCountSort();
        self::assertEquals(3, count($result));
        self::assertEquals(4, $result[48888]['count']);
        self::assertEquals(1, $result[48887]['count']);
        self::assertEquals(1, $result[48889]['count']);
    }

    //===========================================
    //            findUserVoteSort
    //===========================================
    public function testFindUserVoteSort()
    {
        $this->createData();
        $result = $this->getRepository()->findUserVoteSort(48888);
        self::assertEquals(3, count($result));
        self::assertEquals(48888, $result['user']);
        self::assertEquals(4, $result['count']);
        self::assertEquals(1, $result['sort']);
    }

    //===========================================
    //            findAllUserVteCount
    //===========================================
    public function testFindAllUserVteCount()
    {
        $this->createData();
        $result = $this->getRepository()->findAllUserVteCount();
        self::assertEquals(4, $result[48888]);
        self::assertEquals(1, $result[48887]);
        self::assertEquals(1, $result[48889]);
    }

    //===========================================
    //            findUserSort
    //===========================================
    public function testFindUserSort()
    {
        $this->createData();
        $result = $this->getRepository()->findUserSort(48887);
        self::assertEquals(2, $result);
        $result = $this->getRepository()->findUserSort(48888);
        self::assertEquals(1, $result);
    }

    private function createData()
    {
        factory(Vote::class)->create([
            'id'   => 1,
            'voter' => '12345678912',
            'type' => Vote::TYPE_APP,
            'user_id' => 48888,
        ]);
        factory(Vote::class)->create([
            'id'   => 2,
            'voter' => '12345678912',
            'type' => Vote::TYPE_APP,
            'user_id' => 48888,
        ]);
        factory(Vote::class)->create([
            'id'   => 3,
            'voter' => '12345678912',
            'type' => Vote::TYPE_APP,
            'user_id' => 48888,
        ]);
        factory(Vote::class)->create([
            'id'   => 4,
            'voter' => '12345678912',
            'type' => Vote::TYPE_APP,
            'user_id' => 48888,
        ]);
        factory(Vote::class)->create([
            'id'   => 5,
            'voter' => '12345678912',
            'type' => Vote::TYPE_APP,
            'user_id' => 48889,
        ]);
        factory(Vote::class)->create([
            'id'   => 6,
            'voter' => '12345678912',
            'type' => Vote::TYPE_WX,
            'user_id' => 48887,
        ]);

    }
    /**
     * @return \Jihe\Repositories\VoteRepository
     */
    private function getRepository()
    {
        return $this->app[VoteRepository::class];
    }
}
