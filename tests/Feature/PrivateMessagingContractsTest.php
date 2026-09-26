<?php

use App\Http\Requests\StorePrivateConversationRequest;
use App\Models\Blog;
use App\Models\Group;
use App\Models\Post;
use App\Models\PrivateConversation;
use App\Models\PrivateConversationParticipant;
use App\Models\PrivateMessage;
use App\Models\User;
use App\Policies\PrivateConversationPolicy;
use App\Policies\PrivateMessagePolicy;
use Illuminate\Support\Facades\Validator;

it('persists a conversation with its messages and participants', function () {
    $owner = User::factory()->create();
    $initiator = User::factory()->create();
    $blog = Blog::factory()->create(['user_id' => $owner->id, 'is_published' => true]);
    $post = Post::factory()->create([
        'blog_id' => $blog->id,
        'user_id' => $owner->id,
        'is_published' => true,
    ]);

    $conversation = PrivateConversation::factory()->create([
        'blog_id' => $blog->id,
        'group_id' => null,
        'post_id' => $post->id,
        'initiator_id' => $initiator->id,
        'owner_id' => $owner->id,
        'subject' => 'Question about the post',
    ]);
    $conversation->participants()->createMany([
        ['user_id' => $initiator->id, 'email_notifications' => true],
        ['user_id' => $owner->id, 'email_notifications' => true],
    ]);
    $message = $conversation->messages()->create([
        'user_id' => $initiator->id,
        'content' => 'I would like to ask a question.',
    ]);

    expect($conversation->fresh()->participantUsers)
        ->toHaveCount(2)
        ->and($conversation->fresh()->messages->first()->is($message))->toBeTrue()
        ->and($message->conversation->is($conversation))->toBeTrue();
});

it('allows only conversation participants to view it and message authors to edit or delete', function () {
    $owner = User::factory()->create();
    $initiator = User::factory()->create();
    $outsider = User::factory()->create();
    $conversation = PrivateConversation::factory()->create([
        'initiator_id' => $initiator->id,
        'owner_id' => $owner->id,
    ]);
    PrivateConversationParticipant::factory()->create([
        'private_conversation_id' => $conversation->id,
        'user_id' => $initiator->id,
    ]);
    PrivateConversationParticipant::factory()->create([
        'private_conversation_id' => $conversation->id,
        'user_id' => $owner->id,
    ]);
    $message = PrivateMessage::factory()->create([
        'private_conversation_id' => $conversation->id,
        'user_id' => $initiator->id,
    ]);
    $conversationPolicy = new PrivateConversationPolicy;
    $messagePolicy = new PrivateMessagePolicy;

    expect($conversationPolicy->view($owner, $conversation))->toBeTrue()
        ->and($conversationPolicy->view($outsider, $conversation))->toBeFalse()
        ->and($messagePolicy->update($initiator, $message))->toBeTrue()
        ->and($messagePolicy->delete($initiator, $message))->toBeTrue()
        ->and($messagePolicy->update($owner, $message))->toBeFalse()
        ->and($messagePolicy->delete($outsider, $message))->toBeFalse();
});

it('rejects conversation creation by a post owner in blog and group contexts', function () {
    $owner = User::factory()->create();
    $blog = Blog::factory()->create(['user_id' => $owner->id, 'is_published' => true]);
    $blogPost = Post::factory()->create([
        'blog_id' => $blog->id,
        'user_id' => $owner->id,
        'is_published' => true,
    ]);
    $group = Group::factory()->create(['user_id' => $owner->id, 'is_published' => true]);
    $groupPost = Post::factory()->create([
        'group_id' => $group->id,
        'user_id' => $owner->id,
        'is_published' => true,
    ]);
    $policy = new PrivateConversationPolicy;

    expect($policy->create($owner, $blogPost))->toBeFalse()
        ->and($policy->create($owner, $groupPost))->toBeFalse();
});

it('requires a post, subject, and content for a new conversation', function () {
    $validator = Validator::make([], (new StorePrivateConversationRequest)->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has(['post_id', 'subject', 'content']))->toBeTrue();
});
