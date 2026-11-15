<?php

namespace MuichiroDev\NeonGlass\Settings;

/**
 * Compiles the Studio settings into one small <style> block.
 * A setting left on its default emits nothing, so an untouched install renders the stock
 * NeonGlass look. Values are validated again here before they reach the CSS text.
 */
final class StudioCss
{
    /** Surfaces that make up "cards". */
    private const CARDS = ['.ng-srv', '.ng-status', '.fi-section', '.fi-sc-section', '.fi-ta-ctn', '.fi-wi-stats-overview-stat', '.fi-in-entry'];

    /** Elements that carry the neon accent. */
    private const NEON = ['.fi-btn-primary', '.fi-sidebar-nav-item-active', '.fi-sidebar-item-active', '.ng-gchip.is-active', '.ng-chip.is-active', '.ng-seg button[aria-pressed="true"]', '.ng-meter__bar i'];

    /** @param array<string, mixed> $s */
    public static function build(array $s): string
    {
        $parts = [
            self::colours($s, 'day', 'html:not(.dark), html:not(.dark) body', [14, 17, 40]),
            self::colours($s, 'night', 'html.dark, html.dark body', [245, 247, 255]),
            self::material($s),
            self::atmosphere($s),
            self::typography($s),
            self::shape($s),
            self::menus($s),
            self::terminal($s),
            self::welcome($s),
        ];

        return implode("\n", array_filter($parts, fn ($p) => $p !== ''));
    }

    // ── helpers ──────────────────────────────────────────────────────────────

    private static function n(array $s, string $key): ?int
    {
        $v = $s[$key] ?? '';

        return is_int($v) ? $v : null;
    }

    /** "html body A, html body B" for a list of selectors. */
    private static function scope(array $selectors, string $prefix = 'html body '): string
    {
        return implode(', ', array_map(fn ($sel) => $prefix . $sel, $selectors));
    }

    private static function decimal(float $v): string
    {
        return rtrim(rtrim(number_format($v, 3, '.', ''), '0'), '.') ?: '0';
    }

    /** @return array{0:int,1:int,2:int}|null */
    private static function rgb(mixed $hex): ?array
    {
        if (! is_string($hex) || ! preg_match('/^#([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})$/Di', $hex, $m)) {
            return null;
        }

        return [(int) hexdec($m[1]), (int) hexdec($m[2]), (int) hexdec($m[3])];
    }

    private static function trio(array $c): string
    {
        return implode(' ', $c);
    }

    private static function blend(array $a, array $b, float $t): array
    {
        return array_map(fn ($x, $y) => (int) round($x + ($y - $x) * $t), $a, $b);
    }

    // ── groups ───────────────────────────────────────────────────────────────

    private static function colours(array $s, string $mode, string $selector, array $defaultInk): string
    {
        $canvas = self::rgb($s[$mode . '_canvas'] ?? '');
        $panel = self::rgb($s[$mode . '_panel'] ?? '');
        $ink = self::rgb($s[$mode . '_ink'] ?? '');
        $soft = self::rgb($s[$mode . '_ink_soft'] ?? '');
        $line = self::rgb($s[$mode . '_line'] ?? '');
        $decl = [];

        if ($canvas) {
            $decl[] = '--ng-bg:' . self::trio($canvas);
        }
        if ($panel) {
            $base = $ink ?? $defaultInk;
            $decl[] = '--ng-surface:' . self::trio($panel);
            $decl[] = '--ng-bg-elevated:' . self::trio($panel);
            $decl[] = '--ng-surface-2:' . self::trio(self::blend($panel, $base, 0.07));
            $decl[] = '--ng-surface-3:' . self::trio(self::blend($panel, $base, 0.14));
            $decl[] = '--lg-base:rgb(' . self::trio($panel) . ' / ' . ($mode === 'night' ? '.55' : '.4') . ')';
        }
        if ($ink) {
            $decl[] = '--ng-text:' . self::trio($ink);
        }
        if ($soft) {
            $decl[] = '--ng-text-muted:' . self::trio($soft);
            $decl[] = '--ng-text-subtle:' . self::trio(self::blend($soft, $canvas ?? $panel ?? $soft, 0.35));
        }
        if ($line) {
            $decl[] = '--ng-border:' . self::trio($line);
        }

        return $decl ? $selector . '{' . implode(';', $decl) . '}' : '';
    }

