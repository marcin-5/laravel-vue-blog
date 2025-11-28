<?php

namespace App\Services;

use App\Models\User;

class UserManagementService
{
    /**
     * @param  array{name:string,email:string,password:string,role:string,blog_quota?:int|null}  $data
     */
    public function createUser(array $data): User
    {
        $role = $data['role'];
        $requestedQuota = $data['blog_quota'] ?? null;

        $blogQuota = $this->determineBlogQuota($role, $requestedQuota);

        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $role,
            'blog_quota' => $blogQuota,
        ]);
    }

    public function determineBlogQuota(string $role, ?int $requestedQuota = null): int
    {
        if (! in_array($role, [User::ROLE_BLOGGER, User::ROLE_ADMIN], true)) {
            return 0;
        }

        if ($requestedQuota !== null) {
            return max(0, $requestedQuota);
        }

        if ($role === User::ROLE_BLOGGER) {
            return 1;
        }

        return 0;
    }

    /**
     * @param  array{role:string,blog_quota?:int|null}  $data
     */
    public function updateUser(User $user, array $data, string $originalRole): User
    {
        $user->role = $data['role'];

        if ($this->canEditBlogQuota($originalRole) && array_key_exists('blog_quota', $data)) {
            $user->blog_quota = $data['blog_quota'] ?? 0;
        }

        $user->save();

        return $user;
    }

    public function canEditBlogQuota(string $originalRole): bool
    {
        return in_array($originalRole, [User::ROLE_BLOGGER, User::ROLE_ADMIN], true);
    }
}
