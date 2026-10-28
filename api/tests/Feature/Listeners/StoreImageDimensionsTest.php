<?php

use App\Models\Article;
use App\Models\Business;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('stores the width and height of added images as custom properties', function () {
    Storage::fake('public');
    $article = Article::factory()->create();

    $media = $article->addMedia(UploadedFile::fake()->image('bild.jpg', 640, 480))->toMediaCollection(Business::MEDIA_IMAGES);

    expect($media->fresh()->getCustomProperty('width'))->toBe(640)
        ->and($media->fresh()->getCustomProperty('height'))->toBe(480);
});

it('leaves files that are not images alone', function () {
    Storage::fake('public');
    $article = Article::factory()->create();

    $media = $article->addMedia(UploadedFile::fake()->create('meny.pdf', 10, 'application/pdf'))->toMediaCollection(Business::MEDIA_IMAGES);

    expect($media->fresh()->hasCustomProperty('width'))->toBeFalse();
});
