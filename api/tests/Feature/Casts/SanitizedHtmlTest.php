<?php

use App\Casts\SanitizedHtml;
use App\Models\Article;
use App\Models\Business;
use App\Models\Event;
use App\Models\Kommun;
use App\Models\LunchMenu;
use App\Models\Offer;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('sanitizes each locale with the editorial profile whenever the column is written', function (string $model, string $column) {
    $record = $model::factory()->create([
        $column => [
            'sv' => '<h2>Rubrik</h2><p onclick="x()">Hej<script>alert(1)</script></p>',
            'en' => '<p>Hello</p><iframe src="https://evil.test"></iframe>',
        ],
    ]);

    expect($record->fresh()->{$column})->toEqual(['sv' => '<h2>Rubrik</h2><p>Hej</p>', 'en' => '<p>Hello</p>']);

    $record->update([$column => ['sv' => '<p style="color:red">Ny</p>', 'en' => '']]);

    expect($record->fresh()->{$column})->toEqual(['sv' => '<p>Ny</p>', 'en' => '']);
})->with([
    'articles.body' => [Article::class, 'body'],
    'pages.body' => [Page::class, 'body'],
    'businesses.beskrivning' => [Business::class, 'beskrivning'],
    'events.beskrivning' => [Event::class, 'beskrivning'],
    'offers.beskrivning' => [Offer::class, 'beskrivning'],
]);

it('keeps the row clean when unchanged content is written again', function () {
    $article = Article::factory()->create(['body' => ['sv' => '<p>Hej</p>', 'en' => '<p>Hello</p>']])->fresh();

    $article->body = $article->body;

    expect($article->isDirty('body'))->toBeFalse();
});

it('lists the rich text columns of a model', function () {
    expect(SanitizedHtml::columns(new Article))->toBe(['body'])
        ->and(SanitizedHtml::columns(new LunchMenu))->toBe([])
        ->and(SanitizedHtml::columns(new Kommun))->toBe([]);
});
