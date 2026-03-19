<?php

use App\Enums\UserRole;
use App\Models\User;
use App\Services\UserManagementService;

it('determines blog quota for non blogger/admin roles as zero', function (): void {
    $service = new UserManagementService;

    expect($service->determineBlogQuota(UserRole::User->value))
        ->toBe(0)
        ->and($service->determineBlogQuota('some-other-role'))->toBe(0);
});

it('uses requested quota for blogger and admin but not below zero', function (): void {
    $service = new UserManagementService;

    expect($service->determineBlogQuota(UserRole::Blogger->value, 5))
        ->toBe(5)
        ->and($service->determineBlogQuota(UserRole::Admin->value, 10))->toBe(10)
        ->and($service->determineBlogQuota(UserRole::Blogger->value, -3))->toBe(0)
        ->and($service->determineBlogQuota(UserRole::Admin->value, -1))->toBe(0);
});

it('applies default quotas when blogger or admin without requested quota', function (): void {
    $service = new UserManagementService;

    expect($service->determineBlogQuota(UserRole::Blogger->value))
        ->toBe(1)
        ->and($service->determineBlogQuota(UserRole::Admin->value))->toBe(0);
});

it('checks if original role can edit blog quota', function (): void {
    $service = new UserManagementService;

    expect($service->canEditBlogQuota(UserRole::Blogger->value))
        ->toBeTrue()
        ->and($service->canEditBlogQuota(UserRole::Admin->value))->toBeTrue()
        ->and($service->canEditBlogQuota(UserRole::User->value))->toBeFalse();
});

it('creates user with computed blog quota', function (): void {
    $service = new UserManagementService;

    $user = $service->createUser([
        'name' => 'Test User',
        'email' => 'test-user-management@example.com',
        'password' => 'password',
        'role' => UserRole::Blogger->value,
        'blog_quota' => null,
    ]);

    expect($user->role)
        ->toBe(UserRole::Blogger->value)
        ->and($user->blog_quota)->toBe(1);
});

it('updates user role and blog quota when editable', function (): void {
    $service = new UserManagementService;

    $user = User::factory()->create([
        'role' => UserRole::Blogger->value,
        'blog_quota' => 2,
    ]);

    $service->updateUser($user, [
        'role' => UserRole::Admin->value,
        'blog_quota' => 5,
    ], UserRole::Blogger->value);

    $user->refresh();

    expect($user->role)
        ->toBe(UserRole::Admin->value)
        ->and($user->blog_quota)->toBe(5);
});

it('does not update blog quota when original role cannot edit', function (): void {
    $service = new UserManagementService;

    $user = User::factory()->create([
        'role' => UserRole::User->value,
        'blog_quota' => 3,
    ]);

    $service->updateUser($user, [
        'role' => UserRole::Blogger->value,
        'blog_quota' => 10,
    ], UserRole::User->value);

    $user->refresh();

    expect($user->role)
        ->toBe(UserRole::Blogger->value)
        ->and($user->blog_quota)->toBe(3);
});
