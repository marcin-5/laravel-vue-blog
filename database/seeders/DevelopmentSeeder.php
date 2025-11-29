<?php

namespace Database\Seeders;

use App\Models\Blog;
use App\Models\Category;
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
        User::factory(5)
            ->create([
                'role' => User::ROLE_BLOGGER,
                'blog_quota' => 3,
            ])
            ->each(function (User $blogger) use ($categories) {
                // 1-3 Blogs per Blogger
                $blogs = Blog::factory(random_int(1, 3))->create([
                    'user_id' => $blogger->id,
                ]);

                $blogs->each(function (Blog $blog) use ($categories) {
                    $blog->categories()->attach(
                        $categories->random(random_int(1, 5))
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
        User::factory(5)->create([
            'role' => User::ROLE_USER,
        ]);
    }
}
