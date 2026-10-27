<?php

use App\Actions\Revisions\SubmitRevision;
use App\Models\BusinessLocation;
use App\Models\Event;
use App\Models\Kommun;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;

uses(RefreshDatabase::class);

it('accepts every proposable field of an event', function () {
    $event = Event::factory()->create();
    $payload = [
        'kommun_id' => Kommun::factory()->create()->id,
        'business_location_id' => BusinessLocation::factory()->create()->id,
        'parent_event_id' => Event::factory()->create()->id,
        'recurrence_rule' => 'FREQ=WEEKLY;BYDAY=TU',
        'created_by' => User::factory()->create()->id,
        'namn' => 'Pubquiz',
        'beskrivning' => ['sv' => 'Frågesport varje tisdag.', 'en' => 'Quiz every Tuesday.'],
        'all_day' => false,
        'starts_at' => '2026-06-19 18:00:00',
        'ends_at' => '2026-06-19 20:00:00',
        'venue_name' => 'Folkets hus',
        'adress' => 'Storgatan 1',
        'lat' => 60.1496,
        'lng' => 15.1877,
        'pris' => '100 kr',
        'ticket_url' => 'https://example.com/biljetter',
    ];

    $revision = app(SubmitRevision::class)->handle($event, $payload, User::factory()->verksamhetsagare()->create());

    expect(array_keys($payload))->toEqualCanonicalizing(revisionPayloadFields($event))
        ->and($revision->payload)->toEqual(Arr::except($payload, SubmitRevision::OWNERSHIP_FIELDS)); // ägarlänkar strippas för ägare
});
