<?php
namespace Jihe\Providers;

use Elastica\Client;
use Illuminate\Support\ServiceProvider;

class SearchServiceProvider extends ServiceProvider
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

        $this->app->singleton(\Elastica\Client::class, function ($app) {
            $servers = $app['config']['search'];
            return new \Elastica\Client($servers);
        });

        // disable Search Service
//        $this->app->singleton(\Jihe\Contracts\Services\Search\SearchService::class,
//            \Jihe\Services\Search\ElasticSearchService::class);
        $this->app->singleton(\Jihe\Contracts\Services\Search\SearchService::class,
            \Jihe\Services\Search\DummySearchService::class);
    }
    
    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            \Jihe\Contracts\Services\Search\SearchService::class,
        ];
    }
}
