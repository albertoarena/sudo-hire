<?php

use Tests\Fakes\FakeSource;

/*
| Every JobSource must pass tests/Feature/Sources/JobSourceContractTest.php.
| Add each new source here. A closure runs inside the test, so it can call
| Http::fake() with fixtures before it returns the source.
*/

dataset('job sources', [
    'fake' => fn () => new FakeSource,
]);
