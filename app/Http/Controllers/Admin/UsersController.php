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
        // Authorization handled by middleware and routes
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
        // Authorization handled by StoreUserRequest
        $this->userManagementService->createUser($request->validated());

        return back()->with('success', 'User created successfully.');
    }

    /**
     * Update the specified user (role and blog_quota).
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        // Authorization handled by UpdateUserRequest
        // Role and blog_quota edit rules enforced by UserManagementService
        $this->userManagementService->updateUser($user, $request->validated(), $user->getOriginal('role'));

        return back()->with('success', 'User updated successfully.');
    }
}
