<?php

use App\Actions\Revisions\SubmitRevision;
use App\Enums\DiscountType;
use App\Models\Business;
use App\Models\Offer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('accepts every proposable field of an offer', function () {
    $offer = Offer::factory()->create();
    $payload = [
        'business_id' => Business::factory()->create()->id,
        'titel' => ['sv' => 'Sommarrabatt', 'en' => 'Summer discount'],
        'beskrivning' => ['sv' => '10 % på allt.', 'en' => '10% off everything.'],
        'discount_type' => DiscountType::Percentage->value,
        'discount_value' => 10,
        'code' => 'SOMMAR10',
        'starts_at' => '2026-06-01 00:00:00',
        'ends_at' => '2026-08-31 23:59:00',
    ];

    $revision = app(SubmitRevision::class)->handle($offer, $payload, User::factory()->verksamhetsagare()->create());

    expect(array_keys($payload))->toEqualCanonicalizing(revisionPayloadFields($offer))
        ->and($revision->payload)->toEqual($payload);
});
