<?php

use App\Actions\Revisions\SubmitRevision;
use App\Models\BusinessLocation;
use App\Models\LunchMenu;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;

uses(RefreshDatabase::class);

it('accepts every proposable field of a lunch menu', function () {
    $lunchMenu = LunchMenu::factory()->create();
    $payload = [
        'business_location_id' => BusinessLocation::factory()->create()->id,
        'iso_year' => 2026,
        'week' => 44,
        'menu' => ['mon' => ['Köttbullar med potatismos'], 'tue' => ['Stekt strömming', 'Vegetarisk lasagne']],
    ];

    $revision = app(SubmitRevision::class)->handle($lunchMenu, $payload, User::factory()->verksamhetsagare()->create());

    expect(array_keys($payload))->toEqualCanonicalizing(revisionPayloadFields($lunchMenu))
        ->and($revision->payload)->toEqual(Arr::except($payload, SubmitRevision::OWNERSHIP_FIELDS)); // ägarlänkar strippas för ägare
});
