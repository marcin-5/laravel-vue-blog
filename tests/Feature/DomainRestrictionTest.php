<?php

use App\Models\Blog;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'app.domain_locales' => [
            'osobliwy.localhost' => 'pl',
            'peculiarmatters.localhost' => 'en',
            'enneagram-test.osobliwy.localhost' => 'pl',
            'enneagram-test.peculiarmatters.localhost' => 'en',
        ],
        'enneagram.domains' => [
            'enneagram-test.osobliwy.localhost' => 'pl',
            'enneagram-test.peculiarmatters.localhost' => 'en',
        ],
    ]);
});

it('allows blog access on main domains', function () {
    $user = User::factory()->create();
    $blog = Blog::factory()->create(['name' => 'enneagram', 'user_id' => $user->id, 'is_published' => true]);

    $response = $this->get('http://osobliwy.localhost/about');
    $response->assertStatus(200);

    $response = $this->get('http://osobliwy.localhost/enneagram');
    $response->assertStatus(200);
});

it('denies blog access on enneagram-test domains', function () {
    $user = User::factory()->create();
    $blog = Blog::factory()->create(['name' => 'enneagram', 'user_id' => $user->id, 'is_published' => true]);

    // This should now return 404 because of the fallback in enneagram.php
    $response = $this->get('http://enneagram-test.osobliwy.localhost/enneagram');
    $response->assertStatus(404);

    $response = $this->get('http://enneagram-test.osobliwy.localhost/enneagram/nie-mow-piatce-dasz-rade');
    $response->assertStatus(404);
});

it('allows enneagram test on enneagram-test domains', function () {
    $response = $this->get('http://enneagram-test.osobliwy.localhost/');
    $response->assertStatus(200);
});
