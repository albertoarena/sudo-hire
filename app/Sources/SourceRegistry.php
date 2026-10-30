<?php

namespace App\Sources;

use App\Settings\Settings;
use App\Sources\Ats\AtsSource;
use App\Sources\Ats\DatabaseCompanyList;
use App\Sources\Himalayas\HimalayasSource;
use App\Sources\Jobicy\JobicySource;
use App\Sources\LaraJobs\LaraJobsSource;
use App\Sources\Remotive\RemotiveSource;
use App\Sources\WeWorkRemotely\WeWorkRemotelySource;
use LogicException;

/**
 * Every source of the app, by its key. Settings turn a source off with its key.
 */
final class SourceRegistry
{
    /** @var array<string, JobSource> */
    private array $sources = [];

    /**
     * @param  list<JobSource>|null  $sources  default: the v1 sources
     */
    public function __construct(?array $sources = null)
    {
        $sources ??= [
            new AtsSource(new DatabaseCompanyList),
            new LaraJobsSource,
            new JobicySource,
            new RemotiveSource,
            new HimalayasSource,
            new WeWorkRemotelySource,
        ];

        foreach ($sources as $source) {
            if (isset($this->sources[$source->key()])) {
                throw new LogicException("Two sources have the key \"{$source->key()}\".");
            }

            $this->sources[$source->key()] = $source;
        }
    }

    /**
     * @return list<string>
     */
    public function keys(): array
    {
        return array_keys($this->sources);
    }

    /**
     * @throws UnknownSource
     */
    public function source(string $key): JobSource
    {
        return $this->sources[$key] ?? throw UnknownSource::key($key);
    }

    /**
     * @param  list<string>  $keys
     *
     * @throws UnknownSource
     */
    public function assertKnown(array $keys): void
    {
        foreach ($keys as $key) {
            $this->source($key);
        }
    }

    /**
     * The sources that the settings do not turn off, in registry order.
     *
     * @return list<JobSource>
     */
    public function enabled(Settings $settings): array
    {
        return array_values(array_diff_key($this->sources, array_flip($settings->disabledSources)));
    }
}
