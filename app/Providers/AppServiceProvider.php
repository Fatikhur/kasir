<?php

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\Transaction;
use App\Models\User;
use App\Policies\TransactionPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Transaction::class, TransactionPolicy::class);

        $adminOrSuperadmin = fn (User $user): bool => $user->isAdminOrSuperadmin();
        $superadminOnly = fn (User $user): bool => $user->isSuperadmin();

        Gate::define('manage-products', $adminOrSuperadmin);
        Gate::define('manage-stock', $adminOrSuperadmin);
        Gate::define('manage-reports', $adminOrSuperadmin);
        Gate::define('manage-users', $superadminOnly);
        Gate::define('manage-settings', $superadminOnly);
        Gate::define('view-audit-logs', $superadminOnly);

        RateLimiter::for('pos-checkout', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('qris-webhook', function (Request $request) {
            return Limit::perMinute(120)->by($request->ip());
        });
    }
}
