<?php
namespace Jihe\Models;

use Illuminate\Database\Eloquent\Model;
use Jihe\Entities\Activity as ActivityEntity;
use Jihe\Entities\Team as TeamEntity;
use Jihe\Entities\City as CityEntity;

class Activity extends Model
{
    use Traits\GeometryTrait;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'activities';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'city_id',
        'team_id',
        'title',
        'qr_code_url',
        'begin_time',
        'end_time',
        'contact',
        'telephone',
        'cover_url',
        'images_url',
        'address',
        'brief_address',
        'detail',
        'enroll_begin_time',
        'enroll_end_time',
        'enroll_type',
        'enroll_limit',
        'enroll_fee_type',
        'enroll_fee',
        'enroll_attrs',
        'status',
        'location',
        'roadmap',
        'publish_time',
        'update_step',
        'auditing',
        'essence',
        'top',
        'has_album',
        'tags',
        'organizers',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];
    protected $geofields = ['location' => 'point', 'roadmap' => 'linestring'];

    public function team() {
        return $this->belongsTo(\Jihe\Models\Team::class, 'team_id', 'id');
    }

    public function city() {
        return $this->belongsTo(\Jihe\Models\City::class, 'city_id', 'id');
    }

    public function checkInQRCode()
    {
        return $this->hasMany(\Jihe\Models\ActivityCheckInQRCode::class, 'activity_id', 'id');
    }

    public function toEntity() {
        $activity = (new ActivityEntity())
                ->setId($this->id)
                ->setTitle($this->title)
                ->setQrCodeUrl($this->qr_code_url)
                ->setBeginTime($this->begin_time)
                ->setEndTime($this->end_time)
                ->setContact($this->contact)
                ->setTelephone($this->telephone)
                ->setCoverUrl($this->cover_url)
                ->setImagesUrl(json_decode($this->images_url, true))
                ->setAddress($this->address)
                ->setBriefAddress($this->brief_address)
                ->setDetail($this->detail)
                ->setEnrollBeginTime($this->enroll_begin_time)
                ->setEnrollEndTime($this->enroll_end_time)
                ->setEnrollType($this->enroll_type)
                ->setEnrollLimit($this->enroll_limit)
                ->setEnrollFeeType($this->enroll_fee_type)
                ->setEnrollFee($this->enroll_fee)
                ->setEnrollAttrs(json_decode($this->enroll_attrs, true))
                ->setStatus($this->status)
                ->setLocation($this->location)
                ->setRoadmap($this->roadmap)
                ->setPublishTime($this->publish_time)
                ->setAuditing($this->auditing)
                ->setUpdateStep($this->update_step)
                ->setEssence($this->essence)
                ->setTop($this->top)
                ->setHasAlbum($this->has_album)
                ->setApplicantsStatus($this->applicants_status)
                ->setTags($this->tags)
                ->setOrganizers(json_decode($this->organizers, true));

        if ($this->relationLoaded('city')) {
            $activity->setCity($this->city->toEntity());
        } else {
            $activity->setCity((new CityEntity())->setId($this->city_id));
        }

        if ($this->relationLoaded('team')) {
            $activity->setTeam($this->team->toEntity());
        } else {
            $activity->setTeam((new TeamEntity())->setId($this->team_id));
        }

        return $activity;
    }

}
