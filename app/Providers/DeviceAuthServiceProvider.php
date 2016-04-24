<?php
namespace Jihe\Providers;

use Illuminate\Support\ServiceProvider;

class DeviceAuthServiceProvider extends ServiceProvider
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
        $this->app->singleton(\Jihe\Services\DeviceAuthService::class);

        $this->app->singleton(
            \Jihe\Contracts\Repositories\LoginDeviceRepository::class,
            \Jihe\Repositories\LoginDeviceRepository::class);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            \Jihe\Services\DeviceAuthService::class,
            \Jihe\Contracts\Repositories\LoginDeviceRepository::class,
        ];
    }
}
