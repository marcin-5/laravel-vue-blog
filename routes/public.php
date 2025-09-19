<?php

use App\Http\Controllers\PublicBlogController;
use App\Http\Controllers\PublicHomeController;
use Illuminate\Support\Facades\Route;

// Keep these at the very end to avoid conflicts.
Route::get('{blog:slug}/{postSlug}', [PublicBlogController::class, 'post'])
    ->name('blog.public.post');

Route::get('{blog:slug}', [PublicBlogController::class, 'landing'])
    ->name('blog.public.landing');

Route::get('/', [PublicHomeController::class, 'welcome'])->name('home');
