<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Nwidart\Modules\Facades\Module;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $panel = $panel
            ->default()
            ->id('supervisor')
            ->path('supervisor')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ]);

        $panel = $this->discoverModuleResources($panel);

        return $panel
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

    /**
     * Adds Filament resource/page/widget discovery for every enabled module that
     * ships its own `app/Filament/{Resources,Pages,Widgets}` directory, on top of
     * the legacy `app/Filament` discovery above. Additive only — does not change
     * discovery/registration order for existing `App\Filament\*` classes.
     *
     * `is_dir()` guards are required: Filament throws on `discoverResources()`
     * for a path that does not exist, it does not silently skip it.
     */
    private function discoverModuleResources(Panel $panel): Panel
    {
        foreach (Module::allEnabled() as $module) {
            $resourcesPath = $module->getPath().'/app/Filament/Resources';
            $pagesPath = $module->getPath().'/app/Filament/Pages';
            $widgetsPath = $module->getPath().'/app/Filament/Widgets';
            $namespace = 'Modules\\'.$module->getStudlyName().'\Filament';

            if (is_dir($resourcesPath)) {
                $panel = $panel->discoverResources(in: $resourcesPath, for: $namespace.'\Resources');
            }

            if (is_dir($pagesPath)) {
                $panel = $panel->discoverPages(in: $pagesPath, for: $namespace.'\Pages');
            }

            if (is_dir($widgetsPath)) {
                $panel = $panel->discoverWidgets(in: $widgetsPath, for: $namespace.'\Widgets');
            }
        }

        return $panel;
    }
}
