<?php

namespace App\Http\Requests;

use App\Enums\DiscountType;
use Illuminate\Validation\Rule;

/** Erbjudandets fält. */
class OfferRequest extends ContentRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'business_id' => ['sometimes', 'integer', Rule::exists('businesses', 'id')->whereNull('deleted_at')],
            ...$this->translatable('titel', required: true, maxLength: 255),
            ...$this->translatable('beskrivning'),
            'discount_type' => ['nullable', Rule::enum(DiscountType::class)],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'code' => ['nullable', 'string', 'max:255'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ];
    }
}
