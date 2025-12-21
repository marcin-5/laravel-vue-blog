<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use App\Services\UserManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UsersController extends Controller
{
    public function __construct(private readonly UserManagementService $userManagementService)
    {
    }

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

        return Inertia::render('app/admin/Users', [
            'users' => $users,
            'currentUserIsAdmin' => $request->user()?->isAdmin() ?? false,
        ]);
    }

    /**
     * Store a newly created user (admin only).
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->authorize('edit-user-blog-quota');

        $this->userManagementService->createUser($request->validated());

        return back()->with('success', 'User created successfully.');
    }

    /**
     * Update the specified user (role and blog_quota).
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        // Centralized authorization via Gate
        $this->authorize('edit-user-blog-quota');

        // Keep the original role from DB to enforce blog_quota edit rules
        $originalRole = $user->role;

        $this->userManagementService->updateUser($user, $request->validated(), $originalRole);

        return back()->with('success', 'User updated successfully.');
    }
}
