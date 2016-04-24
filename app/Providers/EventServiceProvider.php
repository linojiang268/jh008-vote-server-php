<?php
namespace Jihe\Providers;

use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        \Jihe\Events\UserApplicantActivityEvent::class => [
            \Jihe\Listeners\UserApplicantActivityEventListener::class,
        ],
        \Jihe\Events\WechatOauthDoneEvent::class => [
            \Jihe\Listeners\WechatOauthDoneEventListener::class,
        ],
    ];

    /**
     * Register any other events for your application.
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @return void
     */
    public function boot(DispatcherContract $events)
    {
        parent::boot($events);

        //
    }
}
