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

test('about page title uses "O mnie" for single author blog in Polish', function () {
    $blog = createBlog([
        'is_published' => true,
        'is_multi_author' => false,
        'locale' => 'pl',
        'name' => 'BlogTestowy',
    ]);

    $this
        ->get(getBlogUrl($blog, '/about'))
        ->assertSuccessful()
        ->assertInertia(fn(Assert $page) => $page
            ->where('seo.title', 'O mnie - BlogTestowy'),
        );
});

test('about page title uses "O nas" for multi author blog in Polish', function () {
    $blog = createBlog([
        'is_published' => true,
        'is_multi_author' => true,
        'locale' => 'pl',
        'name' => 'BlogWielu',
    ]);

    $this
        ->get(getBlogUrl($blog, '/about'))
        ->assertSuccessful()
        ->assertInertia(fn(Assert $page) => $page
            ->where('seo.title', 'O nas - BlogWielu'),
        );
});
