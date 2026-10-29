<?php

namespace App\Support;

use App\Models\Site;
use Dom\HTMLDocument;
use InvalidArgumentException;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer as SymfonyHtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerAction;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Sanerar innehåll innan det sparas – aldrig vid rendering. Körs av casterna i App\Casts
 * (SanitizedHtml, PlainText) på allt som skrivs till kolumnerna och av SubmitRevision på
 * payloaden innan den hamnar i kön.
 *
 * Profiler:
 *  - editorial: p, br, strong, em, u, s, h2, h3, h4, ul, ol, li, blockquote, a och img.
 *    Redaktionens rich editor; img bara med absolut URL på en egen mediadomän. Resultatet är
 *    ett HTML-fragment som frontend renderar som det är.
 *  - owner: samma utan img och h2 – ägare laddar upp bilder via media, inte inline.
 *    Allt som inte kommer från redaktionen.
 *  - text: ren text. All markup strippas och entiteter avkodas, så "Fisk &amp; chips" lagras
 *    som "Fisk & chips". För fält som visas som text (ingress, lunchrätter) – den som renderar
 *    escapar själv.
 *
 * Allt annat strippas. Vanliga textbärande element utanför profilen (div, span, h1, b, table …)
 * tas bort men texten behålls; element vars innehåll inte är text (script, style, iframe, svg …)
 * och okända element tas bort med innehåll. Attribut utanför listan – style, class, id, on*,
 * data-* – strippas alltid. Länkar: href med http, https eller mailto, relativa tillåtna;
 * javascript:- och data:-URL:er strippas – länken blir då ren text och bilden tas bort. Länkar
 * till andra värdar än de egna får rel="noopener nofollow".
 */
class HtmlSanitizer
{
    public const string EDITORIAL = 'editorial';

    public const string OWNER = 'owner';

    public const string TEXT = 'text';

    public const string EXTERNAL_LINK_REL = 'noopener nofollow';

    /** @var list<string> */
    protected const array EDITORIAL_ELEMENTS = [
        'p', 'br', 'strong', 'em', 'u', 's', 'h2', 'h3', 'h4', 'ul', 'ol', 'li', 'blockquote', 'a', 'img',
    ];

    /** @var list<string> */
    protected const array OWNER_EXCLUDED_ELEMENTS = ['img', 'h2'];

    /** @var array<string, list<string>> Tillåtna attribut per element – övriga element har inga. */
    protected const array ATTRIBUTES = [
        'a' => ['href'],
        'img' => ['src', 'alt'],
    ];

    /**
     * Element som, liksom de i EDITORIAL_ELEMENTS, tas bort med texten kvar när profilen inte
     * tillåter dem – ofta inklistrat innehåll. Allt annat tas bort med innehåll.
     *
     * @var list<string>
     */
    protected const array TEXT_BEARING_ELEMENTS = [
        'div', 'span', 'section', 'article', 'aside', 'header', 'footer', 'main', 'nav', 'figure', 'figcaption',
        'h1', 'h5', 'h6', 'pre', 'code', 'b', 'i', 'small', 'sub', 'sup', 'mark', 'ins', 'del', 'abbr',
        'cite', 'q', 'dfn', 'time', 'kbd', 'samp', 'var', 'address', 'font', 'center', 'label', 'details',
        'summary', 'table', 'caption', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td', 'dl', 'dt', 'dd',
    ];

    /** @var array<string, SymfonyHtmlSanitizer> */
    protected array $sanitizers = [];

    /**
     * @param  list<string>|null  $mediaHosts  Värdar img får hämtas från. null = APP_URL samt mediabibliotekets och Filaments disk.
     * @param  list<string>|null  $ownHosts  Länkmål som inte räknas som externa. null = APP_URL och sajternas domäner.
     */
    public function __construct(
        protected ?array $mediaHosts = null,
        protected ?array $ownHosts = null,
    ) {}

