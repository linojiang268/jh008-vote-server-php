<?php
namespace Jihe\Providers\Admin;

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
            \Jihe\Contracts\Repositories\Admin\UserRepository::class,
            \Jihe\Repositories\Admin\UserRepository::class
        );
        
        $this->app->when(\Jihe\Services\Admin\UserService::class)
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
           \Jihe\Contracts\Repositories\Admin\UserRepository::class,
           \Jihe\Services\Admin\UserService::class,
        ];
    }
}
