<?php
namespace Jihe\Providers;

use Illuminate\Support\ServiceProvider;

class QrcodeServiceProvider extends ServiceProvider
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
        $this->app->singleton(\Jihe\Contracts\Services\Qrcode\QrcodeService::class, function ($app) {
            return new \Jihe\Services\Qrcode\QrcodeService();
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
            \Jihe\Contracts\Services\Qrcode\QrcodeService::class,
        ];
    }
}
