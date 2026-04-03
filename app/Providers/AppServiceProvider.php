<?php

namespace App\Providers;

use App\Models\Blog;
use App\Models\Category;
use App\Models\PageView;
use App\Models\Post;
use App\Models\User;
use App\Observers\BlogObserver;
use App\Observers\CategoryObserver;
use App\Observers\IndexNowObserver;
use App\Observers\PageViewObserver;
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
        Blog::observe(IndexNowObserver::class);
        Post::observe(IndexNowObserver::class);
        Category::observe(CategoryObserver::class);
        PageView::observe(PageViewObserver::class);

        // Implicitly grant "Admin" role all permissions
        Gate::before(function (User $user, string $ability) {
            return $user->isAdmin() ? true : null;
        });

        // Authorization gate to control who can create a blog (fallback when not using policies)
        Gate::define('create-blog', function (User $user): bool {
            return $user->can('view_blogs');
        });

        // Only admins can edit the blog_quota attribute on users
        Gate::define('edit-user-blog-quota', function (User $user): bool {
            return $user->isAdmin();
        });
    }
}
