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

        Gate::before(function (?User $user, string $ability): ?bool {
            if (! $user || (! str_starts_with($ability, 'consultations.') && ! str_starts_with($ability, 'website_forms.'))) {
                return null;
            }

            if (! $user->is_active) {
                return false;
            }

            $permissions = [
                'admin' => ['consultations.view', 'consultations.update', 'consultations.update_status', 'consultations.add_notes', 'consultations.assign', 'consultations.export', 'consultations.stats', 'consultations.delete'],
                'manager' => ['consultations.view', 'consultations.update', 'consultations.update_status', 'consultations.add_notes', 'consultations.assign', 'consultations.export', 'consultations.stats'],
                'agent' => ['consultations.view', 'consultations.update_status', 'consultations.add_notes', 'consultations.stats'],
            ];

            $formPermissions = [
                'admin' => ['website_forms.view', 'website_forms.update', 'website_forms.update_status', 'website_forms.add_notes', 'website_forms.assign', 'website_forms.export', 'website_forms.stats', 'website_forms.delete'],
                'manager' => ['website_forms.view', 'website_forms.update', 'website_forms.update_status', 'website_forms.add_notes', 'website_forms.assign', 'website_forms.export', 'website_forms.stats'],
                'agent' => ['website_forms.view', 'website_forms.update_status', 'website_forms.add_notes', 'website_forms.stats'],
            ];

            $allowed = array_merge($permissions[$user->role] ?? [], $formPermissions[$user->role] ?? []);

            return in_array($ability, $allowed, true);
        });
    }
}
