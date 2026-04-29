<?php

use Inertia\Testing\AssertableInertia as Assert;

it('loads the enneagram test page with valid data', function () {
    $response = $this->get('http://enneagram-test.localhost/');

    $response->assertStatus(200);
    $response->assertInertia(fn(Assert $page) => $page
        ->component('EnneagramTest/Index')
        ->has('testData')
        ->has('testData.questions')
        ->has('testData.testConfig.stages.stage2.part3')
        ->has('testData.testConfig.stages.stage2.part4')
        ->where('testData.questions', function ($questions) {
            return collect($questions)->every(function ($question) {
                return isset($question['id']);
            });
        }),
    );
});
