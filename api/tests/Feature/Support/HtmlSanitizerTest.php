<?php

use App\Models\Site;
use App\Support\HtmlSanitizer;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Fasta värdar så att testerna inte beror på APP_URL eller sites-tabellen.
    $this->sanitizer = new HtmlSanitizer(mediaHosts: ['media.alltom.test'], ownHosts: ['alltomludvika.test']);
});

it('strips script, event handlers and iframes', function (string $profile) {
    $html = '<p onclick="steal()">Hej</p><script>alert(1)</script><iframe src="https://evil.test"></iframe><p onmouseover="x()">då</p>';

    expect($this->sanitizer->sanitize($html, $profile))->toBe('<p>Hej</p><p>då</p>');
})->with(['editorial' => HtmlSanitizer::EDITORIAL, 'owner' => HtmlSanitizer::OWNER]);

it('keeps the allowed tags and strips style, class and id', function () {
    $html = '<p class="lead" style="color:red">Ett <strong>fett</strong>, <em>kursivt</em>, <u>understruket</u> och <s>struket</s> ord.</p>'
        .'<h2 id="rubrik">Rubrik</h2><h3>Under</h3><h4>Under-under</h4>'
        .'<ul><li>Ett</li><li>Två</li></ul><ol><li>Första</li></ol>'
        .'<blockquote>Citat</blockquote><p>Rad<br>bryt</p>';

    expect($this->sanitizer->sanitize($html))->toBe(
        '<p>Ett <strong>fett</strong>, <em>kursivt</em>, <u>understruket</u> och <s>struket</s> ord.</p>'
        .'<h2>Rubrik</h2><h3>Under</h3><h4>Under-under</h4>'
        .'<ul><li>Ett</li><li>Två</li></ul><ol><li>Första</li></ol>'
        .'<blockquote>Citat</blockquote><p>Rad<br>bryt</p>',
    );
});

it('keeps the text of pasted wrappers but drops the content of style, svg and unknown elements', function () {
    $html = '<div><h1>Rubrik</h1> <span>text</span><style>p { color: red }</style><svg><text>svg</text></svg>'
        .'<table><tr><td>cell</td></tr></table><custom-el>okänt</custom-el></div>';

    expect($this->sanitizer->sanitize($html))->toBe('Rubrik textcell');
});

it('adds rel="noopener nofollow" to external links only', function () {
    $html = '<p><a href="https://example.com/sida" target="_blank">Extern</a> <a href="https://www.alltomludvika.test/evenemang">Egen</a> '
        .'<a href="/evenemang/quiz">Relativ</a> <a href="mailto:info@example.com">Mejl</a></p>';

    expect($this->sanitizer->sanitize($html))->toBe(
        '<p><a href="https://example.com/sida" rel="noopener nofollow">Extern</a> <a href="https://www.alltomludvika.test/evenemang">Egen</a> '
        .'<a href="/evenemang/quiz">Relativ</a> <a href="mailto:info@example.com">Mejl</a></p>',
    );
});

it('drops javascript: and data: hrefs but keeps the link text', function () {
    $html = '<p><a href="javascript:alert(1)">Klick</a> <a href="JaVaScRiPt:alert(1)">Blandat</a> '
        .'<a href="data:text/html;base64,PHNjcmlwdD4=">Data</a> <a href="https://example.com/?a=1&amp;b=2">Ok</a></p>';

    $sanitized = $this->sanitizer->sanitize($html);

    expect($sanitized)->toBe('<p>Klick Blandat Data <a href="https://example.com/?a=1&amp;b=2" rel="noopener nofollow">Ok</a></p>')
        ->and($sanitized)->not->toContain('javascript');
});

it('allows images from the media host for the editorial profile only', function () {
    $html = '<p><img src="https://media.alltom.test/1/bild.jpg" alt="Bild" class="w-full" width="100"><img src="https://evil.test/x.png">'
        .'<img src="data:image/png;base64,AAAA"><img src="/storage/1/bild.jpg"></p>';

    expect($this->sanitizer->sanitize($html, HtmlSanitizer::EDITORIAL))->toBe('<p><img src="https://media.alltom.test/1/bild.jpg" alt="Bild"></p>')
        ->and($this->sanitizer->sanitize($html, HtmlSanitizer::OWNER))->toBe('<p></p>');
});

