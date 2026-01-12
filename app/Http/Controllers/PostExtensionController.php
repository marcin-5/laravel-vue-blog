<?php

namespace App\Http\Controllers;

use App\Http\Requests\PostExtensionRequest;
use App\Models\Post;
use App\Models\PostExtension;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class PostExtensionController extends AuthenticatedController
{
    /**
     * Store a newly created post extension in storage.
     */
    public function store(PostExtensionRequest $request, Post $post): RedirectResponse
    {
        Gate::authorize('update', $post);

        $post->extensions()->create($request->validated());

        return back()->with('success', __('app.messages.extension_created'));
    }

    /**
     * Update the specified post extension in storage.
     */
    public function update(PostExtensionRequest $request, PostExtension $extension): RedirectResponse
    {
        Gate::authorize('update', $extension->post);

        $extension->update($request->validated());

        return back()->with('success', __('app.messages.extension_updated'));
    }

    /**
     * Remove the specified post extension from storage.
     */
    public function destroy(PostExtension $extension): RedirectResponse
    {
        Gate::authorize('update', $extension->post);

        $extension->delete();

        return back()->with('success', __('app.messages.extension_deleted'));
    }
}
