<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

/** Adressuppgifter och slug för en lokalisering. Öppettider och avvikelser är egna rader och ingår inte. */
class BusinessLocationRequest extends ContentRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'business_id' => ['sometimes', 'integer', Rule::exists('businesses', 'id')->whereNull('deleted_at')],
            'kommun_id' => ['required', 'integer', Rule::exists('kommuner', 'id')],
            'namn' => ['nullable', 'string', 'max:255'],
            'telefon' => ['nullable', 'string', 'max:255'],
            'adress' => ['nullable', 'string', 'max:255'],
            'postnummer' => ['nullable', 'string', 'max:255'],
            'ort' => ['nullable', 'string', 'max:255'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'is_primary' => ['sometimes', 'boolean'],
            ...$this->slugs(),
        ];
    }
}
