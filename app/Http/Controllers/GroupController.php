<?php

namespace App\Http\Controllers;

use App\Models\Group;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GroupController extends Controller
{
    use AuthorizesRequests;

    public function landing(Request $request, Group $group): Response
    {
        $this->authorize('view', $group);

        $posts = $group->posts()
            ->forGroupView()
            ->paginate($group->page_size ?? 15);

        return Inertia::render('app/group/Landing', [
            'group' => $group,
            'posts' => $posts->items(),
            'pagination' => [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'total' => $posts->total(),
            ],
            'theme' => $group->theme,
            'sidebar' => $group->sidebar,
        ]);
    }

    public function post(Request $request, Group $group, string $postSlug): Response
    {
        $this->authorize('view', $group);

        $post = $group->posts()
            ->where('slug', $postSlug)
            ->forGroupView()
            ->firstOrFail();

        return Inertia::render('app/group/Post', [
            'group' => $group,
            'post' => $post,
            'theme' => $group->theme,
            'sidebar' => $group->sidebar,
        ]);
    }
}
