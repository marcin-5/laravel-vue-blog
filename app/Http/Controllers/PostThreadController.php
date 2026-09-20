<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreThreadRequest;
use App\Http\Resources\ThreadResource;
use App\Models\Post;
use App\Models\Thread;
use App\Policies\ThreadPolicy;
use App\Services\CommentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PostThreadController extends Controller
{
    public function index(Request $request, Post $post): AnonymousResourceCollection
    {
        abort_unless((new ThreadPolicy)->viewAny($request->user(), $post), 403);

        $threads = $post
            ->threads()
            ->with('user')
            ->withCount('comments')
            ->when($request->user() === null, function ($query) {
                $query->where('visibility', Thread::VIS_PUBLIC);
            })
            ->latest()
            ->latest('id')
            ->get();

        return ThreadResource::collection($threads);
    }

    public function store(StoreThreadRequest $request, Post $post, CommentService $commentService): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 401);

        $thread = $commentService->createThread($post, $user, $request->validated());

        return new ThreadResource($thread)->response()->setStatusCode(201);
    }
}
