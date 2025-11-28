<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UsersController extends Controller
{
    public function __construct(private readonly UserManagementService $userManagementService) {}

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

        $this->userManagementService->createUser($validated);

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

        $this->userManagementService->updateUser($user, $validated, $originalRole);

        return back()->with('success', 'User updated successfully.');
    }
}
