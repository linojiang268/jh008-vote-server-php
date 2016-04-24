<?php
namespace Jihe\Providers;

use Illuminate\Support\ServiceProvider;

class PushServiceProvider extends ServiceProvider
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
        $this->app->singleton(\Jihe\Contracts\Services\Push\PushService::class, function ($app) {
            $config = $app['config']['push.drivers'];
//            $noPush = $app['config']['push.config.no_push'];
//            if ($noPush) {
//                throw new \Exception('服务已关闭');
//            }
            $driver = $config['default']; // find default driver
            $sendConfig = $config[$driver];
            $shutdownConfig = $sendConfig['shutdownConfig'];
            if($shutdownConfig){
                foreach($shutdownConfig as $index){
                    unset($sendConfig['config'][$index]);
                }
            }
            $pushServices = [];
            foreach($sendConfig['config'] as $conf ){
                $fun = 'create' . ucfirst($driver) . 'PushService';
                $pushServices[] = $this->$fun($app, $conf);
            }

            return call_user_func([$this, 'createPushService'], $pushServices);
        });
    }

    protected function createYunbaPushService($app, $config)
    {
        if (!array_key_exists('appKey', $config) || empty($config['appKey'])) {
            throw new \Exception('AppKey未配置');
        }

        if (!array_key_exists('secretKey', $config) || empty($config['secretKey'])) {
            throw new \Exception('Secret Key未配置');
        }

        return new \Jihe\Services\Push\YunbaPushService($config['appKey'],
            $config['secretKey'],
            array_get($config, 'signature', ''),
            $app[\GuzzleHttp\ClientInterface::class]);
    }

    protected function createPushService($pushServices){
        return new \Jihe\Services\Push\CompoundPushService($pushServices);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            \Jihe\Contracts\Services\Push\PushService::class,
        ];
    }
}
