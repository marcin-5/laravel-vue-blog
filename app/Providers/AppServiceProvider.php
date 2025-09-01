<?php

namespace App\Providers;

use App\Models\Blog;
use App\Models\Category;
use App\Models\User;
use App\Observers\BlogObserver;
use App\Observers\CategoryObserver;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Model observers for cross-cutting slug generation logic
        Blog::observe(BlogObserver::class);
        Category::observe(CategoryObserver::class);

        // Authorization gate to control who can create a blog (fallback when not using policies)
        Gate::define('create-blog', function (User $user): bool {
            return $user->canCreateBlog();
        });

        // Only admins can edit the blog_quota attribute on users
        Gate::define('edit-user-blog-quota', function (User $user): bool {
            return $user->isAdmin();
        });
    }
}
