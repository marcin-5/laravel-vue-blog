<?php

use App\Models\User;
use Illuminate\Support\Facades\Mail;
use App\Mail\ContactMessageMail;
use Inertia\Testing\AssertableInertia as Assert;

it('renders the welcome page', function () {
    $blog = createBlog(['is_published' => true, 'locale' => 'en']);

    $response = $this->get(route('home'));

    $response->assertSuccessful();
    $response->assertInertia(fn(Assert $page) => $page
        ->component('public/Welcome')
        ->has('blogs')
        ->has('categories')
        ->has('selectedCategoryIds')
        ->has('locale')
        ->has('userGroups')
        ->has('seo')
    );
});

it('renders the welcome page with filtered categories', function () {
    $blog = createBlog(['is_published' => true, 'locale' => 'en']);

    $response = $this->get(route('home', ['categories' => '1,2']));

    $response->assertSuccessful();
    $response->assertInertia(fn(Assert $page) => $page
        ->component('public/Welcome')
        ->where('selectedCategoryIds', [1, 2])
    );
});

it('includes user groups for authenticated users on welcome page', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('home'));

    $response->assertSuccessful();
    $response->assertInertia(fn(Assert $page) => $page
        ->has('userGroups')
    );
});

it('renders the about page', function () {
    $response = $this->get(route('about'));

    $response->assertSuccessful();
    $response->assertInertia(fn(Assert $page) => $page
        ->component('public/About')
        ->has('locale')
        ->has('aboutHeading')
        ->has('aboutHtml')
        ->has('translations')
        ->has('seo')
    );
});

it('renders the contact page', function () {
    $response = $this->get(route('contact'));

    $response->assertSuccessful();
    $response->assertInertia(fn(Assert $page) => $page
        ->component('public/Contact')
        ->has('locale')
        ->has('translations')
        ->has('seo')
    );
});

it('submits the contact form and sends an email', function () {
    Mail::fake();

    $data = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'subject' => 'Hello',
        'message' => 'Test message',
    ];

    $response = $this->post(route('public.contact.submit'), $data);

    $response->assertOk();
    $response->assertJson(['message' => 'Thanks for your message. We will get back to you soon.']);

    Mail::assertSent(ContactMessageMail::class, function ($mail) use ($data) {
        return $mail->hasTo(config('mail.contact_to')) &&
               $mail->data['email'] === $data['email'] &&
               $mail->data['message'] === $data['message'];
    });
});

it('validates contact form submission', function () {
    $response = $this->post(route('public.contact.submit'), []);

    $response->assertSessionHasErrors(['name', 'email', 'subject', 'message']);
});
