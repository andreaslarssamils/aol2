<?php

use App\Actions\Revisions\SubmitRevision;
use App\Enums\ContentStatus;
use App\Enums\RevisionStatus;
use App\Models\Article;
use App\Models\Business;
use App\Models\BusinessLocation;
use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('queues a pending revision with the validated payload and leaves the row untouched', function () {
    $article = Article::factory()->create(['titel' => ['sv' => 'Gammal titel', 'en' => 'Old title']]);
    $owner = User::factory()->verksamhetsagare()->create();

    $revision = app(SubmitRevision::class)->handle($article, [
        'kommun_id' => $article->kommun_id,
        'titel' => ['sv' => 'Ny titel', 'en' => 'New title'],
        'ingress' => ['sv' => 'Så firas midsommar.', 'en' => null],
        'featured' => true,
        'slugs' => ['sv' => 'Ny Titel!', 'en' => ''],
    ], $owner);

    expect($revision->status)->toBe(RevisionStatus::Pending)
        ->and($revision->submitted_by)->toBe($owner->id)
        ->and($revision->revisionable->is($article))->toBeTrue()
        ->and($revision->payload['titel'])->toBe(['sv' => 'Ny titel', 'en' => 'New title'])
        ->and($revision->payload['featured'])->toBeTrue()
        ->and($revision->payload['slugs'])->toBe(['sv' => 'ny-titel']) // normaliserad, tom slug bortfiltrerad
        ->and($revision->reviewed_at)->toBeNull()
        ->and($revision->applied_at)->toBeNull();

    $article->refresh();

    expect($article->titel['sv'])->toBe('Gammal titel')
        ->and($article->featured)->toBeFalse()
        ->and($article->status)->toBe(ContentStatus::Draft)
        ->and($article->slugFor('sv'))->toBe('gammal-titel');
});

it('keeps the revision pending for a business without auto_approve', function () {
    $business = Business::factory()->create(['namn' => 'Gamla namnet']);
    $owner = User::factory()->verksamhetsagare()->create();

    $revision = app(SubmitRevision::class)->handle($business, ['namn' => 'Nya namnet'], $owner);

    expect($revision->status)->toBe(RevisionStatus::Pending)
        ->and($business->fresh()->namn)->toBe('Gamla namnet');
});

it('approves immediately when the business has auto_approve', function () {
    $this->freezeSecond();
    $business = Business::factory()->create(['auto_approve' => true, 'namn' => 'Gamla namnet']);
    $owner = User::factory()->verksamhetsagare()->create();

    $revision = app(SubmitRevision::class)->handle($business, ['namn' => 'Nya namnet'], $owner);

    expect($revision->status)->toBe(RevisionStatus::Approved)
        ->and($revision->submitted_by)->toBe($owner->id)
        ->and($revision->reviewed_by)->toBeNull()
        ->and($revision->review_comment)->toBe('Godkänd automatiskt')
        ->and($revision->applied_at)->toEqual(now());

    $business->refresh();

    expect($business->namn)->toBe('Nya namnet')
        ->and($business->status)->toBe(ContentStatus::Published)
        ->and($business->published_at)->toEqual(now());
});

it('auto-approves content that belongs to a business with auto_approve, such as a location', function () {
    $location = BusinessLocation::factory()
        ->for(Business::factory()->state(['auto_approve' => true]))
        ->create(['adress' => 'Gamla gatan 1']);
    $owner = User::factory()->verksamhetsagare()->create();

    $revision = app(SubmitRevision::class)->handle($location, [
        'kommun_id' => $location->kommun_id,
        'adress' => 'Nya gatan 2',
    ], $owner);

    expect($revision->status)->toBe(RevisionStatus::Approved)
        ->and($location->fresh()->adress)->toBe('Nya gatan 2');
});

it('rejects an invalid payload before it reaches the queue', function (array $overrides, string $field) {
    $event = Event::factory()->create();
    $owner = User::factory()->verksamhetsagare()->create();
    $payload = [
        'kommun_id' => $event->kommun_id,
        'namn' => 'Pubquiz',
        'starts_at' => '2026-06-19 18:00:00',
        'ends_at' => '2026-06-19 20:00:00',
        'ticket_url' => 'https://example.com/biljetter',
        'beskrivning' => ['sv' => 'Frågesport', 'en' => 'Quiz'],
        ...$overrides,
    ];

    expect(fn () => app(SubmitRevision::class)->handle($event, $payload, $owner))
        ->toThrow(fn (ValidationException $e) => expect($e->errors())->toHaveKey($field));

    $this->assertDatabaseCount('revisions', 0);
})->with([
    'name missing' => [['namn' => ''], 'namn'],
    'end before start' => [['ends_at' => '2026-06-19 17:00:00'], 'ends_at'],
    'invalid ticket url' => [['ticket_url' => 'inte-en-url'], 'ticket_url'],
    'unknown kommun' => [['kommun_id' => 999999], 'kommun_id'],
    'unknown locale in description' => [['beskrivning' => ['de' => 'Quiz']], 'beskrivning'],
]);

it('rejects categories of another type than the content', function () {
    $event = Event::factory()->create();
    $businessCategory = Category::factory()->create();
    $owner = User::factory()->verksamhetsagare()->create();

    expect(fn () => app(SubmitRevision::class)->handle($event, [
        'kommun_id' => $event->kommun_id,
        'namn' => 'Pubquiz',
        'starts_at' => '2026-06-19 18:00:00',
        'categories' => [$businessCategory->id],
    ], $owner))->toThrow(fn (ValidationException $e) => expect($e->errors())->toHaveKey('categories.0'));

    $this->assertDatabaseCount('revisions', 0);
});

it('drops fields outside the form request, such as status and auto_approve', function () {
    $business = Business::factory()->create();
    $owner = User::factory()->verksamhetsagare()->create();

    $revision = app(SubmitRevision::class)->handle($business, [
        'namn' => 'Nytt namn',
        'status' => ContentStatus::Published->value,
        'auto_approve' => true,
        'managed_by' => 'self',
    ], $owner);

    expect($revision->payload)->toBe(['namn' => 'Nytt namn'])
        ->and($business->fresh()->auto_approve)->toBeFalse();
});
