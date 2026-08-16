<?php

declare(strict_types=1);

namespace App\Queries\Public;

use App\Models\Blog;
use App\Models\NewsletterSubscription;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class NewsletterQuery
{
    /**
     * @return array{blogs: EloquentCollection<int, Blog>, selectedBlogId: ?int}
     */
    public function handleSubscribe(Request $request): array
    {
        $selectedBlogId = $request->integer('blog_id') ?: null;
        $blogs = $this->getPublishedBlogs();

        $this->setLocaleFromBlog($blogs, $selectedBlogId);

        return [
            'blogs' => $blogs,
            'selectedBlogId' => $selectedBlogId,
        ];
    }

    /**
     * @return array{blogs: EloquentCollection<int, Blog>, currentSubscriptions: Collection<int, array<string, mixed>>}
     */
    public function handleManage(string $email): array
    {
        $subscriptions = NewsletterSubscription::query()
            ->where('email', $email)
            ->with(['blog' => fn($query) => $query->withoutGlobalScopes()->select(['id', 'locale'])])
            ->get();

        $this->setLocaleFromSubscriptions($subscriptions);

        return [
            'blogs' => $this->getPublishedBlogs(),
            'currentSubscriptions' => $this->mapSubscriptions($subscriptions),
        ];
    }

    /**
     * @return EloquentCollection<int, Blog>
     */
    private function getPublishedBlogs(): Collection
    {
        return Blog::query()
            ->where('is_published', true)
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'locale']);
    }

    /**
     * @param EloquentCollection<int, Blog> $blogs
     */
    private function setLocaleFromBlog(Collection $blogs, ?int $blogId): void
    {
        if ($blogId === null) {
            return;
        }

        $currentBlog = $blogs->firstWhere('id', $blogId);
        if ($currentBlog?->locale) {
            app()->setLocale($currentBlog->locale);
        }
    }

    /**
     * @param EloquentCollection<int, NewsletterSubscription> $subscriptions
     */
    private function setLocaleFromSubscriptions(Collection $subscriptions): void
    {
        $locale = $subscriptions->first()?->blog?->locale;
        if ($locale) {
            app()->setLocale($locale);
        }
    }

    /**
     * @param Collection<int, NewsletterSubscription> $subscriptions
     * @return Collection<int, array{blog_id: int, frequency: string, send_time: string|null, send_time_weekend: string|null, send_day: int|null}>
     */
    private function mapSubscriptions(Collection $subscriptions): Collection
    {
        return $subscriptions->map(fn(NewsletterSubscription $subscription): array => [
            'blog_id' => $subscription->blog_id,
            'frequency' => $subscription->frequency,
            'send_time' => $subscription->send_time,
            'send_time_weekend' => $subscription->send_time_weekend,
            'send_day' => $subscription->send_day,
        ])->values();
    }
}
