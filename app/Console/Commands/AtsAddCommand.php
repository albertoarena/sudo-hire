<?php

namespace App\Console\Commands;

use App\Models\AtsCompany;
use App\Sources\Ats\CompanyEntry;
use App\Sources\Ats\InvalidCompanyEntry;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('ats:add {entry : The company as <ats>:<slug>, e.g. greenhouse:acme} {--name= : The display name. Default: the slug in title case}')]
#[Description('Add a company to the ATS reader')]
class AtsAddCommand extends Command
{
    public function handle(): int
    {
        try {
            $entry = CompanyEntry::parse((string) $this->argument('entry'), $this->option('name'));
        } catch (InvalidCompanyEntry $invalid) {
            $this->error($invalid->getMessage());

            return self::FAILURE;
        }

        if (AtsCompany::query()->forEntry($entry)->exists()) {
            $this->error("{$entry->key()} is already in the list.");

            return self::FAILURE;
        }

        AtsCompany::create([
            'ats' => $entry->ats,
            'slug' => $entry->slug,
            'display_name' => $entry->displayName,
        ]);

        $this->info("Added {$entry->key()} ({$entry->displayName}).");

        return self::SUCCESS;
    }
}
