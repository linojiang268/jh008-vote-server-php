<?php
namespace Jihe\Repositories;

use \Jihe\Contracts\Repositories\QuestionRepository as QuestionRepositoryContract;
use Jihe\Entities\Question as QuestionEntity;
use Jihe\Models\Question;

class QuestionRepository implements QuestionRepositoryContract
{
    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Repositories\QuestionRepository::add()
     */
    public function add($question)
    {
        if ($question == null) {
            return 0;
        }
        return Question::create($question)->id;
    }

    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Repositories\QuestionRepository::findActivityQuestions()
     */
    public function findActivityQuestions($activityId)
    {
        $questions= $this->findQuestionsData($activityId, QuestionEntity::SOURCE_ACTIVITY);
        $questions = $this->makeQuestions($questions);
        return $questions;
    }

    /**
     * {@inheritdoc}
     * @see \Jihe\Contracts\Repositories\QuestionRepository::findTeamQuestions()
     */
    public function findTeamQuestions($teamId)
    {
        $questions= $this->findQuestionsData($teamId, QuestionEntity::SOURCE_TEAM);
        $questions = $this->makeQuestions($questions);
        return $questions;
    }

    /**
     * @param int $relateId
     * @param int $source
     *
     * @return array
     */
    private function findQuestionsData($relateId, $source)
    {
        $questions = Question::with('questionType')
            ->where('relate_id', $relateId)
            ->where('source', $source)
            ->orderBy('pid', 'asc')
            ->orderBy('relate_id', 'asc')
            ->get()
            ->all();
        $questions = array_map([$this, 'convertToEntity'], $questions);

        return $questions;
    }

    /**
     * @param array $questions
     *
     * @return array
     */
    private function makeQuestions($questions)
    {
        if(!empty($questions)){
            $tmp = [];
            foreach($questions as $question){
                if( $question->getPid() == 0){
                    $tmp[$question->getId()]['title'] = $question;
                }else{
                    $tmp[$question->getPId()]['options'][] = $question;
                }
            }
            $questions = array_values($tmp);
        }

        return $questions;
    }

    /**
     * {@inheritdoc}
     * @return QuestionEntity | null
     */
    private function convertToEntity(Question $question)
    {
        if ($question == null) {
            return null;
        }
        return $question->toEntity();
    }
}
