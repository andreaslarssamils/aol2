<?php

use App\Actions\Revisions\SubmitRevision;
use App\Models\Kommun;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('accepts every proposable field of a page', function () {
    $page = Page::factory()->create();
    $payload = [
        'kommun_id' => Kommun::factory()->create()->id,
        'titel' => ['sv' => 'Om Allt om Ludvika', 'en' => 'About Allt om Ludvika'],
        'body' => ['sv' => '<p>Vi är ...</p>', 'en' => '<p>We are ...</p>'],
    ];

    $revision = app(SubmitRevision::class)->handle($page, $payload, User::factory()->verksamhetsagare()->create());

    expect(array_keys($payload))->toEqualCanonicalizing(revisionPayloadFields($page))
        ->and($revision->payload)->toEqual($payload);
});
