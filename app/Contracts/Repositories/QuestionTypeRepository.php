<?php
namespace Jihe\Contracts\Repositories;

interface QuestionTypeRepository
{
    /**
     * add question type
     *
     * @return int  QuestionType id
     */
    public function add($questionType);
    
    /**
     * find all question type
     *
     * @return array  array of \Jihe\Entities\QuestionType
     */
    public function findAll();

    /**
     * delete question type
     *
     * @param int $id question type id
     *
     * @return bool
     */
    public function delete($id);

}
