<?php

namespace Jihe\Events;

use Jihe\Events\Event;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class WechatOauthDoneEvent extends Event
{
    use SerializesModels;

    /**
     * @var string
     */
    public $openid;

    /**
     * Create a new event instance.
     *
     * @param string $openid
     *
     * @return void
     */
    public function __construct($openid)
    {
        $this->openid = $openid;
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
