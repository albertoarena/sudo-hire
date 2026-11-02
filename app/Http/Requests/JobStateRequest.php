<?php

namespace App\Http\Requests;

use App\List\JobState;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The new state of a job: saved, applied, hidden, or null for New.
 */
final class JobStateRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'state' => ['present', 'nullable', Rule::enum(JobState::class)],
        ];
    }

    public function state(): ?JobState
    {
        $state = $this->validated('state');

        return is_string($state) ? JobState::from($state) : null;
    }
}
