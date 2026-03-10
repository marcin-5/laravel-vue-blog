<?php

use App\Models\Blog;
use App\Models\Category;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $this->blogger = User::factory()->create(['role' => User::ROLE_BLOGGER]);
});

// --- Index ---

it('allows admin to view categories page', function () {
    Category::factory()->count(3)->create();

    $this->actingAs($this->admin)
        ->get(route('admin.categories.index'))
        ->assertSuccessful()
        ->assertInertia(fn($page) => $page
            ->component('app/admin/Categories')
            ->has('categories', 3),
        );
});

it('forbids non-admin from viewing categories page', function () {
    $this->actingAs($this->blogger)
        ->get(route('admin.categories.index'))
        ->assertForbidden();
});

it('redirects guest from categories page', function () {
    $this->get(route('admin.categories.index'))
        ->assertRedirect(route('login'));
});

it('includes blogs_count in categories listing', function () {
    $category = Category::factory()->create();
    $blog = Blog::factory()->for($this->blogger)->create();
    $blog->categories()->attach($category);

    $this->actingAs($this->admin)
        ->get(route('admin.categories.index'))
        ->assertSuccessful()
        ->assertInertia(fn($page) => $page
            ->component('app/admin/Categories')
            ->has('categories', 1)
            ->where('categories.0.blogs_count', 1),
        );
});

// --- Store ---

it('allows admin to create a category', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.categories.store'), [
            'name' => 'Technology',
            'locale' => 'en',
        ])
        ->assertRedirect();

    expect(Category::query()->where('slug', 'technology')->exists())->toBeTrue();
});

it('stores category with correct translation', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.categories.store'), [
            'name' => 'Technologia',
            'locale' => 'pl',
        ])
        ->assertRedirect();

    $category = Category::all()->first(fn($c) => $c->getTranslation('name', 'pl') === 'Technologia');
    expect($category)->not->toBeNull()
        ->and($category->getTranslation('name', 'pl'))->toBe('Technologia');
});

it('forbids non-admin from creating a category', function () {
    $this->actingAs($this->blogger)
        ->post(route('admin.categories.store'), [
            'name' => 'Technology',
            'locale' => 'en',
        ])
        ->assertForbidden();
});

it('validates required name when storing category', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.categories.store'), [
            'name' => '',
            'locale' => 'en',
        ])
        ->assertSessionHasErrors('name');
});

it('validates locale value when storing category', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.categories.store'), [
            'name' => 'Technology',
            'locale' => 'de',
        ])
        ->assertSessionHasErrors('locale');
});

// --- Update ---

it('allows admin to update a category name', function () {
    $category = Category::factory()->create();

    $this->actingAs($this->admin)
        ->patch(route('admin.categories.update', $category), [
            'name' => 'Updated Name',
            'locale' => 'en',
        ])
        ->assertRedirect();

    expect($category->fresh()->getTranslation('name', 'en'))->toBe('Updated Name');
});

it('allows admin to add a translation to existing category', function () {
    $category = Category::factory()->create(['name' => ['en' => 'Technology']]);

    $this->actingAs($this->admin)
        ->patch(route('admin.categories.update', $category), [
            'name' => 'Technologia',
            'locale' => 'pl',
        ])
        ->assertRedirect();

    $category->refresh();
    expect($category->getTranslation('name', 'pl'))->toBe('Technologia')
        ->and($category->getTranslation('name', 'en'))->toBe('Technology');
});

it('forbids non-admin from updating a category', function () {
    $category = Category::factory()->create();

    $this->actingAs($this->blogger)
        ->patch(route('admin.categories.update', $category), [
            'name' => 'Hacked',
            'locale' => 'en',
        ])
        ->assertForbidden();
});

it('validates required name when updating category', function () {
    $category = Category::factory()->create();

    $this->actingAs($this->admin)
        ->patch(route('admin.categories.update', $category), [
            'name' => '',
            'locale' => 'en',
        ])
        ->assertSessionHasErrors('name');
});

// --- Destroy ---

it('allows admin to delete a category', function () {
    $category = Category::factory()->create();

    $this->actingAs($this->admin)
        ->delete(route('admin.categories.destroy', $category))
        ->assertRedirect();

    expect(Category::find($category->id))->toBeNull();
});

it('forbids non-admin from deleting a category', function () {
    $category = Category::factory()->create();

    $this->actingAs($this->blogger)
        ->delete(route('admin.categories.destroy', $category))
        ->assertForbidden();

    expect(Category::find($category->id))->not->toBeNull();
});

it('returns 404 when deleting non-existent category', function () {
    $this->actingAs($this->admin)
        ->delete(route('admin.categories.destroy', 99999))
        ->assertNotFound();
});
