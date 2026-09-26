<?php

namespace App\Http\Controllers;

use App\Http\Requests\PrivateConversationIndexRequest;
use App\Http\Requests\StorePrivateConversationRequest;
use App\Http\Requests\StorePrivateMessageRequest;
use App\Http\Requests\UpdatePrivateConversationNotificationsRequest;
use App\Http\Requests\UpdatePrivateMessageRequest;
use App\Http\Resources\PrivateConversationResource;
use App\Models\Post;
use App\Models\PrivateConversation;
use App\Models\PrivateMessage;
use App\Services\PrivateConversationService;
use App\Services\TranslationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PrivateConversationController extends Controller
{
    public function __construct(
        private readonly PrivateConversationService $conversationService,
        private readonly TranslationService $translations,
    ) {}

    public function index(PrivateConversationIndexRequest $request): Response
    {
        return $this->renderIndex($request);
    }

    public function show(
        PrivateConversationIndexRequest $request,
        PrivateConversation $privateConversation,
    ): Response {
        $this->authorize('view', $privateConversation);

        $privateConversation->load([
            'initiator:id,name',
            'owner:id,name',
            'participants',
            'messages' => fn($query) => $query->with('user:id,name')->oldest()->oldest('id'),
        ]);

        return $this->renderIndex($request, $privateConversation);
    }

    public function store(StorePrivateConversationRequest $request): RedirectResponse
    {
        $post = Post::query()->findOrFail($request->validated('post_id'));
        $conversation = $this->conversationService->create(
            $post,
            $request->user(),
            $request->validated(),
        );

        return redirect()->route('private-conversations.show', $conversation);
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

    public function destroy(Request $request, PrivateMessage $privateMessage): RedirectResponse
    {
        Gate::authorize('delete', $privateMessage);
        $this->conversationService->deleteMessage($privateMessage);

        return back();
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
        ?PrivateConversation $selectedConversation = null,
    ): Response {
        $validated = $request->validated();
        $sortBy = $validated['sort_by'] ?? 'updated_at';
        $sortDirection = $validated['sort_dir'] ?? 'desc';
        $perPage = $validated['per_page'] ?? 20;

        $conversations = PrivateConversation::query()
            ->whereHas('participants', fn($query) => $query->where('user_id', $request->user()->id))
            ->with(['initiator:id,name', 'owner:id,name', 'participants'])
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
                'sort_by' => $sortBy,
                'sort_dir' => $sortDirection,
                'per_page' => $perPage,
            ],
            'translations' => [
                'locale' => app()->getLocale(),
                'messages' => $this->translations->getPageTranslations('dashboard'),
            ],
        ]);
    }
}
