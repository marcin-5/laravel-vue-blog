<?php

use App\Models\Blog;
use App\Models\Post;
use App\Models\PrivateConversation;
use App\Models\PrivateConversationParticipant;
use App\Models\PrivateMessage;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function createPrivateConversationFor(User $initiator, User $owner, string $subject = 'A subject'): PrivateConversation
{
    $conversation = PrivateConversation::factory()->create([
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

    $response->assertRedirect(route('private-conversations.show', $conversation));
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

    $response = $this->actingAs($outsider)->get(route('private-conversations.show', $conversation));

    $response->assertForbidden();
});

it('allows participants to reply, edit and delete their own messages', function () {
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

    $this->actingAs($initiator)
        ->patch(route('private-messages.update', $message), ['content' => 'Updated content'])
        ->assertRedirect();
    $this->assertDatabaseHas('private_messages', [
        'id' => $message->id,
        'content' => 'Updated content',
    ]);

    $this->actingAs($initiator)
        ->delete(route('private-messages.destroy', $message))
        ->assertRedirect();
    $this->assertDatabaseMissing('private_messages', ['id' => $message->id]);
});

it('sorts conversations by an allow-listed subject column with a stable order', function () {
    $owner = User::factory()->create();
    $initiator = User::factory()->create();
    $alpha = createPrivateConversationFor($initiator, $owner, 'Alpha');
    $beta = createPrivateConversationFor($initiator, $owner, 'Beta');

    $this->actingAs($initiator)
        ->get(route('private-conversations.index', ['sort_by' => 'subject', 'sort_dir' => 'asc']))
        ->assertSuccessful()
        ->assertInertia(fn(Assert $page) => $page
            ->component('app/messages/Index', false)
            ->where('conversations.0.id', $alpha->id)
            ->where('conversations.1.id', $beta->id)
            ->where('filters.sort_by', 'subject')
            ->where('filters.sort_dir', 'asc')
        );

    $this->actingAs($initiator)
        ->get(route('private-conversations.index', ['sort_by' => 'subjects']))
        ->assertInvalid(['sort_by']);
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
