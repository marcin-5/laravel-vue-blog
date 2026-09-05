<?php

use App\Models\Group;
use App\Models\User;
use App\Services\Blogger\GroupMemberService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

it('redirects guests from an enabled group to group registration', function () {
    $owner = User::factory()->create();
    $group = Group::factory()->create([
        'user_id' => $owner->id,
        'slug' => 'open-group',
        'allow_registration' => true,
    ]);

    $response = $this->get(route('group.landing', $group));

    $response->assertRedirect(route('group.register.create', $group));
    $response->assertSessionHas('url.intended', route('group.landing', $group));
});

it('redirects guests from a disabled group to login', function () {
    $owner = User::factory()->create();
    $group = Group::factory()->create([
        'user_id' => $owner->id,
        'slug' => 'closed-group',
        'allow_registration' => false,
    ]);

    $response = $this->get(route('group.landing', $group));

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('url.intended', route('group.landing', $group));
});

it('renders registration for an enabled group', function () {
    $owner = User::factory()->create();
    $group = Group::factory()->create([
        'user_id' => $owner->id,
        'slug' => 'open-group',
        'allow_registration' => true,
    ]);

    $response = $this->get(route('group.register.create', $group));

    $response->assertOk();
    $response->assertSee($group->name);
    $response->assertInertia(fn($page) => $page
        ->component('app/auth/Register')
        ->where('translations.messages.auth.register.group_description', fn($description) => is_string($description))
        ->where('translations.messages.auth.register.group_link', fn($link) => is_string($link))
    );
});

it('creates and authenticates a member through group registration', function () {
    Event::fake([Registered::class]);

    $owner = User::factory()->create();
    $group = Group::factory()->create([
        'user_id' => $owner->id,
        'slug' => 'open-group',
        'allow_registration' => true,
    ]);

    $response = $this->post(route('group.register.store', $group), [
        'name' => 'New Member',
        'email' => 'new-member@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertRedirect(route('group.landing', $group));
    $this->assertAuthenticated();
    $this->assertDatabaseHas('users', ['email' => 'new-member@example.com']);
    $this->assertDatabaseHas('group_user', [
        'group_id' => $group->id,
        'role' => 'member',
    ]);
    Event::assertDispatched(Registered::class);
});

it('rejects direct registration for a disabled group', function () {
    $owner = User::factory()->create();
    $group = Group::factory()->create([
        'user_id' => $owner->id,
        'allow_registration' => false,
    ]);

    $this->post(route('group.register.store', $group), [
        'name' => 'Blocked Member',
        'email' => 'blocked@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertNotFound();

    $this->assertDatabaseMissing('users', ['email' => 'blocked@example.com']);
});

it('rejects registration for an unknown group', function () {
    $this->post(route('group.register.store', ['group' => 'missing-group']), [
        'name' => 'Missing Member',
        'email' => 'missing@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertNotFound();

    $this->assertDatabaseMissing('users', ['email' => 'missing@example.com']);
});

it('rejects duplicate email during group registration', function () {
    $existingUser = User::factory()->create(['email' => 'existing@example.com']);
    $group = Group::factory()->create([
        'user_id' => $existingUser->id,
        'allow_registration' => true,
    ]);

    $response = $this->post(route('group.register.store', $group), [
        'name' => 'Duplicate Member',
        'email' => $existingUser->email,
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
    $this->assertDatabaseMissing('group_user', [
        'group_id' => $group->id,
        'user_id' => $existingUser->id,
    ]);
});

it('rolls back the user when membership creation fails', function () {
    Event::fake([Registered::class]);

    $owner = User::factory()->create();
    $group = Group::factory()->create([
        'user_id' => $owner->id,
        'allow_registration' => true,
    ]);

    $this->mock(GroupMemberService::class, function ($mock) {
        $mock->shouldReceive('addMember')
            ->once()
            ->andThrow(new RuntimeException('Membership failed'));
    });

    $response = $this->post(route('group.register.store', $group), [
        'name' => 'Rolled Back Member',
        'email' => 'rolled-back@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertServerError();
    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['email' => 'rolled-back@example.com']);
});
