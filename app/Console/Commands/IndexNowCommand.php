<?php

namespace App\Console\Commands;

use App\Models\Blog;
use App\Models\IndexNowQueuedUrl;
use App\Models\Post;
use App\Services\IndexNowService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('blog:indexnow {path? : blog_slug, blog_slug/post_slug, or blog_slug/about} {--locale= : Blog locale (pl or en)} {--logs : Show only recent logs}')]
#[Description('Submit URLs to IndexNow API')]
class IndexNowCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(IndexNowService $indexNowService): int
    {
        if ($this->option('logs')) {
            $this->displayRecentLogs(20);

            return self::SUCCESS;
        }

        $path = $this->argument('path');
        $locale = $this->option('locale');

        if ($locale && !in_array($locale, config('app.supported_locales', []), true)) {
            $this->error('Locale must be one of: ' . implode(', ', config('app.supported_locales', [])) . '.');

            return self::FAILURE;
        }

        if (!$path) {
            // Submit all pages
            $this->info('Submitting all blogs, about pages, and posts...');
            $urls = $this->getAllUrls();
        } elseif (!str_contains($path, '/')) {
            // Submit all pages for a given blog
            $blogSlug = $path;
            $blog = $this->findBlog($blogSlug);
            if (!$blog) {
                return self::FAILURE;
            }
            if (!$blog->is_published) {
                $this->error("Blog is not published: $blogSlug");

                return self::FAILURE;
            }
            $this->info("Submitting all pages for blog: $blogSlug...");
            $urls = $this->getBlogUrls($blog);
        } else {
            // Submit a single page
            [$blogSlug, $pageSlug] = explode('/', $path, 2);
            $blog = $this->findBlog($blogSlug);

            if (!$blog) {
                return self::FAILURE;
            }

            if ($pageSlug === 'about') {
                if (!$blog->is_published) {
                    $this->error("Blog is not published: $blogSlug");

                    return self::FAILURE;
                }

                $this->info("Submitting about page: $blogSlug/about...");
                $urls = [$this->getAboutUrl($blog)];
            } else {
                $post = $blog->posts()
                    ->published()
                    ->public()
                    ->whereNull('group_id')
                    ->where('slug', $pageSlug)
                    ->first();

                if (!$post) {
                    $this->error("Post not found: $blogSlug/$pageSlug");

                    return self::FAILURE;
                }
                $this->info("Submitting post: $blogSlug/$pageSlug...");
                $urls = [route('blog.public.post', [
                    'blog' => $blogSlug,
                    'postSlug' => $pageSlug,
                    'mainDomain' => $blog->main_domain,
                ])];
            }
        }

        if (empty($urls)) {
            $this->warn('No URLs found to submit.');
            return self::SUCCESS;
        }

        $filteredUrls = array_filter($urls, function ($url) use ($indexNowService) {
            // In manual command, we can skip checking is_published/visibility
            // if the user asks for it, but according to requirements, we should stick to the rules.
            // However, the artisan command is usually used to force, so I will only check robots.txt.
            return $indexNowService->isAllowedByRobots($url);
        });

        if (empty($filteredUrls)) {
            $this->warn('All URLs were filtered out by robots.txt.');
            return self::SUCCESS;
        }

        $this->info('Submitting ' . count($filteredUrls) . ' URLs to IndexNow...');
        if ($indexNowService->submitUrls($filteredUrls)) {
            $this->info('Successfully submitted URLs.');
        } else {
            $this->error('Failed to submit URLs.');
        }

        $this->displayRecentLogs();

        return self::SUCCESS;
    }

    /**
     * Displays the most recent lines from the Laravel log file and the status of the pending queue.
     *
     * This method reads the log file located at 'storage/logs/laravel.log'
     * and outputs a specified number of recent lines using the \`tail\` command
     * for efficiency. If the log file does not exist or is empty, appropriate
     * warnings will be displayed.
     *
     * @param  int  $lines  The number of recent log lines to display. Defaults to 10.
     *
     * @return void
     */
    protected function displayRecentLogs(int $lines = 10): void
    {
        $this->displayPendingQueueStatus();

        $logPath = storage_path('logs/laravel.log');

        if (!file_exists($logPath)) {
            $this->warn('Log file not found at: ' . $logPath);

            return;
        }

        $this->newLine();
        $this->info('Recent IndexNow logs from laravel.log:');

        // Escaping double quotes for shell command
        $pattern = escapeshellarg('IndexNow API response');
        $filePath = escapeshellarg($logPath);
        $output = shell_exec("grep -a $pattern $filePath | tail -n $lines");

        if ($output) {
            $this->line($output);
        } else {
            $this->warn('No IndexNow log entries found or log file is empty.');
        }
    }

    /**
     * Display the current status of the pending URLs queue.
     *
     * @return void
     */
    protected function displayPendingQueueStatus(): void
    {
        $pendingCount = IndexNowQueuedUrl::count();
        $this->newLine();
        $this->info("Pending IndexNow URL queue: $pendingCount URL(s) waiting to be submitted.");
    }

    protected function getAllUrls(): array
    {
        $urls = [];

        Blog::withoutGlobalScopes()->where('is_published', true)->each(function (Blog $blog) use (&$urls): void {
            $urls[] = $blog->public_url;
            $urls[] = $this->getAboutUrl($blog);
        });

        Post::published()->public()->whereNull('group_id')->whereHas('blog', fn($q) => $q
            ->withoutGlobalScopes()
            ->where('is_published', true))
            ->with('blog')
            ->each(function (Post $post) use (&$urls): void {
                $urls[] = route('blog.public.post', [
                    'blog' => $post->blog->slug,
                    'postSlug' => $post->slug,
                    'mainDomain' => $post->blog->main_domain,
                ]);
            });

        return $urls;
    }

    protected function getBlogUrls(Blog $blog): array
    {
        $urls = [$blog->public_url, $this->getAboutUrl($blog)];

        $blog->posts()->published()->public()->whereNull('group_id')->each(function (Post $post) use ($blog, &$urls): void {
            $urls[] = route('blog.public.post', [
                'blog' => $blog->slug,
                'postSlug' => $post->slug,
                'mainDomain' => $blog->main_domain,
            ]);
        });

        return $urls;
    }

    protected function findBlog(string $slug): ?Blog
    {
        $query = Blog::withoutGlobalScopes()->where('slug', $slug);
        $locale = $this->option('locale');

        if ($locale) {
            $query->where('locale', $locale);
        }

        $blogs = $query->get();

        if ($blogs->isEmpty()) {
            $this->error("Blog not found: $slug");

            return null;
        }

        if ($blogs->count() > 1) {
            $this->error("Multiple blogs found for slug '$slug'. Use --locale=pl or --locale=en.");

            return null;
        }

        return $blogs->first();
    }

    protected function getAboutUrl(Blog $blog): string
    {
        return route('blog.public.about', [
            'blog' => $blog->slug,
            'mainDomain' => $blog->main_domain,
        ]);
    }
}
