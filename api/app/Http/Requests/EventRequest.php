<?php

namespace App\Http\Requests;

use App\Enums\CategoryType;
use Illuminate\Validation\Rule;

/** Hela uppsättningen fält för ett evenemang. Kommun krävs bara om platsen inte finns i katalogen. */
class EventRequest extends ContentRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'business_location_id' => ['nullable', 'integer', Rule::exists('business_locations', 'id')->whereNull('deleted_at')],
            'kommun_id' => ['required_without:business_location_id', 'nullable', 'integer', Rule::exists('kommuner', 'id')],
            'parent_event_id' => ['nullable', 'integer', Rule::exists('events', 'id')->whereNull('deleted_at')],
            'created_by' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'namn' => ['required', 'string', 'max:255'],
            ...$this->translatable('beskrivning'),
            'all_day' => ['sometimes', 'boolean'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'recurrence_rule' => ['nullable', 'string', 'max:255'],
            'venue_name' => ['nullable', 'string', 'max:255'],
            'adress' => ['nullable', 'string', 'max:255'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'pris' => ['nullable', 'string', 'max:255'],
            'ticket_url' => ['nullable', 'url', 'max:255'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['integer', Rule::exists('categories', 'id')->where('type', CategoryType::Event->value)],
            ...$this->slugs(),
        ];
    }
}
