<?php
namespace Jihe\Providers;

use Illuminate\Support\ServiceProvider;

class SmsServiceProvider extends ServiceProvider
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
        $this->app->singleton(\Jihe\Contracts\Services\Sms\SmsService::class, function ($app) {
            $config = $app['config']['sms.drivers'];

            $driver = $config['default']; // find default driver
            return call_user_func([$this, sprintf('create' . ucfirst($driver) . 'SmsService')],
                                  $app, $config[$driver]);
        });
    }

    // Chuanglan
    protected function createChuanglanSmsService($app, $config)
    {
        if (!array_key_exists('account', $config) || empty($config['account'])) {
            throw new \Exception('短信账户未配置');
        }

        if (!array_key_exists('pass', $config) || empty($config['pass'])) {
            throw new \Exception('短信账户密钥未配置');
        }

        return new \Jihe\Services\Sms\ChuanglanSmsService($config['account'],
                                                          $config['pass'],
                                                          array_get($config, 'affix', ''),
                                                          $app[\GuzzleHttp\ClientInterface::class]);
    }

    // Guodu
    protected function createGuoduSmsService($app, $config)
    {
        if (!array_key_exists('account', $config) || empty($config['account'])) {
            throw new \Exception('短信账户未配置');
        }

        if (!array_key_exists('pass', $config) || empty($config['pass'])) {
            throw new \Exception('短信账户密钥未配置');
        }

        return new \Jihe\Services\Sms\GuoduSmsService($config['account'],
                                                      $config['pass'],
                                                      array_get($config, 'affix', ''),
                                                      array_get($config, 'signature', ''),
                                                      $app[\GuzzleHttp\ClientInterface::class]);
    }

    // webchinese
    protected function createWebChineseSmsService($app, $config)
    {
        if (!array_key_exists('account', $config) || empty($config['account'])) {
            throw new \Exception('短信账户未配置');
        }

        if (!array_key_exists('key', $config) || empty($config['key'])) {
            throw new \Exception('短信账户密钥未配置');
        }

        return new \Jihe\Services\Sms\WebChineseSmsService($config['account'],
            strtoupper(md5($config['key'])),
            $app[\GuzzleHttp\ClientInterface::class]);
    }
    
    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            \Jihe\Contracts\Services\Sms\SmsService::class,
        ];
    }
}
