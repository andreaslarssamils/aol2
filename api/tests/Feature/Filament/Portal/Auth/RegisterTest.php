<?php

use App\Filament\Portal\Auth\Register;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('shows the registration page', function () {
    $this->get('/portal/register')->assertOk();
});

it('registers a user with the verksamhetsägare role and logs them in', function () {
    Filament::setCurrentPanel(User::PANEL_PORTAL);

    Livewire::test(Register::class)
        ->fillForm([
            'name' => 'Anna Andersson',
            'email' => 'anna@example.com',
            'password' => 'hemligt-losenord-123',
            'passwordConfirmation' => 'hemligt-losenord-123',
        ])
        ->call('register')
        ->assertHasNoFormErrors();

    $user = User::query()->where('email', 'anna@example.com')->sole();

    expect($user->hasRole(User::ROLE_VERKSAMHETSAGARE))->toBeTrue()
        ->and($user->isEditorial())->toBeFalse()
        ->and($user->canAccessPanel(Filament::getPanel(User::PANEL_PORTAL)))->toBeTrue();

    $this->assertAuthenticatedAs($user);
});
