<?php

use App\Sources\RunErrorKind;
use App\Sources\UnreadableResponse;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;

it('gives the kind of a run error', function (Throwable $error, RunErrorKind $kind) {
    expect(RunErrorKind::of($error))->toBe($kind);
})->with([
    'cURL error 60' => [new ConnectionException('cURL error 60: SSL certificate problem: unable to get local issuer certificate'), RunErrorKind::Certificate],
    'cURL error 35, TLS handshake' => [new ConnectionException('cURL error 35: OpenSSL SSL_connect: SSL_ERROR_SYSCALL in connection to example.com:443'), RunErrorKind::Certificate],
    'cURL error 51, wrong host name' => [new ConnectionException("cURL error 51: SSL: no alternative certificate subject name matches target host name 'example.com'"), RunErrorKind::Certificate],
    'cURL error 6, unknown host' => [new ConnectionException('cURL error 6: Could not resolve host: example.com'), RunErrorKind::Http],
    'cURL error 28, timeout' => [new ConnectionException('cURL error 28: Operation timed out after 20001 milliseconds'), RunErrorKind::Http],
    'HTTP status' => [new RequestException(new Response(new Psr7Response(503))), RunErrorKind::Http],
    'unreadable response' => [new UnreadableResponse('The LaraJobs feed is not RSS.'), RunErrorKind::Parse],
    'bad JSON' => [new JsonException('Syntax error'), RunErrorKind::Parse],
    'anything else' => [new LogicException('A bug'), RunErrorKind::Other],
]);
