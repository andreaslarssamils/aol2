<?php

namespace App\Filament\Forms\Components;

use App\Models\Business;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;

/**
 * Ägaruppladdningar i portalen. Hamnar i collection "pending" och flyttas till "images" när
 * revisionen godkänns (ApproveRevision) – eller raderas vid avslag. Sparas av Filament direkt
 * på raden, men syns aldrig publikt förrän då.
 */
class PendingImagesUpload extends SpatieMediaLibraryFileUpload
{
    public static function make(?string $name = null): static
    {
        return parent::make($name ?? Business::MEDIA_PENDING);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Nya bilder');
        $this->helperText('Bilderna visas publikt först när redaktionen godkänt ändringen.');
        $this->collection(Business::MEDIA_PENDING);
        $this->multiple();
        $this->image();
        $this->reorderable();
        $this->appendFiles();
        $this->columnSpanFull();
    }
}
