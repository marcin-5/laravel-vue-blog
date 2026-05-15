<?php

namespace App\Http\Controllers\Blogger;

use App\Http\Controllers\AuthenticatedController;
use App\Http\Requests\Blogger\AttachPostExtensionRequest;
use App\Http\Requests\Blogger\ReorderPostExtensionsRequest;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Models\Post;
use App\Services\PostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class PostsController extends AuthenticatedController
{
    public function __construct(
        private readonly PostService $postService,
    ) {
        parent::__construct();
        // Authorize extension management actions as 'update' on the post
        $this->middleware('can:update,post')->only([
            'availableExtensions',
            'attachExtension',
            'detachExtension',
            'reorderExtensions',
        ]);

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
        $group = $request->getGroup();
        $postData = $request->getPostData();

        $this->postService->createPost($blog, $postData, $request->user()->id, $group);

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
        $extensions = $this->postService->getAvailableExtensions($post);

        return response()->json($extensions);
    }

    /**
     * Przypisz rozszerzenie do posta
     */
    public function attachExtension(AttachPostExtensionRequest $request, Post $post): JsonResponse
    {
        $this->postService->attachExtension(
            $post,
            $request->validated('extension_post_id'),
            $request->validated('display_order', 0),
        );

        return response()->json(['message' => 'Extension attached successfully']);
    }

    /**
     * Odłącz rozszerzenie od posta
     */
    public function detachExtension(Post $post, int $extensionPostId): JsonResponse
    {
        $this->postService->detachExtension($post, $extensionPostId);

        return response()->json(['message' => 'Extension detached successfully']);
    }

    /**
     * Aktualizuj kolejność rozszerzeń
     */
    public function reorderExtensions(ReorderPostExtensionsRequest $request, Post $post): JsonResponse
    {
        $this->postService->reorderExtensions($post, $request->validated('extensions'));

        return response()->json(['message' => 'Extensions reordered successfully']);
    }
}
