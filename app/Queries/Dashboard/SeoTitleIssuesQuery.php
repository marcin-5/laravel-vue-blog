<?php

declare(strict_types=1);

namespace App\Queries\Dashboard;

use App\Builders\PublicBlogSeoBuilder;
use App\Models\Blog;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Collection;

class SeoTitleIssuesQuery
{
    private const int TITLE_LENGTH_LIMIT = 15;

    public function __construct(
        private readonly PublicBlogSeoBuilder $seoBuilder,
    ) {}

    /**
     * @return Collection<int, array{type: 'blog'|'post', blog_id: int, post_id: int|null, blog_name: string, title: string, length: int, edit_url: string}>
     */
    public function handle(User $user): Collection
    {
        return Blog::query()
            ->where('user_id', $user->id)
            ->with('posts')
            ->get()
            ->flatMap(function (Blog $blog): Collection {
                $issues = collect();
                $blogIssue = $this->makeIssue($blog, null);

                if ($blogIssue !== null) {
                    $issues->push($blogIssue);
                }

                foreach ($blog->posts as $post) {
                    $postIssue = $this->makeIssue($blog, $post);

                    if ($postIssue !== null) {
                        $issues->push($postIssue);
                    }
                }

                return $issues;
            })
            ->values();
    }

    /**
     * @return array{type: 'blog'|'post', blog_id: int, post_id: int|null, blog_name: string, title: string, length: int, edit_url: string}|null
     */
    private function makeIssue(Blog $blog, ?Post $post): ?array
    {
        $title = trim(html_entity_decode(
            $this->seoBuilder->resolveHtmlTitle($blog, $post),
            ENT_QUOTES,
            'UTF-8',
        ));
        $length = mb_strlen($title);

        if ($length >= self::TITLE_LENGTH_LIMIT) {
            return null;
        }

        return [
            'type' => $post === null ? 'blog' : 'post',
            'blog_id' => $blog->id,
            'post_id' => $post?->id,
            'blog_name' => $blog->name,
            'title' => $title,
            'length' => $length,
            'edit_url' => route('blogs.index') . '#blog-' . $blog->id,
        ];
    }
}
