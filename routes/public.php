<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicBlogController;

// Keep these at the very end to avoid conflicts.
Route::get('{blog:slug}/{postSlug}', [PublicBlogController::class, 'post'])
    ->name('blog.public.post');

Route::get('{blog:slug}', [PublicBlogController::class, 'landing'])
    ->name('blog.public.landing');
