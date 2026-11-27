<?php

namespace App\Http\Controllers\Blogger;

use App\Http\Controllers\AuthenticatedController;
use App\Http\Requests\Blogger\AttachPostExtensionRequest;
use App\Http\Requests\Blogger\DetachPostExtensionRequest;
use App\Http\Requests\Blogger\ReorderPostExtensionsRequest;
use App\Models\Post;
use App\Services\PostService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class PostExtensionsController extends AuthenticatedController
{
    public function __construct(private readonly PostService $postService)
    {
        parent::__construct();
    }

    public function index(Request $request, Post $post): Response
    {
        $this->authorize('update', $post);

        $context = $this->context($request, $post);

        return Inertia::render('app/blogger/PostExtensions', [
            'post' => $post->only(['id', 'title']),
            'extensions' => $post
                ->extensions()
                ->get(['posts.id', 'posts.title', 'posts.excerpt'])
                ->map(fn(Post $extension): array => [
                    'id' => $extension->id,
                    'title' => $extension->title,
                    'excerpt' => $extension->excerpt,
                    'display_order' => $extension->attachment->display_order,
                ])
                ->values(),
            'availableExtensions' => $this->postService
                ->getAvailableExtensions($post)
                ->map(fn(Post $extension): array => [
                    'id' => $extension->id,
                    'title' => $extension->title,
                    'excerpt' => $extension->excerpt,
                ])
                ->values(),
            'context' => $context,
            'backUrl' => $context === 'group' ? route('blogger.groups.content.index') : route('blogs.index'),
        ]);
    }

    private function context(Request $request, Post $post): string
    {
        return $request->query('context') === 'group' && $post->group_id !== null ? 'group' : 'blog';
    }

    public function store(AttachPostExtensionRequest $request, Post $post): RedirectResponse
    {
        $this->authorize('update', $post);

        $displayOrder = (int) ($request->validated('display_order') ?? $post->extensions()->count());

        $this->postService->attachExtension($post, (int) $request->validated('extension_post_id'), $displayOrder);

        return $this
            ->redirectToIndex($request, $post)
            ->with('success', __('blogger.extensions.attached_success'));
    }

    private function redirectToIndex(Request $request, Post $post): RedirectResponse
    {
        $parameters = ['post' => $post->id];

        if ($this->context($request, $post) === 'group') {
            $parameters['context'] = 'group';
        }

        return redirect()->route('blogger.posts.extensions.index', $parameters);
    }

    public function destroy(DetachPostExtensionRequest $request, Post $post): RedirectResponse
    {
        $this->authorize('update', $post);

        $this->postService->detachExtension($post, (int) $request->validated('extension_post_id'));

        return $this
            ->redirectToIndex($request, $post)
            ->with('success', __('blogger.extensions.detached_success'));
    }

    /**
     * @throws Throwable
     */
    public function reorder(ReorderPostExtensionsRequest $request, Post $post): RedirectResponse
    {
        $this->authorize('update', $post);

        $extensions = array_map(
            static fn(array $extension): array => [
                'id' => (int) $extension['id'],
                'display_order' => (int) $extension['display_order'],
            ],
            $request->validated('extensions'),
        );

        DB::transaction(fn() => $this->postService->reorderExtensions($post, $extensions));

        return $this
            ->redirectToIndex($request, $post)
            ->with('success', __('blogger.extensions.reordered_success'));
    }
}
