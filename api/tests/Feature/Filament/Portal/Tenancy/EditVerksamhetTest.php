<?php

use App\Actions\Revisions\SubmitRevision;
use App\Enums\BusinessUserRole;
use App\Enums\ContentStatus;
use App\Enums\RevisionStatus;
use App\Filament\Portal\Schemas\Components\PendingRevisionBanner;
use App\Filament\Portal\Tenancy\EditVerksamhet;
use App\Models\Business;
use App\Models\Category;
use App\Models\Revision;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('submits the business details as a pending revision and leaves the row untouched', function (BusinessUserRole $role) {
    $user = User::factory()->verksamhetsagare()->create();
    $business = businessOwnedBy($user, $role, ['namn' => 'Gamla namnet', 'epost' => 'gammal@example.com']);
    $category = Category::factory()->create();
    actingAsPortalUser($user, $business);

    Livewire::test(EditVerksamhet::class)
        ->fillForm([
            'namn' => 'Nya namnet',
            'epost' => 'ny@example.com',
            'categories' => [$category->id],
            'beskrivning.sv' => '<p>Ny text</p><script>alert(1)</script>',
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified('Skickad för granskning');

    $revision = Revision::sole();

    expect($revision->status)->toBe(RevisionStatus::Pending)
        ->and($revision->submitted_by)->toBe($user->id)
        ->and($revision->revisionable->is($business))->toBeTrue()
        ->and($revision->payload['namn'])->toBe('Nya namnet')
        ->and($revision->payload['epost'])->toBe('ny@example.com')
        ->and($revision->payload['categories'])->toBe([$category->id])
        ->and($revision->payload['beskrivning']['sv'])->toBe('<p>Ny text</p>');

    $business->refresh();

    expect($business->namn)->toBe('Gamla namnet')
        ->and($business->epost)->toBe('gammal@example.com')
        ->and($business->categories)->toHaveCount(0);
})->with([
    'owner' => BusinessUserRole::Owner,
    'manager' => BusinessUserRole::Manager,
]);

it('publishes immediately when the business has auto_approve', function () {
    $owner = User::factory()->verksamhetsagare()->create();
    $business = businessOwnedBy($owner, attributes: ['namn' => 'Gamla namnet', 'auto_approve' => true]);
    actingAsPortalUser($owner, $business);

    Livewire::test(EditVerksamhet::class)
        ->fillForm(['namn' => 'Nya namnet'])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified('Publicerad');

    $business->refresh();

    expect($business->namn)->toBe('Nya namnet')
        ->and($business->status)->toBe(ContentStatus::Published)
        ->and(Revision::sole()->status)->toBe(RevisionStatus::Approved);
});

it('stores uploaded images in the pending collection until approval', function () {
    Storage::fake('public');
    $owner = User::factory()->verksamhetsagare()->create();
    $business = businessOwnedBy($owner);
    actingAsPortalUser($owner, $business);

    Livewire::test(EditVerksamhet::class)
        ->fillForm(['pending' => [UploadedFile::fake()->image('ny.jpg')]])
        ->call('save')
        ->assertHasNoFormErrors();

    $business->refresh();

    expect($business->getMedia(Business::MEDIA_PENDING))->toHaveCount(1)
        ->and($business->getMedia(Business::MEDIA_IMAGES))->toHaveCount(0);
});

it('shows a banner when a revision is already pending, with its diff', function () {
    $owner = User::factory()->verksamhetsagare()->create();
    $business = businessOwnedBy($owner, attributes: ['namn' => 'Gamla namnet']);
    Revision::factory()->for($business, 'revisionable')->create(['submitted_by' => $owner->id, 'payload' => ['namn' => 'Väntande namnet']]);
    actingAsPortalUser($owner, $business);

    Livewire::test(EditVerksamhet::class)
        ->assertSee(PendingRevisionBanner::HEADING)
        ->assertSee("av {$owner->name}")
        ->assertSee('Visa väntande ändring');
});

it('shows no banner without a pending revision', function () {
    $owner = User::factory()->verksamhetsagare()->create();
    $business = businessOwnedBy($owner);
    Revision::factory()->approved()->for($business, 'revisionable')->create();
    actingAsPortalUser($owner, $business);

    Livewire::test(EditVerksamhet::class)
        ->assertDontSee(PendingRevisionBanner::HEADING);
});

it('replaces the pending revision when the owner submits again', function () {
    $owner = User::factory()->verksamhetsagare()->create();
    $business = businessOwnedBy($owner, attributes: ['namn' => 'Gamla namnet']);
    $first = Revision::factory()->for($business, 'revisionable')->create(['submitted_by' => $owner->id, 'payload' => ['namn' => 'Första förslaget']]);
    actingAsPortalUser($owner, $business);

    Livewire::test(EditVerksamhet::class)
        ->fillForm(['namn' => 'Andra förslaget'])
        ->call('save')
        ->assertHasNoFormErrors();

    $first->refresh();

    expect($first->status)->toBe(RevisionStatus::Rejected)
        ->and($first->review_comment)->toBe(SubmitRevision::SUPERSEDED_COMMENT)
        ->and(Revision::query()->pending()->get())->toHaveCount(1)
        ->and(Revision::query()->pending()->sole()->payload['namn'])->toBe('Andra förslaget')
        ->and($business->fresh()->namn)->toBe('Gamla namnet');
});
