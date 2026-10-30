<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * The one row of the settings table. See App\Settings\SettingsStore.
 *
 * @property array<string, mixed> $data
 */
#[Fillable(['data'])]
class StoredSettings extends Model
{
    protected $table = 'settings';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }
}
