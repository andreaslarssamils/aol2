<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

/** Hela uppsättningen fält för en artikel. Status och publiceringsdatum sätts vid godkännande, inte här. */
class ArticleRequest extends ContentRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'kommun_id' => ['required', 'integer', Rule::exists('kommuner', 'id')],
            'author_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'author_name' => ['nullable', 'string', 'max:255'],
            ...$this->translatable('titel', required: true, maxLength: 255),
            ...$this->translatable('ingress'),
            ...$this->translatable('body'),
            'featured' => ['sometimes', 'boolean'],
            ...$this->slugs(),
        ];
    }
}