    /** null förblir null; tom eller blank sträng blir tom. */
    public function sanitize(?string $html, string $profile = self::EDITORIAL): ?string
    {
        if ($html === null) {
            return null;
        }

        $sanitized = $this->sanitizer($profile)->sanitize($html);

        return match (true) {
            $sanitized === '' => '',
            $profile === self::TEXT => html_entity_decode($sanitized, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            default => $this->postProcess($sanitized),
        };
    }

    /**
     * Sanerar varje sträng i ett värde: en locale-map ({"sv": …, "en": …}), lunchmenyns
     * dagslistor eller en enskild sträng. Nycklar och andra värden lämnas orörda.
     */
    public function sanitizeValue(mixed $value, string $profile = self::EDITORIAL): mixed
    {
        return match (true) {
            is_string($value) => $this->sanitize($value, $profile),
            is_array($value) => array_map(fn (mixed $item): mixed => $this->sanitizeValue($item, $profile), $value),
            default => $value,
        };
    }

    protected function sanitizer(string $profile): SymfonyHtmlSanitizer
    {
        return $this->sanitizers[$profile] ??= new SymfonyHtmlSanitizer($this->config($profile));
    }

    protected function config(string $profile): HtmlSanitizerConfig
    {
        $elements = match ($profile) {
            self::EDITORIAL => self::EDITORIAL_ELEMENTS,
            self::OWNER => array_values(array_diff(self::EDITORIAL_ELEMENTS, self::OWNER_EXCLUDED_ELEMENTS)),
            self::TEXT => [],
            default => throw new InvalidArgumentException("Okänd saneringsprofil \"{$profile}\"."),
        };

        // Symfonys standardgräns (20 000 tecken) skulle tyst kapa långa artiklar; storleken begränsas av requesten.
        $config = (new HtmlSanitizerConfig)
            ->defaultAction(HtmlSanitizerAction::Drop)
            ->withMaxInputLength(-1)
            ->allowLinkSchemes(['http', 'https', 'mailto'])
            ->allowRelativeLinks()
            ->allowMediaSchemes(['http', 'https'])
            ->allowMediaHosts($this->mediaHosts())
            ->allowRelativeMedias(false);

        foreach ($elements as $element) {
            $config = $config->allowElement($element, self::ATTRIBUTES[$element] ?? []);
        }

        foreach (array_diff([...self::EDITORIAL_ELEMENTS, ...self::TEXT_BEARING_ELEMENTS], $elements) as $element) {
            $config = $config->blockElement($element);
        }

        return $config;
    }

    /**
     * Efterbehandling i DOM: externa länkar får rel="noopener nofollow", länkar vars href
     * strippades blir ren text och bilder utan giltig src tas bort. Fragmentet går genom samma
     * HTML-parser som saneraren, så resultatet serialiseras alltid likadant (<br>, minimala entiteter).
     */
    protected function postProcess(string $html): string
    {
        $document = HTMLDocument::createFromString("<!DOCTYPE html><body>{$html}", LIBXML_NOERROR);

        foreach ($document->querySelectorAll('a') as $anchor) {
            if (! $anchor->hasAttribute('href')) {
                $anchor->replaceWith(...iterator_to_array($anchor->childNodes));
            } elseif ($this->isExternal($anchor->getAttribute('href'))) {
                $anchor->setAttribute('rel', self::EXTERNAL_LINK_REL);
            }
        }

        foreach ($document->querySelectorAll('img:not([src])') as $image) {
            $image->remove();
        }

        return $document->body->innerHTML;
    }

    /** Länk till en annan värd än de egna. Relativa länkar och mailto har ingen värd och är aldrig externa. */
    protected function isExternal(string $href): bool
    {
        $host = parse_url($href, PHP_URL_HOST);

        return is_string($host) && ! $this->isOwnHost(strtolower($host));
    }

    protected function isOwnHost(string $host): bool
    {
        foreach ($this->ownHosts() as $ownHost) {
            if ($host === $ownHost || str_ends_with($host, ".{$ownHost}")) {
                return true;
            }
        }

        return false;
    }

    /** @return list<string> */
    protected function mediaHosts(): array
    {
        return $this->mediaHosts ??= $this->hosts([
            config('app.url'),
            config('filesystems.disks.'.config('media-library.disk_name').'.url'),
            config('filesystems.disks.'.config('filament.default_filesystem_disk').'.url'),
        ]);
    }

    /** @return list<string> */
    protected function ownHosts(): array
    {
        return $this->ownHosts ??= $this->hosts([
            config('app.url'),
            ...Site::query()->whereNotNull('domain')->pluck('domain')->all(),
        ]);
    }

    /**
     * Värdnamn ur URL:er eller rena domäner, utan dubbletter och tomma värden.
     *
     * @param  list<mixed>  $urls
     * @return list<string>
     */
    protected function hosts(array $urls): array
    {
        $hosts = [];

        foreach ($urls as $url) {
            if (! is_string($url) || $url === '') {
                continue;
            }

            $host = parse_url(str_contains($url, '//') ? $url : "//{$url}", PHP_URL_HOST);

            if (is_string($host) && $host !== '') {
                $hosts[] = strtolower($host);
            }
        }

        return array_values(array_unique($hosts));
    }
}
