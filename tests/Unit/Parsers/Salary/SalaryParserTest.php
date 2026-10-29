<?php

use App\Parsers\Salary\SalaryParser;
use App\Schema\Salary;
use App\Sources\Support\HtmlToText;

function expectSalary(?Salary $result, ?float $min, ?float $max, ?string $currency, ?string $period, string $raw): void
{
    if ($currency === null) {
        expect($result)->toBeNull();

        return;
    }

    expect($result)->not->toBeNull()
        ->and($result?->min)->toBe($min)
        ->and($result?->max)->toBe($max)
        ->and($result?->currency)->toBe($currency)
        ->and($result?->period->value)->toBe($period)
        ->and($result?->raw)->toBe($raw);
}

/**
 * The fixture file as the sources can see it: raw, JSON escaped, or HTML as plain text.
 */
function fixtureContains(string $file, string $text): bool
{
    $raw = (string) file_get_contents(dirname(__DIR__, 3).'/Fixtures/'.$file);

    $forms = [
        $text,
        substr((string) json_encode($text), 1, -1),
        substr((string) json_encode($text, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 1, -1),
    ];

    foreach ($forms as $form) {
        if (str_contains($raw, $form)) {
            return true;
        }
    }

    return str_contains((string) HtmlToText::convert(html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8')), $text);
}

it('reads the real salary strings of the fixtures', function (string $file, string $text, ?string $hint, ?float $min = null, ?float $max = null, ?string $currency = null, ?string $period = null) {
    expect(fixtureContains($file, $text))->toBeTrue("\"{$text}\" is not in {$file}");

    expectSalary((new SalaryParser)->parse($text, $hint), $min, $max, $currency, $period, $text);
})->with(require __DIR__.'/salary-table.php');

// Extra rows: the examples in REQUIREMENTS section 7, not from a fixture.
it('reads the examples of the requirements (not from a fixture)', function (string $text, ?float $min, ?float $max, string $currency, string $period) {
    expectSalary((new SalaryParser)->parse($text), $min, $max, $currency, $period, $text);
})->with([
    'k range with a code after' => ['60-70k EUR DOE', 60000.0, 70000.0, 'EUR', 'unknown'],
    'pounds per day' => ['£450 p/d', 450.0, null, 'GBP', 'day'],
    'dollars per hour' => ['$60/hour', 60.0, null, 'USD', 'hour'],
]);

it('keeps the input text as raw', function () {
    $text = '  OTE $25k - $35k  ';

    expect((new SalaryParser)->parse($text)?->raw)->toBe($text);
});

it('reads a bare dollar as USD', function () {
    expect((new SalaryParser)->parse('$170k - $200k')?->currency)->toBe('USD');
});

it('reads a bare dollar as the currency hint when there is one', function () {
    expect((new SalaryParser)->parse('$170k - $200k', 'CAD')?->currency)->toBe('CAD');
});

it('lets a currency in the text win over the hint', function () {
    expect((new SalaryParser)->parse('€108K - €145K', 'USD')?->currency)->toBe('EUR')
        ->and((new SalaryParser)->parse("\$115,200\u{2014}\$162,000 USD", 'CAD')?->currency)->toBe('USD');
});

it('gives null for text with no amount or no currency (not from a fixture)', function (string $text) {
    expect((new SalaryParser)->parse($text))->toBeNull();
})->with([
    'no amount' => ['Competitive salary, DOE'],
    'no currency' => ['60000 - 70000'],
    'two currencies' => ['€60k - $70k'],
    'min above max' => ['$200k - $170k'],
    '401(k) is not an amount' => ['401(k) retirement plan'],
]);

it('gives null for an unknown currency hint that is not ISO 4217 (not from a fixture)', function () {
    expect((new SalaryParser)->parse('$170k - $200k', 'dollars'))->toBeNull();
});

it('reads PHP as the peso only when it touches an amount (not from a fixture)', function (string $text, ?float $min, ?string $currency) {
    expectSalary((new SalaryParser)->parse($text), $min, null, $currency, 'unknown', $text);
})->with([
    'code before, with a space' => ['PHP 50,000', 50000.0, 'PHP'],
    'code after, with a space' => ['50,000 PHP', 50000.0, 'PHP'],
    'peso sign' => ['₱50,000', 50000.0, 'PHP'],
    'other code touching the amount' => ['60000EUR', 60000.0, 'EUR'],
    'the language' => ['Senior PHP developer', null, null],
    'the language and a version' => ['PHP 8.3 and Laravel 12', null, null],
]);

it('reads a dollar amount next to the PHP language (not from a fixture)', function () {
    expectSalary((new SalaryParser)->parse('PHP 8 developer, $60k'), 60000.0, null, 'USD', 'unknown', 'PHP 8 developer, $60k');
});
