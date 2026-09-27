<?php

use App\Models\Blog;
use App\Models\Group;
use App\Models\Post;
use App\Models\PrivateConversation;
use App\Models\PrivateConversationParticipant;
use App\Models\PrivateMessage;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Illuminate\Support\Facades\Queue;

function createPrivateConversationFor(User $initiator, User $owner, string $subject = 'A subject'): PrivateConversation
{
    $blog = Blog::factory()->create(['user_id' => $owner->id]);
    $conversation = PrivateConversation::factory()->create([
        'blog_id' => $blog->id,
        'initiator_id' => $initiator->id,
        'owner_id' => $owner->id,
        'subject' => $subject,
    ]);

    PrivateConversationParticipant::factory()->create([
        'private_conversation_id' => $conversation->id,
        'user_id' => $initiator->id,
    ]);
    PrivateConversationParticipant::factory()->create([
        'private_conversation_id' => $conversation->id,
        'user_id' => $owner->id,
    ]);

    return $conversation;
}

it('redirects guests from creating a private conversation', function () {
    $response = $this->post(route('private-conversations.store'), []);

    $response->assertRedirect(route('login'));
});

it('creates a private conversation from a blog post for an authenticated user', function () {
    $owner = User::factory()->create();
    $initiator = User::factory()->create();
    $blog = Blog::factory()->create(['user_id' => $owner->id, 'is_published' => true]);
    $post = Post::factory()->for($blog)->create([
        'user_id' => $owner->id,
        'is_published' => true,
    ]);

    $response = $this->actingAs($initiator)->post(route('private-conversations.store'), [
        'post_id' => $post->id,
        'subject' => '  Question about the post  ',
        'content' => '  Please clarify this point.  ',
        'email_notifications' => false,
    ]);

    $conversation = PrivateConversation::query()->latest('id')->firstOrFail();

    $response->assertRedirect(route('blog-private-conversations.show', $conversation));
    $this->assertDatabaseHas('private_conversations', [
        'id' => $conversation->id,
        'blog_id' => $blog->id,
        'owner_id' => $owner->id,
        'subject' => 'Question about the post',
    ]);
    $this->assertDatabaseHas('private_messages', [
        'private_conversation_id' => $conversation->id,
        'user_id' => $initiator->id,
        'content' => 'Please clarify this point.',
    ]);
    $this->assertDatabaseHas('private_conversation_participants', [
        'private_conversation_id' => $conversation->id,
        'user_id' => $initiator->id,
        'email_notifications' => false,
    ]);
});

it('exposes the blog post as the conversation source', function () {
    $owner = User::factory()->create();
    $initiator = User::factory()->create();
    $blog = Blog::factory()->create(['user_id' => $owner->id, 'is_published' => true]);
    $post = Post::factory()->for($blog)->create([
        'user_id' => $owner->id,
        'is_published' => true,
    ]);
    $conversation = PrivateConversation::factory()->create([
        'blog_id' => $blog->id,
        'post_id' => $post->id,
        'initiator_id' => $initiator->id,
        'owner_id' => $owner->id,
    ]);
    PrivateConversationParticipant::factory()->create([
        'private_conversation_id' => $conversation->id,
        'user_id' => $initiator->id,
    ]);

    $this->actingAs($initiator)
        ->get(route('blog-private-conversations.index'))
        ->assertInertia(fn(Assert $page) => $page
            ->where('conversations.0.source.type', 'post')
            ->where('conversations.0.source.label', $post->title)
            ->where('conversations.0.source.url', $post->public_url)
        );
});

it('exposes the private message endpoint only to an eligible authenticated visitor', function () {
    $owner = User::factory()->create();
    $initiator = User::factory()->create();
    $blog = Blog::factory()->create(['user_id' => $owner->id, 'is_published' => true]);
    $post = Post::factory()->for($blog)->create([
        'user_id' => $owner->id,
        'is_published' => true,
    ]);

    $this->get(getBlogUrl($blog, "/$post->slug"))
        ->assertInertia(fn(Assert $page) => $page
            ->where('post.private_message_url', null)
        );

    $this->actingAs($initiator)
        ->get(getBlogUrl($blog, "/$post->slug"))
        ->assertInertia(fn(Assert $page) => $page
            ->where('post.private_message_url', route('private-conversations.store'))
        );
});

