<?php

namespace MuichiroDev\NeonGlass;

use App\Contracts\Plugins\HasPluginSettings;
use App\Traits\EnvironmentWriterTrait;
use Filament\Contracts\Plugin;
use Filament\Enums\ThemeMode;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Panel;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\File;
use MuichiroDev\NeonGlass\Filament\App\Pages\ServerDashboard;
use MuichiroDev\NeonGlass\Http\Middleware\DashboardHome;
use MuichiroDev\NeonGlass\Settings\Studio;
use MuichiroDev\NeonGlass\Settings\StudioCss;
use MuichiroDev\NeonGlass\Settings\StudioForm;
use MuichiroDev\NeonGlass\Settings\PresetStore;
use Filament\View\PanelsRenderHook;

class NeonGlassPlugin implements Plugin, HasPluginSettings
{
    use EnvironmentWriterTrait;

    /**
     * @var array<string, array{env: string, type: string, default: mixed, values?: string[]}>
     */
    public const CORE_SETTINGS = [
        'enabled' => [
            'env' => 'NEONGLASS_ENABLED',
            'type' => 'bool',
            'default' => true,
        ],
        'theme_mode' => [
            'env' => 'NEONGLASS_THEME_MODE',
            'type' => 'enum',
            'default' => 'dark',
            'values' => ['dark', 'light', 'system'],
        ],
        'lock_mode' => [
            'env' => 'NEONGLASS_LOCK_MODE',
            'type' => 'bool',
            'default' => false,
        ],
        'panel_colors' => [
            'env' => 'NEONGLASS_PANEL_COLORS',
            'type' => 'bool',
            'default' => true,
        ],
        'accent_hue' => [
            'env' => 'NEONGLASS_ACCENT_HUE',
            'type' => 'enum',
            'default' => 'cyan',
            'values' => ['cyan', 'violet', 'rose', 'teal', 'amber', 'emerald', 'indigo', 'sky', 'fuchsia', 'orange', 'white'],
        ],
        'primary' => [
            'env' => 'NEONGLASS_PRIMARY',
            'type' => 'color',
            'default' => '',
        ],
        'secondary' => [
            'env' => 'NEONGLASS_SECONDARY',
            'type' => 'color',
            'default' => '',
        ],
        'success' => [
            'env' => 'NEONGLASS_SUCCESS',
            'type' => 'color',
            'default' => '',
        ],
        'warning' => [
            'env' => 'NEONGLASS_WARNING',
            'type' => 'color',
            'default' => '',
        ],
        'danger' => [
            'env' => 'NEONGLASS_DANGER',
            'type' => 'color',
            'default' => '',
        ],
        'info' => [
            'env' => 'NEONGLASS_INFO',
            'type' => 'color',
            'default' => '',
        ],
        'background_style' => [
            'env' => 'NEONGLASS_BACKGROUND_STYLE',
            'type' => 'enum',
            'default' => 'aurora',
            'values' => ['aurora', 'grid', 'plain'],
        ],
        'surface_style' => [
            'env' => 'NEONGLASS_SURFACE_STYLE',
            'type' => 'enum',
            'default' => 'glass',
            'values' => ['glass', 'solid'],
        ],
        'radius' => [
            'env' => 'NEONGLASS_RADIUS',
            'type' => 'enum',
            'default' => 'rounded',
            'values' => ['sharp', 'rounded', 'soft'],
        ],
        'glass_intensity' => [
            'env' => 'NEONGLASS_GLASS_INTENSITY',
            'type' => 'enum',
            'default' => 'medium',
            'values' => ['low', 'medium', 'high'],
        ],
        'particles' => [
            'env' => 'NEONGLASS_PARTICLES',
            'type' => 'enum',
            'default' => 'low',
            'values' => ['off', 'low', 'medium'],
        ],
        'dashboard_hero' => [
            'env' => 'NEONGLASS_DASHBOARD_HERO',
            'type' => 'bool',
            'default' => true,
        ],
        'dashboard_page' => [
            'env' => 'NEONGLASS_DASHBOARD_PAGE',
            'type' => 'bool',
            'default' => true,
        ],
        'dashboard_layout' => [
            'env' => 'NEONGLASS_DASHBOARD_LAYOUT',
            'type' => 'enum',
            'default' => 'grid',
            'values' => ['grid', 'list', 'compact'],
        ],
        'dashboard_status' => [
            'env' => 'NEONGLASS_DASHBOARD_STATUS',
            'type' => 'bool',
            'default' => true,
        ],
        'reduce_motion' => [
            'env' => 'NEONGLASS_REDUCE_MOTION',
            'type' => 'bool',
            'default' => false,
        ],
        'console_palette' => [
            'env' => 'NEONGLASS_CONSOLE_PALETTE',
            'type' => 'enum',
            'default' => 'neonglass',
            'values' => ['neonglass', 'midnight', 'aurora', 'nord', 'minecraft', 'dracula', 'catppuccin', 'tokyo_night', 'one_dark'],
        ],
        'console_glow' => [
            'env' => 'NEONGLASS_CONSOLE_GLOW',
            'type' => 'bool',
            'default' => true,
        ],
        'console_always_dark' => [
            'env' => 'NEONGLASS_CONSOLE_ALWAYS_DARK',
            'type' => 'bool',
            'default' => true,
        ],
        'tab_title_suffix' => [
            'env' => 'NEONGLASS_TAB_TITLE_SUFFIX',
            'type' => 'bool',
            'default' => true,
        ],
        'compact_admin' => [
            'env' => 'NEONGLASS_COMPACT_ADMIN',
            'type' => 'bool',
            'default' => false,
        ],
        'brand_favicon' => [
            'env' => 'NEONGLASS_BRAND_FAVICON',
            'type' => 'bool',
            'default' => true,
        ],
    ];

