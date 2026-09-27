<?php

namespace App\Http\Controllers;

use App\Http\Requests\PrivateConversationIndexRequest;
use App\Http\Requests\StorePrivateConversationRequest;
use App\Http\Requests\StorePrivateMessageRequest;
use App\Http\Requests\UpdatePrivateConversationNotificationsRequest;
use App\Http\Requests\UpdatePrivateMessageRequest;
use App\Http\Resources\PrivateConversationResource;
use App\Models\Blog;
use App\Models\Post;
use App\Models\Group;
use App\Models\PrivateConversation;
use App\Models\PrivateMessage;
use App\Services\PrivateConversationService;
use App\Services\TranslationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PrivateConversationController extends Controller
{
    public function __construct(
        private readonly PrivateConversationService $conversationService,
        private readonly TranslationService $translations,
    ) {}

    public function blogIndex(PrivateConversationIndexRequest $request): Response
    {
        return $this->renderIndex($request, 'blog');
    }

    public function groupIndex(PrivateConversationIndexRequest $request): Response
    {
        return $this->renderIndex($request, 'group');
    }

    public function blogShow(
        PrivateConversationIndexRequest $request,
        PrivateConversation $privateConversation,
    ): Response {
        return $this->renderShow($request, $privateConversation, 'blog');
    }

    public function groupShow(
        PrivateConversationIndexRequest $request,
        PrivateConversation $privateConversation,
    ): Response {
        return $this->renderShow($request, $privateConversation, 'group');
    }

    private function renderShow(
        PrivateConversationIndexRequest $request,
        PrivateConversation $privateConversation,
        string $context,
    ): Response {
        $this->authorize('view', $privateConversation);
        abort_unless($this->conversationMatchesContext($privateConversation, $context), 404);

        $privateConversation->load([
            'initiator:id,name',
            'owner:id,name',
            'participants',
            'blog:id,name,slug,locale',
            'group:id,name,slug',
            'post:id,title,slug,blog_id,group_id',
            'post.blog:id,name,slug,locale',
            'post.group:id,name,slug',
            'messages' => fn($query) => $query->with('user:id,name')->oldest()->oldest('id'),
        ]);

        return $this->renderIndex($request, $context, $privateConversation);
    }

    public function store(StorePrivateConversationRequest $request): RedirectResponse
    {
        $target = $request->filled('post_id')
            ? Post::query()->findOrFail($request->validated('post_id'))
            : Group::query()->findOrFail($request->validated('group_id'));
        $conversation = $this->conversationService->create(
            $target,
            $request->user(),
            $request->validated(),
        );

        $route = $conversation->blog_id !== null
            ? 'blog-private-conversations.show'
            : 'group-private-conversations.show';

        return redirect()->route($route, $conversation);
    }

    public function reply(
        StorePrivateMessageRequest $request,
        PrivateConversation $privateConversation,
    ): RedirectResponse {
        $this->conversationService->reply(
            $privateConversation,
            $request->user(),
            $request->validated('content'),
        );

        return back();
    }

    public function update(
        UpdatePrivateMessageRequest $request,
        PrivateMessage $privateMessage,
    ): RedirectResponse {
        $this->conversationService->updateMessage(
            $privateMessage,
            $request->validated('content'),
        );

        return back();
    }

    public function destroy(PrivateMessage $privateMessage): RedirectResponse
    {
        Gate::authorize('delete', $privateMessage);
        $conversation = $privateMessage->conversation;
        $conversationDeleted = $this->conversationService->deleteMessage($privateMessage);

        if ($conversationDeleted) {
            return redirect()->route($conversation->blog_id !== null
                ? 'blog-private-conversations.index'
                : 'group-private-conversations.index');
        }

        return back();
    }

    public function destroyConversation(PrivateConversation $privateConversation): RedirectResponse
    {
        Gate::authorize('delete', $privateConversation);
        $indexRoute = $privateConversation->blog_id !== null
            ? 'blog-private-conversations.index'
            : 'group-private-conversations.index';

        $this->conversationService->deleteConversation($privateConversation);

        return redirect()->route($indexRoute);
    }

    public function updateNotifications(
        UpdatePrivateConversationNotificationsRequest $request,
        PrivateConversation $privateConversation,
    ): RedirectResponse {
        $privateConversation->participants()
            ->where('user_id', $request->user()->id)
            ->update(['email_notifications' => $request->boolean('email_notifications')]);

        return back();
    }

    private function renderIndex(
        PrivateConversationIndexRequest $request,
        string $context,
        ?PrivateConversation $selectedConversation = null,
    ): Response {
        $validated = $request->validated();
        $sortBy = $validated['sort_by'] ?? 'updated_at';
        $sortDirection = $validated['sort_dir'] ?? 'desc';
        $perPage = $validated['per_page'] ?? 20;
        $contextColumn = $context === 'blog' ? 'blog_id' : 'group_id';
        $contextId = isset($validated[$contextColumn]) ? (int) $validated[$contextColumn] : null;

        $conversationQuery = PrivateConversation::query()
            ->whereHas('participants', fn($query) => $query->where('user_id', $request->user()->id))
            ->whereNotNull($contextColumn);

        if ($contextId !== null) {
            $conversationQuery->where($contextColumn, $contextId);
        }

        $conversations = $conversationQuery
            ->with([
                'initiator:id,name',
                'owner:id,name',
                'participants',
                'blog:id,name,slug,locale',
                'group:id,name,slug',
                'post:id,title,slug,blog_id,group_id',
                'post.blog:id,name,slug,locale',
                'post.group:id,name,slug',
            ])
            ->withCount('messages')
            ->orderBy('private_conversations.' . $sortBy, $sortDirection)
            ->orderBy('private_conversations.id', $sortDirection)
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('app/messages/Index', [
            'conversations' => PrivateConversationResource::collection($conversations->items()),
            'pagination' => [
                'current_page' => $conversations->currentPage(),
                'last_page' => $conversations->lastPage(),
                'per_page' => $conversations->perPage(),
                'total' => $conversations->total(),
                'links' => $conversations->linkCollection(),
            ],
            'selectedConversation' => $selectedConversation
                ? new PrivateConversationResource($selectedConversation)
                : null,
            'filters' => [
                'context' => $context,
                'context_id' => $contextId,
                'sort_by' => $sortBy,
                'sort_dir' => $sortDirection,
                'per_page' => $perPage,
            ],
            'contextOptions' => $this->contextOptions($request, $context),
            'translations' => [
                'locale' => app()->getLocale(),
                'messages' => $this->translations->getPageTranslations('dashboard'),
            ],
        ]);
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    private function contextOptions(PrivateConversationIndexRequest $request, string $context): array
    {
        $column = $context === 'blog' ? 'blog_id' : 'group_id';
        $model = $context === 'blog' ? Blog::class : Group::class;
        $contextIds = PrivateConversation::query()
            ->whereHas('participants', fn($query) => $query->where('user_id', $request->user()->id))
            ->whereNotNull($column)
            ->distinct()
            ->pluck($column);

        return $model::query()
            ->whereIn('id', $contextIds)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn(Blog|Group $contextModel): array => [
                'id' => $contextModel->id,
                'name' => $contextModel->name,
            ])
            ->all();
    }

    private function conversationMatchesContext(PrivateConversation $conversation, string $context): bool
    {
        return $context === 'blog'
            ? $conversation->blog_id !== null
            : $conversation->group_id !== null;
    }
}
