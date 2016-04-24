<?php
namespace Jihe\Providers;

use Illuminate\Support\ServiceProvider;


class PromotionActivityProvider extends ServiceProvider
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
            \Jihe\Contracts\Repositories\PromotionActivityManagerRepository::class,
            \Jihe\Repositories\PromotionActivityManagerRepository::class
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
            \Jihe\Contracts\Repositories\PromotionActivityManagerRepository::class
        ];
    }
}
