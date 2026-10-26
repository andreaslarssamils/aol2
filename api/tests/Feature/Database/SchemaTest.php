<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('creates every v1 table from the schema', function () {
    $tables = [
        'lan', 'kommuner', 'sites',
        'users', 'roles', 'model_has_roles',
        'categories', 'business_category', 'event_category',
        'businesses', 'business_user', 'business_locations', 'opening_hours', 'lunch_menus',
        'events', 'articles', 'pages', 'revisions', 'media',
        'newsletter_subscribers', 'promotions', 'offers',
    ];

    foreach ($tables as $table) {
        expect(Schema::hasTable($table))->toBeTrue("Tabellen {$table} saknas");
    }
});

it('does not migrate the framtid tables in v1', function () {
    foreach (['framtid_teams', 'framtid_campaigns', 'framtid_audiences'] as $table) {
        expect(Schema::hasTable($table))->toBeFalse();
    }
});
