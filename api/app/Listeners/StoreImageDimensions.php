<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAddedEvent;

/**
 * Sparar bredd och höjd på uppladdade bilder som custom properties, så att API:et kan ge
 * dimensioner (för layout utan hopp) utan att läsa filen vid varje svar.
 */
class StoreImageDimensions
{
    public function handle(MediaHasBeenAddedEvent $event): void
    {
        $media = $event->media;

        if (! str_starts_with((string) $media->mime_type, 'image/')) {
            return;
        }

        $contents = Storage::disk($media->disk)->get($media->getPathRelativeToRoot());
        $size = $contents === null ? false : @getimagesizefromstring($contents);

        if ($size === false) {
            return;
        }

        $media->setCustomProperty('width', $size[0])
            ->setCustomProperty('height', $size[1])
            ->save();
    }
}
