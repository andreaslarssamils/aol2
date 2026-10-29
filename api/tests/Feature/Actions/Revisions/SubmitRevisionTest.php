<?php

use App\Actions\Revisions\ApproveRevision;
use App\Actions\Revisions\SubmitRevision;
use App\Enums\ContentStatus;
use App\Enums\RevisionStatus;
use App\Models\Article;
use App\Models\Business;
use App\Models\BusinessLocation;
use App\Models\Category;
use App\Models\Event;
use App\Models\Kommun;
use App\Models\LunchMenu;
use App\Models\Offer;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
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

it('strips ownership links from a revision on an existing row, so an owner cannot move a location to another business', function (bool $belongsToBusiness) {
    $owner = User::factory()->verksamhetsagare()->create();
    $location = BusinessLocation::factory()->create(['adress' => 'Gamla gatan 1']);
    $originalBusinessId = $location->business_id;
    $otherBusiness = Business::factory()->create();

    if ($belongsToBusiness) {
        $location->business->addUser($owner);
    }

    $revision = app(SubmitRevision::class)->handle($location, [
        'business_id' => $otherBusiness->id,
        'kommun_id' => $location->kommun_id,
        'adress' => 'Nya gatan 2',
    ], $owner);

    expect($revision->payload)->toEqual(['kommun_id' => $location->kommun_id, 'adress' => 'Nya gatan 2']);

    app(ApproveRevision::class)->handle($revision, User::factory()->redaktor()->create());

    expect($location->fresh()->business_id)->toBe($originalBusinessId)
        ->and($location->fresh()->adress)->toBe('Nya gatan 2');
})->with([
    'owner of the business' => true,
    'user without any link to the business' => false,
]);

it('forbids owners to create content under a business they do not belong to', function () {
    $owner = User::factory()->verksamhetsagare()->create();
    Business::factory()->create()->addUser($owner);
    $otherBusiness = Business::factory()->create();

    expect(fn () => app(SubmitRevision::class)->handle(new BusinessLocation, [
        'business_id' => $otherBusiness->id,
        'kommun_id' => Kommun::factory()->create()->id,
        'adress' => 'Storgatan 1',
    ], $owner))->toThrow(AuthorizationException::class);

    $this->assertDatabaseCount('revisions', 0);
    $this->assertDatabaseCount('business_locations', 0);
});

it('forbids owners to create content under a location of a business they do not belong to', function () {
    $owner = User::factory()->verksamhetsagare()->create();
    Business::factory()->create()->addUser($owner);
    $othersLocation = BusinessLocation::factory()->create();

    expect(fn () => app(SubmitRevision::class)->handle(new LunchMenu, [
        'business_location_id' => $othersLocation->id,
        'iso_year' => 2026,
        'week' => 44,
        'menu' => ['mon' => ['Köttbullar']],
    ], $owner))->toThrow(AuthorizationException::class);

    $this->assertDatabaseCount('revisions', 0);
    $this->assertDatabaseCount('lunch_menus', 0);
});

it('creates new content under the owner\'s own business as a draft that the approval publishes', function () {
    $owner = User::factory()->verksamhetsagare()->create();
    $business = Business::factory()->create();
    $business->addUser($owner);

    $revision = app(SubmitRevision::class)->handle(new Offer, [
        'business_id' => $business->id,
        'titel' => ['sv' => 'Sommarrabatt', 'en' => 'Summer discount'],
        'beskrivning' => ['sv' => '10 % på allt.', 'en' => '10% off everything.'],
    ], $owner);

    $offer = $revision->revisionable;

    expect($revision->status)->toBe(RevisionStatus::Pending)
        ->and($offer)->toBeInstanceOf(Offer::class)
        ->and($offer->business_id)->toBe($business->id)
        ->and($offer->titel['sv'])->toBe('Sommarrabatt')
        ->and($offer->status)->toBe(ContentStatus::Draft);

    app(ApproveRevision::class)->handle($revision, User::factory()->redaktor()->create());

    expect($offer->fresh()->status)->toBe(ContentStatus::Published);
});

