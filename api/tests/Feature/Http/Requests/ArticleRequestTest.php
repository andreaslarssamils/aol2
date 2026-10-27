<?php

use App\Actions\Revisions\SubmitRevision;
use App\Models\Article;
use App\Models\Kommun;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('accepts every proposable field of an article', function () {
    $article = Article::factory()->create();
    $payload = [
        'kommun_id' => Kommun::factory()->create()->id,
        'author_id' => User::factory()->create()->id,
        'author_name' => 'Anna Andersson',
        'titel' => ['sv' => 'Midsommar i Ludvika', 'en' => 'Midsummer in Ludvika'],
        'ingress' => ['sv' => 'Så firas midsommar.', 'en' => 'How midsummer is celebrated.'],
        'body' => ['sv' => '<p>Brödtext</p>', 'en' => '<p>Body</p>'],
        'featured' => true,
    ];

    $revision = app(SubmitRevision::class)->handle($article, $payload, User::factory()->verksamhetsagare()->create());

    expect(array_keys($payload))->toEqualCanonicalizing(revisionPayloadFields($article))
        ->and($revision->payload)->toEqual($payload);
});
