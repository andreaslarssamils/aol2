<?php

namespace App\Casts;

use App\Support\HtmlSanitizer;

/**
 * Rich text som HTML-fragment, sanerat med editorial-profilen. Ägarnas snävare profil
 * appliceras tidigare, i SubmitRevision.
 */
class SanitizedHtml extends SanitizedJson
{
    protected function profile(): string
    {
        return HtmlSanitizer::EDITORIAL;
    }
}
