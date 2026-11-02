<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * A company for the ATS reader: the provider (the ATS), the slug and an optional name.
 * CompanyEntry checks the values with the ats:add rules.
 */
final class StoreCompanyRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'provider' => ['required', 'string'],
            'slug' => ['required', 'string'],
            'name' => ['nullable', 'string'],
        ];
    }
}
