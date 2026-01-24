<?php

namespace Database\Seeders;

use App\Models\Blog;
use App\Models\Category;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\LandingPage;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;

class DevelopmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = Category::factory(10)->create();

        // Create 5 Bloggers
        $bloggers = User::factory(5)
            ->create([
                'role' => User::ROLE_BLOGGER,
                'blog_quota' => 3,
            ]);

        $bloggers->each(function (User $blogger) use ($categories) {
            // 0-3 Blogs per Blogger
            $blogs = Blog::factory(random_int(0, 3))->create([
                'user_id' => $blogger->id,
            ]);

            $blogs->each(function (Blog $blog) use ($categories) {
                $blog->categories()->attach(
                    $categories->random(random_int(1, 5)),
                );

                // Landing Page for each Blog
                LandingPage::factory()->create([
                    'blog_id' => $blog->id,
                ]);

                // 5-15 Posts for each Blog
                Post::factory(random_int(5, 15))->create([
                    'blog_id' => $blog->id,
                ]);
            });
        });

        // Create 5 Regular Users
        $regularUsers = User::factory(5)->create([
            'role' => User::ROLE_USER,
        ]);

        // Owners pool for groups (bloggers and admins)
        $potentialOwners = $bloggers->merge(User::where('role', User::ROLE_ADMIN)->get());

        // Create 5 Groups
        Group::factory(5)
            ->create([
                'user_id' => fn() => $potentialOwners->random()->id,
            ])
            ->each(function (Group $group) use ($bloggers, $regularUsers) {
                // Get the owner of the group
                $owner = $group->user;

                // Create 3-5 members (excluding owner)
                // Mix of bloggers and regular users
                $allPotentialMembers = $bloggers->merge($regularUsers)->reject(fn($u) => $u->id === $owner->id);
                $members = $allPotentialMembers->random(random_int(3, 5));

                foreach ($members as $member) {
                    $group->members()->attach($member, [
                        'role' => GroupMember::ROLE_MEMBER,
                        'joined_at' => now(),
                    ]);
                }

                // 2-5 posts for the group
                // If owner has a blog, we use their first blog_id
                $blogId = $owner->role === User::ROLE_BLOGGER ? $owner->blogs()->first()?->id : null;

                Post::factory(random_int(2, 5))->create([
                    'group_id' => $group->id,
                    'blog_id' => $blogId,
                    'is_published' => true,
                ]);
            });
    }
}
