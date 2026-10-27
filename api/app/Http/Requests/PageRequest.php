<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

/** Statisk sida. kommun_id null = nationell sida. */
class PageRequest extends ContentRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'kommun_id' => ['nullable', 'integer', Rule::exists('kommuner', 'id')],
            ...$this->translatable('titel', required: true, maxLength: 255),
            ...$this->translatable('body'),
            ...$this->slugs(),
        ];
    }
}
