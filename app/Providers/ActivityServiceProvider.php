<?php
namespace Jihe\Providers;

use Illuminate\Support\ServiceProvider;

class ActivityServiceProvider extends ServiceProvider
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
            \Jihe\Contracts\Repositories\ActivityRepository::class,
            \Jihe\Repositories\ActivityRepository::class
        );
        
        $this->app->singleton(
            \Jihe\Contracts\Repositories\ActivityAlbumRepository::class,
            \Jihe\Repositories\ActivityAlbumRepository::class
        );

        $this->app->singleton(
            \Jihe\Contracts\Repositories\ActivityFileRepository::class,
            \Jihe\Repositories\ActivityFileRepository::class
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
            \Jihe\Contracts\Repositories\ActivityRepository::class,
            \Jihe\Contracts\Repositories\ActivityAlbumRepository::class,
            \Jihe\Contracts\Repositories\ActivityFileRepository::class,
        ];
    }
}
