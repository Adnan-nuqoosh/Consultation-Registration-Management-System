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

        Gate::before(function (User $user, string $ability): ?bool {
            if (! str_starts_with($ability, 'consultations.')) {
                return null;
            }

            $permissions = [
                'admin' => ['consultations.view', 'consultations.update', 'consultations.update_status', 'consultations.add_notes', 'consultations.assign', 'consultations.export', 'consultations.stats', 'consultations.delete'],
                'manager' => ['consultations.view', 'consultations.update', 'consultations.update_status', 'consultations.add_notes', 'consultations.assign', 'consultations.export', 'consultations.stats'],
                'agent' => ['consultations.view', 'consultations.update_status', 'consultations.add_notes', 'consultations.stats'],
            ];

            return in_array($ability, $permissions[$user->role] ?? [], true);
        });
    }
}
