<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UsersController extends Controller
{
    /**
     * Display a listing of users (admin only).
     */
    public function index(Request $request): Response
    {
        // Centralized authorization via Gate ability used in routes; double-check here as well
        $this->authorize('edit-user-blog-quota');

        $users = User::query()
            ->select(['id', 'name', 'email', 'role', 'blog_quota'])
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/Users', [
            'users' => $users,
            'currentUserIsAdmin' => $request->user()?->isAdmin() ?? false,
        ]);
    }

    /**
     * Store a newly created user (admin only).
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('edit-user-blog-quota');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'in:admin,blogger,user'],
            'blog_quota' => ['nullable', 'integer', 'min:0'],
        ]);

        // Determine blog_quota based on selected role
        $role = $validated['role'];
        $blogQuota = 0;
        if (in_array($role, [User::ROLE_BLOGGER, User::ROLE_ADMIN], true)) {
            $blogQuota = (int)($validated['blog_quota'] ?? ($role === User::ROLE_BLOGGER ? 1 : 0));
        }

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'], // hashed via casts()
            'role' => $role,
            'blog_quota' => $blogQuota,
        ]);

        return back()->with('success', 'User created successfully.');
    }

    /**
     * Update the specified user (role and blog_quota).
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        // Centralized authorization via Gate
        $this->authorize('edit-user-blog-quota');

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
        if ($request->user()?->isAdmin() && in_array(
                $originalRole,
                [User::ROLE_BLOGGER, User::ROLE_ADMIN],
                true,
            ) && array_key_exists('blog_quota', $validated)) {
            $user->blog_quota = $validated['blog_quota'] ?? 0;
        }

        $user->save();

        return back()->with('success', 'User updated successfully.');
    }
}