    private static function material(array $s): string
    {
        $r = [];

        if (($frost = self::n($s, 'frost')) !== null) {
            $r[] = 'html[data-ng-glass]{--ng-lg-blur:' . $frost . 'px' . ($frost === 0 ? ';--ng-lg-sat:100%' : '') . '}';
        }

        if (($tint = self::n($s, 'tint')) !== null) {
            $dark = 0.02 + $tint * 0.0022;
            $light = 0.35 + $tint * 0.0055;
            $r[] = 'html.dark{--lg-fill-a:rgb(255 255 255 / ' . self::decimal($dark) . ');--lg-fill-b:rgb(255 255 255 / ' . self::decimal($dark * 0.32) . ')}';
            $r[] = 'html:not(.dark){--lg-fill-a:rgb(255 255 255 / ' . self::decimal($light) . ');--lg-fill-b:rgb(255 255 255 / ' . self::decimal($light * 0.55) . ')}';
        }

        if (($edge = self::n($s, 'edge_light')) !== null) {
            $r[] = 'html:is(.dark,:root){--lg-spec:rgb(255 255 255 / ' . self::decimal($edge / 100) . ')}';
            $r[] = 'html body .ng-srv::before, html body .ng-status::before, html body .ng-empty::before, html body .fi-simple-card::before{opacity:' . self::decimal(min(1, $edge / 55)) . '}';
        }

        if (($glow = self::n($s, 'neon_glow')) !== null) {
            if ($glow === 0) {
                $r[] = self::scope(self::NEON) . ', html body .ng-power, html body .fi-btn-group .fi-btn{box-shadow:inset 0 1px 0 rgb(255 255 255 / .3) !important;filter:none !important}';
            } else {
                $r[] = self::scope(self::NEON) . '{filter:drop-shadow(0 0 ' . self::decimal($glow * 0.16) . 'px rgb(var(--ng-accent) / ' . self::decimal($glow / 150) . '))}';
            }
        }

        $depth = $s['depth'] ?? 'soft';
        if ($depth === 'none') {
            $r[] = 'html:is(.dark,:root){--lg-shadow:0 0 #0000}';
        } elseif ($depth === 'deep') {
            $r[] = 'html:is(.dark,:root){--lg-shadow:0 28px 64px -16px rgb(0 0 0 / .8)}';
        }

        $cards = self::scope(self::CARDS, 'html:is(.dark,:root) body ');
        $finish = $s['card_finish'] ?? 'liquid';
        if ($finish === 'outline') {
            $r[] = $cards . '{background:transparent !important;box-shadow:none !important;-webkit-backdrop-filter:none !important;backdrop-filter:none !important;border:1px solid rgb(var(--ng-border) / .38) !important}';
        } elseif ($finish === 'matte') {
            $r[] = $cards . '{background:rgb(var(--ng-surface) / .62) !important;box-shadow:none !important;border-color:transparent !important}';
        }

        if (($inset = self::n($s, 'card_inset')) !== null) {
            $r[] = 'html body .fi-section-content-ctn, html body .fi-section-content, html body .ng-srv{padding:' . $inset . 'px !important}';
        }
        if (! ($s['card_lift'] ?? true)) {
            $r[] = 'html body .ng-srv:hover, html body .fi-section:hover, html body .fi-wi-stats-overview-stat:hover{transform:none !important}';
        }

        return implode("\n", $r);
    }

    private static function atmosphere(array $s): string
    {
        $r = [];

        if (($orb = self::n($s, 'orb_strength')) !== null) {
            $r[] = 'html body .ng-aurora{opacity:' . self::decimal($orb / 100) . '}';
        }

        $pace = ['calm' => [64, 84, 104], 'lively' => [16, 22, 28]][$s['orb_pace'] ?? ''] ?? null;
        if ($pace !== null) {
            foreach ($pace as $i => $seconds) {
                $r[] = 'html body .ng-orb--' . ($i + 1) . '{animation-duration:' . $seconds . 's !important}';
            }
        }

        return implode("\n", $r);
    }

