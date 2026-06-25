<?php

use App\Services\MarkdownService;

it('converts null to empty string', function () {
    $service = new MarkdownService;

    $result = $service->convertToHtml(null);

    expect($result)->toBe('');
});

it('converts empty string to empty string', function () {
    $service = new MarkdownService;

    $result = $service->convertToHtml('');

    expect($result)->toBe('');
});

it('converts basic markdown to html', function () {
    $service = new MarkdownService;

    $result = $service->convertToHtml('**bold**');

    expect($result)->toContain('<strong>bold</strong>');
});

it('converts markdown with links', function () {
    $service = new MarkdownService;

    $result = $service->convertToHtml('[link](https://example.com)');

    expect($result)
        ->toContain('<a')
        ->toContain('href="https://example.com"')
        ->toContain('link</a>');
});

it('preserves id attribute in headers', function () {
    $service = new MarkdownService;

    // The user's input: raw HTML with id in header
    $markdown = '<h2 id="czesc-i">Część I: Spektrum zdrowia – od otwartości do paranoi</h2>';
    $result = $service->convertToHtml($markdown);

    // Assert that the id is present
    expect($result)->toContain('id="czesc-i"');
});

it('preserves id attribute in h2 tag using markdown syntax', function () {
    $service = new MarkdownService;

    // Standard markdown H2
    $markdown = '## Część I: Spektrum zdrowia – od otwartości do paranoi {#czesc-i}';
    $result = $service->convertToHtml($markdown);

    // Assert that the id is present
    expect($result)->toContain('id="czesc-i"');
});

it('preserves id attribute from markdown extra syntax', function () {
    $service = new MarkdownService;

    $markdown = '## Header {#some-id}';
    $result = $service->convertToHtml($markdown);

    expect($result)->toContain('id="some-id"');
});

it('preserves class attribute from markdown extra syntax', function () {
    $service = new MarkdownService;

    $markdown = '## Header {.some-class}';
    $result = $service->convertToHtml($markdown);

    expect($result)->toContain('class="some-class"');
});
