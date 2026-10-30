<?php

namespace App\Settings;

use App\Models\StoredSettings;
use App\Sources\SourceRegistry;
use App\Sources\UnknownSource;

/**
 * Loads and saves the settings in the settings table, one row of JSON.
 * Before the first save, the defaults use the timezone of the Mac.
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
        $stored->data = $settings->toArray();
        $stored->save();
    }
}
