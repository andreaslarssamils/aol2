<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('creates every v4 table from the schema', function () {
    $tables = [
        'lan', 'kommuner', 'sites', 'site_kommun',
        'users', 'roles', 'model_has_roles', 'slugs',
        'categories', 'business_category', 'event_category',
        'businesses', 'business_user', 'business_claims', 'business_locations',
        'opening_hours', 'opening_hour_exceptions', 'lunch_menus',
        'events', 'articles', 'pages', 'revisions', 'media',
        'newsletter_subscribers', 'promotions', 'offers',
    ];

    foreach ($tables as $table) {
        expect(Schema::hasTable($table))->toBeTrue("Tabellen {$table} saknas");
    }
});

it('keeps the slug column out of the content tables', function () {
    foreach (['articles', 'events', 'pages', 'business_locations'] as $table) {
        expect(Schema::hasColumn($table, 'slug'))->toBeFalse("{$table}.slug ska inte finnas – URL:en bor i slugs");
    }

    expect(Schema::hasColumn('sites', 'kommun_id'))->toBeFalse()
        ->and(Schema::hasColumns('sites', ['slug', 'primary_kommun_id']))->toBeTrue();
});

it('does not migrate the framtid tables in v1', function () {
    foreach (['framtid_teams', 'framtid_campaigns', 'framtid_audiences'] as $table) {
        expect(Schema::hasTable($table))->toBeFalse();
    }
});
