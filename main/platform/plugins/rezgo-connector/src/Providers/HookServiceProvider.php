<?php

namespace Botble\RezgoConnector\Providers;

use Botble\Base\Facades\DashboardMenu;
use Illuminate\Support\ServiceProvider;

class HookServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        DashboardMenu::beforeRetrieving(function (): void {
            DashboardMenu::make()
                ->registerItem([
                    'id'          => 'cms-plugins-rezgo-connector',
                    'priority'    => 120,
                    'name'        => 'plugins/rezgo-connector::rezgo.menu_name',
                    'icon'        => 'ti ti-plug-connected',
                    'url'         => fn () => route('rezgo-connector.settings'),
                    'permissions' => ['rezgo-connector.settings'],
                ]);
        });
    }
}
