<?php

namespace App\Http\Controllers\Blogger;

use App\Builders\SimpleSeoBuilder;
use App\Http\Controllers\AuthenticatedController;
use App\Http\Requests\StoreBlogRequest;
use App\Http\Requests\UpdateBlogRequest;
use App\Models\Blog;
use App\Services\BlogService;
use App\Services\TranslationService;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BlogsController extends AuthenticatedController
{
    public function __construct(
        private readonly BlogService $blogService,
        private readonly TranslationService $translations,
        private readonly SimpleSeoBuilder $seoBuilder,
    ) {
        parent::__construct();
        $this->authorizeResource(Blog::class, 'blog');
    }

    /**
     * Display a listing of the authenticated user's blogs.
     * @throws FileNotFoundException
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Blog::class);
        $user = $request->user();

        $blogs = $this->blogService->getUserBlogs($user);
        $categories = $this->blogService->getCategories();

        return Inertia::render('app/blogger/Blogs', [
            'blogs' => $blogs,
            'categories' => $categories,
            'canCreate' => $user->canCreateBlog(),
            'translations' => [
                'locale' => app()->getLocale(),
                'messages' => $this->translations->getPageTranslations('dashboard'),
            ],
            'seo' => $this->seoBuilder->build('My Blogs')->toArray(),
        ]);
    }

    /**
     * Store a newly created blog in storage.
     */
    public function store(StoreBlogRequest $request): RedirectResponse
    {
        $blogData = $request->getBlogData();
        $categories = $request->getCategories();

        $this->blogService->createBlog($blogData, $categories);

        return redirect()->route('blogs.index')->with('success', __('blogs.messages.blog_created'));
    }

    /**
     * Update the specified blog in storage.
     */
    public function update(UpdateBlogRequest $request, Blog $blog): RedirectResponse
    {
        $blogData = $request->getBlogData();
        $categories = $request->hasCategories() ? $request->getCategories() : null;

        $this->blogService->updateBlog($blog, $blogData, $categories);

        return back()->with('success', __('blogs.messages.blog_updated'));
    }
}
