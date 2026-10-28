<?php

use Illuminate\Http\Client\StrayRequestException;
use Illuminate\Support\Facades\Http;

it('refuses a live HTTP request that is not faked', function () {
    Http::get('https://example.com');
})->throws(StrayRequestException::class);
