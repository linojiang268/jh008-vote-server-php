<?php

namespace Jihe\Events;

use Jihe\Events\Event;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Jihe\Models\ActivityApplicant;

class UserApplicantActivityEvent extends Event
{
    use SerializesModels;

    /**
     * @var integer
     */
    public $applicant;

    /**
     * Create a new event instance.
     *
     * @param integer $applicant
     *
     * @return void
     */
    public function __construct($applicant)
    {
        $this->applicant = $applicant;
    }

    /**
     * Get the channels the event should be broadcast on.
     *
     * @return array
     */
    public function broadcastOn()
    {
        return [];
    }
}
