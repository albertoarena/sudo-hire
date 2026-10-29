<?php

use App\Sources\Support\HtmlToText;

it('gives null for empty input', function (?string $html) {
    expect(HtmlToText::convert($html))->toBeNull();
})->with([
    'null' => [null],
    'empty' => [''],
    'spaces' => ["  \n "],
    'only tags' => ['<p></p><br/>'],
]);

it('turns HTML into plain text', function (string $html, string $text) {
    expect(HtmlToText::convert($html))->toBe($text);
})->with([
    'paragraphs' => ['<p>One.</p><p>Two.</p>', "One.\n\nTwo."],
    'line break' => ['One<br>Two<br/>Three', "One\nTwo\nThree"],
    'list' => ['<ul><li>PHP</li><li>Laravel</li></ul>', "- PHP\n- Laravel"],
    'heading' => ['<h4><strong>Benefits</strong></h4><p>Remote.</p>', "Benefits\n\nRemote."],
    'inline tags' => ['<p>Use <em>Rust</em> and <a href="https://example.com">Go</a>.</p>', 'Use Rust and Go.'],
    'entities' => ['<p>Fish &amp; chips&nbsp;&mdash; you&#39;ll &quot;like&quot; it</p>', 'Fish & chips — you\'ll "like" it'],
    'spaces' => ["<p>  Too   many \t spaces  </p>", 'Too many spaces'],
    'script and style' => ['<style>p{color:red}</style><p>Text</p><script>alert(1)</script>', 'Text'],
    'plain text' => ['Already plain text.', 'Already plain text.'],
]);

it('reads a real Jobicy description', function () {
    $jobs = json_decode((string) file_get_contents(__DIR__.'/../../../Fixtures/jobicy/remote-jobs.json'), true);

    $text = HtmlToText::convert($jobs['jobs'][0]['jobDescription']);

    expect($text)->toStartWith("What We Do:\n\nCybercrime is growing")
        ->and($text)->not->toContain('<')
        ->and($text)->not->toContain('&amp;');
});

it('reads a real Greenhouse description after the source decodes it once', function () {
    $jobs = json_decode((string) file_get_contents(__DIR__.'/../../../Fixtures/greenhouse/jobs.json'), true);

    $text = HtmlToText::convert(html_entity_decode($jobs['jobs'][0]['content'], ENT_QUOTES | ENT_HTML5, 'UTF-8'));

    expect($text)->toStartWith('GitLab is the intelligent orchestration platform')
        ->and($text)->toContain('- Implement and iterate on backend features')
        ->and($text)->toContain('$115,200—$162,000 USD')
        ->and($text)->not->toContain('<')
        ->and($text)->not->toContain('&nbsp;');
});
