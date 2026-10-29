<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\RichEditor;

/**
 * Redaktionens rich editor. Inline-bilder laddas upp till den publika disken under inline/,
 * så att de får en URL på mediadomänen som HtmlSanitizer tillåter. En privat disk skulle ge
 * tidsbegränsade URL:er som slutar fungera i det sparade innehållet.
 */
class RichText extends RichEditor
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->fileAttachmentsDisk('public');
        $this->fileAttachmentsDirectory('inline');
    }
}
