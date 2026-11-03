<?php

return [
    /*
    |--------------------------------------------------------------------------
    | NeonGlass Theme Configuration
    |--------------------------------------------------------------------------
    |
    | These values are the fallbacks used when the corresponding NEONGLASS_*
    | environment variable is not present. The settings form writes the
    | env keys; this file is only published for reference / override.
    |
    */

    'enabled' => env('NEONGLASS_ENABLED', true),
    'theme_mode' => env('NEONGLASS_THEME_MODE', 'dark'),
    'lock_mode' => env('NEONGLASS_LOCK_MODE', false),
    'panel_colors' => env('NEONGLASS_PANEL_COLORS', true),
    'accent_hue' => env('NEONGLASS_ACCENT_HUE', 'cyan'),
    'primary' => env('NEONGLASS_PRIMARY', ''),
    'secondary' => env('NEONGLASS_SECONDARY', ''),
    'success' => env('NEONGLASS_SUCCESS', ''),
    'warning' => env('NEONGLASS_WARNING', ''),
    'danger' => env('NEONGLASS_DANGER', ''),
    'info' => env('NEONGLASS_INFO', ''),
    'background_style' => env('NEONGLASS_BACKGROUND_STYLE', 'aurora'),
    'surface_style' => env('NEONGLASS_SURFACE_STYLE', 'glass'),
    'radius' => env('NEONGLASS_RADIUS', 'rounded'),
    'dashboard_hero' => env('NEONGLASS_DASHBOARD_HERO', true),
    'dashboard_page' => env('NEONGLASS_DASHBOARD_PAGE', true),
    'dashboard_layout' => env('NEONGLASS_DASHBOARD_LAYOUT', 'grid'),
    'glass_intensity' => env('NEONGLASS_GLASS_INTENSITY', 'medium'),
    'particles' => env('NEONGLASS_PARTICLES', 'low'),
    'reduce_motion' => env('NEONGLASS_REDUCE_MOTION', false),
    'console_palette' => env('NEONGLASS_CONSOLE_PALETTE', 'neonglass'),
    'console_glow' => env('NEONGLASS_CONSOLE_GLOW', true),
    'console_always_dark' => env('NEONGLASS_CONSOLE_ALWAYS_DARK', true),
    'tab_title_suffix' => env('NEONGLASS_TAB_TITLE_SUFFIX', true),
    'compact_admin' => env('NEONGLASS_COMPACT_ADMIN', false),
    'dashboard_status' => env('NEONGLASS_DASHBOARD_STATUS', true),
    'brand_favicon' => env('NEONGLASS_BRAND_FAVICON', true),
];
