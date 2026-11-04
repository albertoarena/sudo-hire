<?php

namespace App\Sources\Support;

/**
 * NativePHP starts its PHP processes with curl.cainfo and openssl.cafile set to its CA bundle.
 * A process that PHP starts (the scheduler starts hire:scan in the background) gets no -d flags,
 * so it would fall back to /etc/ssl/cert.pem. SSL_CERT_FILE gives it the same bundle.
 * See docs/DECISIONS.md, "TLS trust comes from the NativePHP CA bundle".
 */
final class CaBundle
{
    public static function shareWithChildProcesses(string|false $cainfo): void
    {
        if ($cainfo === false || $cainfo === '') {
            return;
        }

        // Symfony Process passes on only the variables that are also in $_SERVER or $_ENV.
        putenv("SSL_CERT_FILE={$cainfo}");
        $_ENV['SSL_CERT_FILE'] = $cainfo;
        $_SERVER['SSL_CERT_FILE'] = $cainfo;
    }
}
