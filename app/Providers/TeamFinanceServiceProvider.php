<?php
namespace Jihe\Providers;

use Illuminate\Support\ServiceProvider;

class TeamFinanceServiceProvider extends ServiceProvider
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
            \Jihe\Contracts\Repositories\ActivityEnrollPaymentRepository::class,
            \Jihe\Repositories\ActivityEnrollPaymentRepository::class
        );

        $this->app->singleton(
            \Jihe\Contracts\Repositories\ActivityEnrollIncomeRepository::class,
            \Jihe\Repositories\ActivityEnrollIncomeRepository::class
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
            \Jihe\Contracts\Repositories\ActivityEnrollPaymentRepository::class,
            \Jihe\Contracts\Repositories\ActivityEnrollIncomeRepository::class,
            \Jihe\Services\TeamFinanceService::class,
        ];
    }
}