    /**
     * Core settings plus the Studio groups (see Settings\Studio).
     *
     * @var array<string, array<string, mixed>>
     */
    public const SETTINGS = self::CORE_SETTINGS + Studio::SPECS;

    public static function coerce(string $key, mixed $value): mixed
    {
        $spec = self::SETTINGS[$key] ?? null;

        if ($spec === null) {
            return null;
        }

        if ($value === null || $value === '') {
            return $spec['default'];
        }

        if ($spec['type'] === 'bool') {
            if (is_bool($value)) {
                return $value;
            }

            return match (strtolower((string) $value)) {
                '1', 'true', 'on', 'yes' => true,
                '0', 'false', 'off', 'no' => false,
                default => $spec['default'],
            };
        }

        if ($spec['type'] === 'color') {
            $value = is_string($value) ? trim($value) : '';
            if ($value === '') {
                return '';
            }
            if (preg_match('/^#[0-9a-fA-F]{6}$/D', $value) === 1) {
                return strtoupper($value);
            }

            return $spec['default'];
        }

        if ($spec['type'] === 'int') {
            if (! is_numeric($value)) {
                return $spec['default'];
            }

            return max((int) $spec['min'], min((int) $spec['max'], (int) $value));
        }

        if ($spec['type'] === 'text') {
            return Studio::cleanText(is_string($value) ? $value : '', (int) ($spec['max'] ?? 120));
        }

        if ($spec['type'] === 'asset') {
            $value = is_string($value) ? trim($value) : '';

            return Studio::validAsset($value) ? $value : $spec['default'];
        }

        return in_array($value, $spec['values'], true) ? $value : $spec['default'];
    }

    public function getId(): string
    {
        return 'neonglass';
    }

