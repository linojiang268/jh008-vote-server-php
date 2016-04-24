<?php

namespace Jihe\Listeners;

use Jihe\Events\WechatOauthDoneEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Jihe\Services\WechatService;

class WechatOauthDoneEventListener
{
    /**
     * Create the event listener.
     *
     * @param \Jihe\Services\WechatService $wechatService
     *
     * @return void
     */
    public function __construct(
        WechatService $wechatService
    ) {
        $this->wechatService = $wechatService;
    }

    /**
     * Handle the event.
     *
     * @param  WechatOauthDoneEvent  $event
     * @return void
     */
    public function handle(WechatOauthDoneEvent $event)
    {
        $user = $this->wechatService->getUser($event->openid);
        if ($user) {
            return null;
        }

        try {
            $this->wechatService->createOrUpdateUserUsingWebToken($event->openid);
        } catch (\Exception $ex) {
            //
            var_dump($ex->getMessage());
        }
    }
}
