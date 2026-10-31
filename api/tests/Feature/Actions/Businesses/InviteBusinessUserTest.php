<?php

use App\Actions\Businesses\InviteBusinessUser;
use App\Enums\BusinessUserRole;
use App\Models\Business;
use App\Models\User;
use App\Notifications\BusinessInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

it('creates a missing user with the verksamhetsägare role and sends a password link', function () {
    Notification::fake();
    $inviter = User::factory()->verksamhetsagare()->create();
    $business = businessOwnedBy($inviter);

    $user = app(InviteBusinessUser::class)->handle($business, $inviter, ' Ny@Example.com ', null, BusinessUserRole::Manager);

    expect($user->email)->toBe('ny@example.com')
        ->and($user->name)->toBe('ny')
        ->and($user->hasRole(User::ROLE_VERKSAMHETSAGARE))->toBeTrue()
        ->and($user->businessRole($business))->toBe(BusinessUserRole::Manager);

    $this->assertDatabaseHas('password_reset_tokens', ['email' => 'ny@example.com']);

    Notification::assertSentTo($user, BusinessInvitation::class, fn (BusinessInvitation $notification): bool => $notification->isNewUser
        && $notification->inviter->is($inviter)
        && str_contains($notification->url, '/portal/password-reset/reset'));
});

it('attaches an existing user and links straight into the business', function () {
    Notification::fake();
    $inviter = User::factory()->verksamhetsagare()->create();
    $business = businessOwnedBy($inviter);
    $existing = User::factory()->create(['email' => 'finns@example.com', 'name' => 'Finns Redan']);

    $user = app(InviteBusinessUser::class)->handle($business, $inviter, 'finns@example.com', 'Annat namn', BusinessUserRole::Owner);

    expect($user->is($existing))->toBeTrue()
        ->and($user->name)->toBe('Finns Redan')
        ->and($user->businessRole($business))->toBe(BusinessUserRole::Owner)
        ->and(User::query()->where('email', 'finns@example.com')->count())->toBe(1);

    Notification::assertSentTo($existing, BusinessInvitation::class, fn (BusinessInvitation $notification): bool => ! $notification->isNewUser
        && $notification->url === "http://localhost:8000/portal/{$business->id}");
});

it('keeps the existing role when the user is already a member', function () {
    Notification::fake();
    $inviter = User::factory()->verksamhetsagare()->create();
    $business = businessOwnedBy($inviter);
    $member = User::factory()->create();
    $business->addUser($member, BusinessUserRole::Owner);

    app(InviteBusinessUser::class)->handle($business, $inviter, $member->email, null, BusinessUserRole::Manager);

    expect($member->businessRole($business))->toBe(BusinessUserRole::Owner)
        ->and($business->users()->count())->toBe(2);
});

it('does not give the editorial team the owner role', function () {
    Notification::fake();
    $inviter = User::factory()->verksamhetsagare()->create();
    $business = businessOwnedBy($inviter);
    $redaktor = User::factory()->redaktor()->create();

    app(InviteBusinessUser::class)->handle(Business::find($business->id), $inviter, $redaktor->email, null, BusinessUserRole::Manager);

    expect($redaktor->fresh()->hasRole(User::ROLE_VERKSAMHETSAGARE))->toBeFalse()
        ->and($redaktor->businessRole($business))->toBe(BusinessUserRole::Manager);
});
