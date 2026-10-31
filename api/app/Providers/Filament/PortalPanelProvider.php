<?php

namespace App\Providers\Filament;

use App\Filament\Portal\Auth\Register;
use App\Filament\Portal\Tenancy\EditVerksamhet;
use App\Filament\Portal\Tenancy\TaOverVerksamhet;
use App\Models\Business;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Verksamhetsägarnas portal: /portal/{business}/... med Business som tenant via business_user.
 * Allt innehåll ändras via revisioner (App\Actions\Revisions\SubmitRevision) – bara öppettider
 * och lunchmeny vid auto_approve skrivs direkt. Redaktionen använder /admin, inte den här panelen.
 */
class PortalPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id(User::PANEL_PORTAL)
            ->path('portal')
            ->login()
            ->registration(Register::class)
            ->passwordReset()
            ->tenant(Business::class, ownershipRelationship: 'business')
            ->tenantRegistration(TaOverVerksamhet::class)
            ->tenantProfile(EditVerksamhet::class)
            ->brandName('Allt om – portal')
            ->colors([
                'primary' => Color::Emerald,
            ])
            ->discoverResources(in: app_path('Filament/Portal/Resources'), for: 'App\Filament\Portal\Resources')
            ->discoverPages(in: app_path('Filament/Portal/Pages'), for: 'App\Filament\Portal\Pages')
            ->widgets([
                AccountWidget::class,
            ])
            ->navigationItems([
                NavigationItem::make('Verksamhet')
                    ->icon(Heroicon::OutlinedBuildingStorefront)
                    ->url(fn (): ?string => Filament::getTenantProfileUrl())
                    ->isActiveWhen(fn (): bool => request()->routeIs(EditVerksamhet::getRouteName()))
                    ->sort(5),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
