<?php

use App\Http\Controllers\Admin\CategoriesController as AdminCategoriesController;
use App\Http\Controllers\Admin\UsersController as AdminUsersController;
use App\Http\Controllers\Blogger\BlogsController;
use App\Http\Controllers\PublicBlogController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;


Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Blogs: index (list current user's blogs)
Route::get('blogs', [BlogsController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('blogs.index');

// Admin: users management page
Route::get('admin/users', [AdminUsersController::class, 'index'])
    ->middleware(['auth', 'verified', 'can:edit-user-blog-quota'])
    ->name('admin.users.index');

// Admin: update user role and blog_quota
Route::patch('admin/users/{user}', [AdminUsersController::class, 'update'])
    ->middleware(['auth', 'verified', 'can:edit-user-blog-quota'])
    ->name('admin.users.update');

// Blogs: create new blog (admin or blogger), enforcing quota
Route::post('blogs', [BlogsController::class, 'store'])
    ->middleware(['auth', 'verified'])
    ->name('blogs.store');

// Blogs: update (owner or admin)
Route::patch('blogs/{blog}', [BlogsController::class, 'update'])
    ->middleware(['auth', 'verified'])
    ->name('blogs.update');

// Admin: categories management page
Route::get('admin/categories', [AdminCategoriesController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('admin.categories.index');

// Admin: create category
Route::post('admin/categories', [AdminCategoriesController::class, 'store'])
    ->middleware(['auth', 'verified'])
    ->name('admin.categories.store');

// Admin: update category
Route::patch('admin/categories/{category}', [AdminCategoriesController::class, 'update'])
    ->middleware(['auth', 'verified'])
    ->name('admin.categories.update');

// Admin: delete category
Route::delete('admin/categories/{category}', [AdminCategoriesController::class, 'destroy'])
    ->middleware(['auth', 'verified'])
    ->name('admin.categories.destroy');

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';

// Public blog routes: must be at the very end to avoid conflicts with app routes above
Route::get('{blog:slug}/{postSlug}', [PublicBlogController::class, 'post'])->name('blog.public.post');
Route::get('{blog:slug}', [PublicBlogController::class, 'landing'])->name('blog.public.landing');
