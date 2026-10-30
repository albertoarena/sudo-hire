<?php

namespace App\Sources;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use JsonException;
use Throwable;

/**
 * Why a source run failed. M8 shows "certificate error" for Certificate.
 */
enum RunErrorKind: string
{
    case Certificate = 'certificate';
    case Http = 'http';
    case Parse = 'parse';
    case Other = 'other';

    /**
     * cURL errors of the TLS layer: 35 (handshake), 51 (host name), 53, 54, 58, 59,
     * 60 (CA bundle), 64, 66, 77, 80, 82, 83, 90 and 91.
     */
    private const string TLS = '/\bcURL error (?:35|51|53|54|58|59|60|64|66|77|80|82|83|90|91)\b/';

    public static function of(Throwable $error): self
    {
        return match (true) {
            $error instanceof ConnectionException => preg_match(self::TLS, $error->getMessage()) === 1 ? self::Certificate : self::Http,
            $error instanceof RequestException => self::Http,
            $error instanceof UnreadableResponse, $error instanceof JsonException => self::Parse,
            default => self::Other,
        };
    }
}
