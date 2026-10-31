<?php

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('redirects guests to the portal login', function () {
    $this->get('/portal')->assertRedirect('/portal/login');
});

it('forbids the editorial team', function (User $user) {
    $this->actingAs($user)->get('/portal')->assertForbidden();
})->with([
    'admin' => fn () => User::factory()->admin()->create(),
    'redaktör' => fn () => User::factory()->redaktor()->create(),
]);

it('sends an owner to their business', function () {
    $owner = User::factory()->verksamhetsagare()->create();
    $business = businessOwnedBy($owner);

    $this->actingAs($owner)->get('/portal')->assertRedirect("/portal/{$business->id}");
    $this->get("/portal/{$business->id}")->assertOk()->assertSee($business->namn);
});

it('sends a user without a business to "Ta över din verksamhet"', function () {
    $user = User::factory()->verksamhetsagare()->create();

    $this->actingAs($user)->get('/portal')->assertRedirect('/portal/ta-over');
    $this->get('/portal/ta-over')->assertOk()->assertSee('Ta över din verksamhet');
});

it('returns 404 for another owner\'s business, even by direct URL', function () {
    $owner = User::factory()->verksamhetsagare()->create();
    businessOwnedBy($owner);
    $other = Business::factory()->create();

    $this->actingAs($owner);

    $this->get("/portal/{$other->id}")->assertNotFound();
    $this->get("/portal/{$other->id}/offers")->assertNotFound();
    $this->get("/portal/{$other->id}/verksamhet")->assertNotFound();
});
