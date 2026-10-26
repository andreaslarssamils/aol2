<?php

use App\Models\Article;
use App\Models\Event;
use App\Models\Kommun;
use App\Models\Page;
use App\Models\Slug;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('gives content one canonical slug per locale', function () {
    $article = Article::factory()->create();
    $article->setSlug('sv', 'midsommar-i-ludvika');
    $article->setSlug('en', 'midsummer-in-ludvika');

    expect($article->slugFor('sv'))->toBe('midsommar-i-ludvika')
        ->and($article->slugFor('en'))->toBe('midsummer-in-ludvika')
        ->and(Article::whereCanonicalSlug('midsummer-in-ludvika', 'en', $article->kommun_id)->first()->is($article))->toBeTrue()
        ->and(Article::whereCanonicalSlug('midsummer-in-ludvika', 'sv', $article->kommun_id)->exists())->toBeFalse()
        ->and($article->slugs()->where('is_canonical', true)->count())->toBe(2);
});

it('keeps the old slug as a non-canonical redirect when renamed', function () {
    $event = Event::factory()->create();
    $event->setSlug('sv', 'pubquiz');
    $event->setSlug('sv', 'pubquiz-tisdagar');

    $old = Slug::lookup('pubquiz', 'sv', $event->kommun_id, Event::class)->first();

    expect($old->is_canonical)->toBeFalse()
        ->and($old->slugable->is($event))->toBeTrue()
        ->and(Event::whereCanonicalSlug('pubquiz', 'sv', $event->kommun_id)->exists())->toBeFalse()
        ->and($event->slugFor('sv'))->toBe('pubquiz-tisdagar');

    // Byte tillbaka befordrar den gamla raden i stället för att skapa en dubblett.
    $event->setSlug('sv', 'pubquiz');

    expect($event->slugFor('sv'))->toBe('pubquiz')
        ->and($event->slugs()->where('locale', 'sv')->count())->toBe(3); // factory-slug + två egna
});

it('appends a numeric suffix when the slug is taken by other content of the same type', function () {
    $kommun = Kommun::factory()->create();
    $first = Article::factory()->create(['kommun_id' => $kommun]);
    $second = Article::factory()->create(['kommun_id' => $kommun]);
    $third = Article::factory()->create(['kommun_id' => $kommun]);

    $first->setSlug('sv', 'nyheter');
    $second->setSlug('sv', 'nyheter');
    $third->setSlug('sv', 'nyheter');

    expect($first->slugFor('sv'))->toBe('nyheter')
        ->and($second->slugFor('sv'))->toBe('nyheter-2')
        ->and($third->slugFor('sv'))->toBe('nyheter-3')
        ->and(Article::whereCanonicalSlug('nyheter-2', 'sv', $kommun)->first()->is($second))->toBeTrue();

    // Samma slug i en annan kommun eller för en annan typ är ledig.
    expect(Article::factory()->create()->setSlug('sv', 'nyheter')->slug)->toBe('nyheter')
        ->and(Event::factory()->create(['kommun_id' => $kommun])->setSlug('sv', 'nyheter')->slug)->toBe('nyheter');
});

it('does not suffix content that already owns the slug, even as an old redirect', function () {
    $kommun = Kommun::factory()->create();
    $article = Article::factory()->create(['kommun_id' => $kommun]);
    $article->setSlug('sv', 'nyheter');
    $article->setSlug('sv', 'veckans-nyheter'); // "nyheter" blir icke-kanonisk men ägs fortfarande

    expect($article->setSlug('sv', 'nyheter')->slug)->toBe('nyheter')
        ->and($article->setSlug('sv', 'nyheter')->slug)->toBe('nyheter') // idempotent
        ->and($article->slugs()->where('locale', 'sv')->count())->toBe(3);
});

it('treats another item\'s old redirect slug as taken', function () {
    $kommun = Kommun::factory()->create();
    $renamed = Article::factory()->create(['kommun_id' => $kommun]);
    $renamed->setSlug('sv', 'nyheter');
    $renamed->setSlug('sv', 'veckans-nyheter'); // "nyheter" 301:ar nu till $renamed

    $other = Article::factory()->create(['kommun_id' => $kommun]);

    expect($other->setSlug('sv', 'nyheter')->slug)->toBe('nyheter-2')
        ->and(Slug::lookup('nyheter', 'sv', $kommun, Article::class)->first()->slugable->is($renamed))->toBeTrue();
});

it('suffixes national slugs too, where kommun_id is null', function () {
    $first = Page::factory()->create(['kommun_id' => null]);
    $second = Page::factory()->create(['kommun_id' => null]);
    $local = Page::factory()->create(['kommun_id' => Kommun::factory()]);

    expect($first->setSlug('sv', 'om-oss')->slug)->toBe('om-oss')
        ->and($second->setSlug('sv', 'om-oss')->slug)->toBe('om-oss-2')
        ->and($local->setSlug('sv', 'om-oss')->slug)->toBe('om-oss'); // egen kommun = ledig
});

it('keeps slugs on soft delete but removes them on force delete', function () {
    $softDeleted = Article::factory()->create();
    $forceDeleted = Article::factory()->create();

    $softDeleted->delete();
    $forceDeleted->forceDelete();

    expect(Slug::where('slugable_id', $softDeleted->id)->count())->toBe(1)
        ->and(Slug::where('slugable_id', $forceDeleted->id)->count())->toBe(0);
});
