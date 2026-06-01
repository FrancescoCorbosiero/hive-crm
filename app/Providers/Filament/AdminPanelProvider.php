<?php

namespace App\Providers\Filament;

use App\Domains\Calendar\Filament\CalendarPanelPlugin;
use App\Domains\Catalog\Filament\CatalogPanelPlugin;
use App\Domains\Contacts\Filament\ContactsPanelPlugin;
use App\Domains\Documents\Filament\DocumentsPanelPlugin;
use App\Domains\DomainNames\Filament\DomainNamesPanelPlugin;
use App\Domains\Finance\Filament\FinancePanelPlugin;
use App\Domains\Leads\Filament\LeadsPanelPlugin;
use App\Domains\Mail\Filament\MailPanelPlugin;
use App\Domains\Quotations\Filament\QuotationsPanelPlugin;
use App\Domains\Repositories\Filament\RepositoriesPanelPlugin;
use App\Domains\Scheduling\Filament\SchedulingPanelPlugin;
use App\Domains\Settings\Filament\SettingsPanelPlugin;
use App\Domains\Websites\Filament\WebsitesPanelPlugin;
use App\Filament\Pages\HomeDashboard;
use App\Http\Middleware\SetLocale;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\SpatieLaravelTranslatablePlugin;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Vite;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName(config('app.name'))
            ->colors([
                'primary' => Color::Amber,
            ])
            // The header hamburger needs an opt-in to actually collapse the
            // sidebar on desktop — without this, Filament still renders the
            // button but clicking it is a no-op.
            ->sidebarCollapsibleOnDesktop()
            ->plugins([
                SpatieLaravelTranslatablePlugin::make()
                    ->defaultLocales(config('app.supported_locales', ['it', 'en'])),

                // ── Domains ─────────────────────────────────────────
                // Each domain mounts its Filament classes through its own
                // panel plugin. Add new domains here.
                ContactsPanelPlugin::make(),
                WebsitesPanelPlugin::make(),
                DomainNamesPanelPlugin::make(),
                RepositoriesPanelPlugin::make(),
                CatalogPanelPlugin::make(),
                FinancePanelPlugin::make(),
                LeadsPanelPlugin::make(),
                CalendarPanelPlugin::make(),
                MailPanelPlugin::make(),
                DocumentsPanelPlugin::make(),
                QuotationsPanelPlugin::make(),
                SchedulingPanelPlugin::make(),
                SettingsPanelPlugin::make(),
            ])
            ->renderHook(
                PanelsRenderHook::USER_MENU_BEFORE,
                fn (): string => view('filament.locale-switcher')->render(),
            )
            // Inject the dashboard-extras CSS bundle so the custom dashboard
            // widgets can use Tailwind utilities outside Filament's
            // precompiled palette (sky/emerald/violet/… plus gradients).
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn (): string => app(Vite::class)('resources/css/dashboard-extras.css')->toHtml(),
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                HomeDashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                SetLocale::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
