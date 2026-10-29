<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Blog;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('blog:post-urls {host : Blog subdomain (for example blog.example.com)}')]
#[Description('List public post URLs for a blog subdomain')]
class BlogPostUrlsCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $blog = Blog::fromHost((string) $this->argument('host'));

        if (!$blog) {
            $this->error('Blog subdomain not found.');

            return self::FAILURE;
        }

        foreach ($blog->posts()
            ->published()
            ->public()
            ->whereNull('group_id')
            ->orderByPublicationDate()
            ->get() as $post) {
            $this->line($post->public_url);
        }

        return self::SUCCESS;
    }
}
