<?php

namespace App\Http\Requests;

use App\Enums\BusinessKind;
use App\Enums\CategoryType;
use Illuminate\Validation\Rule;

/** Fälten en verksamhetsägare får föreslå. managed_by, auto_approve och status sätts bara av redaktionen. */
class BusinessRequest extends ContentRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'kind' => ['sometimes', Rule::enum(BusinessKind::class)],
            'namn' => ['required', 'string', 'max:255'],
            ...$this->translatable('beskrivning'),
            'epost' => ['nullable', 'email', 'max:255'],
            'webbplats' => ['nullable', 'url', 'max:255'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['integer', Rule::exists('categories', 'id')->where('type', CategoryType::Business->value)],
        ];
    }
}
