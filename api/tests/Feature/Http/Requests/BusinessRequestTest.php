<?php

use App\Actions\Revisions\SubmitRevision;
use App\Enums\BusinessKind;
use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('accepts every proposable field of a business', function () {
    $business = Business::factory()->create();
    $payload = [
        'kind' => BusinessKind::Association->value,
        'namn' => 'Ludvika IF',
        'beskrivning' => ['sv' => '<p>Fotboll sedan 1909.</p>', 'en' => '<p>Football since 1909.</p>'],
        'epost' => 'kansli@ludvikaif.se',
        'webbplats' => 'https://ludvikaif.se',
    ];

    $revision = app(SubmitRevision::class)->handle($business, $payload, User::factory()->verksamhetsagare()->create());

    expect(array_keys($payload))->toEqualCanonicalizing(revisionPayloadFields($business))
        ->and($revision->payload)->toEqual($payload);
});
