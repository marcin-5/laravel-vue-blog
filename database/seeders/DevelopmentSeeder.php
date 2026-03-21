<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Blog;
use App\Models\Category;
use App\Models\ExternalLink;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\LandingPage;
use App\Models\Post;
use App\Models\RelatedPost;
use App\Models\User;
use Illuminate\Database\Seeder;
use Random\RandomException;

class DevelopmentSeeder extends Seeder
{
    private const int CATEGORY_COUNT = 10;

    private const int BLOGGER_COUNT = 5;

    private const int BLOGGER_BLOG_QUOTA = 3;

    private const int BLOGS_PER_BLOGGER_MIN = 0;

    private const int BLOGS_PER_BLOGGER_MAX = 3;

    private const int BLOG_CATEGORIES_MIN = 1;

    private const int BLOG_CATEGORIES_MAX = 5;

    private const int POSTS_PER_BLOG_MIN = 2;

    private const int POSTS_PER_BLOG_MAX = 15;

    private const int REGULAR_USER_COUNT = 5;

    private const int GROUP_COUNT = 5;

    private const int GROUP_MEMBERS_MIN = 3;

    private const int GROUP_MEMBERS_MAX = 5;

    private const int POSTS_PER_GROUP_MIN = 2;

    private const int POSTS_PER_GROUP_MAX = 5;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = Category::factory(self::CATEGORY_COUNT)->create();

        $bloggers = User::factory(self::BLOGGER_COUNT)->create([
            'role' => UserRole::Blogger->value,
            'blog_quota' => self::BLOGGER_BLOG_QUOTA,
        ]);

        $this->seedBlogsForBloggers($bloggers, $categories);

        $regularUsers = User::factory(self::REGULAR_USER_COUNT)->create([
            'role' => UserRole::User->value,
        ]);

        $groupOwnerPool = $bloggers->merge(
            User::where('role', UserRole::Admin->value)->get(),
        );

        $this->seedGroups($groupOwnerPool, $bloggers, $regularUsers);
    }

    private function seedBlogsForBloggers($bloggers, $categories): void
    {
        $bloggers->each(function (User $blogger) use ($categories): void {
            $blogCount = random_int(self::BLOGS_PER_BLOGGER_MIN, self::BLOGS_PER_BLOGGER_MAX);

            $blogs = Blog::factory($blogCount)->create([
                'user_id' => $blogger->id,
            ]);

            $blogs->each(
            /**
             * @throws RandomException
             */ function (Blog $blog) use ($categories): void {
                $categoryCount = random_int(self::BLOG_CATEGORIES_MIN, self::BLOG_CATEGORIES_MAX);

                $blog->categories()->attach(
                    $categories->random($categoryCount),
                );

                LandingPage::factory()->create([
                    'blog_id' => $blog->id,
                ]);

                $postCount = random_int(self::POSTS_PER_BLOG_MIN, self::POSTS_PER_BLOG_MAX);

                $posts = Post::factory($postCount)->create([
                    'blog_id' => $blog->id,
                    'user_id' => $blog->user_id,
                ]);

                $this->seedRelationsForPosts($posts, $blog);
            },
            );
        });
    }

    private function seedRelationsForPosts($posts, Blog $blog): void
    {
        $posts->each(
        /**
         * @throws RandomException
         */ function (Post $post) use ($posts, $blog): void {
            // Seeding related posts: 0 or 1, then if 1, then 1 or 2
            if (random_int(0, 1) === 1) {
                $count = random_int(1, 2);
                $potentialRelated = $posts->reject(fn(Post $p) => $p->id === $post->id);

                if ($potentialRelated->isNotEmpty()) {
                    $countToTake = min($count, $potentialRelated->count());
                    $relatedToCreate = $potentialRelated->random($countToTake);

                    if ($relatedToCreate instanceof Post) {
                        $relatedToCreate = collect([$relatedToCreate]);
                    }

                    $relatedToCreate->each(function (Post $rp, int $index) use ($post, $blog): void {
                        RelatedPost::factory()->create([
                            'post_id' => $post->id,
                            'blog_id' => $blog->id,
                            'related_post_id' => $rp->id,
                            'display_order' => $index,
                        ]);
                    });
                }
            }

            // Seeding external links (optional, but good for completeness)
            if (random_int(0, 1) === 1) {
                $count = random_int(1, 3);
                ExternalLink::factory($count)->create([
                    'post_id' => $post->id,
                ]);
            }
        },
        );
    }

    private function seedGroups($groupOwnerPool, $bloggers, $regularUsers): void
    {
        $joinedAt = now();

        Group::factory(self::GROUP_COUNT)
            ->create([
                'user_id' => fn() => $groupOwnerPool->random()->id,
            ])
            ->each(function (Group $group) use ($bloggers, $regularUsers, $joinedAt): void {
                $owner = $group->user;

                $memberPool = $bloggers
                    ->merge($regularUsers)
                    ->reject(fn(User $user) => $user->id === $owner->id);

                $memberCount = random_int(self::GROUP_MEMBERS_MIN, self::GROUP_MEMBERS_MAX);
                $members = $memberPool->random($memberCount);

                foreach ($members as $member) {
                    $group->members()->attach($member, [
                        'role' => GroupMember::ROLE_MEMBER,
                        'joined_at' => $joinedAt,
                    ]);
                }

                $ownerBlogId = $owner->role === UserRole::Blogger->value
                    ? $owner->blogs()->first()?->id
                    : null;

                $postCount = random_int(self::POSTS_PER_GROUP_MIN, self::POSTS_PER_GROUP_MAX);

                $posts = Post::factory($postCount)->create([
                    'group_id' => $group->id,
                    'blog_id' => $ownerBlogId,
                    'user_id' => $owner->id,
                    'is_published' => true,
                ]);

                if ($ownerBlogId) {
                    $this->seedRelationsForPosts($posts, Blog::find($ownerBlogId));
                }
            });
    }
}
