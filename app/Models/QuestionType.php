<?php
namespace Jihe\Models;

use Illuminate\Database\Eloquent\Model;
use Jihe\Entities\QuestionType as QuestionTypeEntity;

class QuestionType extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'question_type';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'status'];

    public function toEntity() {
        $questionType = (new QuestionTypeEntity())
            ->setId($this->id)
            ->setName($this->name)
            ->setStatus($this->status);

        return $questionType;
    }
}
