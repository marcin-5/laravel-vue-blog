<?php

namespace App\Http\Controllers\Blogger;

use App\Http\Controllers\AuthenticatedController;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Models\Post;
use App\Services\PostService;
use Illuminate\Http\RedirectResponse;

class PostsController extends AuthenticatedController
{
    public function __construct(
        private readonly PostService $postService,
    ) {
        parent::__construct();
        $this->authorizeResource(Post::class, 'post', ['except' => ['store']]);
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
}
