<?php
namespace Jihe\Providers;

use Illuminate\Support\ServiceProvider;

class UserServiceProvider extends ServiceProvider
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
            \Jihe\Contracts\Repositories\UserRepository::class,
            \Jihe\Repositories\UserRepository::class
        );

        $this->app->when(\Jihe\Services\UserService::class)
             ->needs('hash')
             ->give(\Jihe\Hashing\PasswordHasher::class);
    }
    
    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
           \Jihe\Contracts\Repositories\UserRepository::class,
           \Jihe\Services\UserService::class,
        ];
    }
}
