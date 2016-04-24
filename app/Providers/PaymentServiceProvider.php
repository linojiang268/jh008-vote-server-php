<?php
namespace Jihe\Providers;

use Illuminate\Support\ServiceProvider;

class PaymentServiceProvider extends ServiceProvider
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
            \Jihe\Services\Payment\Wxpay\WxpayAppService::class, function ($app) {
                return new \Jihe\Services\Payment\Wxpay\WxpayAppService(
                    $app['config']->get('payment.wx_app_pay'),
                    $app[\GuzzleHttp\ClientInterface::class]);
        });

        $this->app->singleton(
            \Jihe\Services\Payment\Wxpay\WxpayWebService::class, function ($app) {
                return new \Jihe\Services\Payment\Wxpay\WxpayWebService(
                    $app['config']->get('payment.wx_mp_pay'),
                    $app[\GuzzleHttp\ClientInterface::class]
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
            \Jihe\Services\Payment\Wxpay\WxpayAppService::class,
        ];
    }
}