    private static function typography(array $s): string
    {
        $r = [];
        $name = (string) ($s['font_family'] ?? '');
        $name = preg_match('/^[A-Za-z0-9 _-]{1,60}$/D', $name) === 1 ? $name : '';

        $stack = match ($s['ui_font'] ?? 'neon') {
            'system' => 'system-ui, -apple-system, "Segoe UI", Roboto, sans-serif',
            'serif' => 'Georgia, "Times New Roman", serif',
            'mono' => 'ui-monospace, "JetBrains Mono", Consolas, monospace',
            'custom' => $name !== '' ? '"' . $name . '", system-ui, sans-serif' : null,
            default => null,
        };

        if ($stack !== null) {
            $file = (string) ($s['font_file'] ?? '');
            if (($s['ui_font'] ?? '') === 'custom' && $file !== '' && Studio::validAsset($file)) {
                $r[] = '@font-face{font-family:"' . $name . '";src:url("' . $file . '") format("woff2");font-display:swap}';
            }
            $r[] = ':root{--ng-font-ui:' . $stack . '}';
            $r[] = 'html body, html body .fi-body, html body button, html body input, html body select, html body textarea{font-family:' . $stack . ' !important}';
        }

        if (($scale = self::n($s, 'text_scale')) !== null) {
            $r[] = 'html{font-size:' . $scale . 'px !important}';
        }
        if (($leading = self::n($s, 'text_leading')) !== null) {
            $r[] = 'html body{line-height:' . self::decimal($leading / 100) . ' !important}';
        }

        return implode("\n", $r);
    }

    private static function shape(array $s): string
    {
        $r = [];

        if (($corner = self::n($s, 'control_corner')) !== null) {
            $controls = ['.fi-input-wrp', '.fi-select-input', '.fi-ta-search-field', '.fi-fo-field-wrp input', '.fi-fo-field-wrp select', '.fi-fo-field-wrp textarea', '.fi-btn', '.fi-btn-group .fi-btn', '.ng-search', '.ng-bar__select'];
            $r[] = self::scope($controls) . '{border-radius:' . $corner . 'px !important}';
        }

        if (($line = self::n($s, 'line_weight')) !== null) {
            $frames = array_merge(self::CARDS, ['.fi-modal-window', '.fi-sidebar', '.fi-topbar', '.fi-simple-card']);
            $r[] = self::scope($frames, 'html:is(.dark,:root) body ') . '{border-width:' . $line . 'px !important}';
        }

        if (($rhythm = self::n($s, 'page_rhythm')) !== null) {
            $r[] = 'html body .fi-page-content, html body .fi-page{gap:' . $rhythm . 'px !important}';
        }

        return implode("\n", $r);
    }

    private static function menus(array $s): string
    {
        $r = [];

        if (($corner = self::n($s, 'menu_corner')) !== null) {
            $r[] = 'html body .fi-sidebar-nav-item, html body .fi-sidebar-item-button, html body .fi-sidebar-group-button{border-radius:' . $corner . 'px !important}';
        }
        if (! ($s['menu_glow'] ?? true)) {
            $r[] = 'html body .fi-sidebar-nav-item-active, html body .fi-sidebar-item-active, html body .fi-sidebar-nav-item[aria-current="page"]{box-shadow:none !important;border-color:transparent !important}';
        }

        return implode("\n", $r);
    }

    private static function terminal(array $s): string
    {
        $corner = self::n($s, 'term_corner');

        return $corner === null ? '' : 'html body #terminal{border-radius:' . $corner . 'px !important}';
    }

    private static function welcome(array $s): string
    {
        $r = [];

        if (($width = self::n($s, 'welcome_width')) !== null) {
            $r[] = 'html body[data-ng-auth] .fi-simple-main{max-width:' . $width . 'px !important;width:100%}';
        }

        $image = (string) ($s['welcome_image'] ?? '');
        if ($image !== '' && Studio::validAsset($image)) {
            $side = ($s['welcome_layout'] ?? 'stacked') === 'side';
            $paint = 'content:"";position:fixed;z-index:-1;pointer-events:none;background:url("' . $image . '") center/cover no-repeat;';
            $r[] = 'body[data-ng-auth]::before{' . $paint . ($side
                ? 'inset:0 50% 0 0;-webkit-mask-image:linear-gradient(90deg,#000 72%,transparent);mask-image:linear-gradient(90deg,#000 72%,transparent)'
                : 'inset:0;opacity:.5') . '}';
            if ($side) {
                $r[] = '@media (max-width:900px){body[data-ng-auth]::before{inset:0;opacity:.35;-webkit-mask-image:none;mask-image:none}}';
                $r[] = '@media (min-width:901px){html body[data-ng-auth] .fi-simple-layout{padding-inline-start:50%}}';
            }
        }

        return implode("\n", $r);
    }
}
