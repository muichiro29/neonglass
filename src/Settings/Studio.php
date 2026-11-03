<?php

namespace MuichiroDev\NeonGlass\Settings;

/**
 * NeonGlass Studio: the fine-tuning controls of the liquid-glass material
 * (frost, tint, edge light, neon glow), the atmosphere, type, shape, menus, terminal,
 * sign-in screen and identity.
 *
 * Every entry is merged into NeonGlassPlugin::SETTINGS, so storage (.env), presets and
 * coercion work for these keys like for the core ones. An empty value ('') always means
 * "use the theme default".
 */
final class Studio
{
    /** Form groups, in display order. */
    public const GROUPS = ['day', 'night', 'material', 'atmosphere', 'type', 'shape', 'menus', 'terminal', 'welcome', 'identity'];

    /** Heroicon shown next to each group. */
    public const ICONS = [
        'day' => 'heroicon-o-sun',
        'night' => 'heroicon-o-moon',
        'material' => 'heroicon-o-cube-transparent',
        'atmosphere' => 'heroicon-o-sparkles',
        'type' => 'heroicon-o-language',
        'shape' => 'heroicon-o-squares-2x2',
        'menus' => 'heroicon-o-bars-3',
        'terminal' => 'heroicon-o-command-line',
        'welcome' => 'heroicon-o-lock-closed',
        'identity' => 'heroicon-o-identification',
    ];

