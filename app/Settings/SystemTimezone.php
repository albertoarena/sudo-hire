<?php

namespace App\Settings;

use DateTimeZone;

/**
 * The timezone of the Mac: the target of the /etc/localtime link, e.g.
 * "/var/db/timezone/zoneinfo/Europe/Rome". UTC when the link cannot be read.
 * Not final: tests replace it with a fake.
 */
class SystemTimezone
{
    public function __construct(private readonly string $link = '/etc/localtime') {}

    public function name(): string
    {
        $target = is_link($this->link) ? readlink($this->link) : false;

        if ($target === false || preg_match('#/zoneinfo/(.+)$#', $target, $match) !== 1) {
            return 'UTC';
        }

        return in_array($match[1], DateTimeZone::listIdentifiers(), true) ? $match[1] : 'UTC';
    }
}
