<?php

use App\Http\Controllers\PublicBlogController;
use App\Models\Blog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Inertia\Inertia;


Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Blogs: index (list current user's blogs)
Route::get('blogs', function () {
    $user = auth()->user();
    abort_unless(!!$user, 403);

    $blogs = Blog::query()
        ->where('user_id', $user->id)
        ->with(['categories:id,name'])
        ->orderByDesc('created_at')
        ->get(['id', 'user_id', 'name', 'slug', 'description', 'is_published', 'created_at']);

    $categories = \App\Models\Category::query()
        ->orderBy('name')
        ->get(['id', 'name']);

    return Inertia::render('Blogs', [
        'blogs' => $blogs,
        'categories' => $categories,
        'canCreate' => $user->canCreateBlog(),
    ]);
})->middleware(['auth', 'verified'])->name('blogs.index');

// Admin: users management page
Route::get('admin/users', function () {
    // Ensure only admins can view
    abort_unless(auth()->user() && auth()->user()->isAdmin(), 403);

    $users = User::query()
        ->select(['id', 'name', 'email', 'role', 'blog_quota'])
        ->orderBy('name')
        ->get();

    return Inertia::render('Admin/Users', [
        'users' => $users,
        'currentUserIsAdmin' => auth()->user()?->isAdmin() ?? false,
    ]);
})->middleware(['auth', 'verified', 'can:edit-user-blog-quota'])->name('admin.users.index');

// Admin: update user role and blog_quota
Route::patch('admin/users/{user}', function (Request $request, User $user) {
    // Ensure only admins can perform updates
    abort_unless(auth()->user() && auth()->user()->isAdmin(), 403);

    // Keep the original role from DB to enforce blog_quota edit rules
    $originalRole = $user->role;

    $validated = $request->validate([
        'role' => ['required', 'in:admin,blogger,user'],
        'blog_quota' => ['nullable', 'integer', 'min:0'],
    ]);

    $user->role = $validated['role'];

    // Only allow blog_quota modifications when:
    //  - the authenticated user is admin (already checked), and
    //  - the target user's original DB role was blogger or admin.
    if ($request->user()?->isAdmin() && in_array(
            $originalRole,
            [User::ROLE_BLOGGER, User::ROLE_ADMIN],
            true,
        ) && array_key_exists('blog_quota', $validated)) {
        $user->blog_quota = $validated['blog_quota'] ?? 0;
    }

    $user->save();

    return back()->with('success', 'User updated successfully.');
})->middleware(['auth', 'verified', 'can:edit-user-blog-quota'])->name('admin.users.update');

// Blogs: create new blog (admin or blogger), enforcing quota
Route::post('blogs', function (Request $request) {
    $user = $request->user();
    abort_unless($user && ($user->isAdmin() || $user->isBlogger()), 403);

    // Enforce quota for bloggers (admins bypass)
    if (!$user->isAdmin()) {
        abort_unless($user->canCreateBlog(), 403, 'Blog quota reached.');
    }

    $name = trim((string)($request->input('name') ?: 'New Blog'));
    $base = Str::slug($name);
    $slug = $base ?: 'blog';
    $i = 1;
    while (Blog::where('slug', $slug)->exists()) {
        $slug = ($base ?: 'blog') . '-' . $i++;
    }

    $validated = $request->validate([
        'description' => ['nullable', 'string'],
        'categories' => ['sometimes', 'array'],
        'categories.*' => ['integer', 'exists:categories,id'],
    ]);

    $blog = Blog::create([
        'user_id' => $user->id,
        'name' => $name,
        'slug' => $slug,
        'description' => $validated['description'] ?? null,
        'is_published' => false,
    ]);

    if (!empty($validated['categories'])) {
        $blog->categories()->sync($validated['categories']);
    }

    return redirect()->route('blogs.index')->with('success', 'Blog created successfully.');
})->middleware(['auth', 'verified'])->name('blogs.store');

// Blogs: update (owner or admin)
Route::patch('blogs/{blog}', function (Request $request, Blog $blog) {
    $user = $request->user();
    abort_unless($user, 403);
    abort_unless($user->isAdmin() || $blog->user_id === $user->id, 403);

    $validated = $request->validate([
        'name' => ['sometimes', 'required', 'string', 'max:255'],
        'description' => ['nullable', 'string'],
        'is_published' => ['sometimes', 'boolean'],
        'categories' => ['sometimes', 'array'],
        'categories.*' => ['integer', 'exists:categories,id'],
    ]);

    if (array_key_exists('name', $validated)) {
        $blog->name = $validated['name'];
        // Regenerate slug if name changed and slug not explicitly provided
        $base = Str::slug($validated['name']);
        $slug = $base ?: 'blog';
        $i = 1;
        while (Blog::where('slug', $slug)->where('id', '!=', $blog->id)->exists()) {
            $slug = ($base ?: 'blog') . '-' . $i++;
        }
        $blog->slug = $slug;
    }

    if (array_key_exists('description', $validated)) {
        $blog->description = $validated['description'];
    }

    if (array_key_exists('is_published', $validated)) {
        $blog->is_published = (bool)$validated['is_published'];
    }

    $blog->save();

    if (array_key_exists('categories', $validated)) {
        $blog->categories()->sync($validated['categories'] ?? []);
    }

    return back()->with('success', 'Blog updated successfully.');
})->middleware(['auth', 'verified'])->name('blogs.update');

// Admin: categories management page
Route::get('admin/categories', function () {
    abort_unless(auth()->user() && auth()->user()->isAdmin(), 403);

    $categories = \App\Models\Category::query()
        ->withCount('blogs')
        ->orderBy('name')
        ->get(['id', 'name', 'slug']);

    return Inertia::render('Admin/Categories', [
        'categories' => $categories,
    ]);
})->middleware(['auth', 'verified'])->name('admin.categories.index');

// Admin: create category
Route::post('admin/categories', function (Request $request) {
    abort_unless(auth()->user() && auth()->user()->isAdmin(), 403);

    $validated = $request->validate([
        'name' => ['required', 'string', 'max:255'],
    ]);

    $base = Str::slug($validated['name']);
    $slug = $base ?: 'category';
    $i = 1;
    while (\App\Models\Category::where('slug', $slug)->exists()) {
        $slug = ($base ?: 'category') . '-' . $i++;
    }

    \App\Models\Category::create([
        'name' => $validated['name'],
        'slug' => $slug,
    ]);

    return back()->with('success', 'Category created.');
})->middleware(['auth', 'verified'])->name('admin.categories.store');

// Admin: update category
Route::patch('admin/categories/{category}', function (Request $request, \App\Models\Category $category) {
    abort_unless(auth()->user() && auth()->user()->isAdmin(), 403);

    $validated = $request->validate([
        'name' => ['required', 'string', 'max:255'],
    ]);

    $category->name = $validated['name'];
    $base = Str::slug($validated['name']);
    $slug = $base ?: 'category';
    $i = 1;
    while (\App\Models\Category::where('slug', $slug)->where('id', '!=', $category->id)->exists()) {
        $slug = ($base ?: 'category') . '-' . $i++;
    }
    $category->slug = $slug;
    $category->save();

    return back()->with('success', 'Category updated.');
})->middleware(['auth', 'verified'])->name('admin.categories.update');

// Admin: delete category
Route::delete('admin/categories/{category}', function (Request $request, \App\Models\Category $category) {
    abort_unless(auth()->user() && auth()->user()->isAdmin(), 403);

    $category->delete();

    return back()->with('success', 'Category deleted.');
})->middleware(['auth', 'verified'])->name('admin.categories.destroy');

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';

// Public blog routes: must be at the very end to avoid conflicts with app routes above
Route::get('{blog:slug}/{postSlug}', [PublicBlogController::class, 'post'])->name('blog.public.post');
Route::get('{blog:slug}', [PublicBlogController::class, 'landing'])->name('blog.public.landing');
