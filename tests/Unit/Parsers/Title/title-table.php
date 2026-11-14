<?php

/*
 * Every real job title in tests/Fixtures, with the expected result.
 * Each row names the fixture file. A test checks that the title is in it,
 * and a test checks that every fixture title has a row.
 * Never add an invented row. To add a row, save a trimmed real response first.
 *
 * Row: [file, title, seniority, contract type]
 */

return [
    'greenhouse vp' => ['greenhouse/cloudflare-levels.json', 'VP, Global Head of Trust & Safety', 'lead', 'unknown'],
    'greenhouse intern' => ['greenhouse/cloudflare-levels.json', 'Software Engineer Intern (2027)', 'junior', 'unknown'],
    'greenhouse contract' => ['greenhouse/cloudflare-levels.json', 'Recruiting Coordinator (Contract)', 'unknown', 'contract'],
    'greenhouse principal manager' => ['greenhouse/cloudflare-levels.json', 'Principal Campaign Manager', 'lead', 'unknown'],
    'greenhouse director' => ['greenhouse/cloudflare-levels.json', 'Commercial Sales Director, West', 'lead', 'unknown'],
    'greenhouse senior principal or director' => ['greenhouse/cloudflare-levels.json', 'GTM Strategy Senior Principal / Director, Consumption Operations', 'senior', 'unknown'],
    'greenhouse intermediate' => ['greenhouse/gitlab-levels.json', 'Intermediate Backend Engineer, India', 'mid', 'unknown'],
    'greenhouse principal' => ['greenhouse/gitlab-levels.json', 'Principal Engineer, Duo Agent Platform', 'lead', 'unknown'],
    'greenhouse intermediate to senior staff' => ['greenhouse/gitlab-remote.json', 'Site Reliability Engineer, Infrastructure Platforms — UK (Intermediate to Senior Staff)', 'mid', 'unknown'],
    'greenhouse no level' => ['greenhouse/jobs.json', 'Backend Engineer, Knowledge Graph (Rust)', 'unknown', 'unknown'],
    'greenhouse no level with colon' => ['greenhouse/jobs.json', 'Backend Engineer (Ruby), Plan: Spec-Driven Development', 'unknown', 'unknown'],
    'greenhouse engineering manager' => ['greenhouse/jobs.json', 'Engineering Manager - Git & Gitaly Operations', 'lead', 'unknown'],
    'greenhouse manager and mid-level' => ['greenhouse/twilio-levels.json', 'Product Manager L2 (mid-level)', 'mid', 'unknown'],

    'lever roman level' => ['lever/postings-salary.json', 'Android Engineer III', 'unknown', 'unknown'],
    'lever no level' => ['lever/postings-salary.json', 'AI Product Engineer', 'unknown', 'unknown'],
    'lever senior' => ['lever/postings.json', 'Senior Software Engineer - Enterprise AI', 'senior', 'unknown'],
    'lever senior backend' => ['lever/postings.json', 'Senior Backend Engineer - Content Platform', 'senior', 'unknown'],
    'lever no level with dash' => ['lever/postings.json', 'AV Engineer - Platform', 'unknown', 'unknown'],

    'ashby senior' => ['ashby/job-board.json', 'Senior Software Engineer, Product Engineering - EU', 'senior', 'unknown'],
    'ashby specialist' => ['ashby/job-board.json', 'Product Support Specialist - Australia', 'unknown', 'unknown'],
    'ashby support engineer' => ['ashby/job-board.json', 'Product Support Engineer - APAC', 'unknown', 'unknown'],

    'personio head of' => ['personio/positions.xml', 'Head of Finance & Controlling (m/w/d)', 'lead', 'unknown'],
    'personio senior' => ['personio/positions.xml', 'Senior AI Engineer – Architecture & Platform (m/w/d)', 'senior', 'unknown'],
    'personio werkstudent' => ['personio/positions.xml', 'Werkstudent - Product Management / Health (m/w/d)', 'unknown', 'unknown'],

    'larajobs senior us only' => ['larajobs/feed.xml', 'Senior Full Stack Software Engineer (PHP/Laravel) - US Only', 'senior', 'unknown'],
    'larajobs senior' => ['larajobs/feed.xml', 'Senior Laravel Developer', 'senior', 'unknown'],
    'larajobs product engineer' => ['larajobs/feed-code-and-symbol.xml', 'Product Engineer (Laravel - Remote)', 'unknown', 'unknown'],
    'larajobs senior fullstack' => ['larajobs/feed.xml', 'Senior Fullstack PHP/Laravel Developer – Remote (Europe/Berlin Time Zone)', 'senior', 'unknown'],

    'jobicy junior permanent' => ['jobicy/remote-jobs-junior.json', 'Junior Accountant (Permanent work from home)', 'junior', 'unknown'],
    'jobicy no level' => ['jobicy/remote-jobs.json', 'Security Operations Analyst - West Coast Shift', 'unknown', 'unknown'],
    'jobicy no level with bar' => ['jobicy/remote-jobs.json', 'DevOps Engineer | Live Entertainment Company', 'unknown', 'unknown'],
    'jobicy permanent' => ['jobicy/remote-jobs.json', 'Graphic Designer (Permanent work from home)', 'unknown', 'unknown'],
    'jobicy staff' => ['jobicy/jobs.json', 'Staff Software Engineer', 'lead', 'unknown'],
    'jobicy no level with in' => ['jobicy/jobs.json', 'Software Engineer in Hardware Infrastructure', 'unknown', 'unknown'],
    'jobicy staff with comma' => ['jobicy/jobs.json', 'Staff Backend Engineer, Developer Experience', 'lead', 'unknown'],

    'remotive contractor' => ['remotive/remote-jobs-contract.json', 'Inside Sales Contractor', 'unknown', 'contract'],
    'remotive freelance' => ['remotive/remote-jobs-contract.json', 'Freelance Writer', 'unknown', 'contract'],
    'remotive tech lead full-stack' => ['remotive/remote-jobs.json', 'Tech Lead Full-Stack Rails Engineer', 'lead', 'unknown'],
    'remotive head of' => ['remotive/remote-jobs.json', 'Head of Engineering', 'lead', 'unknown'],
    'remotive senior' => ['remotive/remote-jobs.json', 'Senior back-end Engineer', 'senior', 'unknown'],

    'himalayas senior scientist' => ['himalayas/jobs.json', 'Senior Research Scientist - AI Security', 'senior', 'unknown'],
    'himalayas senior ai' => ['himalayas/jobs.json', 'Senior AI/ML Engineer', 'senior', 'unknown'],
    'himalayas no level' => ['himalayas/jobs.json', 'Product Analyst - Pendo (Europe, Remote)', 'unknown', 'unknown'],
    'himalayas mid senior' => ['himalayas/search-mid.json', 'Mid Senior PHP Developer (Remoto)', 'mid', 'unknown'],
    'himalayas mid with dot' => ['himalayas/search-mid.json', 'MID. PHP DEVELOPER (SYMFONY)', 'mid', 'unknown'],
    'himalayas mid or sr' => ['himalayas/search-mid.json', 'Mid/Sr PHP Developer', 'mid', 'unknown'],
    'himalayas sr with dot' => ['himalayas/search.json', 'SR. PHP DEVELOPER (LARAVEL OR SYMFONY)', 'senior', 'unknown'],
    'himalayas senior' => ['himalayas/search.json', 'Senior PHP Developer', 'senior', 'unknown'],
    'himalayas lead' => ['himalayas/search.json', 'Lead PHP Developer (Laravel)', 'lead', 'unknown'],

    // We Work Remotely titles are "Company: Title". The source keeps the part after the first ": ".
    'wwr senior java' => ['weworkremotely/back-end.rss', 'Senior Java Developer', 'senior', 'unknown'],
    'wwr senior backend' => ['weworkremotely/back-end.rss', 'Senior Backend Developer (Python)', 'senior', 'unknown'],
    'wwr staff' => ['weworkremotely/back-end.rss', 'Staff Software Engineer, Revenue Systems', 'lead', 'unknown'],
];
