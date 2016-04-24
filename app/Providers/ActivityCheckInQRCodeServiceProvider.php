<?php
namespace Jihe\Providers;

use Illuminate\Support\ServiceProvider;

class ActivityCheckInQRCodeServiceProvider extends ServiceProvider
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
            \Jihe\Contracts\Repositories\ActivityCheckInQRCodeRepository::class,
            \Jihe\Repositories\ActivityCheckInQRCodeRepository::class
        );
    }
    
    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            \Jihe\Contracts\Repositories\ActivityCheckInQRCodeRepository::class,
            \Jihe\Services\ActivityCheckInQRCodeService::class,
        ];
    }
}
