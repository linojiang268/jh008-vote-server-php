<?php
namespace Jihe\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * @var array
     */
    protected $middleware = [
        \Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode::class,
        \Jihe\Http\Middleware\EncryptCookies::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \Jihe\Http\Middleware\CheckForAuthenticatedUserStatus::class,
    ];

    /**
     * The application's route middleware.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'csrf' => \Jihe\Http\Middleware\VerifyCsrfToken::class,
        'auth' => \Jihe\Http\Middleware\Authenticate::class,
        'auth.device' => \Jihe\Http\Middleware\DeviceAuthenticate::class,
        'guest' => \Jihe\Http\Middleware\RedirectIfAuthenticated::class,
        'request.sign' => \Jihe\Http\Middleware\CheckRequestSignature::class,
        'team.inject' => \Jihe\Http\Middleware\InjectTeam::class,
        'admin.auth' => \Jihe\Http\Middleware\AdminAuthenticate::class,
        'admin.driver.inject' => \Jihe\Http\Middleware\InjectAdminDriver::class,
        'admin.role.auth' => \Jihe\Http\Middleware\AdminRoleAuthenticate::class,
        'pm.act'    => \Jihe\Http\Middleware\PromotionActivityManager::class,
    ];
}
