<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Thread;
use App\Policies\ThreadPolicy;
use App\Services\CommentService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection as BaseCollection;

class ThreadCommentController extends Controller
{
    public function index(Request $request, Thread $thread): AnonymousResourceCollection
    {
        abort_unless((new ThreadPolicy)->view($request->user(), $thread), 403);

        $comments = $thread
            ->comments()
            ->with('user')
            ->oldest()
            ->oldest('id')
            ->get();
        /** @var BaseCollection<int|string, Collection<int, Comment>> $commentsByParent */
        $commentsByParent = collect($comments->groupBy(fn(Comment $comment): int => $comment->parent_id ?? 0)->all());

        $buildTree = function (Collection $comments) use (&$buildTree, $commentsByParent): Collection {
            return $comments->map(function (Comment $comment) use (&$buildTree, $commentsByParent): Comment {
                $comment->setRelation(
                    'children',
                    $buildTree($commentsByParent->get($comment->id, new Collection)),
                );

                return $comment;
            });
        };

        return CommentResource::collection($buildTree($commentsByParent->get(0, new Collection)));
    }

    public function store(
        StoreCommentRequest $request,
        Thread $thread,
        CommentService $commentService,
    ): JsonResponse {
        $user = $request->user();
        abort_unless($user !== null, 401);

        $comment = $commentService->createComment($thread, $user, $request->validated());

        return new CommentResource($comment)->response()->setStatusCode(201);
    }
}
