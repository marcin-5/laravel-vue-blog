<?php

use Inertia\Testing\AssertableInertia as Assert;

test('about page uses about_seo_description if provided', function () {
    $blog = createBlog([
        'is_published' => true,
        'about_seo_description' => 'Custom about SEO description',
    ]);

    $this
        ->get(getBlogUrl($blog, '/about'))
        ->assertSuccessful()
        ->assertInertia(fn(Assert $page) => $page
            ->where('seo.description', 'Custom about SEO description'),
        );
});

test('about page uses fallback translation if about_seo_description is missing', function () {
    $blog = createBlog([
        'is_published' => true,
        'about_seo_description' => null,
        'locale' => 'pl',
    ]);

    $this
        ->get(getBlogUrl($blog, '/about'))
        ->assertSuccessful()
        ->assertInertia(fn(Assert $page) => $page
            ->where('seo.description', 'Poznaj historię, wartości i misję tego bloga.'),
        );
});

test('contact page uses contact_seo_description if provided', function () {
    $blog = createBlog([
        'is_published' => true,
        'contact_seo_description' => 'Custom contact SEO description',
    ]);

    $this
        ->get(getBlogUrl($blog, '/contact'))
        ->assertSuccessful()
        ->assertInertia(fn(Assert $page) => $page
            ->where('seo.description', 'Custom contact SEO description'),
        );
});

test('contact page uses fallback translation if contact_seo_description is missing', function () {
    $blog = createBlog([
        'is_published' => true,
        'contact_seo_description' => null,
        'locale' => 'pl',
    ]);

    $this
        ->get(getBlogUrl($blog, '/contact'))
        ->assertSuccessful()
        ->assertInertia(fn(Assert $page) => $page
            ->where('seo.description', 'Tutaj można zadać pytanie lub podzielić się opinią.'),
        );
});

test('about page title uses "O blogu" in Polish', function () {
    $blog = createBlog([
        'is_published' => true,
        'locale' => 'pl',
        'name' => 'BlogTestowy',
    ]);

    $this
        ->get(getBlogUrl($blog, '/about'))
        ->assertSuccessful()
        ->assertInertia(fn(Assert $page) => $page
            ->where('seo.title', 'O blogu - BlogTestowy'),
        );
});

test('blog landing page exposes the Bing verification token for that blog only', function () {
    $blog = createBlog([
        'is_published' => true,
        'bing_site_verification_token' => 'current-blog-bing-token',
    ]);
    createBlog([
        'is_published' => true,
        'bing_site_verification_token' => 'other-blog-bing-token',
    ]);

    $this->get(getBlogUrl($blog))
        ->assertSuccessful()
        ->assertInertia(fn(Assert $page) => $page
            ->where('seo.bingSiteVerificationToken', 'current-blog-bing-token'),
        );
});

test('blog landing page omits the Bing verification token when it is unset', function () {
    $blog = createBlog([
        'is_published' => true,
        'bing_site_verification_token' => null,
    ]);

    $this->get(getBlogUrl($blog))
        ->assertSuccessful()
        ->assertInertia(fn(Assert $page) => $page
            ->missing('seo.bingSiteVerificationToken'),
        );
});
