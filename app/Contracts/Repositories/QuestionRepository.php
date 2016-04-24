<?php
namespace Jihe\Contracts\Repositories;

interface QuestionRepository
{
    /**
     * add question
     *
     * @return int  Question id
     */
    public function add($question);
    
    /**
     * find activity question
     *
     * @return array  array of \Jihe\Entities\Question
     */
    public function findActivityQuestions($activityId);

    /**
     * find team question
     *
     * @return array  array of \Jihe\Entities\Question
     */
    public function findTeamQuestions($teamId);

}
