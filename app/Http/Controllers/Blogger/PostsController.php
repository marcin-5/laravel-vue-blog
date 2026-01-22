<?php

namespace App\Http\Controllers\Blogger;

use App\Http\Controllers\AuthenticatedController;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Models\Post;
use App\Services\PostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PostsController extends AuthenticatedController
{
    public function __construct(
        private readonly PostService $postService,
    ) {
        parent::__construct();
        // Only authorize existing post resources (show, update, delete)
        // Don't use authorizeResource for 'store' since it's handled in StorePostRequest
        $this->authorizeResource(Post::class, 'post', [
            'except' => ['store', 'availableExtensions', 'attachExtension', 'detachExtension', 'reorderExtensions'],
        ]);
    }

    /**
     * Store a newly created post in storage.
     */
    public function store(StorePostRequest $request): RedirectResponse
    {
        $blog = $request->getBlog();
        $postData = $request->getPostData();

        $this->postService->createPost($blog, $postData);

        return back()->with('success', __('blogs.messages.post_created'));
    }

    /**
     * Update an existing post.
     */
    public function update(UpdatePostRequest $request, Post $post): RedirectResponse
    {
        $postData = $request->getPostData();

        $this->postService->updatePost($post, $postData);

        return back()->with('success', __('blogs.messages.post_updated'));
    }

    /**
     * Pobierz dostępne rozszerzenia dla bloga
     */
    public function availableExtensions(Post $post): JsonResponse
    {
        $attachedIds = $post->extensions()->pluck('extension_post_id');

        $query = Post::query()
            ->extensionType()
            ->whereNotIn('id', $attachedIds)
            ->where('id', '!=', $post->id)
            ->select(['id', 'title', 'excerpt']);

        if ($post->group_id) {
            $query->where('group_id', $post->group_id);
        } else {
            $query->where('blog_id', $post->blog_id);
        }

        $extensions = $query->get();

        return response()->json($extensions);
    }

    /**
     * Przypisz rozszerzenie do posta
     */
    public function attachExtension(Request $request, Post $post): JsonResponse
    {
        $validated = $request->validate([
            'extension_post_id' => 'required|exists:posts,id',
            'display_order' => 'nullable|integer|min:0',
        ]);

        $post->extensions()->syncWithoutDetaching([
            $validated['extension_post_id'] => [
                'display_order' => $validated['display_order'] ?? 0,
            ],
        ]);

        return response()->json(['message' => 'Extension attached successfully']);
    }

    /**
     * Odłącz rozszerzenie od posta
     */
    public function detachExtension(Request $request, Post $post, int $extensionPostId): JsonResponse
    {
        $post->extensions()->detach($extensionPostId);

        return response()->json(['message' => 'Extension detached successfully']);
    }

    /**
     * Aktualizuj kolejność rozszerzeń
     */
    public function reorderExtensions(Request $request, Post $post): JsonResponse
    {
        $validated = $request->validate([
            'extensions' => 'required|array',
            'extensions.*.id' => 'required|exists:posts,id',
            'extensions.*.display_order' => 'required|integer|min:0',
        ]);

        foreach ($validated['extensions'] as $extension) {
            $post->extensions()->updateExistingPivot($extension['id'], [
                'display_order' => $extension['display_order'],
            ]);
        }

        return response()->json(['message' => 'Extensions reordered successfully']);
    }
}
