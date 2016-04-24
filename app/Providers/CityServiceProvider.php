<?php
namespace Jihe\Providers;

use Illuminate\Support\ServiceProvider;

class CityServiceProvider extends ServiceProvider
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
            \Jihe\Contracts\Repositories\CityRepository::class,
            \Jihe\Repositories\CityRepository::class
        );
        
        $this->app->singleton(\Jihe\Services\CityService::class, function ($app) {
            return new \Jihe\Services\CityService(
                $app[\Jihe\Contracts\Repositories\CityRepository::class]
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
            \Jihe\Contracts\Repositories\CityRepository::class,
            \Jihe\Services\CityService::class,
        ];
    }
}
