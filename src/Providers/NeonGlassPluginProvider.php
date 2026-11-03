<?php

namespace MuichiroDev\NeonGlass\Providers;

use Illuminate\Support\Facades\Lang;
use Illuminate\Support\ServiceProvider;
use MuichiroDev\NeonGlass\NeonGlassPlugin;

class NeonGlassPluginProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'neonglass');

        $this->publishes([
            __DIR__ . '/../../config/neonglass.php' => config_path('neonglass.php'),
        ], 'ng-config');

        $this->publishes([
            __DIR__ . '/../../css' => public_path('plugins/neonglass'),
            __DIR__ . '/../../js' => public_path('plugins/neonglass'),
            __DIR__ . '/../../fonts' => public_path('plugins/neonglass/fonts'),
        ], 'ng-assets');

        // Patch missing Pelican core translation keys (visible as raw keys in UI).
        // Key format shown in UI: server/user.permissions.versions_title
        $this->app->booted(function () {
            $this->patchMissingPelicanTranslations();
        });
    }

    protected function patchMissingPelicanTranslations(): void
    {
        $map = [
            'en' => [
                'permissions.versions_title' => 'Versions',
                'permissions.versions' => 'Allow the user to manage server versions.',
            ],
            'es' => [
                'permissions.versions_title' => 'Versiones',
                'permissions.versions' => 'Permitir gestionar las versiones del servidor.',
            ],
            'de' => [
                'permissions.versions_title' => 'Versionen',
                'permissions.versions' => 'Server-Versionen verwalten erlauben.',
            ],
            'fr' => [
                'permissions.versions_title' => 'Versions',
                'permissions.versions' => 'Autoriser la gestion des versions du serveur.',
            ],
            'pt_BR' => [
                'permissions.versions_title' => 'Versões',
                'permissions.versions' => 'Permitir gerenciar as versões do servidor.',
            ],
        ];

        foreach ($map as $locale => $lines) {
            // Group name used by Pelican for subuser permission UI
            try {
                Lang::addLines($lines, $locale, 'server/user');
            } catch (\Throwable) {
                // ignore if translator not ready
            }
        }
    }
}
