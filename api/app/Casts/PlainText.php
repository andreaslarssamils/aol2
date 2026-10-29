<?php

namespace App\Casts;

use App\Support\HtmlSanitizer;

/**
 * Ren text: all markup strippas och entiteter avkodas, så "Fisk & chips" lagras som just det.
 * För fält som visas som text – frontend och Filament escapar dem själva.
 */
class PlainText extends SanitizedJson
{
    protected function profile(): string
    {
        return HtmlSanitizer::TEXT;
    }
}
