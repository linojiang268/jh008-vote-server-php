<?php
namespace Jihe\Providers;

use Illuminate\Support\ServiceProvider;


class WxpayServiceProvider extends ServiceProvider
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
        $this->app->singleton(\Jihe\Service\Payment\Wxpay\WxpayAppService::class, function($app) {
            $config = $app['config']['payment.wxpay'];

            return new \Jihe\Services\Payment\Wxpay\WxpayAppService($config, $app[\GuzzleHttp\ClientInterface::class]);
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
           \Jihe\Services\Payment\Wxpay\WxpayAppService::class,
        ];
    }
}
