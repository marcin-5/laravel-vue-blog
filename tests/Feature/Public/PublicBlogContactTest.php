<?php

use App\Mail\ContactMessageMail;
use App\Models\Blog;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;

it('renders the contact page for a published blog', function () {
    $owner = User::factory()->create(['name' => 'Blog Owner', 'email' => 'owner@example.com']);
    $blog = Blog::factory()->for($owner)->create(['is_published' => true]);

    $response = $this->get(getBlogUrl($blog, '/contact'));

    $response->assertSuccessful();
    $response->assertInertia(fn(Assert $page) => $page
        ->component('public/blog/Contact')
        ->has('blog')
        ->has('seo')
        ->has('navigation')
        ->has('footerHtml')
        ->has('submitUrl')
        ->where('recipientName', 'Blog Owner')
    );
});

it('returns 404 for contact page on unpublished blog', function () {
    $owner = User::factory()->create();
    $blog = Blog::factory()->for($owner)->create(['is_published' => false]);

    $response = $this->get(getBlogUrl($blog, '/contact'));

    $response->assertNotFound();
});

it('submits the contact form and sends email to blog owner', function () {
    Mail::fake();

    $owner = User::factory()->create(['name' => 'Blog Owner', 'email' => 'owner@example.com']);
    $blog = Blog::factory()->for($owner)->create(['is_published' => true]);

    $data = [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'subject' => 'Hello',
        'message' => 'Test message',
    ];

    $response = $this->post(getBlogUrl($blog, '/contact'), $data);

    $response->assertOk();
    $response->assertJson(['message' => 'Thanks for your message. We will get back to you soon.']);

    Mail::assertSent(ContactMessageMail::class, function ($mail) use ($owner, $data) {
        return $mail->hasTo($owner->email) &&
               $mail->data['email'] === $data['email'] &&
               $mail->data['message'] === $data['message'];
    });
});

it('validates contact form submission on blog subdomain', function () {
    $owner = User::factory()->create();
    $blog = Blog::factory()->for($owner)->create(['is_published' => true]);

    $response = $this->post(getBlogUrl($blog, '/contact'), []);

    $response->assertSessionHasErrors(['name', 'email', 'subject', 'message']);
});