    public function register(Panel $panel): void
    {
        // A theme must never be able to take the panel down: every step below
        // is isolated, and a failure simply leaves the default look in place.
        try {
            $settings = $this->settings();

            if (! ($settings['enabled'] ?? true)) {
                return;
            }

            $mode = ThemeMode::tryFrom($settings['theme_mode']) ?? ThemeMode::Dark;
            $panel->defaultThemeMode($mode);

            if ($settings['lock_mode'] ?? false) {
                $forcedDark = $mode === ThemeMode::Dark;
                // system cannot be locked — fall back to dark
                if ($mode === ThemeMode::System) {
                    $forcedDark = true;
                }
                $panel->darkMode($forcedDark, isForced: true)->themeSwitcher(false);
            } else {
                $panel->darkMode(true)->themeSwitcher(true);
            }

            $panel
                ->renderHook('panels::head.end', fn () => $this->renderHead())
                ->renderHook('panels::body.start', fn () => $this->renderBody());

            // Real Filament dashboard instead of decorating the server-list table from JS.
            if ($settings['dashboard_page'] && $panel->getId() === 'app') {
                $panel
                    ->pages([ServerDashboard::class])
                    ->middleware([DashboardHome::class])
                    ->homeUrl(fn () => ServerDashboard::getUrl(panel: 'app'));
            }

            if ($settings['panel_colors']) {
                $primary = $this->resolveColor($settings['primary'] ?? '', match ($settings['accent_hue']) {
                    'violet' => Color::Violet,
                    'rose' => Color::Rose,
                    'teal' => Color::Teal,
                    'amber' => Color::Amber,
                    'emerald' => Color::Emerald,
                    'indigo' => Color::Indigo,
                    'sky' => Color::Sky,
                    'fuchsia' => Color::Fuchsia,
                    'orange' => Color::Orange,
                    'white' => Color::Zinc,
                    default => Color::Cyan,
                });

                $panel->colors([
                    'primary' => $primary,
                    'gray' => Color::Slate,
                    'danger' => $this->resolveColor($settings['danger'] ?? '', Color::Rose),
                    'success' => $this->resolveColor($settings['success'] ?? '', Color::Emerald),
                    'warning' => $this->resolveColor($settings['warning'] ?? '', Color::Amber),
                    'info' => $this->resolveColor($settings['info'] ?? '', Color::Sky),
                    'secondary' => $this->resolveColor($settings['secondary'] ?? '', Color::Cyan),
                ]);
            }

            $this->applyStudio($panel, $settings);
        } catch (\Throwable $e) {
            $this->warn('register failed', $e);
        }
    }

    /**
     * Panel-level options from the Studio settings (layout, branding, login, footer).
     * Isolated so a bad value can never take the panel down.
     *
     * @param  array<string, mixed>  $s
     */
    protected function applyStudio(Panel $panel, array $s): void
    {
        try {
            $width = ['xl' => 'screen-xl', 'xxl' => 'screen-2xl', 'full' => 'full'][$s['canvas_width'] ?? 'default'] ?? null;
            if ($width !== null) {
                $panel->maxContentWidth($width);
            }

            if (is_int($s['rail_width'] ?? null)) {
                $panel->sidebarWidth($s['rail_width'] . 'px');
            }

            if (($s['rail_collapse'] ?? 'inherit') === 'on') {
                $panel->sidebarCollapsibleOnDesktop(true);
            } elseif (($s['rail_collapse'] ?? 'inherit') === 'off') {
                $panel->sidebarCollapsibleOnDesktop(false);
            }

            if (($s['panel_name'] ?? '') !== '') {
                $panel->brandName($s['panel_name']);
            }

            if (($s['logo_light'] ?? '') !== '') {
                $panel->brandLogo($s['logo_light']);
            }

            if (($s['logo_dark'] ?? '') !== '') {
                $panel->darkModeBrandLogo($s['logo_dark']);
            }

            if (is_int($s['logo_height'] ?? null)) {
                $panel->brandLogoHeight($s['logo_height'] . 'px');
            }

            if (($s['welcome_title'] ?? '') !== '' || ($s['welcome_text'] ?? '') !== '') {
                $panel->renderHook(PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE, fn () => $this->renderWelcome());
            }

            if (($s['footer_note'] ?? '') !== '') {
                $panel->renderHook(PanelsRenderHook::FOOTER, fn () => $this->renderFooterNote());
            }
        } catch (\Throwable $e) {
            $this->warn('studio settings failed', $e);
        }
    }

    protected function renderWelcome(): string
    {
        $s = $this->settings();
        $html = '';

        if (($s['welcome_title'] ?? '') !== '') {
            $html .= '<h2 class="ng-welcome__title">' . e($s['welcome_title']) . '</h2>';
        }

        if (($s['welcome_text'] ?? '') !== '') {
            $html .= '<p class="ng-welcome__text">' . e($s['welcome_text']) . '</p>';
        }

        return $html === '' ? '' : '<div class="ng-welcome">' . $html . '</div>';
    }

