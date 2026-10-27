<?php

use App\Actions\Revisions\SubmitRevision;
use App\Models\Business;
use App\Models\BusinessLocation;
use App\Models\Kommun;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;

uses(RefreshDatabase::class);

it('accepts every proposable field of a business location', function () {
    $location = BusinessLocation::factory()->create();
    $payload = [
        'business_id' => Business::factory()->create()->id,
        'kommun_id' => Kommun::factory()->create()->id,
        'namn' => 'Acme Centrum',
        'telefon' => '0240-123 45',
        'adress' => 'Storgatan 1',
        'postnummer' => '771 30',
        'ort' => 'Ludvika',
        'lat' => 60.1496,
        'lng' => 15.1877,
        'is_primary' => true,
    ];

    $revision = app(SubmitRevision::class)->handle($location, $payload, User::factory()->verksamhetsagare()->create());

    expect(array_keys($payload))->toEqualCanonicalizing(revisionPayloadFields($location))
        ->and($revision->payload)->toEqual(Arr::except($payload, SubmitRevision::OWNERSHIP_FIELDS)); // ägarlänkar strippas för ägare
});
