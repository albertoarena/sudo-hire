<?php

namespace App\Console\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

/**
 * The release workflow runs this before the build. The app trusts only the CA bundle
 * that NativePHP copies into the build, so a release must not ship an old one.
 */
#[Signature('release:check-cacert {--path= : The CA bundle to check. Default: the bundle of nativephp/desktop}')]
#[Description('Fail when the CA bundle that the build ships is older than 180 days')]
class ReleaseCheckCacertCommand extends Command
{
    public const int MAX_AGE_DAYS = 180;

    public function handle(): int
    {
        $path = $this->option('path') ?: base_path('vendor/nativephp/desktop/resources/cacert.pem');

        if (! is_file($path)) {
            $this->error("CA bundle not found: {$path}");

            return self::FAILURE;
        }

        $date = $this->dataDate((string) file_get_contents($path));

        if ($date === null) {
            $this->error("The CA bundle has no date: {$path}");

            return self::FAILURE;
        }

        $age = (int) $date->diffInDays(CarbonImmutable::now(), true);

        if ($age > self::MAX_AGE_DAYS) {
            $this->error(sprintf('The CA bundle is from %s, %d days ago.', $date->toDateString(), $age));
            $this->error(sprintf('It is older than %d days. Update nativephp/desktop before the release.', self::MAX_AGE_DAYS));

            return self::FAILURE;
        }

        $this->info(sprintf('The CA bundle is from %s, %d days ago.', $date->toDateString(), $age));

        return self::SUCCESS;
    }

    /**
     * The Mozilla bundle says when its certificate data was extracted, in its header.
     */
    private function dataDate(string $contents): ?CarbonImmutable
    {
        if (preg_match('/^## Certificate data from Mozilla as of: (.+)$/m', $contents, $match) !== 1) {
            return null;
        }

        try {
            return CarbonImmutable::parse(trim($match[1]));
        } catch (Throwable) {
            return null;
        }
    }
}
