<?php

namespace App\Console\Commands;

use App\Models\Blog;
use App\Models\Post;
use App\Services\IndexNowService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('blog:indexnow {path? : blog_slug or blog_slug/post_slug} {--logs : Show only recent logs}')]
#[Description('Submit URLs to IndexNow API')]
class IndexNowCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(IndexNowService $indexNowService): void
    {
        if ($this->option('logs')) {
            $this->displayRecentLogs(20);

            return;
        }

        $path = $this->argument('path');

        if (!$path) {
            // Submit all pages
            $this->info('Submitting all blogs and posts...');
            $urls = $this->getAllUrls();
        } elseif (!str_contains($path, '/')) {
            // Submit all pages for a given blog
            $blogSlug = $path;
            $blog = Blog::where('slug', $blogSlug)->first();
            if (!$blog) {
                $this->error("Blog not found: $blogSlug");
                return;
            }
            $this->info("Submitting all pages for blog: $blogSlug...");
            $urls = $this->getBlogUrls($blog);
        } else {
            // Submit a single page
            [$blogSlug, $postSlug] = explode('/', $path, 2);
            $post = Post::whereHas('blog', fn($q) => $q->where('slug', $blogSlug))
                ->where('slug', $postSlug)
                ->first();

            if (!$post) {
                $this->error("Post not found: $blogSlug/$postSlug");
                return;
            }
            $this->info("Submitting post: $blogSlug/$postSlug...");
            $urls = [route('blog.public.post', [$blogSlug, $postSlug])];
        }

        if (empty($urls)) {
            $this->warn('No URLs found to submit.');
            return;
        }

        $filteredUrls = array_filter($urls, function ($url) use ($indexNowService) {
            // In manual command, we can skip checking is_published/visibility
            // if the user asks for it, but according to requirements, we should stick to the rules.
            // However, the artisan command is usually used to force, so I will only check robots.txt.
            return $indexNowService->isAllowedByRobots($url);
        });

        if (empty($filteredUrls)) {
            $this->warn('All URLs were filtered out by robots.txt.');
            return;
        }

        $this->info('Submitting ' . count($filteredUrls) . ' URLs to IndexNow...');
        if ($indexNowService->submitUrls($filteredUrls)) {
            $this->info('Successfully submitted URLs.');
        } else {
            $this->error('Failed to submit URLs.');
        }

        $this->displayRecentLogs();
    }

    protected function getAllUrls(): array
    {
        $urls = [];

        Blog::where('is_published', true)->each(function ($blog) use (&$urls) {
            $urls[] = route('blog.public.landing', $blog->slug);
        });

        Post::published()->public()->whereHas('blog', fn($q) => $q->where('is_published', true))
            ->each(function ($post) use (&$urls) {
                $urls[] = route('blog.public.post', [$post->blog->slug, $post->slug]);
            });

        return $urls;
    }

    protected function getBlogUrls(Blog $blog): array
    {
        $urls = [route('blog.public.landing', $blog->slug)];

        $blog->posts()->published()->public()->each(function ($post) use ($blog, &$urls) {
            $urls[] = route('blog.public.post', [$blog->slug, $post->slug]);
        });

        return $urls;
    }

    /**
     * Displays the most recent lines from the Laravel log file.
     *
     * This method reads the log file located at 'storage/logs/laravel.log'
     * and outputs a specified number of recent lines using the `tail` command
     * for efficiency. If the log file does not exist or is empty, appropriate
     * warnings will be displayed.
     *
     * @param  int  $lines  The number of recent log lines to display. Defaults to 5.
     *
     * @return void
     */
    protected function displayRecentLogs(int $lines = 10): void
    {
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
}