it('lets the editorial team move content between businesses and create it under any business', function (User $editor) {
    $location = BusinessLocation::factory()->create();
    $otherBusiness = Business::factory()->create();

    $moved = app(SubmitRevision::class)->handle($location, [
        'business_id' => $otherBusiness->id,
        'kommun_id' => $location->kommun_id,
    ], $editor);
    $created = app(SubmitRevision::class)->handle(new BusinessLocation, [
        'business_id' => $otherBusiness->id,
        'kommun_id' => Kommun::factory()->create()->id,
        'adress' => 'Storgatan 1',
    ], $editor);

    expect($moved->payload['business_id'])->toBe($otherBusiness->id)
        ->and($created->revisionable->business_id)->toBe($otherBusiness->id);
})->with([
    'admin' => fn () => User::factory()->admin()->create(),
    'redaktör' => fn () => User::factory()->redaktor()->create(),
]);

it('keeps a new location out of the published scope until its revision is approved', function () {
    $owner = User::factory()->verksamhetsagare()->create();
    $business = Business::factory()->published()->create();
    $business->addUser($owner);

    $revision = app(SubmitRevision::class)->handle(new BusinessLocation, [
        'business_id' => $business->id,
        'kommun_id' => Kommun::factory()->create()->id,
        'adress' => 'Storgatan 1',
    ], $owner);

    $location = $revision->revisionable;

    expect($location->status)->toBe(ContentStatus::Draft)
        ->and($location->published_at)->toBeNull()
        ->and(BusinessLocation::published()->whereKey($location)->exists())->toBeFalse()
        ->and(BusinessLocation::inCatalog()->whereKey($location)->exists())->toBeFalse();

    $this->freezeSecond();
    app(ApproveRevision::class)->handle($revision, User::factory()->redaktor()->create());

    $location->refresh();

    expect($location->status)->toBe(ContentStatus::Published)
        ->and($location->published_at)->toEqual(now())
        ->and(BusinessLocation::published()->whereKey($location)->exists())->toBeTrue()
        ->and(BusinessLocation::inCatalog()->whereKey($location)->exists())->toBeTrue();
});

it('sanitizes rich text in the payload with the owner profile before it reaches the queue', function () {
    $business = Business::factory()->create();
    $owner = User::factory()->verksamhetsagare()->create();
    $image = Storage::disk(config('media-library.disk_name'))->url('1/bild.jpg');

    $revision = app(SubmitRevision::class)->handle($business, [
        'namn' => 'Bageri & Café',
        'beskrivning' => [
            'sv' => "<h2>Rubrik</h2><p onclick=\"x()\">Hej <a href=\"https://example.com/sida\">där</a></p><img src=\"{$image}\"><script>alert(1)</script>",
            'en' => '<p>Hello</p>',
        ],
    ], $owner);

    expect($revision->payload['beskrivning'])->toBe([
        'sv' => 'Rubrik<p>Hej <a href="https://example.com/sida" rel="noopener nofollow">där</a></p>',
        'en' => '<p>Hello</p>',
    ])->and($revision->payload['namn'])->toBe('Bageri & Café'); // ren text saneras inte – den escapas vid rendering
});

it('lets the editorial team keep inline images and h2 headings', function () {
    $article = Article::factory()->create();
    $editor = User::factory()->redaktor()->create();
    $image = Storage::disk(config('media-library.disk_name'))->url('1/bild.jpg');

    $revision = app(SubmitRevision::class)->handle($article, [
        'kommun_id' => $article->kommun_id,
        'titel' => ['sv' => 'Titel', 'en' => 'Title'],
        'body' => [
            'sv' => "<h2>Rubrik</h2><p>Hej</p><img src=\"{$image}\" alt=\"Bild\"><img src=\"https://evil.test/x.png\"><script>alert(1)</script>",
            'en' => '<p>Hello</p>',
        ],
    ], $editor);

    expect($revision->payload['body']['sv'])->toBe("<h2>Rubrik</h2><p>Hej</p><img src=\"{$image}\" alt=\"Bild\">");
});

it('validates the sanitized value, so stripped markup does not count toward length limits', function () {
    $owner = User::factory()->verksamhetsagare()->create();
    $location = BusinessLocation::factory()->create();
    $location->business->addUser($owner);
    $dish = str_repeat('a', 250);

    $revision = app(SubmitRevision::class)->handle(new LunchMenu, [
        'business_location_id' => $location->id,
        'iso_year' => 2026,
        'week' => 44,
        'menu' => ['mon' => [$dish.'<script>alert(1)</script>']],
    ], $owner);

    expect($revision->payload['menu'])->toBe(['mon' => [$dish]])
        ->and($revision->revisionable->menu)->toBe(['mon' => [$dish]]);
});
