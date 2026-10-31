<?php

use App\Enums\BusinessUserRole;
use App\Enums\ClaimStatus;
use App\Enums\ManagedBy;
use App\Filament\Resources\Claims\ClaimResource;
use App\Filament\Resources\Claims\Pages\ListClaims;
use App\Models\Business;
use App\Models\BusinessClaim;
use App\Models\User;
use App\Notifications\BusinessClaimDecided;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('lists pending claims for the editorial team, oldest first', function () {
    $this->actingAs(User::factory()->redaktor()->create());
    $older = BusinessClaim::factory()->create(['created_at' => now()->subDay()]);
    $newer = BusinessClaim::factory()->create(['created_at' => now()]);
    $approved = BusinessClaim::factory()->create(['status' => ClaimStatus::Approved]);

    $this->get(ClaimResource::getUrl('index'))->assertOk();

    Livewire::test(ListClaims::class)
        ->assertCanSeeTableRecords([$older, $newer], inOrder: true)
        ->assertCanNotSeeTableRecords([$approved])
        ->filterTable('status', ClaimStatus::Approved->value)
        ->assertCanSeeTableRecords([$approved])
        ->assertCanNotSeeTableRecords([$older, $newer]);
});

it('approves a claim, creates the owner row and mails the applicant', function () {
    Notification::fake();
    $redaktor = User::factory()->redaktor()->create();
    $this->actingAs($redaktor);
    $applicant = User::factory()->verksamhetsagare()->create();
    $business = Business::factory()->published()->create(['managed_by' => ManagedBy::Editorial]);
    $claim = BusinessClaim::factory()->for($business)->for($applicant)->create();

    Livewire::test(ListClaims::class)
        ->callAction(TestAction::make('approve')->table($claim))
        ->assertHasNoActionErrors()
        ->assertNotified('Anspråket är godkänt')
        ->assertCanNotSeeTableRecords([$claim]);

    $claim->refresh();

    expect($claim->status)->toBe(ClaimStatus::Approved)
        ->and($claim->handled_by)->toBe($redaktor->id)
        ->and($applicant->businessRole($business))->toBe(BusinessUserRole::Owner)
        ->and($business->fresh()->managed_by)->toBe(ManagedBy::Self);

    Notification::assertSentTo($applicant, BusinessClaimDecided::class, fn (BusinessClaimDecided $notification): bool => $notification->claim->is($claim)
        && $notification->claim->status === ClaimStatus::Approved);
});

it('rejects a claim without touching the business and mails the applicant', function () {
    Notification::fake();
    $redaktor = User::factory()->redaktor()->create();
    $this->actingAs($redaktor);
    $applicant = User::factory()->verksamhetsagare()->create();
    $business = Business::factory()->published()->create(['managed_by' => ManagedBy::Editorial]);
    $claim = BusinessClaim::factory()->for($business)->for($applicant)->create();

    Livewire::test(ListClaims::class)
        ->callAction(TestAction::make('reject')->table($claim))
        ->assertHasNoActionErrors()
        ->assertNotified('Anspråket är avslaget');

    $claim->refresh();

    expect($claim->status)->toBe(ClaimStatus::Rejected)
        ->and($claim->handled_by)->toBe($redaktor->id)
        ->and($applicant->isMemberOf($business))->toBeFalse()
        ->and($business->fresh()->managed_by)->toBe(ManagedBy::Editorial);

    Notification::assertSentTo($applicant, BusinessClaimDecided::class, fn (BusinessClaimDecided $notification): bool => $notification->claim->status === ClaimStatus::Rejected);
});

it('forbids users without an editorial role', function (User $user) {
    $this->actingAs($user)
        ->get(ClaimResource::getUrl('index'))
        ->assertForbidden();
})->with([
    'no role' => fn () => User::factory()->create(),
    'verksamhetsägare' => fn () => User::factory()->verksamhetsagare()->create(),
]);
