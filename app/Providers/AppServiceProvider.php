<?php
namespace Jihe\Providers;

use Auth, Validator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->extendValidator();
        $this->extendAuthManager();
        $this->extendViewComposer();
    }
    
    private function extendAuthManager()
    {
        Auth::extend('extended-eloquent', function ($app) {
            // AuthManager allows us only provide UserProvider instead of
            // the whole Guard implmenetation
            return new \Jihe\Auth\UserProvider(new \Jihe\Hashing\PasswordHasher(),
                                               $app['config']['auth.model']);
        });
        
        Auth::extend('extended-eloquent-admin', function ($app) {
            // AuthManager allows us only provide UserProvider instead of
            // the whole Guard implmenetation
            return new \Jihe\Auth\AdminUserProvider(new \Jihe\Hashing\PasswordHasher(),
                                                $app['config']['auth.model-admin']);
        });
    }

    private function extendViewComposer()
    {
        view()->composer('backstage.*', \Jihe\Http\ViewComposers\TeamComposer::class);
    }
    
    private function extendValidator()
    {
        Validator::extend('mobile', function($attribute, $value, $parameters) {
            return preg_match('/^1[34578]\d{9}$/', $value) > 0;
        });
        Validator::extend('phone', function($attribute, $value, $parameters) {
            return (preg_match('/^([0-9]{3,4}-)?[0-9]{7,8}$/', $value) > 0)
                || (preg_match('/^1[34578]\d{9}$/', $value) > 0);
        });
    }
    
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // 
    }
    
}