it('forbids a non-participant from viewing a private conversation', function () {
    $owner = User::factory()->create();
    $initiator = User::factory()->create();
    $outsider = User::factory()->create();
    $conversation = createPrivateConversationFor($initiator, $owner);

    $response = $this->actingAs($outsider)->get(route('blog-private-conversations.show', $conversation));

    $response->assertForbidden();
});

it('allows only the author to edit the last message and deletes only the last message', function () {
    $owner = User::factory()->create();
    $initiator = User::factory()->create();
    $conversation = createPrivateConversationFor($initiator, $owner);
    $message = PrivateMessage::factory()->create([
        'private_conversation_id' => $conversation->id,
        'user_id' => $initiator->id,
        'content' => 'Original content',
    ]);

    $this->actingAs($owner)
        ->post(route('private-conversations.messages.store', $conversation), ['content' => 'A reply'])
        ->assertRedirect();
    $this->assertDatabaseHas('private_messages', [
        'private_conversation_id' => $conversation->id,
        'user_id' => $owner->id,
        'content' => 'A reply',
    ]);

    $this->actingAs($owner)
        ->patch(route('private-messages.update', $message), ['content' => 'Updated content'])
        ->assertForbidden();

    $lastMessage = $conversation->messages()->latest('created_at')->latest('id')->firstOrFail();

    $this->actingAs($initiator)
        ->patch(route('private-messages.update', $lastMessage), ['content' => 'Unauthorized update'])
        ->assertForbidden();

    $this->actingAs($owner)
        ->patch(route('private-messages.update', $lastMessage), ['content' => 'Updated content'])
        ->assertRedirect();
    $this->assertDatabaseHas('private_messages', [
        'id' => $lastMessage->id,
        'content' => 'Updated content',
    ]);

    $this->actingAs($initiator)
        ->delete(route('private-messages.destroy', $message))
        ->assertForbidden();

    $this->actingAs($owner)
        ->delete(route('private-messages.destroy', $lastMessage))
        ->assertRedirect();
    $this->assertDatabaseMissing('private_messages', ['id' => $lastMessage->id]);
});

it('deletes only the last message and allows authorized users to delete the whole conversation', function () {
    Queue::fake();
    $owner = User::factory()->create();
    $initiator = User::factory()->create();
    $conversation = createPrivateConversationFor($initiator, $owner);
    $first = PrivateMessage::factory()->create([
        'private_conversation_id' => $conversation->id,
        'user_id' => $initiator->id,
        'notification_version' => 1,
    ]);
    $middle = PrivateMessage::factory()->create([
        'private_conversation_id' => $conversation->id,
        'user_id' => $owner->id,
        'notification_version' => 1,
    ]);
    $last = PrivateMessage::factory()->create([
        'private_conversation_id' => $conversation->id,
        'user_id' => $owner->id,
        'notification_version' => 1,
    ]);

    $this->actingAs($initiator)
        ->delete(route('private-messages.destroy', $middle))
        ->assertForbidden();

    $this->actingAs($owner)
        ->delete(route('private-messages.destroy', $first))
        ->assertForbidden();
    $this->assertDatabaseHas('private_messages', ['id' => $first->id]);

    $this->actingAs($owner)
        ->delete(route('private-messages.destroy', $last))
        ->assertRedirect();
    $this->assertDatabaseMissing('private_messages', ['id' => $last->id]);
    $this->assertDatabaseHas('private_messages', ['id' => $middle->id]);

    $this->actingAs($owner)
        ->delete(route('private-messages.destroy', $middle))
        ->assertRedirect();

    $this->actingAs($initiator)
        ->delete(route('private-conversations.destroy', $conversation))
        ->assertRedirect();

    $this->assertDatabaseMissing('private_conversations', ['id' => $conversation->id]);
    $this->assertDatabaseMissing('private_messages', ['id' => $middle->id]);
});

it('forbids outsiders from deleting the whole conversation', function () {
    $owner = User::factory()->create();
    $initiator = User::factory()->create();
    $outsider = User::factory()->create();
    $conversation = createPrivateConversationFor($initiator, $owner);

    $this->actingAs($outsider)
        ->delete(route('private-conversations.destroy', $conversation))
        ->assertForbidden();

    $this->assertDatabaseHas('private_conversations', ['id' => $conversation->id]);
});

