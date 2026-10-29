<?php

namespace App\Console\Commands;

use App\Models\AtsCompany;
use App\Sources\Ats\CompanyEntry;
use App\Sources\Ats\InvalidCompanyEntry;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('ats:remove {entry : The company as <ats>:<slug>, e.g. greenhouse:acme}')]
#[Description('Remove a company from the ATS reader')]
class AtsRemoveCommand extends Command
{
    public function handle(): int
    {
        try {
            $entry = CompanyEntry::parse((string) $this->argument('entry'));
        } catch (InvalidCompanyEntry $invalid) {
            $this->error($invalid->getMessage());

            return self::FAILURE;
        }

        if (AtsCompany::query()->forEntry($entry)->delete() === 0) {
            $this->error("{$entry->key()} is not in the list.");

            return self::FAILURE;
        }

        $this->info("Removed {$entry->key()}.");

        return self::SUCCESS;
    }
}
