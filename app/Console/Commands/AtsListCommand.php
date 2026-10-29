<?php

namespace App\Console\Commands;

use App\Models\AtsCompany;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('ats:list')]
#[Description('List the companies of the ATS reader with the last result of each')]
class AtsListCommand extends Command
{
    public function handle(): int
    {
        $companies = AtsCompany::query()->orderBy('id')->get();

        if ($companies->isEmpty()) {
            $this->info('No companies yet. Add one with: php artisan ats:add <ats>:<slug>');

            return self::SUCCESS;
        }

        $this->table(
            ['Entry', 'Name', 'Enabled', 'Last checked', 'Last result'],
            $companies->map(fn (AtsCompany $company) => [
                $company->toEntry()->key(),
                $company->display_name,
                $company->enabled ? 'yes' : 'no',
                $company->last_checked_at === null ? 'never' : $company->last_checked_at->utc()->format('Y-m-d H:i').' UTC',
                $this->lastResult($company),
            ])->all(),
        );

        return self::SUCCESS;
    }

    private function lastResult(AtsCompany $company): string
    {
        return match (true) {
            $company->last_error !== null => "Error: {$company->last_error}",
            $company->last_job_count !== null => $company->last_job_count === 1 ? '1 job' : "{$company->last_job_count} jobs",
            default => 'not checked yet',
        };
    }
}
