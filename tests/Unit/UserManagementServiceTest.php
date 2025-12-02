<?php

use App\Models\User;
use App\Services\UserManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class);

it('determines blog quota for non blogger/admin roles as zero', function (): void {
    $service = new UserManagementService;

    expect($service->determineBlogQuota(User::ROLE_USER))->toBe(0)
        ->and($service->determineBlogQuota('some-other-role'))->toBe(0);
});

it('uses requested quota for blogger and admin but not below zero', function (): void {
    $service = new UserManagementService;

    expect($service->determineBlogQuota(User::ROLE_BLOGGER, 5))->toBe(5)
        ->and($service->determineBlogQuota(User::ROLE_ADMIN, 10))->toBe(10)
        ->and($service->determineBlogQuota(User::ROLE_BLOGGER, -3))->toBe(0)
        ->and($service->determineBlogQuota(User::ROLE_ADMIN, -1))->toBe(0);
});

it('applies default quotas when blogger or admin without requested quota', function (): void {
    $service = new UserManagementService;

    expect($service->determineBlogQuota(User::ROLE_BLOGGER))->toBe(1)
        ->and($service->determineBlogQuota(User::ROLE_ADMIN))->toBe(0);
});

it('checks if original role can edit blog quota', function (): void {
    $service = new UserManagementService;

    expect($service->canEditBlogQuota(User::ROLE_BLOGGER))->toBeTrue()
        ->and($service->canEditBlogQuota(User::ROLE_ADMIN))->toBeTrue()
        ->and($service->canEditBlogQuota(User::ROLE_USER))->toBeFalse();
});

it('creates user with computed blog quota', function (): void {
    $service = new UserManagementService;

    $user = $service->createUser([
        'name' => 'Test User',
        'email' => 'test-user-management@example.com',
        'password' => 'password',
        'role' => User::ROLE_BLOGGER,
        'blog_quota' => null,
    ]);

    expect($user->role)->toBe(User::ROLE_BLOGGER)
        ->and($user->blog_quota)->toBe(1);
});

it('updates user role and blog quota when editable', function (): void {
    $service = new UserManagementService;

    $user = User::factory()->create([
        'role' => User::ROLE_BLOGGER,
        'blog_quota' => 2,
    ]);

    $service->updateUser($user, [
        'role' => User::ROLE_ADMIN,
        'blog_quota' => 5,
    ], User::ROLE_BLOGGER);

    $user->refresh();

    expect($user->role)->toBe(User::ROLE_ADMIN)
        ->and($user->blog_quota)->toBe(5);
});

it('does not update blog quota when original role cannot edit', function (): void {
    $service = new UserManagementService;

    $user = User::factory()->create([
        'role' => User::ROLE_USER,
        'blog_quota' => 3,
    ]);

    $service->updateUser($user, [
        'role' => User::ROLE_BLOGGER,
        'blog_quota' => 10,
    ], User::ROLE_USER);

    $user->refresh();

    expect($user->role)->toBe(User::ROLE_BLOGGER)
        ->and($user->blog_quota)->toBe(3);
});
