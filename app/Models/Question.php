<?php
namespace Jihe\Models;

use Illuminate\Database\Eloquent\Model;
use Jihe\Entities\Question as QuestionEntity;
use Jihe\Entities\QuestionType as QuestionTypeEntity;

class Question extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'questions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['content', 'type', 'source', 'relate_id', 'pid'];

    public function QuestionType() {
        return $this->belongsTo(QuestionType::class, 'type', 'id');
    }

    public function toEntity() {
        $question = (new QuestionEntity())
            ->setId($this->id)
            ->setContent($this->content)
            ->setSource($this->source)
            ->setRelateId($this->relate_id)
            ->setPid($this->pid);

        if ($this->relationLoaded('questionType')) {
            $question->setType($this->questionType->toEntity());
        } else {
            $question->setType((new QuestionTypeEntity())->setId($this->type));
        }

        return $question;
    }
}