it('sorts conversations by an allow-listed subject column with a stable order', function () {
    $owner = User::factory()->create();
    $initiator = User::factory()->create();
    $alpha = createPrivateConversationFor($initiator, $owner, 'Alpha');
    $beta = createPrivateConversationFor($initiator, $owner, 'Beta');

    $this->actingAs($initiator)
        ->get(route('blog-private-conversations.index', ['sort_by' => 'subject', 'sort_dir' => 'asc']))
        ->assertSuccessful()
        ->assertInertia(fn(Assert $page) => $page
            ->component('app/messages/Index', false)
            ->where('conversations.0.id', $alpha->id)
            ->where('conversations.1.id', $beta->id)
            ->where('filters.sort_by', 'subject')
            ->where('filters.sort_dir', 'asc')
        );

    $this->actingAs($initiator)
        ->get(route('blog-private-conversations.index', ['sort_by' => 'subjects']))
        ->assertInvalid(['sort_by']);
});

it('separates blog and group conversations and filters only available contexts', function () {
    $owner = User::factory()->create();
    $initiator = User::factory()->create();
    $blog = Blog::factory()->create(['user_id' => $owner->id]);
    $group = Group::factory()->create(['user_id' => $owner->id]);
    $blogConversation = PrivateConversation::factory()->create([
        'blog_id' => $blog->id,
        'initiator_id' => $initiator->id,
        'owner_id' => $owner->id,
        'subject' => 'Blog conversation',
    ]);
    $groupConversation = PrivateConversation::factory()->create([
        'blog_id' => null,
        'group_id' => $group->id,
        'initiator_id' => $initiator->id,
        'owner_id' => $owner->id,
        'subject' => 'Group conversation',
    ]);
    PrivateConversationParticipant::factory()->create([
        'private_conversation_id' => $blogConversation->id,
        'user_id' => $initiator->id,
    ]);
    PrivateConversationParticipant::factory()->create([
        'private_conversation_id' => $groupConversation->id,
        'user_id' => $initiator->id,
    ]);

    $this->actingAs($initiator)
        ->get(route('blog-private-conversations.index'))
        ->assertInertia(fn(Assert $page) => $page
            ->where('conversations.0.id', $blogConversation->id)
            ->where('pagination.total', 1)
            ->where('filters.context', 'blog')
            ->where('contextOptions.0.id', $blog->id)
        );

    $this->actingAs($initiator)
        ->get(route('group-private-conversations.index', ['group_id' => $group->id]))
        ->assertInertia(fn(Assert $page) => $page
            ->where('conversations.0.id', $groupConversation->id)
            ->where('pagination.total', 1)
            ->where('filters.context', 'group')
            ->where('filters.context_id', $group->id)
        );

    $this->actingAs($initiator)
        ->get(route('blog-private-conversations.index', ['blog_id' => $blog->id + 100000]))
        ->assertInvalid(['blog_id']);

    $this->actingAs($initiator)
        ->get(route('group-private-conversations.index', ['blog_id' => $blog->id]))
        ->assertInvalid(['blog_id']);
});

it('does not open a conversation through the wrong context route', function () {
    $owner = User::factory()->create();
    $initiator = User::factory()->create();
    $conversation = createPrivateConversationFor($initiator, $owner);

    $this->actingAs($initiator)
        ->get(route('group-private-conversations.show', $conversation))
        ->assertNotFound();
});

it('updates notification preference only for the authenticated participant', function () {
    $owner = User::factory()->create();
    $initiator = User::factory()->create();
    $outsider = User::factory()->create();
    $conversation = createPrivateConversationFor($initiator, $owner);

    $this->actingAs($initiator)
        ->patch(route('private-conversations.notifications.update', $conversation), [
            'email_notifications' => false,
        ])
        ->assertRedirect();
    $this->assertDatabaseHas('private_conversation_participants', [
        'private_conversation_id' => $conversation->id,
        'user_id' => $initiator->id,
        'email_notifications' => false,
    ]);

    $this->actingAs($outsider)
        ->patch(route('private-conversations.notifications.update', $conversation), [
            'email_notifications' => false,
        ])
        ->assertForbidden();
});
