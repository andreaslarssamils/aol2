<?php

use App\Enums\BusinessUserRole;
use App\Enums\RevisionStatus;
use App\Models\Business;
use App\Models\Category;
use App\Models\Revision;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can be managed by several users with different roles', function () {
    $business = Business::factory()->create();
    $owner = User::factory()->create();
    $manager = User::factory()->create();

    $business->addUser($owner);
    $business->addUser($manager, BusinessUserRole::Manager);

    expect($business->users)->toHaveCount(2)
        ->and($business->users->firstWhere('id', $manager->id)->pivot->role)->toBe('manager')
        ->and($manager->businesses->first()->is($business))->toBeTrue();
});

it('stores revisions with the short morph alias', function () {
    $business = Business::factory()->create();
    $user = User::factory()->create();

    $revision = $business->revisions()->create([
        'submitted_by' => $user->id,
        'payload' => ['namn' => 'Nytt namn'],
    ]);

    expect($revision->revisionable_type)->toBe('business')
        ->and($revision->status)->toBe(RevisionStatus::Pending)
        ->and($business->latestPendingRevision->is($revision))->toBeTrue()
        ->and(Revision::pending()->count())->toBe(1);
});

it('attaches categories on the business, not the location', function () {
    $business = Business::factory()->create();
    $categories = Category::factory()->count(2)->create();

    $business->categories()->sync($categories);

    expect($business->categories)->toHaveCount(2)
        ->and($categories->first()->businesses->first()->is($business))->toBeTrue();
});
