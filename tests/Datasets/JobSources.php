<?php

use App\Sources\Ats\AtsSource;
use App\Sources\Ats\CompanyEntry;
use Illuminate\Support\Facades\Http;
use Tests\Fakes\FakeSource;
use Tests\Fakes\InMemoryCompanyList;
use Tests\Support\JobFields;

/*
| Every JobSource must pass tests/Feature/Sources/JobSourceContractTest.php.
| Add each new source here. A closure runs inside the test, so it can call
| Http::fake() with fixtures before it returns the source.
*/

dataset('job sources', [
    'fake' => fn () => new FakeSource,
    'ats' => function () {
        Http::fake([
            'boards-api.greenhouse.io/*' => Http::response(JobFields::fixture('greenhouse/jobs.json')),
            'api.lever.co/*' => Http::response(JobFields::fixture('lever/postings.json')),
            'api.ashbyhq.com/*' => Http::response(JobFields::fixture('ashby/job-board.json')),
            '*.jobs.personio.de/*' => Http::response(JobFields::fixture('personio/positions.xml')),
        ]);

        return new AtsSource(new InMemoryCompanyList(array_map(
            fn (string $entry) => CompanyEntry::parse($entry),
            ['greenhouse:gitlab', 'lever:spotify', 'ashby:ashby', 'personio:ottonova'],
        )));
    },
]);
