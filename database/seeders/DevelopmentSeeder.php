<?php

namespace Database\Seeders;

use App\Models\Blog;
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
        // Create 5 Bloggers
        User::factory(5)
            ->create([
                'role' => User::ROLE_BLOGGER,
                'blog_quota' => 3,
            ])
            ->each(function (User $blogger) {
                // 1-3 Blogs per Blogger
                $blogs = Blog::factory(random_int(1, 3))->create([
                    'user_id' => $blogger->id,
                ]);

                $blogs->each(function (Blog $blog) {
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
        User::factory(5)->create([
            'role' => User::ROLE_USER,
        ]);
    }
}
