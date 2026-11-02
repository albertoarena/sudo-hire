<?php

/*
 * Real job titles from tests/Fixtures that name a location limit, with the expected places.
 * A test checks that each title is in its file, and that every other fixture title gives unknown.
 *
 * Row: [file, title, regions, countries]
 */

return [
    'ashby eu after a dash' => ['ashby/job-board.json', 'Senior Software Engineer, Product Engineering - EU', ['eu'], []],
    'ashby apac after a dash' => ['ashby/job-board.json', 'Product Support Engineer - APAC', ['apac'], []],
    'larajobs us only' => ['larajobs/feed.xml', 'Senior Full Stack Software Engineer (PHP/Laravel) - US Only', [], ['US']],
    'himalayas europe and remote in brackets' => ['himalayas/jobs.json', 'Product Analyst - Pendo (Europe, Remote)', ['europe'], []],
];
