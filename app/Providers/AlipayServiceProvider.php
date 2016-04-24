<?php
namespace Jihe\Providers;

use Illuminate\Support\ServiceProvider;

class AlipayServiceProvider extends ServiceProvider
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
        $this->app->singleton(\Jihe\Services\Payment\Alipay\AlipayService::class, function ($app) {
            $config = $app['config']['payment.alipay'];
            
            return new \Jihe\Services\Payment\Alipay\AlipayService($config, 
                $app[\GuzzleHttp\ClientInterface::class], 
                // todo: if needed, register OpensslHasher 
                new \Jihe\Hashing\OpensslHasher());
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
           \Jihe\Services\Payment\Alipay\AlipayService::class,
        ];
    }
}
