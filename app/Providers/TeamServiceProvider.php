<?php
namespace Jihe\Providers;

use Illuminate\Support\ServiceProvider;

class TeamServiceProvider extends ServiceProvider
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
            \Jihe\Contracts\Repositories\TeamRepository::class,
            \Jihe\Repositories\TeamRepository::class
        );
        
        $this->app->singleton(
            \Jihe\Contracts\Repositories\TeamRequestRepository::class,
            \Jihe\Repositories\TeamRequestRepository::class
        );

        $this->app->singleton(
            \Jihe\Contracts\Repositories\TeamGroupRepository::class,
            \Jihe\Repositories\TeamGroupRepository::class
        );

        $this->app->singleton(
            \Jihe\Contracts\Repositories\TeamMemberRepository::class,
            \Jihe\Repositories\TeamMemberRepository::class
        );

        $this->app->singleton(
            \Jihe\Contracts\Repositories\TeamMemberEnrollmentRequestRepository::class,
            \Jihe\Repositories\TeamMemberEnrollmentRequestRepository::class
        );
    }
    
    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            \Jihe\Contracts\Repositories\TeamRepository::class,
            \Jihe\Contracts\Repositories\TeamRequestRepository::class,
            \Jihe\Contracts\Repositories\TeamGroupRepository::class,
            \Jihe\Contracts\Repositories\TeamMemberRepository::class,
            \Jihe\Contracts\Repositories\TeamMemberEnrollmentRequestRepository::class,
        ];
    }
}
