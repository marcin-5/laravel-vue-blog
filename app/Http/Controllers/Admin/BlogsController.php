<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateBlogBingVerificationTokenRequest;
use App\Models\Blog;
use Illuminate\Http\RedirectResponse;

class BlogsController extends Controller
{
    public function updateBingVerificationToken(
        UpdateBlogBingVerificationTokenRequest $request,
        Blog $blog,
    ): RedirectResponse {
        $blog->update($request->validated());

        return back()->with('success', __('Blog verification token updated.'));
    }
}
