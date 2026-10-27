<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

/** Veckans lunch: {"mon": ["Rätt", ...], ...}. */
class LunchMenuRequest extends ContentRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'business_location_id' => ['sometimes', 'integer', Rule::exists('business_locations', 'id')->whereNull('deleted_at')],
            'iso_year' => ['required', 'integer', 'between:2000,2100'],
            'week' => ['required', 'integer', 'between:1,53'],
            'menu' => ['required', 'array:mon,tue,wed,thu,fri,sat,sun'],
            'menu.*' => ['nullable', 'array'],
            'menu.*.*' => ['string', 'max:255'],
        ];
    }
}
