<?php

namespace App\Sources\Ats;

/**
 * One company in the ATS company list, e.g. "greenhouse:gitlab".
 */
final readonly class CompanyEntry
{
    public string $displayName;

    public function __construct(public Ats $ats, public string $slug, ?string $displayName = null)
    {
        if (preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*$/', $slug) !== 1) {
            throw new InvalidCompanyEntry("The slug \"{$slug}\" can only have letters, digits, dots, dashes and underscores.");
        }

        $displayName = trim($displayName ?? '');

        $this->displayName = $displayName !== '' ? $displayName : self::defaultDisplayName($slug);
    }

    /**
     * @throws InvalidCompanyEntry
     */
    public static function parse(string $entry, ?string $displayName = null): self
    {
        $parts = explode(':', trim($entry), 2);

        if (count($parts) !== 2 || trim($parts[1]) === '') {
            throw new InvalidCompanyEntry("Use the form <ats>:<slug>, e.g. greenhouse:acme. Got \"{$entry}\".");
        }

        $ats = Ats::tryFrom(strtolower(trim($parts[0])))
            ?? throw new InvalidCompanyEntry(sprintf(
                'Unknown ATS "%s". Use one of: %s.',
                trim($parts[0]),
                implode(', ', array_column(Ats::cases(), 'value')),
            ));

        return new self($ats, trim($parts[1]), $displayName);
    }

    public static function defaultDisplayName(string $slug): string
    {
        return ucwords(strtolower(str_replace(['-', '_'], ' ', $slug)));
    }

    public function key(): string
    {
        return "{$this->ats->value}:{$this->slug}";
    }
}
