<?php

use App\Enums\ContentStatus;
use App\Enums\RevisionableType;
use App\Enums\RevisionStatus;
use App\Filament\Pages\GranskningsKo;
use App\Models\Article;
use App\Models\Business;
use App\Models\BusinessLocation;
use App\Models\Event;
use App\Models\Kommun;
use App\Models\Revision;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('lists pending revisions of every type, oldest first, for the editorial team', function () {
    $this->actingAs(User::factory()->redaktor()->create());
    $older = Revision::factory()->for(Article::factory()->create(), 'revisionable')->create(['created_at' => now()->subDay()]);
    $business = Revision::factory()->create(['created_at' => now()->subHour()]);
    $newer = Revision::factory()->for(Event::factory()->create(), 'revisionable')->create(['created_at' => now()]);
    $approved = Revision::factory()->approved()->create();
    $rejected = Revision::factory()->rejected()->create();

    $this->get(GranskningsKo::getUrl())->assertOk();

    Livewire::test(GranskningsKo::class)
        ->assertCanSeeTableRecords([$older, $business, $newer], inOrder: true)
        ->assertCanNotSeeTableRecords([$approved, $rejected]);
});

it('filters the queue on type and kommun', function () {
    $this->actingAs(User::factory()->redaktor()->create());
    $ludvika = Kommun::factory()->create();
    $articleInLudvika = Revision::factory()->for(Article::factory()->create(['kommun_id' => $ludvika]), 'revisionable')->create();
    $eventInLudvika = Revision::factory()->for(Event::factory()->create(['kommun_id' => $ludvika]), 'revisionable')->create();
    $businessInLudvika = Revision::factory()->for(BusinessLocation::factory()->create(['kommun_id' => $ludvika])->business, 'revisionable')->create();
    $elsewhere = Revision::factory()->for(Article::factory()->create(), 'revisionable')->create();

    Livewire::test(GranskningsKo::class)
        ->filterTable('kommun', $ludvika->id)
        ->assertCanSeeTableRecords([$articleInLudvika, $eventInLudvika, $businessInLudvika])
        ->assertCanNotSeeTableRecords([$elsewhere])
        ->filterTable('revisionable_type', RevisionableType::Article->value)
        ->assertCanSeeTableRecords([$articleInLudvika])
        ->assertCanNotSeeTableRecords([$eventInLudvika, $businessInLudvika, $elsewhere]);
});

it('shows the current row against the payload field by field', function () {
    $this->actingAs(User::factory()->admin()->create());
    $article = Article::factory()->create(['titel' => ['sv' => 'Gammal titel', 'en' => 'Old title']]);
    $revision = Revision::factory()->for($article, 'revisionable')->create(['payload' => ['titel' => ['sv' => 'Ny titel', 'en' => 'Old title']]]);

    Livewire::test(GranskningsKo::class)
        ->mountAction(TestAction::make('diff')->table($revision))
        ->assertMountedActionModalSee('titel.sv')
        ->assertMountedActionModalSee('Gammal titel')
        ->assertMountedActionModalSee('Ny titel');
});

it('approves a revision from the queue with an optional comment', function () {
    $this->freezeSecond();
    $reviewer = User::factory()->redaktor()->create();
    $this->actingAs($reviewer);
    $business = Business::factory()->create(['namn' => 'Gamla namnet']);
    $revision = Revision::factory()->for($business, 'revisionable')->create(['payload' => ['namn' => 'Nya namnet']]);

    Livewire::test(GranskningsKo::class)
        ->callAction(TestAction::make('approve')->table($revision), ['comment' => 'Ser bra ut'])
        ->assertHasNoActionErrors()
        ->assertNotified('Ändringen är godkänd och publicerad')
        ->assertCanNotSeeTableRecords([$revision]);

    $business->refresh();

    expect($business->namn)->toBe('Nya namnet')
        ->and($business->status)->toBe(ContentStatus::Published);

    $revision->refresh();

    expect($revision->status)->toBe(RevisionStatus::Approved)
        ->and($revision->reviewed_by)->toBe($reviewer->id)
        ->and($revision->review_comment)->toBe('Ser bra ut')
        ->and($revision->applied_at)->toEqual(now());
});

it('requires a comment to reject', function () {
    $this->actingAs(User::factory()->redaktor()->create());
    $revision = Revision::factory()->create();

    Livewire::test(GranskningsKo::class)
        ->callAction(TestAction::make('reject')->table($revision), ['comment' => ''])
        ->assertHasActionErrors(['comment' => 'required']);

    expect($revision->fresh()->status)->toBe(RevisionStatus::Pending);
});

it('rejects a revision from the queue without touching the row', function () {
    $reviewer = User::factory()->redaktor()->create();
    $this->actingAs($reviewer);
    $business = Business::factory()->create(['namn' => 'Gamla namnet']);
    $revision = Revision::factory()->for($business, 'revisionable')->create(['payload' => ['namn' => 'Nya namnet']]);

    Livewire::test(GranskningsKo::class)
        ->callAction(TestAction::make('reject')->table($revision), ['comment' => 'Fel namn'])
        ->assertHasNoActionErrors()
        ->assertNotified('Ändringen är avslagen')
        ->assertCanNotSeeTableRecords([$revision]);

    $business->refresh();

    expect($business->namn)->toBe('Gamla namnet')
        ->and($business->status)->toBe(ContentStatus::Draft);

    $revision->refresh();

    expect($revision->status)->toBe(RevisionStatus::Rejected)
        ->and($revision->reviewed_by)->toBe($reviewer->id)
        ->and($revision->review_comment)->toBe('Fel namn');
});

it('grants access to admin and redaktör only', function (?string $role, bool $expected) {
    $factory = User::factory();
    $this->actingAs(($role ? $factory->{$role}() : $factory)->create());

    expect(GranskningsKo::canAccess())->toBe($expected);
})->with([
    'admin' => ['admin', true],
    'redaktör' => ['redaktor', true],
    'verksamhetsägare' => ['verksamhetsagare', false],
    'no role' => [null, false],
]);

it('forbids users without an editorial role', function (User $user) {
    $this->actingAs($user)
        ->get(GranskningsKo::getUrl())
        ->assertForbidden();
})->with([
    'no role' => fn () => User::factory()->create(),
    'verksamhetsägare' => fn () => User::factory()->verksamhetsagare()->create(),
]);

it('redirects guests to the login page', function () {
    $this->get(GranskningsKo::getUrl())
        ->assertRedirect();
});
