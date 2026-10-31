<?php

use App\Enums\BusinessUserRole;
use App\Models\Business;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
 // ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Fälten en revision får föreslå för modellen: alla fillable utom dem som bara redaktionen sätter.
 *
 * @return list<string>
 */
function revisionPayloadFields(Model $model): array
{
    return array_values(array_diff($model->getFillable(), ['status', 'published_at', 'auto_approve', 'managed_by']));
}

/**
 * Loggar in i portalen som $user med $business som aktuell tenant – det Livewire-tester av
 * portalens sidor behöver (HTTP-anrop får tenant via IdentifyTenant-middlewaret i stället).
 * Skapa testdata innan anropet: med tenant satt kopplar Filament nya Offer/BusinessLocation-rader
 * till den aktuella verksamheten.
 */
function actingAsPortalUser(User $user, Business $business): void
{
    test()->actingAs($user);

    Filament::setCurrentPanel(User::PANEL_PORTAL);
    Filament::bootCurrentPanel();
    Filament::setTenant($business, isQuiet: true);
}

/** Verksamhet med $user som ägare (eller annan roll) i business_user. */
function businessOwnedBy(User $user, BusinessUserRole $role = BusinessUserRole::Owner, array $attributes = []): Business
{
    $business = Business::factory()->create($attributes);
    $business->addUser($user, $role);

    return $business;
}
