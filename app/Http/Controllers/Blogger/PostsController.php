<?php

namespace App\Http\Controllers\Blogger;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Models\Post;
use App\Services\PostService;
use Illuminate\Http\RedirectResponse;

class PostsController extends Controller
{
    public function __construct(
        private readonly PostService $postService,
    ) {
        $this->middleware(['auth', 'verified']);
        // Only authorize existing post resources (show, update, delete)
        // Don't use authorizeResource for 'store' since it's handled in StorePostRequest
        $this->authorizeResource(Post::class, 'post', [
            'except' => ['store']
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

}
