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
use Goez\LaravelFakedown\FakedownGenerator;
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

    private const array FONT_PAIRS = [
        ['--font-header' => 'var(--font-raleway)', '--font-body' => 'var(--font-inter)'],
        ['--font-header' => 'var(--font-bitter)', '--font-body' => 'var(--font-nunito)'],
        ['--font-header' => 'var(--font-montserrat)', '--font-body' => 'var(--font-faustina)'],
        ['--font-header' => 'var(--font-vollkorn)', '--font-body' => 'var(--font-baloo-2)'],
    ];

    private const array COLOR_PALETTES = [
        [
            'light' => [
                '--primary' => 'hsl(200 65% 38%)',
                '--primary-foreground' => 'hsl(220 70% 56%)',
                '--secondary' => 'hsl(220 35% 88%)',
                '--link' => 'hsl(180 75% 42%)',
            ],
            'dark' => [
                '--primary' => 'hsl(180 55% 72%)',
                '--primary-foreground' => 'hsl(220 65% 82%)',
                '--secondary' => 'hsl(220 30% 24%)',
                '--link' => 'hsl(200 75% 78%)',
            ],
        ],
        [
            'light' => [
                '--primary' => 'hsl(115 55% 32%)',
                '--primary-foreground' => 'hsl(145 60% 67%)',
                '--secondary' => 'hsl(125 30% 88%)',
                '--link' => 'hsl(155 65% 30%)',
            ],
            'dark' => [
                '--primary' => 'hsl(155 45% 68%)',
                '--primary-foreground' => 'hsl(125 60% 90%)',
                '--secondary' => 'hsl(145 25% 22%)',
                '--link' => 'hsl(115 65% 74%)',
            ],
        ],
        [
            'light' => [
                '--primary' => 'hsl(355 60% 43%)',
                '--primary-foreground' => 'hsl(345 70% 78%)',
                '--secondary' => 'hsl(5 35% 90%)',
                '--link' => 'hsl(345 70% 42%)',
            ],
            'dark' => [
                '--primary' => 'hsl(325 55% 72%)',
                '--primary-foreground' => 'hsl(345 65% 72%)',
                '--secondary' => 'hsl(345 30% 25%)',
                '--link' => 'hsl(355 75% 78%)',
            ],
        ],
    ];

    private const array CONTENT_TOPICS = [
        'technology' => [
            'headings' => ['Technologia w praktyce', 'Narzędzia przyszłości', 'Cyfrowe rozwiązania'],
            'sentences' => [
                'Nowoczesne narzędzia zmieniają sposób, w jaki rozwiązujemy codzienne problemy.',
                'Dobrze zaprojektowane oprogramowanie łączy prostotę obsługi z niezawodnością.',
                'Przemyślana architektura ułatwia rozwój i utrzymanie każdego projektu.',
                'Automatyzacja pozwala poświęcić więcej czasu na kreatywne zadania.',
            ],
        ],
        'travel' => [
            'headings' => ['W drogę ku przygodzie', 'Odkrywanie nowych miejsc', 'Podróżnicze inspiracje'],
            'sentences' => [
                'Każda podróż pozwala spojrzeć na znane sprawy z zupełnie nowej perspektywy.',
                'Lokalne smaki i spotkania z mieszkańcami zostają w pamięci na długo.',
                'Najciekawsze szlaki często prowadzą z dala od utartych turystycznych ścieżek.',
                'Dobre przygotowanie daje swobodę, by cieszyć się każdą chwilą wyprawy.',
            ],
        ],
        'food' => [
            'headings' => ['Smaki, które inspirują', 'Sekrety dobrej kuchni', 'Przy stole'],
            'sentences' => [
                'Sezonowe składniki potrafią wydobyć z prostego dania wyjątkowy smak.',
                'Wspólne gotowanie sprawia, że nawet codzienny posiłek staje się okazją do spotkania.',
                'Dobrze dobrane przyprawy nadają potrawom głębi i niepowtarzalnego charakteru.',
                'Najlepsze przepisy zostawiają miejsce na własne pomysły i eksperymenty.',
            ],
        ],
        'health' => [
            'headings' => ['Codzienna troska o siebie', 'Małe kroki ku zdrowiu', 'Dobre nawyki'],
            'sentences' => [
                'Regularny odpoczynek pomaga odzyskać energię i lepiej radzić sobie z wyzwaniami.',
                'Niewielkie zmiany w codziennych nawykach mogą przynieść długofalowe korzyści.',
                'Ruch dopasowany do własnych możliwości wspiera dobre samopoczucie.',
                'Uważność na potrzeby organizmu pomaga zachować równowagę każdego dnia.',
            ],
        ],
    ];

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

        // Explicitly sync roles for bloggers since listeners may be affected by early DB access/caching during seeding
        $bloggers->each(fn(User $blogger) => $blogger->assignRole(UserRole::Blogger->value));

        $this->seedBlogsForBloggers($bloggers, $categories);

        $regularUsers = User::factory(self::REGULAR_USER_COUNT)->create([
            'role' => UserRole::User->value,
        ]);

        // Explicitly sync roles for regular users
        $regularUsers->each(fn(User $user) => $user->assignRole(UserRole::User->value));

        $groupOwnerPool = $bloggers->merge(
            User::where('role', UserRole::Admin->value)->get(),
        );

        $this->seedGroups($groupOwnerPool, $bloggers, $regularUsers);
    }

    private function seedBlogsForBloggers($bloggers, $categories): void
    {
        $bloggers->each(function (User $blogger) use ($categories): void {
            $blogCount = random_int(self::BLOGS_PER_BLOGGER_MIN, self::BLOGS_PER_BLOGGER_MAX);
            $randomBlogPresentation = fn(): array => $this->randomBlogPresentation();

            $blogs = Blog::factory($blogCount)
                ->state(fn(): array => [
                    'user_id' => $blogger->id,
                    ...$randomBlogPresentation(),
                ])
                ->create();

            $blogs->each(
                /**
                 * @throws RandomException
                 */ function (Blog $blog) use ($categories): void {
                    $topic = self::CONTENT_TOPICS[array_rand(self::CONTENT_TOPICS)];
                    $categoryCount = random_int(self::BLOG_CATEGORIES_MIN, self::BLOG_CATEGORIES_MAX);

                    $blog->categories()->attach(
                        $categories->random($categoryCount),
                    );

                    LandingPage::factory()->create([
                        'blog_id' => $blog->id,
                        'content' => $this->generateMarkdownContent($topic),
                    ]);

                    $postCount = random_int(self::POSTS_PER_BLOG_MIN, self::POSTS_PER_BLOG_MAX);
                    $seeder = $this;

                    $posts = Post::factory($postCount)
                        ->state(fn(): array => ['content' => $seeder->generateMarkdownContent($topic)])
                        ->create([
                            'blog_id' => $blog->id,
                            'user_id' => $blog->user_id,
                        ]);

                    $this->seedRelationsForPosts($posts, $blog);
                },
            );
        });
    }

    /**
     * @return array{theme: array{light: array<string, string>, dark: array<string, string>}, footer: string|null, motto: string|null, about: string|null}
     * @throws RandomException
     */
    private function randomBlogPresentation(): array
    {
        $fonts = self::FONT_PAIRS[array_rand(self::FONT_PAIRS)];
        $palette = self::COLOR_PALETTES[array_rand(self::COLOR_PALETTES)];
        $lightTheme = [...$palette['light'], ...$fonts];
        $darkTheme = [...$palette['dark'], ...$fonts];

        return [
            'theme' => ['light' => $lightTheme, 'dark' => $darkTheme],
            'footer' => random_int(0, 1) === 1 ? 'Dziękujemy za odwiedziny! Do zobaczenia wkrótce.' : null,
            'motto' => random_int(0, 1) === 1 ? 'Każda podróż zaczyna się od pierwszego kroku.' : null,
            'about' => random_int(0, 1) === 1 ? 'To miejsce na kilka słów o autorze i tematyce bloga.' : null,
        ];
    }

    private function generateMarkdownContent(array $topic): string
    {
        $generator = new FakedownGenerator([
            'default_language' => 'en',
            'default_length' => random_int(3, 6),
            'templates' => ['en' => $topic],
            'structure' => [
                'enable_headings' => true,
                'enable_lists' => true,
                'enable_code_blocks' => true,
                'enable_tables' => true,
                'enable_blockquotes' => true,
                'enable_links' => true,
                'enable_images' => true,
            ],
        ]);

        return $generator->generate();
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