it('keeps the text of h2 headings for owners but not the tag', function () {
    $html = '<h2>Rubrik</h2><h3>Under</h3>';

    expect($this->sanitizer->sanitize($html, HtmlSanitizer::OWNER))->toBe('Rubrik<h3>Under</h3>')
        ->and($this->sanitizer->sanitize($html, HtmlSanitizer::EDITORIAL))->toBe('<h2>Rubrik</h2><h3>Under</h3>');
});

it('sanitizes every string in nested values and leaves other values alone', function () {
    $value = [
        'sv' => '<p>Hej<script>x()</script></p>',
        'en' => null,
        'mon' => ['Köttbullar <b>&</b> mos', 7, true],
    ];

    expect($this->sanitizer->sanitizeValue($value, HtmlSanitizer::OWNER))->toBe([
        'sv' => '<p>Hej</p>',
        'en' => null,
        'mon' => ['Köttbullar &amp; mos', 7, true],
    ]);
});

it('returns null for null and an empty string for blank input', function () {
    expect($this->sanitizer->sanitize(null))->toBeNull()
        ->and($this->sanitizer->sanitize(''))->toBe('')
        ->and($this->sanitizer->sanitize("  \n"))->toBe('');
});

it('is idempotent, so re-saving sanitized content leaves it unchanged', function () {
    $html = '<p>Fisk &amp; skaldjur, "citat" och 1 &lt; 2 @ <a href="https://example.com/?a=1&amp;b=2">länk</a></p>';

    $once = $this->sanitizer->sanitize($html);

    expect($this->sanitizer->sanitize($once))->toBe($once);
});

it('does not truncate long bodies', function () {
    $html = str_repeat('<p>'.str_repeat('x', 98).'</p>', 500); // 52 500 tecken, långt över Symfonys standardgräns

    expect($this->sanitizer->sanitize($html))->toBe($html);
});

it('rejects an unknown profile', function () {
    $this->sanitizer->sanitize('<p>Hej</p>', 'public');
})->throws(InvalidArgumentException::class);

it('treats the app url, the media disk and the sites\' domains as own hosts', function () {
    config()->set('app.url', 'https://api.alltom.test');
    config()->set('media-library.disk_name', 'public');
    config()->set('filesystems.disks.public.url', 'https://media.alltom.test/storage');
    Site::factory()->create(['domain' => 'alltomludvika.se']);

    $html = '<p><a href="https://alltomludvika.se/evenemang">Sajt</a><a href="https://api.alltom.test/x">App</a><a href="https://example.com/">Extern</a>'
        .'<img src="https://media.alltom.test/storage/1/bild.jpg"><img src="https://api.alltom.test/bild.jpg"><img src="https://example.com/bild.jpg"></p>';

    expect(app(HtmlSanitizer::class)->sanitize($html))->toBe(
        '<p><a href="https://alltomludvika.se/evenemang">Sajt</a><a href="https://api.alltom.test/x">App</a><a href="https://example.com/" rel="noopener nofollow">Extern</a>'
        .'<img src="https://media.alltom.test/storage/1/bild.jpg"><img src="https://api.alltom.test/bild.jpg"></p>',
    );
});

it('reduces markup to plain text with decoded entities in the text profile', function () {
    $html = '<p onclick="x()">Fisk &amp; chips <strong>deluxe</strong> för 10 &lt; 20 kr</p><script>alert(1)</script><style>p {}</style>'
        .'<h2>Rubrik</h2><a href="https://example.com">länk</a><img src="https://media.alltom.test/1/bild.jpg">';

    expect($this->sanitizer->sanitize($html, HtmlSanitizer::TEXT))->toBe('Fisk & chips deluxe för 10 < 20 krRubriklänk');
});

it('keeps plain text as typed in the text profile', function () {
    $text = 'Fisk & chips, 1 < 2 & "citat" @ 10+1=11';

    expect($this->sanitizer->sanitize($text, HtmlSanitizer::TEXT))->toBe($text);
});

it('cleans nested values to plain text', function () {
    $value = ['sv' => '<b>Fisk</b> &amp; chips', 'mon' => ['<i>Soppa</i>', null]];

    expect($this->sanitizer->sanitizeValue($value, HtmlSanitizer::TEXT))->toBe(['sv' => 'Fisk & chips', 'mon' => ['Soppa', null]]);
});
