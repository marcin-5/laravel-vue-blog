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