    /**
     * @var array<string, array{env: string, type: string, default: mixed, group: string, min?: int, max?: int, values?: string[]}>
     */
    public const SPECS = [
        'day_canvas' => ['env' => 'NEONGLASS_DAY_CANVAS', 'type' => 'color', 'default' => '', 'group' => 'day'],
        'day_panel' => ['env' => 'NEONGLASS_DAY_PANEL', 'type' => 'color', 'default' => '', 'group' => 'day'],
        'day_ink' => ['env' => 'NEONGLASS_DAY_INK', 'type' => 'color', 'default' => '', 'group' => 'day'],
        'day_ink_soft' => ['env' => 'NEONGLASS_DAY_INK_SOFT', 'type' => 'color', 'default' => '', 'group' => 'day'],
        'day_line' => ['env' => 'NEONGLASS_DAY_LINE', 'type' => 'color', 'default' => '', 'group' => 'day'],
        'night_canvas' => ['env' => 'NEONGLASS_NIGHT_CANVAS', 'type' => 'color', 'default' => '', 'group' => 'night'],
        'night_panel' => ['env' => 'NEONGLASS_NIGHT_PANEL', 'type' => 'color', 'default' => '', 'group' => 'night'],
        'night_ink' => ['env' => 'NEONGLASS_NIGHT_INK', 'type' => 'color', 'default' => '', 'group' => 'night'],
        'night_ink_soft' => ['env' => 'NEONGLASS_NIGHT_INK_SOFT', 'type' => 'color', 'default' => '', 'group' => 'night'],
        'night_line' => ['env' => 'NEONGLASS_NIGHT_LINE', 'type' => 'color', 'default' => '', 'group' => 'night'],
        'frost' => ['env' => 'NEONGLASS_FROST', 'type' => 'int', 'default' => '', 'group' => 'material', 'min' => 0, 'max' => 40],
        'tint' => ['env' => 'NEONGLASS_TINT', 'type' => 'int', 'default' => '', 'group' => 'material', 'min' => 0, 'max' => 100],
        'edge_light' => ['env' => 'NEONGLASS_EDGE_LIGHT', 'type' => 'int', 'default' => '', 'group' => 'material', 'min' => 0, 'max' => 100],
        'neon_glow' => ['env' => 'NEONGLASS_NEON_GLOW', 'type' => 'int', 'default' => '', 'group' => 'material', 'min' => 0, 'max' => 100],
        'depth' => ['env' => 'NEONGLASS_DEPTH', 'type' => 'enum', 'default' => 'soft', 'group' => 'material', 'values' => ['soft', 'none', 'deep']],
        'card_finish' => ['env' => 'NEONGLASS_CARD_FINISH', 'type' => 'enum', 'default' => 'liquid', 'group' => 'material', 'values' => ['liquid', 'outline', 'matte']],
        'card_inset' => ['env' => 'NEONGLASS_CARD_INSET', 'type' => 'int', 'default' => '', 'group' => 'material', 'min' => 12, 'max' => 36],
        'card_lift' => ['env' => 'NEONGLASS_CARD_LIFT', 'type' => 'bool', 'default' => true, 'group' => 'material'],
        'orb_strength' => ['env' => 'NEONGLASS_ORB_STRENGTH', 'type' => 'int', 'default' => '', 'group' => 'atmosphere', 'min' => 0, 'max' => 100],
        'orb_pace' => ['env' => 'NEONGLASS_ORB_PACE', 'type' => 'enum', 'default' => 'steady', 'group' => 'atmosphere', 'values' => ['steady', 'calm', 'lively']],
        'ui_font' => ['env' => 'NEONGLASS_UI_FONT', 'type' => 'enum', 'default' => 'neon', 'group' => 'type', 'values' => ['neon', 'system', 'serif', 'mono', 'custom']],
        'font_family' => ['env' => 'NEONGLASS_FONT_FAMILY', 'type' => 'text', 'default' => '', 'group' => 'type', 'max' => 60],
        'font_file' => ['env' => 'NEONGLASS_FONT_FILE', 'type' => 'asset', 'default' => '', 'group' => 'type', 'max' => 300],
        'text_scale' => ['env' => 'NEONGLASS_TEXT_SCALE', 'type' => 'int', 'default' => '', 'group' => 'type', 'min' => 14, 'max' => 20],
        'text_leading' => ['env' => 'NEONGLASS_TEXT_LEADING', 'type' => 'int', 'default' => '', 'group' => 'type', 'min' => 110, 'max' => 200],
        'control_corner' => ['env' => 'NEONGLASS_CONTROL_CORNER', 'type' => 'int', 'default' => '', 'group' => 'shape', 'min' => 0, 'max' => 24],
        'line_weight' => ['env' => 'NEONGLASS_LINE_WEIGHT', 'type' => 'int', 'default' => '', 'group' => 'shape', 'min' => 0, 'max' => 3],
        'page_rhythm' => ['env' => 'NEONGLASS_PAGE_RHYTHM', 'type' => 'int', 'default' => '', 'group' => 'shape', 'min' => 0, 'max' => 48],
        'canvas_width' => ['env' => 'NEONGLASS_CANVAS_WIDTH', 'type' => 'enum', 'default' => 'default', 'group' => 'shape', 'values' => ['default', 'xl', 'xxl', 'full']],
        'rail_width' => ['env' => 'NEONGLASS_RAIL_WIDTH', 'type' => 'int', 'default' => '', 'group' => 'menus', 'min' => 220, 'max' => 360],
        'rail_collapse' => ['env' => 'NEONGLASS_RAIL_COLLAPSE', 'type' => 'enum', 'default' => 'inherit', 'group' => 'menus', 'values' => ['inherit', 'on', 'off']],
        'menu_corner' => ['env' => 'NEONGLASS_MENU_CORNER', 'type' => 'int', 'default' => '', 'group' => 'menus', 'min' => 0, 'max' => 24],
        'menu_glow' => ['env' => 'NEONGLASS_MENU_GLOW', 'type' => 'bool', 'default' => true, 'group' => 'menus'],
        'term_size' => ['env' => 'NEONGLASS_TERM_SIZE', 'type' => 'int', 'default' => '', 'group' => 'terminal', 'min' => 10, 'max' => 24],
        'term_leading' => ['env' => 'NEONGLASS_TERM_LEADING', 'type' => 'int', 'default' => '', 'group' => 'terminal', 'min' => 100, 'max' => 200],
        'term_cursor' => ['env' => 'NEONGLASS_TERM_CURSOR', 'type' => 'enum', 'default' => 'default', 'group' => 'terminal', 'values' => ['default', 'bar', 'block', 'underline']],
        'term_blink' => ['env' => 'NEONGLASS_TERM_BLINK', 'type' => 'enum', 'default' => 'default', 'group' => 'terminal', 'values' => ['default', 'on', 'off']],
        'term_corner' => ['env' => 'NEONGLASS_TERM_CORNER', 'type' => 'int', 'default' => '', 'group' => 'terminal', 'min' => 0, 'max' => 28],
        'welcome_layout' => ['env' => 'NEONGLASS_WELCOME_LAYOUT', 'type' => 'enum', 'default' => 'stacked', 'group' => 'welcome', 'values' => ['stacked', 'side']],
        'welcome_title' => ['env' => 'NEONGLASS_WELCOME_TITLE', 'type' => 'text', 'default' => '', 'group' => 'welcome', 'max' => 80],
        'welcome_text' => ['env' => 'NEONGLASS_WELCOME_TEXT', 'type' => 'text', 'default' => '', 'group' => 'welcome', 'max' => 200],
        'welcome_image' => ['env' => 'NEONGLASS_WELCOME_IMAGE', 'type' => 'asset', 'default' => '', 'group' => 'welcome', 'max' => 300],
        'welcome_width' => ['env' => 'NEONGLASS_WELCOME_WIDTH', 'type' => 'int', 'default' => '', 'group' => 'welcome', 'min' => 320, 'max' => 600],
        'panel_name' => ['env' => 'NEONGLASS_PANEL_NAME', 'type' => 'text', 'default' => '', 'group' => 'identity', 'max' => 60],
        'logo_light' => ['env' => 'NEONGLASS_LOGO_LIGHT', 'type' => 'asset', 'default' => '', 'group' => 'identity', 'max' => 300],
        'logo_dark' => ['env' => 'NEONGLASS_LOGO_DARK', 'type' => 'asset', 'default' => '', 'group' => 'identity', 'max' => 300],
        'logo_height' => ['env' => 'NEONGLASS_LOGO_HEIGHT', 'type' => 'int', 'default' => '', 'group' => 'identity', 'min' => 20, 'max' => 72],
        'footer_note' => ['env' => 'NEONGLASS_FOOTER_NOTE', 'type' => 'text', 'default' => '', 'group' => 'identity', 'max' => 160],
    ];

    /** Single-line text that is safe in .env and in HTML. */
    public static function cleanText(string $value, int $max): string
    {
        $value = preg_replace('/[\x00-\x1f\x7f$`"\\\\]/u', '', $value) ?? '';

        return mb_substr(trim($value), 0, $max);
    }

    /** Site-relative path or https URL with nothing that could escape CSS / HTML. */
    public static function validAsset(string $value): bool
    {
        if ($value === '') {
            return true;
        }
        if (preg_match('/[\x00-\x20\x7f<>"\'()\\\\;{}]/', $value) === 1) {
            return false;
        }
        if (str_starts_with($value, '/') && ! str_starts_with($value, '//')) {
            return true;
        }

        return filter_var($value, FILTER_VALIDATE_URL) !== false
            && parse_url($value, PHP_URL_SCHEME) === 'https'
            && parse_url($value, PHP_URL_USER) === null
            && parse_url($value, PHP_URL_PASS) === null;
    }
}
