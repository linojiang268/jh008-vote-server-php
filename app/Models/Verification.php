<?php
namespace Jihe\Models;

use Illuminate\Database\Eloquent\Model;
use Jihe\Entities\Verification as VerificationEntity;

class Verification extends Model
{
    protected $table = 'mobile_verifications';

    protected $fillable = ['mobile', 'code', 'expired_at'];

    public function toEntity()
    {
        return new VerificationEntity($this->id,
                                      $this->mobile,
                                      $this->code,
                                      $this->expired_at);
    }
}
