<?php

namespace App\List;

/**
 * What the menu bar list shows: the New matches, the "Location not stated" group,
 * the Saved and Applied tabs, and their counts.
 */
final readonly class ListView
{
    /**
     * @param  list<array<string, mixed>>  $new  New matches with no state, newest first
     * @param  list<array<string, mixed>>  $locationNotStated  the group, newest first
     * @param  list<array<string, mixed>>  $saved  newest state first
     * @param  list<array<string, mixed>>  $applied  newest state first
     */
    public function __construct(
        public array $new,
        public array $locationNotStated,
        public array $saved,
        public array $applied,
    ) {}

    /**
     * The unseen New matches, without the group. The menu bar badge shows it.
     */
    public function unseen(): int
    {
        return count(array_filter($this->new, fn (array $row) => $row['seen'] === false));
    }

    /**
     * @return array{new: int, saved: int, applied: int, locationNotStated: int, unseen: int}
     */
    public function counts(): array
    {
        return [
            'new' => count($this->new),
            'saved' => count($this->saved),
            'applied' => count($this->applied),
            'locationNotStated' => count($this->locationNotStated),
            'unseen' => $this->unseen(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'counts' => $this->counts(),
            'new' => $this->new,
            'locationNotStated' => $this->locationNotStated,
            'saved' => $this->saved,
            'applied' => $this->applied,
        ];
    }
}
