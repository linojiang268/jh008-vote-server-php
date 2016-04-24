<?php
namespace Jihe\Providers;

use Illuminate\Support\ServiceProvider;

class VerificationServiceProvider extends ServiceProvider
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
            \Jihe\Contracts\Repositories\VerificationRepository::class,
            \Jihe\Repositories\VerificationRepository::class
        );
        
        $this->app->singleton(\Jihe\Services\VerificationService::class, function ($app) {
            $config = $app['config']['verification'];
            return new \Jihe\Services\VerificationService(
                $app[\Jihe\Contracts\Repositories\VerificationRepository::class],
                $app[\Jihe\Contracts\Repositories\UserRepository::class],
                $app[\Jihe\Contracts\Repositories\ActivityMemberRepository::class],
                $config);
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
            \Jihe\Services\VerificationService::class,
            \Jihe\Contracts\Repositories\VerificationRepository::class,
        ];
    }
}
