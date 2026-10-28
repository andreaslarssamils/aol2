<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

it('writes a spec that documents every public API route', function () {
    $path = 'storage/framework/testing/openapi.json';

    $this->artisan('api:openapi', ['--path' => $path])->assertSuccessful();

    $spec = json_decode(File::get(base_path($path)), true, flags: JSON_THROW_ON_ERROR);
    File::delete(base_path($path));

    $routes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route): bool => str_starts_with($route->uri(), 'api/v1/'))
        ->map(fn ($route): string => '/'.Str::after($route->uri(), 'api/v1/'))
        ->unique()
        ->values()
        ->all();

    expect($spec['openapi'])->toStartWith('3.1')
        ->and(array_keys($spec['paths']))->toEqualCanonicalizing($routes)
        ->and($spec['paths']['/{site}/businesses/{slug}']['get']['responses'])->toHaveKeys(['200', '301', '304', '404'])
        ->and($spec['components']['parameters']['locale']['schema']['enum'])->toBe(array_keys(config('app.locales')));
});
