<?php
namespace Jihe\Providers;

use Illuminate\Support\ServiceProvider;

class StorageServiceProvider extends ServiceProvider
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
        $this->app->singleton(\Jihe\Contracts\Services\Storage\StorageService::class, function ($app) {
            $config = $app['config']['storage.alioss'];
            if (empty($config)) {
                throw new \Exception('config alioss storage first');
            }
            
            $client = \Aliyun\OSS\OSSClient::factory([
                \Aliyun\OSS\Models\OSSOptions::ENDPOINT          => $config['server'],
                \Aliyun\OSS\Models\OSSOptions::ACCESS_KEY_ID     => $config['key'],
                \Aliyun\OSS\Models\OSSOptions::ACCESS_KEY_SECRET => $config['secret'],
            ]);
            
            return new \Jihe\Services\Storage\AliossService($client, [
                'bucket'         => $config['bucket'],
            ]);
        });

        $this->app->singleton(\Jihe\Services\StorageService::class, function ($app) {
            $config = $app['config']['storage.alioss'];
            if (empty($config)) {
                throw new \Exception('config alioss storage first');
            }

            return new \Jihe\Services\StorageService(
                $app[\Jihe\Contracts\Services\Storage\StorageService::class], [
                'base_url'       => $config['base_url'],
                'base_image_url' => $config['base_image_url'],
            ]);
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
            \Jihe\Contracts\Services\Storage\StorageService::class,
            \Jihe\Services\StorageService::class,
        ];
    }
}
