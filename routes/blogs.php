<?php

use App\Http\Controllers\Blogger\BlogsController;
use App\Http\Controllers\Blogger\GroupMembersController;
use App\Http\Controllers\Blogger\GroupsController;
use App\Http\Controllers\Blogger\PostsController;
use App\Http\Controllers\Blogger\StatsController as BloggerStatsController;
use App\Http\Controllers\MarkdownController;
use Illuminate\Support\Facades\Route;

// Blogger routes for managing own blogs
Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('blogs', BlogsController::class)
        ->only(['index', 'store', 'update']);

    // Groups - content management
    Route::resource('groups/content', GroupsController::class)
        ->only(['index', 'store', 'update'])
        ->names([
            'index' => 'blogger.groups.content.index',
            'store' => 'blogger.groups.content.store',
            'update' => 'blogger.groups.content.update',
        ])
        ->parameters([
            'content' => 'group',
        ]);

    // Groups - membership management
    Route::get('groups/members', [GroupMembersController::class, 'index'])
        ->name('blogger.groups.members.index');
    Route::post('groups/members/{group}', [GroupMembersController::class, 'store'])
        ->name('blogger.groups.members.store');
    Route::patch(
        'groups/members/{group}/{user}',
        [GroupMembersController::class, 'update'],
    )
        ->name('blogger.groups.members.update');
    Route::delete(
        'groups/members/{group}/{user}',
        [GroupMembersController::class, 'destroy'],
    )
        ->name('blogger.groups.members.destroy');

    // Blogger stats
    Route::get('blogs/stats', [BloggerStatsController::class, 'index'])->name('blogger.stats.index');

    // Posts routes
    Route::post('posts', [PostsController::class, 'store'])->name('posts.store');
    Route::patch('posts/{post}', [PostsController::class, 'update'])->name('posts.update');

    // Post Extensions (New pivot-based routes)
    Route::prefix('blogger/posts/{post}')->group(function () {
        Route::get('extensions/available', [PostsController::class, 'availableExtensions'])
            ->name('blogger.posts.extensions.available');
        Route::post('extensions', [PostsController::class, 'attachExtension'])
            ->name('blogger.posts.extensions.attach');
        Route::delete('extensions/{extensionPostId}', [PostsController::class, 'detachExtension'])
            ->name('blogger.posts.extensions.detach');
        Route::put('extensions/reorder', [PostsController::class, 'reorderExtensions'])
            ->name('blogger.posts.extensions.reorder');
    });

    // Markdown preview route
    Route::post('markdown/preview', [MarkdownController::class, 'preview'])->name('markdown.preview');
});
