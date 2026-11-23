<?php

namespace App\Providers;

use App\Models\User;
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
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)->by(($request->input('email') ?? 'anonymous').'|'.$request->ip()));
        RateLimiter::for('consultations', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('website-forms', fn (Request $request) => Limit::perMinute((int) config('website_forms.rate_limit_per_minute', 5))->by($request->ip()));

        Gate::define(
            'access-admin-dashboard',
            fn (User $user): bool => $user->is_active && $user->role === 'admin'
        );
    }
}
