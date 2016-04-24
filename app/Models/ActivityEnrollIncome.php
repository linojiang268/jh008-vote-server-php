<?php

namespace Jihe\Models;

use DateTime;
use Illuminate\Database\Eloquent\Model;
use Jihe\Entities\ActivityEnrollIncome as ActivityEnrollIncomeEntity;
use Jihe\Entities\Activity as ActivityEntity;
use Jihe\Entities\Team as TeamEntity;

class ActivityEnrollIncome extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'activity_enroll_incomes';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'team_id',
        'activity_id',
        'total_fee',
        'transfered_fee',
        'enroll_end_time',
        'financial_action_result',
        'status',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['enroll_end_time', 'created_at', 'updated_at'];

    public function team()
    {
        return $this->belongsTo(\Jihe\Models\Team::class, 'team_id', 'id');
    }

    public function activity()
    {
        return $this->belongsTo(\Jihe\Models\Activity::class, 'activity_id', 'id');
    }

    public function getFinancialActionResultAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * @param array|null    $financialActionResult
     */
    public function setFinancialActionResultAttribute($financialActionResult)
    {
        if ($financialActionResult === null) {
            $financialActionResult = [];
        }
        foreach ($financialActionResult as $item) {
            if ( ! $this->checkFinancialActionResult($item)) {
                throw new \Exception('field financial_action_result format error');
            }
        }
        $this->attributes['financial_action_result'] = json_encode($financialActionResult);
    }

    public function addFinancialActionResult(array $value)
    {
        if ( ! $this->checkFinancialActionResult($value)) {
            throw new \Exception('field financial_action_result format error');
        }

        $old = $this->financial_action_result;
        $old[] = $value;
        $this->financial_action_result = $old;
        return $this;
    }

    public function toEntity()
    {
        $entity = (new ActivityEnrollIncomeEntity())
            ->setId($this->id)
            ->setTotalFee($this->total_fee)
            ->setTransferedFee($this->transfered_fee)
            ->setEnrollEndTime($this->enroll_end_time)
            ->setFinancialActionResult($this->financial_action_result)
            ->setStatus($this->status);

        if ($this->relationLoaded('activity')) {
            $entity->setActivity($this->activity->toEntity()); 
        } else {
            $entity->setActivity((new ActivityEntity())->setId($this->activity_id));
        }

        if ($this->relationLoaded('team')) {
            $entity->setTeam($this->team->toEntity());
        } else {
            $entity->setTeam((new TeamEntity())->setId($this->team_id));
        }

        return $entity;
    }

    private function checkFinancialActionResult(array $item)
    {
        if (count($item) != 3) {
            return false;
        }
        list($confirmTimestamp, $fee, $evidenceUrl) = $item;
        if ( ! is_int($fee) || $fee < 0) {
            return false;
        }
        if ( ! is_string($evidenceUrl)) {
            return false;
        }
        if ( ! is_int($confirmTimestamp) &&
            strtotime(date("Y-m-d H:i:s", $confirmTimestamp)) != $confirmTimestamp) {
            return false;
        }

        return true;
    }
}
