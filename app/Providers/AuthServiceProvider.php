<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Gate to allow only admins to view the admin stats page
        Gate::define('view_admin_stats', function (?User $user): bool {
            return $user instanceof User
                && ($user->isAdmin() || $user->hasAbility('view_admin_stats'));
        });
    }
}
