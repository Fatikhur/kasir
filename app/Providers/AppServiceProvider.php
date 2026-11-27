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

        $ownerOnly = fn (User $user): bool => $user->role === UserRole::Owner;

        Gate::define('manage-products', $ownerOnly);
        Gate::define('manage-stock', $ownerOnly);
        Gate::define('manage-reports', $ownerOnly);
        Gate::define('manage-users', $ownerOnly);
        Gate::define('manage-settings', $ownerOnly);
        Gate::define('view-audit-logs', $ownerOnly);

        RateLimiter::for('pos-checkout', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('qris-webhook', function (Request $request) {
            return Limit::perMinute(120)->by($request->ip());
        });
    }
}
