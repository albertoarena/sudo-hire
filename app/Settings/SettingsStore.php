<?php

namespace App\Settings;

use App\Models\StoredSettings;
use App\Sources\SourceRegistry;
use App\Sources\UnknownSource;

/**
 * Loads and saves the settings in the settings table, one row of JSON.
 * Before the first save, the defaults use the timezone of the Mac.
 * The first save with a country makes the next scan the first scan.
 */
final class SettingsStore
{
    public function __construct(
        private readonly SystemTimezone $timezone,
        private readonly SourceRegistry $registry,
    ) {}

    public function load(): Settings
    {
        $stored = StoredSettings::query()->orderBy('id')->first();

        return $stored === null ? Settings::defaults($this->timezone->name()) : Settings::fromArray($stored->data);
    }

    /**
     * @throws UnknownSource for a disabled source that the registry does not know
     */
    public function save(Settings $settings): void
    {
        $this->registry->assertKnown($settings->disabledSources);

        $stored = StoredSettings::query()->orderBy('id')->first() ?? new StoredSettings;

        if ($settings->country !== null && ($stored->data['country'] ?? null) === null) {
            $stored->first_scan_pending = true;
        }

        $stored->data = $settings->toArray();
        $stored->save();
    }

    /**
     * True until the first scan with a country has run.
     */
    public function firstScanPending(): bool
    {
        return StoredSettings::query()->orderBy('id')->first()?->first_scan_pending === true;
    }

    public function firstScanDone(): void
    {
        StoredSettings::query()->update(['first_scan_pending' => false]);
    }
}
