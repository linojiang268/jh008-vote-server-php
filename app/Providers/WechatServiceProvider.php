<?php
namespace Jihe\Providers;

use Illuminate\Support\ServiceProvider;

class WechatServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(
            \Jihe\Contracts\Repositories\WechatTokenRepository::class,
            \Jihe\Repositories\WechatTokenRepository::class
        );

        $this->app->singleton(
            \Jihe\Contracts\Repositories\WechatUserRepository::class,
            \Jihe\Repositories\WechatUserRepository::class
        );

        $this->app->singleton(\Jihe\Services\WechatService::class, function($app) {
            return new \Jihe\Services\WechatService(
                $app[\GuzzleHttp\ClientInterface::class],
                $app[\Jihe\Contracts\Repositories\WechatTokenRepository::class],
                $app[\Jihe\Contracts\Repositories\WechatUserRepository::class],
                $app['config']['wechat']
            );
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            \Jihe\Services\WechatService::class,
            \Jihe\Contracts\Repositories\WechatTokenRepository::class,
            \Jihe\Contracts\Repositories\WechatUserRepository::class,
        ];
    }
}
