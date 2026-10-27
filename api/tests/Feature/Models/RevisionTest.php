<?php

use App\Models\Article;
use App\Models\Category;
use App\Models\Event;
use App\Models\Revision;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('never deletes an approved revision', function () {
    $revision = Revision::factory()->approved()->create();

    expect(fn () => $revision->delete())->toThrow(LogicException::class);

    $this->assertModelExists($revision);
});

it('deletes revisions that were never approved', function (Revision $revision) {
    $revision->delete();

    $this->assertModelMissing($revision);
})->with([
    'pending' => fn () => Revision::factory()->create(),
    'rejected' => fn () => Revision::factory()->rejected()->create(),
]);

it('diffs the current row against the payload field by field', function () {
    $event = Event::factory()->create([
        'namn' => 'Pubquiz',
        'starts_at' => '2026-06-19 18:00:00',
        'all_day' => false,
        'beskrivning' => ['sv' => 'Text', 'en' => 'Text'],
    ]);
    $category = Category::factory()->event()->create(['namn' => ['sv' => 'Musik', 'en' => 'Music']]);
    $revision = Revision::factory()->for($event, 'revisionable')->create(['payload' => [
        'namn' => 'Pubquiz på tisdagar',
        'starts_at' => '2026-06-19 18:00', // samma tidpunkt, annat format
        'all_day' => '1',
        'beskrivning' => ['sv' => 'Ny text', 'en' => 'Text'],
        'slugs' => ['sv' => 'pubquiz-tisdagar'],
        'categories' => [$category->id],
    ]]);

    // Fältordningen följer EventRequest, språken config('app.locales') – inte jsonb:s nyckelordning.
    expect($revision->fresh()->diff())->toBe([
        ['field' => 'namn', 'current' => 'Pubquiz', 'proposed' => 'Pubquiz på tisdagar', 'changed' => true],
        ['field' => 'beskrivning.sv', 'current' => 'Text', 'proposed' => 'Ny text', 'changed' => true],
        ['field' => 'beskrivning.en', 'current' => 'Text', 'proposed' => 'Text', 'changed' => false],
        ['field' => 'all_day', 'current' => 'Nej', 'proposed' => 'Ja', 'changed' => true],
        ['field' => 'starts_at', 'current' => '2026-06-19 18:00', 'proposed' => '2026-06-19 18:00', 'changed' => false],
        ['field' => 'categories', 'current' => null, 'proposed' => 'Musik', 'changed' => true],
        ['field' => 'slug.sv', 'current' => 'pubquiz', 'proposed' => 'pubquiz-tisdagar', 'changed' => true],
    ]);
});

it('diffs against empty values when the content no longer exists', function () {
    $article = Article::factory()->create();
    $revision = Revision::factory()->for($article, 'revisionable')->create(['payload' => ['titel' => ['sv' => 'Ny titel']]]);
    $article->forceDelete();

    expect($revision->fresh()->diff())->toBe([
        ['field' => 'titel.sv', 'current' => null, 'proposed' => 'Ny titel', 'changed' => true],
    ]);
});
