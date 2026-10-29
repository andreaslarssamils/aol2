<?php

use App\Casts\PlainText;
use App\Models\Article;
use App\Models\Kommun;
use App\Models\LunchMenu;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('strips all markup and decodes entities per locale whenever the column is written', function () {
    $article = Article::factory()->create([
        'ingress' => [
            'sv' => '<p onclick="x()">Fisk &amp; chips <script>alert(1)</script><b>deluxe</b></p>',
            'en' => 'Fish &amp; chips<iframe src="https://evil.test"></iframe>',
        ],
    ]);

    expect($article->fresh()->ingress)->toEqual(['sv' => 'Fisk & chips deluxe', 'en' => 'Fish & chips']);

    $article->update(['ingress' => ['sv' => '<em>Ny</em> &quot;ingress&quot;', 'en' => '']]);

    expect($article->fresh()->ingress)->toEqual(['sv' => 'Ny "ingress"', 'en' => '']);
});

it('keeps ampersands, angle brackets and quotes as typed', function () {
    $ingress = ['sv' => 'Fisk & chips för 10 < 20 kr, "billigt"', 'en' => 'Fish & chips'];

    $article = Article::factory()->create(['ingress' => $ingress]);

    expect($article->fresh()->ingress)->toEqual($ingress);
});

it('cleans every dish in a lunch menu', function () {
    $menu = LunchMenu::factory()->create([
        'menu' => ['mon' => ['Köttbullar<script>alert(1)</script>', '<b>Fisk</b> &amp; skaldjur'], 'tue' => []],
    ]);

    expect($menu->fresh()->menu)->toBe(['mon' => ['Köttbullar', 'Fisk & skaldjur'], 'tue' => []]);
});

it('lists the plain text columns of a model', function () {
    expect(PlainText::columns(new Article))->toBe(['ingress'])
        ->and(PlainText::columns(new LunchMenu))->toBe(['menu'])
        ->and(PlainText::columns(new Kommun))->toBe([]);
});
