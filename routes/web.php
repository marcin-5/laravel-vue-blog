<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Admin: users management page
Route::get('admin/users', function () {
    // Ensure only admins can view
    abort_unless(auth()->user() && auth()->user()->isAdmin(), 403);

    $users = User::query()
        ->select(['id', 'name', 'email', 'role', 'blog_quota'])
        ->orderBy('name')
        ->get();

    return Inertia::render('Admin/Users', [
        'users' => $users,
        'currentUserIsAdmin' => auth()->user()?->isAdmin() ?? false,
    ]);
})->middleware(['auth', 'verified', 'can:edit-user-blog-quota'])->name('admin.users.index');

// Admin: update user role and blog_quota
Route::patch('admin/users/{user}', function (Request $request, User $user) {
    // Ensure only admins can perform updates
    abort_unless(auth()->user() && auth()->user()->isAdmin(), 403);

    // Keep the original role from DB to enforce blog_quota edit rules
    $originalRole = $user->role;

    $validated = $request->validate([
        'role' => ['required', 'in:admin,blogger,user'],
        'blog_quota' => ['nullable', 'integer', 'min:0'],
    ]);

    $user->role = $validated['role'];

    // Only allow blog_quota modifications when:
    //  - the authenticated user is admin (already checked), and
    //  - the target user's original DB role was blogger or admin.
    if ($request->user()?->isAdmin() && in_array($originalRole, [User::ROLE_BLOGGER, User::ROLE_ADMIN], true) && array_key_exists('blog_quota', $validated)) {
        $user->blog_quota = $validated['blog_quota'] ?? 0;
    }

    $user->save();

    return back()->with('success', 'User updated successfully.');
})->middleware(['auth', 'verified', 'can:edit-user-blog-quota'])->name('admin.users.update');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
