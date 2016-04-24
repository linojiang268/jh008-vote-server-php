<?php
namespace Jihe\Providers;

use Illuminate\Support\ServiceProvider;

class SignatureServiceProvider extends ServiceProvider
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
        $this->app->singleton(\Jihe\Services\SignatureService::class, function ($app) {
            $config = $app['config']['signature'];
            
            return new \Jihe\Services\SignatureService( $config);
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
            \Jihe\Services\SignatureService::class,
        ];
    }
}
