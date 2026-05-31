<?php

namespace App\Providers;

use App\Domain\Repositories\ConversationLockInterface;
use App\Domain\Repositories\MonthlyUsageRepositoryInterface;
use App\Domain\Repositories\PreRegisterRepositoryInterface;
use App\Domain\Repositories\RequestRepositoryInterface;
use App\Domain\Repositories\UserRepositoryInterface;
use App\Infrastructure\Locks\MySqlConversationLock;
use App\Infrastructure\Repositories\EloquentMonthlyUsageRepository;
use App\Infrastructure\Repositories\EloquentPreRegisterRepository;
use App\Infrastructure\Repositories\EloquentRequestRepository;
use App\Infrastructure\Repositories\EloquentUserRepository;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
        $this->app->bind(PreRegisterRepositoryInterface::class, EloquentPreRegisterRepository::class);
        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);
        $this->app->bind(RequestRepositoryInterface::class, EloquentRequestRepository::class);
        $this->app->bind(MonthlyUsageRepositoryInterface::class, EloquentMonthlyUsageRepository::class);
        $this->app->bind(ConversationLockInterface::class, MySqlConversationLock::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url')."/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
        });
    }
}
