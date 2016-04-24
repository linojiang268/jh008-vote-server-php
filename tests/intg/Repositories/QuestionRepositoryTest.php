<?php
namespace intg\Jihe\Repositories;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use intg\Jihe\TestCase;
use Jihe\Models\Activity;
use Jihe\Models\City;
use Jihe\Models\Question;
use Jihe\Models\Team;

class QuestionRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    //==============================
    //            add
    //==============================
    public function testAdd()
    {
        $id = $this->getRepository()->add([
            'content'   => '填空',
            'type'      => 1,
            'source'    => 1,
            'relate_id' => 1,
            'pid'       => 0,
        ]);
        self::assertGreaterThan(0, $id);

        $this->seeInDatabase('questions', [
            'content'   => '填空',
            'type'      => 1,
            'source'    => 1,
            'relate_id' => 1,
            'pid'       => 0,
        ]);
    }

//    //==============================
//    //            findActivityQuestions
//    //==============================
//    public function testFindActivityQuestions()
//    {
//        $this->createData();
//        $questions = $this->getRepository()->findActivityQuestions(1);
//        self::assertEquals(1, $questions[0]['title']->getId());
//        self::assertEquals(2, $questions[1]['title']->getId());
//        self::assertCount(2, $questions[1]['options']);
//        self::assertEquals(5, $questions[2]['title']->getId());
//        self::assertCount(4, $questions[2]['options']);
//    }
//
//    //==============================
//    //            findTeamQuestions
//    //==============================
//    public function testFindTeamQuestions()
//    {
//        $this->createData();
//        $questions = $this->getRepository()->findTeamQuestions(1);
//        self::assertEquals(10, $questions[0]['title']->getId());
//        self::assertEquals(11, $questions[1]['title']->getId());
//        self::assertCount(2, $questions[1]['options']);
//        self::assertEquals(14, $questions[2]['title']->getId());
//        self::assertCount(4, $questions[2]['options']);
//    }
//
//    private function createData()
//    {
//        factory(City::class)->create([
//            'id'   => 1,
//            'name' => '成都',
//        ]);
//
//        factory(Team::class)->create([
//            'id'     => 1,
//            'status' => \Jihe\Entities\Team::STATUS_NORMAL,
//        ]);
//
//        factory(Activity::class)->create([
//            'team_id' => 1,
//            'city_id' => 1,
//            'title'   => '自行车',
//            'status'  => \Jihe\Entities\Activity::STATUS_PUBLISHED,
//        ]);
//
//        factory(Question::class)->create([
//            'id'        => 1,
//            'content'   => 'activity填空',
//            'type'      => \Jihe\Entities\Question::QUESTION_TYPE_TEXT,
//            'source'    => \Jihe\Entities\Question::SOURCE_ACTIVITY,
//            'relate_id' => 1,
//            'pid'       => 0,
//        ]);
//
//        factory(Question::class)->create([
//            'id'        => 2,
//            'content'   => 'activity单选',
//            'type'      => \Jihe\Entities\Question::QUESTION_TYPE_RADIO,
//            'source'    => \Jihe\Entities\Question::SOURCE_ACTIVITY,
//            'relate_id' => 1,
//            'pid'       => 0,
//        ]);
//        factory(Question::class)->create([
//            'id'        => 3,
//            'content'   => 'activity单选答案一',
//            'type'      => \Jihe\Entities\Question::QUESTION_TYPE_RADIO,
//            'source'    => \Jihe\Entities\Question::SOURCE_ACTIVITY,
//            'relate_id' => 1,
//            'pid'       => 2,
//        ]);
//        factory(Question::class)->create([
//            'id'        => 4,
//            'content'   => 'activity单选答案二',
//            'type'      => \Jihe\Entities\Question::QUESTION_TYPE_RADIO,
//            'source'    => \Jihe\Entities\Question::SOURCE_ACTIVITY,
//            'relate_id' => 1,
//            'pid'       => 2,
//        ]);
//        factory(Question::class)->create([
//            'id'        => 5,
//            'content'   => 'activity多选',
//            'type'      => \Jihe\Entities\Question::QUESTION_TYPE_CHECK,
//            'source'    => \Jihe\Entities\Question::SOURCE_ACTIVITY,
//            'relate_id' => 1,
//            'pid'       => 0,
//        ]);
//        factory(Question::class)->create([
//            'id'        => 6,
//            'content'   => 'activity多选答案一',
//            'type'      => \Jihe\Entities\Question::QUESTION_TYPE_CHECK,
//            'source'    => \Jihe\Entities\Question::SOURCE_ACTIVITY,
//            'relate_id' => 1,
//            'pid'       => 5,
//        ]);
//        factory(Question::class)->create([
//            'id'        => 7,
//            'content'   => 'activity多选答案二',
//            'type'      => \Jihe\Entities\Question::QUESTION_TYPE_CHECK,
//            'source'    => \Jihe\Entities\Question::SOURCE_ACTIVITY,
//            'relate_id' => 1,
//            'pid'       => 5,
//        ]);
//        factory(Question::class)->create([
//            'id'        => 8,
//            'content'   => 'activity多选答案三',
//            'type'      => \Jihe\Entities\Question::QUESTION_TYPE_CHECK,
//            'source'    => \Jihe\Entities\Question::SOURCE_ACTIVITY,
//            'relate_id' => 1,
//            'pid'       => 5,
//        ]);
//        factory(Question::class)->create([
//            'id'        => 9,
//            'content'   => 'activity多选答案四',
//            'type'      => \Jihe\Entities\Question::QUESTION_TYPE_CHECK,
//            'source'    => \Jihe\Entities\Question::SOURCE_ACTIVITY,
//            'relate_id' => 1,
//            'pid'       => 5,
//        ]);
//
//
//        factory(Question::class)->create([
//            'id'        => 10,
//            'content'   => 'team填空',
//            'type'      => \Jihe\Entities\Question::QUESTION_TYPE_TEXT,
//            'source'    => \Jihe\Entities\Question::SOURCE_TEAM,
//            'relate_id' => 1,
//            'pid'       => 0,
//        ]);
//
//        factory(Question::class)->create([
//            'id'        => 11,
//            'content'   => 'team单选',
//            'type'      => \Jihe\Entities\Question::QUESTION_TYPE_TEXT,
//            'source'    => \Jihe\Entities\Question::SOURCE_TEAM,
//            'relate_id' => 1,
//            'pid'       => 0,
//        ]);
//
//        factory(Question::class)->create([
//            'id'        => 12,
//            'content'   => 'team单选答案一',
//            'type'      => \Jihe\Entities\Question::QUESTION_TYPE_RADIO,
//            'source'    => \Jihe\Entities\Question::SOURCE_TEAM,
//            'relate_id' => 1,
//            'pid'       => 11,
//        ]);
//        factory(Question::class)->create([
//            'id'        => 13,
//            'content'   => 'team单选答案二',
//            'type'      => \Jihe\Entities\Question::QUESTION_TYPE_RADIO,
//            'source'    => \Jihe\Entities\Question::SOURCE_TEAM,
//            'relate_id' => 1,
//            'pid'       => 11,
//        ]);
//        factory(Question::class)->create([
//            'id'        => 14,
//            'content'   => 'team多选',
//            'type'      => \Jihe\Entities\Question::QUESTION_TYPE_CHECK,
//            'source'    => \Jihe\Entities\Question::SOURCE_TEAM,
//            'relate_id' => 1,
//            'pid'       => 0,
//        ]);
//        factory(Question::class)->create([
//            'id'        => 15,
//            'content'   => 'team多选答案一',
//            'type'      => \Jihe\Entities\Question::QUESTION_TYPE_CHECK,
//            'source'    => \Jihe\Entities\Question::SOURCE_TEAM,
//            'relate_id' => 1,
//            'pid'       => 14,
//        ]);
//        factory(Question::class)->create([
//            'id'        => 16,
//            'content'   => 'team多选答案二',
//            'type'      => \Jihe\Entities\Question::QUESTION_TYPE_CHECK,
//            'source'    => \Jihe\Entities\Question::SOURCE_TEAM,
//            'relate_id' => 1,
//            'pid'       => 14,
//        ]);
//        factory(Question::class)->create([
//            'id'        => 17,
//            'content'   => 'team多选答案三',
//            'type'      => \Jihe\Entities\Question::QUESTION_TYPE_CHECK,
//            'source'    => \Jihe\Entities\Question::SOURCE_TEAM,
//            'relate_id' => 1,
//            'pid'       => 14,
//        ]);
//        factory(Question::class)->create([
//            'id'        => 18,
//            'content'   => 'team多选答案四',
//            'type'      => \Jihe\Entities\Question::QUESTION_TYPE_CHECK,
//            'source'    => \Jihe\Entities\Question::SOURCE_TEAM,
//            'relate_id' => 1,
//            'pid'       => 14,
//        ]);
//
//    }
    /**
     * @return \Jihe\Contracts\Repositories\QuestionRepository
     */
    private function getRepository()
    {
        //return $this->app[\Jihe\Contracts\Repositories\QuestionRepository::class];
        return $this->app[\Jihe\Contracts\Repositories\QuestionRepository::class];
    }
}
