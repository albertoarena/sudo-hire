<?php

/*
 * Real salary strings from tests/Fixtures, with the expected result.
 * Each row names the fixture file. A test checks that the string is in it.
 * Add a row for every bug found (REQUIREMENTS section 7).
 *
 * Row: [file, text, currency hint, expected min, max, currency, period] or [file, text, hint, null].
 * "\u{2013}" is an en dash and "\u{2014}" is an em dash, as in the files.
 */

return [
    // Salary fields
    'LaraJobs dollars with decimals' => ['larajobs/feed.xml', '$160,000.00 - $180,000.00', null, 160000.0, 180000.0, 'USD', 'unknown'],
    'LaraJobs pounds' => ['larajobs/feed.xml', '£70,000 - £90,000', null, 70000.0, 90000.0, 'GBP', 'unknown'],
    'LaraJobs empty salary' => ['larajobs/feed.xml', '', null, null],
    'Remotive k range' => ['remotive/remote-jobs.json', '$170k - $200k', null, 170000.0, 200000.0, 'USD', 'unknown'],
    'Remotive hourly range' => ['remotive/remote-jobs-contract.json', '$50-$75 /hour', null, 50.0, 75.0, 'USD', 'hour'],
    'Remotive on-target earnings' => ['remotive/remote-jobs-contract.json', 'OTE $25k - $35k', null, 25000.0, 35000.0, 'USD', 'unknown'],
    'Remotive empty salary' => ['remotive/remote-jobs.json', '', null, null],
    'Greenhouse pay range' => ['greenhouse/jobs.json', "\$115,200\u{2014}\$162,000 USD", null, 115200.0, 162000.0, 'USD', 'unknown'],
    'Greenhouse pay range, high' => ['greenhouse/gitlab-levels.json', "\$250,000\u{2014}\$325,000 USD", null, 250000.0, 325000.0, 'USD', 'unknown'],

    // Ashby salary summaries (the source also gives structured fields)
    'Ashby euro k' => ['ashby/job-board.json', '€108K - €145K', 'EUR', 108000.0, 145000.0, 'EUR', 'unknown'],
    'Ashby euro k with en dash' => ['ashby/job-board.json', "€108K \u{2013} €133K", null, 108000.0, 133000.0, 'EUR', 'unknown'],
    'Ashby Australian dollars' => ['ashby/job-board.json', 'A$89K - A$105K', 'AUD', 89000.0, 105000.0, 'AUD', 'unknown'],
    'Ashby Australian dollars, no hint' => ['ashby/job-board.json', 'A$89K - A$105K', null, 89000.0, 105000.0, 'AUD', 'unknown'],
    'Ashby country and Australian dollars' => ['ashby/job-board.json', "Australia A\$136,000 \u{2013} A\$165,000", null, 136000.0, 165000.0, 'AUD', 'unknown'],
    'Ashby New Zealand dollars' => ['ashby/job-board.json', "New Zealand NZ\$138,000 \u{2013} NZ\$164,000", null, 138000.0, 164000.0, 'NZD', 'unknown'],
    'Ashby yen with hint' => ['ashby/job-board.json', '¥11,950,000 - ¥13,750,000', 'JPY', 11950000.0, 13750000.0, 'JPY', 'unknown'],
    'Ashby yen with no hint (JPY or CNY)' => ['ashby/job-board.json', '¥11,950,000 - ¥13,750,000', null, null],

    // Salary sentences in descriptions
    'Lever base range' => ['lever/postings.json', "The United States base range for this position is \$164,448\u{2013}\$234,926 USD, plus equity.", null, 164448.0, 234926.0, 'USD', 'unknown'],
    'Lever base range, next sentence says monthly' => ['lever/postings.json', "The United States base range for this position is \$164,448\u{2013}\$234,926 USD, plus equity. The benefits available for this position include health insurance, six-month paid parental leave, 401(k) retirement plan, monthly meal allowance", null, 164448.0, 234926.0, 'USD', 'unknown'],
    'Lever salary text with no amount' => ['lever/postings-salary.json', 'This salary range is reflective of a position based in New York City.', null, null],
    'Cloudflare annual salary' => ['greenhouse/cloudflare-levels.json', 'Estimated annual salary of $164,000 - $226,000.', null, 164000.0, 226000.0, 'USD', 'year'],
    'Himalayas monthly gross' => ['himalayas/search-mid.json', 'Salary range: €5000 - €5800 (GROSS) / month.', null, 5000.0, 5800.0, 'EUR', 'month'],
    'We Work Remotely cash range' => ['weworkremotely/back-end.rss', 'Cash Compensation Range: $170,000 - $299,000 USD', null, 170000.0, 299000.0, 'USD', 'unknown'],
    'Remotive budget per month' => ['remotive/remote-jobs.json', 'starting budget is $5k/mo', null, 5000.0, null, 'USD', 'month'],
    'Jobicy pesos touching the amount, per month' => ['jobicy/remote-jobs-junior.json', 'Work from Home Allowance of 1000PHP per month', null, 1000.0, null, 'PHP', 'month'],
    'Jobicy pesos touching the amount, per year' => ['jobicy/remote-jobs-junior.json', 'Health and wellness benefit of 7500PHP per year', null, 7500.0, null, 'PHP', 'year'],
    'Remotive per hour' => ['remotive/remote-jobs-contract.json', '$50 per hour', null, 50.0, null, 'USD', 'hour'],
];