    protected function renderFooterNote(): string
    {
        $text = (string) ($this->settings()['footer_note'] ?? '');

        return $text === '' ? '' : '<div class="ng-footer-note">' . e($text) . '</div>';
    }

    /**
     * @param  array<int, string>|string  $fallback
     * @return array<int, string>|string
     */
    protected function resolveColor(string $hex, array|string $fallback): array|string
    {
        if ($hex !== '' && preg_match('/^#[0-9a-fA-F]{6}$/D', $hex) === 1) {
            return Color::hex($hex);
        }

        return $fallback;
    }

    public function boot(Panel $panel): void
    {
        //
    }

    protected function warn(string $message, \Throwable $e): void
    {
        try {
            logger()->warning('[NeonGlass] ' . $message . ': ' . $e->getMessage());
        } catch (\Throwable) {
            // logging must not fail either
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function settings(): array
    {
        static $cached = null;

        if ($cached !== null) {
            return $cached;
        }

        try {
            $stored = $this->readEnvFile();
        } catch (\Throwable) {
            $stored = [];
        }
        $settings = [];

        foreach (self::SETTINGS as $key => $spec) {
            $settings[$key] = self::coerce($key, $stored[$spec['env']] ?? null);
        }

        return $cached = $settings;
    }

    /**
     * @return array<string, string>
     */
    protected function readEnvFile(): array
    {
        $path = base_path('.env');

        if (!is_file($path) || !is_readable($path)) {
            return [];
        }

        try {
            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        } catch (\Throwable) {
            return [];
        }

        $prefixed = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (!str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            if (str_starts_with($key, 'NEONGLASS_')) {
                $value = trim($value);
                // Strip surrounding quotes
                if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                    (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                    $value = substr($value, 1, -1);
                }
                $prefixed[$key] = $value;
            }
        }

        return $prefixed;
    }

    /**
     * Strings the JS needs (the hero), translated server-side.
     *
     * @return array<string, string>
     */
    protected function clientStrings(): array
    {
        $keys = [
            'js_greet_morning', 'js_greet_afternoon', 'js_greet_evening', 'js_greet_night',
            'js_hello', 'js_welcome', 'js_servers', 'js_server_one', 'js_online', 'js_offline',
            'js_busy', 'js_empty', 'js_layout', 'js_grid', 'js_list', 'js_compact',
            'js_search_hint', 'js_dashboard',
        ];

        $out = [];
        foreach ($keys as $key) {
            $value = trans('neonglass::strings.' . $key);
            if (is_string($value) && $value !== 'neonglass::strings.' . $key) {
                $out[$key] = $value;
            }
        }

        return $out;
    }

    protected function currentUserName(): string
    {
        try {
            $user = auth()->user();

            return (string) ($user->username ?? $user->name ?? '');
        } catch (\Throwable) {
            return '';
        }
    }

    protected function renderHead(): string
    {
        try {
            $settings = $this->settings();

            $payload = $settings + [
                'user' => $this->currentUserName(),
                'i18n' => $this->clientStrings(),
            ];
            $json = e(json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}');

            $favicon = $this->resolveFavicon($settings);

            $cssUrl = url('neonglass/assets/neonglass.css') . '?v=' . $this->assetVersion('css/neonglass.css');
            $jsUrl = url('neonglass/assets/neonglass.js') . '?v=' . $this->assetVersion('js/neonglass.js');

            // Sets the data-* hooks before first paint (no flash of unstyled theme).
            $boot = <<<'JS'
(function(){try{
var m=document.querySelector('meta[name="ng-settings"]');if(!m)return;
var s=JSON.parse(m.content),r=document.documentElement,a=function(k,v){r.setAttribute('data-ng-'+k,v)};
a('hue',s.accent_hue||'cyan');a('glass',s.glass_intensity||'medium');a('particles',s.particles||'low');
a('bg',s.background_style||'aurora');a('surface',s.surface_style||'glass');a('radius',s.radius||'rounded');
a('palette',s.console_palette||'neonglass');
if(s.reduce_motion)a('reduce','1');
if(s.console_always_dark)a('console-dark','1');
if(s.console_glow)a('console-glow','1');
if(s.compact_admin)a('compact','1');
}catch(e){}})();
JS;

            $iconType = str_contains($favicon, 'image/svg') ? 'image/svg+xml' : 'image/png';

            return '<meta name="ng-settings" content="' . $json . '">'
                . '<link rel="icon" type="' . $iconType . '" href="' . e($favicon) . '">'
                . '<link rel="stylesheet" href="' . e($cssUrl) . '">'
                . $this->renderStudioCss($settings)
                . '<script>' . $boot . '</script>'
                . '<script src="' . e($jsUrl) . '" defer></script>';
        } catch (\Throwable $e) {
            $this->warn('renderHead failed', $e);

            return '';
        }
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    protected function renderStudioCss(array $settings): string
    {
        try {
            $css = StudioCss::build($settings);

            // Defensive: this block only ever contains validated tokens, never markup.
            $css = str_ireplace('</style', '', $css);

            return $css === '' ? '' : '<style id="ng-studio-css">' . $css . '</style>';
        } catch (\Throwable $e) {
            $this->warn('studio css failed', $e);

            return '';
        }
    }

    protected function assetVersion(string $relative): int
    {
        $path = plugin_path('neonglass', $relative);

        return is_string($path) && is_file($path) ? (int) filemtime($path) : 0;
    }

    /**
     * Prefer the panel brand logo as favicon when enabled; otherwise the NeonGlass mark.
     */
    protected function resolveFavicon(array $settings): string
    {
        $fallback = 'data:image/svg+xml;utf8,' . rawurlencode(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64">'
            . '<defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1">'
            . '<stop offset="0" stop-color="#22d3ee"/><stop offset="1" stop-color="#e879f9"/>'
            . '</linearGradient></defs>'
            . '<rect width="64" height="64" rx="18" fill="#0a0b1a"/>'
            . '<rect x="14" y="14" width="36" height="36" rx="12" fill="none" stroke="url(#g)" stroke-width="3.5"/>'
            . '<circle cx="32" cy="32" r="5" fill="url(#g)"/>'
            . '</svg>'
        );

        if (! ($settings['brand_favicon'] ?? true)) {
            return $fallback;
        }

        $logo = $this->brandLogoUrl();

        return $logo !== null && $logo !== '' ? $logo : $fallback;
    }

    /**
     * Public URL for the Filament/Pelican brand logo, if configured.
     */
    public function brandLogoUrl(): ?string
    {
        try {
            foreach (['app', 'admin', 'server'] as $id) {
                try {
                    $panel = \Filament\Facades\Filament::getPanel($id);
                    $logo = $panel->getBrandLogo();
                    if (is_string($logo) && $logo !== '') {
                        if (str_starts_with($logo, 'http') || str_starts_with($logo, 'data:') || str_starts_with($logo, '/')) {
                            return $logo;
                        }

                        return asset($logo);
                    }
                } catch (\Throwable) {
                    continue;
                }
            }
        } catch (\Throwable) {
            //
        }

        return null;
    }

    protected function renderBody(): string
    {
        try {
            $settings = $this->settings();
            $static = ($settings['particles'] ?? 'low') === 'off' || ($settings['reduce_motion'] ?? false);

            return '<div id="ng-bg" class="ng-bg' . ($static ? ' ng-bg--static' : '') . '" aria-hidden="true">'
                . '<div class="ng-aurora"><i class="ng-orb ng-orb--1"></i><i class="ng-orb ng-orb--2"></i><i class="ng-orb ng-orb--3"></i></div>'
                . '<div class="ng-grid"></div>'
                . ($static ? '' : '<div class="ng-particles"></div>')
                . '</div>';
        } catch (\Throwable $e) {
            $this->warn('renderBody failed', $e);

            return '';
        }
    }

    // ─── HasPluginSettings ───────────────────────────────────────────────

    public function getSettingsForm(): array
    {
        $t = fn (string $key) => trans('neonglass::strings.' . $key);

        return [
            Section::make($t('section_presets'))
                ->description($t('section_presets_help'))
                ->columns(['default' => 1, 'md' => 2])
                ->collapsible()
                ->schema([
                    Select::make('apply_preset')
                        ->label($t('apply_preset'))
                        ->helperText($t('apply_preset_help'))
                        ->options(fn () => PresetStore::options(includeEmpty: true))
                        ->native(false)
                        ->live()
                        ->dehydrated(false)
                        ->afterStateUpdated(function (?string $state, Set $set): void {
                            if ($state === null || $state === '') {
                                return;
                            }
                            $settings = PresetStore::get($state);
                            if ($settings === null) {
                                return;
                            }
                            foreach ($settings as $key => $value) {
                                $set($key, $value);
                            }
                        })
                        ->columnSpanFull(),

                    TextInput::make('save_preset_as')
                        ->label($t('save_preset_as'))
                        ->helperText($t('save_preset_as_help'))
                        ->maxLength(48)
                        ->placeholder($t('save_preset_placeholder')),

                    Select::make('delete_preset')
                        ->label($t('delete_preset'))
                        ->helperText($t('delete_preset_help'))
                        ->options(fn () => PresetStore::options(includeEmpty: true, customOnly: true))
                        ->native(false),
                ]),

            Section::make($t('section_general'))
                ->description($t('section_general_help'))
                ->columns(['default' => 1, 'md' => 2])
                ->collapsible()
                ->schema([
                    Toggle::make('enabled')
                        ->label($t('enabled'))
                        ->helperText($t('enabled_help'))
                        ->inline(false)
                        ->columnSpanFull(),

                    Select::make('theme_mode')
                        ->label($t('theme_mode'))
                        ->helperText($t('theme_mode_help'))
                        ->options([
                            'dark' => $t('mode_dark'),
                            'light' => $t('mode_light'),
                            'system' => $t('mode_system'),
                        ])
                        ->native(false)
                        ->required(),

                    Toggle::make('lock_mode')
                        ->label($t('lock_mode'))
                        ->helperText($t('lock_mode_help'))
                        ->inline(false),

                    Toggle::make('panel_colors')
                        ->label($t('panel_colors'))
                        ->helperText($t('panel_colors_help'))
                        ->inline(false)
                        ->live()
                        ->columnSpanFull(),
                ]),

            Section::make($t('section_colors'))
                ->description($t('section_colors_help'))
                ->columns(['default' => 1, 'md' => 2])
                ->collapsible()
                ->collapsed()
                ->schema([
                    Select::make('accent_hue')
                        ->label($t('accent_hue'))
                        ->helperText($t('accent_hue_help'))
                        ->options([
                            'cyan' => $t('hue_cyan'),
                            'violet' => $t('hue_violet'),
                            'rose' => $t('hue_rose'),
                            'teal' => $t('hue_teal'),
                            'amber' => $t('hue_amber'),
                            'emerald' => $t('hue_emerald'),
                            'indigo' => $t('hue_indigo'),
                            'sky' => $t('hue_sky'),
                            'fuchsia' => $t('hue_fuchsia'),
                            'orange' => $t('hue_orange'),
                            'white' => $t('hue_white'),
                        ])
                        ->native(false)
                        ->required()
                        ->columnSpanFull(),

                    ColorPicker::make('primary')
                        ->label($t('color_primary'))
                        ->helperText($t('color_primary_help'))
                        ->regex('/^#[0-9a-fA-F]{6}$/D'),

                    ColorPicker::make('secondary')
                        ->label($t('color_secondary'))
                        ->helperText($t('color_override_help'))
                        ->regex('/^#[0-9a-fA-F]{6}$/D'),

                    ColorPicker::make('success')
                        ->label($t('color_success'))
                        ->helperText($t('color_override_help'))
                        ->regex('/^#[0-9a-fA-F]{6}$/D'),

                    ColorPicker::make('warning')
                        ->label($t('color_warning'))
                        ->helperText($t('color_override_help'))
                        ->regex('/^#[0-9a-fA-F]{6}$/D'),

                    ColorPicker::make('danger')
                        ->label($t('color_danger'))
                        ->helperText($t('color_override_help'))
                        ->regex('/^#[0-9a-fA-F]{6}$/D'),

                    ColorPicker::make('info')
                        ->label($t('color_info'))
                        ->helperText($t('color_override_help'))
                        ->regex('/^#[0-9a-fA-F]{6}$/D'),
                ]),

            Section::make($t('section_glass'))
                ->description($t('section_glass_help'))
                ->columns(['default' => 1, 'md' => 2])
                ->collapsible()
                ->collapsed()
                ->schema([
                    Select::make('surface_style')
                        ->label($t('surface_style'))
                        ->helperText($t('surface_style_help'))
                        ->options([
                            'glass' => $t('surface_glass'),
                            'solid' => $t('surface_solid'),
                        ])
                        ->native(false)
                        ->required(),

                    Select::make('glass_intensity')
                        ->label($t('glass_intensity'))
                        ->helperText($t('glass_intensity_help'))
                        ->options([
                            'low' => $t('glass_low'),
                            'medium' => $t('glass_medium'),
                            'high' => $t('glass_high'),
                        ])
                        ->native(false)
                        ->required(),

                    Select::make('radius')
                        ->label($t('radius'))
                        ->helperText($t('radius_help'))
                        ->options([
                            'sharp' => $t('radius_sharp'),
                            'rounded' => $t('radius_rounded'),
                            'soft' => $t('radius_soft'),
                        ])
                        ->native(false)
                        ->required()
                        ->columnSpanFull(),
                ]),

            Section::make($t('section_atmosphere'))
                ->description($t('section_atmosphere_help'))
                ->columns(['default' => 1, 'md' => 2])
                ->collapsible()
                ->collapsed()
                ->schema([
                    Select::make('background_style')
                        ->label($t('background_style'))
                        ->helperText($t('background_style_help'))
                        ->options([
                            'aurora' => $t('bg_aurora'),
                            'grid' => $t('bg_grid'),
                            'plain' => $t('bg_plain'),
                        ])
                        ->native(false)
                        ->required(),

                    Select::make('particles')
                        ->label($t('particles'))
                        ->helperText($t('particles_help'))
                        ->options([
                            'off' => $t('particles_off'),
                            'low' => $t('particles_low'),
                            'medium' => $t('particles_medium'),
                        ])
                        ->native(false)
                        ->required(),
                ]),

            Section::make($t('section_dashboard'))
                ->description($t('section_dashboard_help'))
                ->columns(['default' => 1, 'md' => 2])
                ->collapsible()
                ->collapsed()
                ->schema([
                    Toggle::make('dashboard_page')
                        ->label($t('dashboard_page'))
                        ->helperText($t('dashboard_page_help'))
                        ->inline(false)
                        ->columnSpanFull(),

                    Toggle::make('dashboard_hero')
                        ->label($t('dashboard_hero'))
                        ->helperText($t('dashboard_hero_help'))
                        ->inline(false),

                    Toggle::make('dashboard_status')
                        ->label($t('dashboard_status'))
                        ->helperText($t('dashboard_status_help'))
                        ->inline(false),

                    Select::make('dashboard_layout')
                        ->label($t('dashboard_layout'))
                        ->helperText($t('dashboard_layout_help'))
                        ->options([
                            'grid' => $t('layout_grid'),
                            'list' => $t('layout_list'),
                            'compact' => $t('layout_compact'),
                        ])
                        ->native(false)
                        ->required()
                        ->columnSpanFull(),
                ]),

            Section::make($t('section_console'))
                ->description($t('section_console_help'))
                ->columns(['default' => 1, 'md' => 2])
                ->collapsible()
                ->collapsed()
                ->schema([
                    Select::make('console_palette')
                        ->label($t('console_palette'))
                        ->helperText($t('console_palette_help'))
                        ->options([
                            'neonglass' => $t('palette_neonglass'),
                            'midnight' => $t('palette_midnight'),
                            'aurora' => $t('palette_aurora'),
                            'nord' => $t('palette_nord'),
                            'minecraft' => $t('palette_minecraft'),
                            'dracula' => $t('palette_dracula'),
                            'catppuccin' => $t('palette_catppuccin'),
                            'tokyo_night' => $t('palette_tokyo_night'),
                            'one_dark' => $t('palette_one_dark'),
                        ])
                        ->native(false)
                        ->required()
                        ->columnSpanFull(),

                    Toggle::make('console_glow')
                        ->label($t('console_glow'))
                        ->helperText($t('console_glow_help'))
                        ->inline(false),

                    Toggle::make('console_always_dark')
                        ->label($t('console_always_dark'))
                        ->helperText($t('console_always_dark_help'))
                        ->inline(false),
                ]),

            Section::make($t('section_accessibility'))
                ->description($t('section_accessibility_help'))
                ->columns(['default' => 1, 'md' => 2])
                ->collapsible()
                ->collapsed()
                ->schema([
                    Toggle::make('reduce_motion')
                        ->label($t('reduce_motion'))
                        ->helperText($t('reduce_motion_help'))
                        ->inline(false)
                        ->columnSpanFull(),
                ]),

            Section::make($t('section_chrome'))
                ->description($t('section_chrome_help'))
                ->columns(['default' => 1, 'md' => 2])
                ->collapsible()
                ->collapsed()
                ->schema([
                    Toggle::make('tab_title_suffix')
                        ->label($t('tab_title_suffix'))
                        ->helperText($t('tab_title_suffix_help'))
                        ->inline(false),

                    Toggle::make('brand_favicon')
                        ->label($t('brand_favicon'))
                        ->helperText($t('brand_favicon_help'))
                        ->inline(false),

                    Toggle::make('compact_admin')
                        ->label($t('compact_admin'))
                        ->helperText($t('compact_admin_help'))
                        ->inline(false)
                        ->columnSpanFull(),
                ]),

            ...StudioForm::sections(),
        ];
    }

    public function getSettingsFormData(): array
    {
        return $this->settings();
    }

    public function saveSettings(array $data): void
    {
        $current = $this->settings();
        $values = [];
        $resolved = [];

        foreach (self::SETTINGS as $key => $spec) {
            $raw = array_key_exists($key, $data) ? $data[$key] : $current[$key];
            $value = self::coerce($key, $raw);
            $resolved[$key] = $value;

            $values[$spec['env']] = match ($spec['type']) {
                'bool' => $value ? 'true' : 'false',
                'color' => $value === '' ? '' : (string) $value,
                default => (string) $value,
            };
        }

        // Lock mode requires a concrete light/dark choice
        if (($values['NEONGLASS_LOCK_MODE'] ?? 'false') === 'true'
            && ($values['NEONGLASS_THEME_MODE'] ?? 'dark') === 'system') {
            $values['NEONGLASS_LOCK_MODE'] = 'false';
            $resolved['lock_mode'] = false;
        }

        $this->writeToEnvironment($values);

        $messages = [trans('neonglass::strings.saved')];

        // Delete a custom preset (if requested)
        $deleteId = is_string($data['delete_preset'] ?? null) ? trim((string) $data['delete_preset']) : '';
        if ($deleteId !== '') {
            try {
                if (PresetStore::deleteCustom($deleteId)) {
                    $messages[] = trans('neonglass::strings.preset_deleted', ['name' => $deleteId]);
                }
            } catch (\Throwable $e) {
                $this->warn('delete preset failed', $e);
            }
        }

        // Save current settings as a named custom preset
        $saveAs = is_string($data['save_preset_as'] ?? null) ? trim((string) $data['save_preset_as']) : '';
        if ($saveAs !== '') {
            try {
                $id = PresetStore::saveCustom($saveAs, $resolved);
                $messages[] = trans('neonglass::strings.preset_saved', ['name' => $id]);
            } catch (\Throwable $e) {
                $this->warn('save preset failed', $e);
                Notification::make()
                    ->title(trans('neonglass::strings.preset_save_failed'))
                    ->body($e->getMessage())
                    ->danger()
                    ->send();
            }
        }

        Notification::make()
            ->title($messages[0])
            ->body(count($messages) > 1 ? implode(' · ', array_slice($messages, 1)) : null)
            ->success()
            ->send();
    }
}
