<?php

namespace App\Filament\Forms\Components;

use App\Models\Business;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;

/** Redaktionens bilder går direkt till collection "images". Pending-collection (ägaruppladdningar) rörs inte här. */
class ImagesUpload extends SpatieMediaLibraryFileUpload
{
    public static function make(?string $name = null): static
    {
        return parent::make($name ?? Business::MEDIA_IMAGES);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Bilder');
        $this->collection(Business::MEDIA_IMAGES);
        $this->multiple();
        $this->image();
        $this->reorderable();
        $this->appendFiles();
        $this->columnSpanFull();
    }
}
