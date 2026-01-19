<?php

namespace Tests\Feature\Blogger;

use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupsTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_group_without_explicit_slug(): void
    {
        $user = User::factory()->create(['role' => 'blogger']);

        $response = $this->actingAs($user)->post(route('groups.store'), [
            'name' => 'A-Team',
            'content' => 'This is a team',
            'footer' => 'This is the footer',
            'is_published' => false,
            'locale' => 'pl',
            'sidebar' => 40,
            'page_size' => 5,
            'theme' => ['light' => [], 'dark' => []],
        ]);

        $response->assertRedirect(route('groups.index'));
        $this->assertDatabaseHas('groups', [
            'name' => 'A-Team',
            'slug' => 'a-team',
        ]);
    }

    public function test_can_update_group(): void
    {
        $user = User::factory()->create(['role' => 'blogger']);
        $group = Group::factory()->create([
            'user_id' => $user->id,
            'name' => 'Old Name',
            'slug' => 'old-name'
        ]);

        $response = $this->actingAs($user)->put(route('groups.update', $group), [
            'name' => 'New Name',
            'content' => 'Updated content',
            'footer' => 'Updated footer',
            'is_published' => true,
            'locale' => 'pl',
            'sidebar' => 20,
            'page_size' => 10,
            'theme' => ['light' => [], 'dark' => []],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('groups', [
            'id' => $group->id,
            'name' => 'New Name',
            'slug' => 'old-name', // Slug should generally stay the same for SEO
        ]);
    }
}
