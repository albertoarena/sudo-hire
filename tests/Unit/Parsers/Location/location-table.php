<?php

/*
 * Real location strings from tests/Fixtures, with the expected result.
 * Each row names the fixture file. A test checks that the string is in it.
 * Add a row for every bug found (REQUIREMENTS section 6).
 *
 * Row: [file, text, work mode, eligibility type, regions, countries, timezone min, timezone max, office location]
 */

return [
    'empty country' => ['weworkremotely/back-end.rss', '', 'unknown', 'unknown', [], [], null, null, null],
    'anywhere in the world' => ['weworkremotely/back-end.rss', 'Anywhere in the World', 'remote', 'worldwide', [], [], null, null, null],

    'city and country' => ['greenhouse/jobs.json', 'Bangalore, India', 'unknown', 'restricted', [], ['IN'], null, null, null],
    'two remote countries' => ['greenhouse/jobs.json', 'Remote, Canada; Remote, United States', 'remote', 'restricted', [], ['CA', 'US'], null, null, null],
    'remote country' => ['greenhouse/jobs.json', 'Remote, Poland', 'remote', 'restricted', [], ['PL'], null, null, null],
    'four remote countries' => ['greenhouse/gitlab-levels.json', 'Remote, Canada; Remote, Poland; Remote, United Kingdom; Remote, United States', 'remote', 'restricted', [], ['CA', 'PL', 'GB', 'US'], null, null, null],
    'remote dash country' => ['greenhouse/twilio-levels.json', 'Remote - Estonia', 'remote', 'restricted', [], ['EE'], null, null, null],
    'distributed and hybrid' => ['greenhouse/cloudflare-levels.json', 'Distributed; Hybrid', 'remote', 'unknown', [], [], null, null, null],
    'hybrid only' => ['greenhouse/cloudflare-levels.json', 'Hybrid', 'hybrid', 'unknown', [], [], null, null, null],
    'remote only' => ['greenhouse/gitlab-remote.json', 'Remote', 'remote', 'unknown', [], [], null, null, null],
    'in office only' => ['greenhouse/cloudflare-levels.json', 'In-Office', 'onsite', 'unknown', [], [], null, null, null],

    'lever city and state' => ['lever/postings.json', 'New York, NY', 'unknown', 'unknown', [], [], null, null, null],
    'lever city' => ['lever/postings.json', 'Stockholm', 'unknown', 'unknown', [], [], null, null, null],
    'lever city and province' => ['lever/postings-salary.json', 'Vancouver, British Columbia', 'unknown', 'unknown', [], [], null, null, null],

    'ashby remote eu' => ['ashby/job-board.json', 'Remote - European Union', 'remote', 'restricted', ['eu'], [], null, null, null],
    'ashby country' => ['ashby/job-board.json', 'Australia', 'unknown', 'restricted', [], ['AU'], null, null, null],
    'ashby address country eu' => ['ashby/job-board.json', 'European Union', 'unknown', 'restricted', ['eu'], [], null, null, null],

    'personio office' => ['personio/positions.xml', 'München', 'unknown', 'unknown', [], [], null, null, null],

    'larajobs us' => ['larajobs/feed.xml', 'Remote/USA', 'remote', 'restricted', [], ['US'], null, null, null],
    'larajobs hybrid office' => ['larajobs/feed.xml', 'London, UK (Hybrid)', 'hybrid', 'unknown', [], [], null, null, 'London, UK'],
    'larajobs timezone' => ['larajobs/feed.xml', 'Remote (Europe/Berlin Time Zone)', 'remote', 'unknown', [], [], 1.0, 2.0, null],

    'jobicy europe' => ['jobicy/remote-jobs.json', 'Europe', 'unknown', 'restricted', ['europe'], [], null, null, null],
    'jobicy usa' => ['jobicy/remote-jobs.json', 'USA', 'unknown', 'restricted', [], ['US'], null, null, null],
    'jobicy philippines' => ['jobicy/remote-jobs-junior.json', 'Philippines', 'unknown', 'restricted', [], ['PH'], null, null, null],

    'remotive worldwide' => ['remotive/remote-jobs.json', 'Worldwide', 'remote', 'worldwide', [], [], null, null, null],
    'remotive us and timezones' => ['remotive/remote-jobs.json', 'USA, Canada, USA timezones', 'unknown', 'restricted', [], ['US', 'CA'], -10.0, -4.0, null],
    'remotive region and countries' => ['remotive/remote-jobs.json', 'Europe, USA, UK, Canada, Australia, Ireland, Switzerland, Singapore, Mexico, Iceland, Norway', 'unknown', 'restricted', ['europe'], ['US', 'GB', 'CA', 'AU', 'IE', 'CH', 'SG', 'MX', 'IS', 'NO'], null, null, null],
];
