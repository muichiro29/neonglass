# NeonGlass

**A modern liquid-glass theme for the Pelican Panel.**

Author: **Muichiro Dev**  
License: MIT  

---

NeonGlass gives Pelican Panel a modern liquid-glass interface with frosted surfaces, specular highlights, neon accents and floating colour orbs. It styles the admin, app and server panels without modifying Pelican core files.

## NeonGlass Studio

Open **Plugins → NeonGlass → Settings** to customize the theme. The settings include glass material, atmosphere, day and night colours, typography, layout, sidebar and menus, terminal, sign-in screen and identity. Empty fields and "Auto" / "Default" options preserve the theme defaults. Settings are stored in `.env` as `NEONGLASS_*`, work with presets, and terminal options are applied live to the console.

## Features

- **Liquid-glass design** — Refractive frosted surfaces, specular highlights, soft inner glow, neon accents and floating colour orbs across the panel.
- **Three-panel support** — Styles the admin, app and server panels.
- **Server dashboard** — Glass hero, live status chips, grid / list / compact layouts, server cards with CPU and RAM meters, search, filters and power controls.
- **Dark and light modes** — Includes configurable colours, glass intensity, corner radius and 11 neon accent hues.
- **Theme Studio and presets** — Customize material, atmosphere, colours, typography, layout, menus, terminal, sign-in and identity; save and apply presets.
- **Server console** — Curated terminal palettes and optional glow.
- **Multi-language** — English, Spanish, French, Portuguese (Brazil) and German.
- **Accessibility** — Respects reduced-motion and reduced-transparency preferences.
- **Self-contained** — Fonts and assets are bundled; no external CDNs.
- **Persistent settings** — Native Filament settings are stored as `NEONGLASS_*` environment variables.

## Installation

### From ZIP (recommended)

1. Download the release ZIP.
2. In the panel go to **Plugins → Import** and upload the ZIP.
3. Click **Install**.
4. Hard-refresh the browser (`Ctrl+Shift+R` / `Cmd+Shift+R`).

### Manual

```bash
cd /var/www/pelican/plugins   # or /var/www/html/plugins on the official Docker image
# place the neonglass folder here — the folder name MUST be exactly "neonglass"
php artisan p:plugin:install
```

Select **neonglass** when prompted.

> The plugin id and the root folder name must match. Renaming the folder after extraction will break loading.

## Settings

Admin → Plugins → **NeonGlass** → Settings.

Use the settings groups to tailor glass material, atmosphere, colours, typography, layout, sidebar and menus, terminal, sign-in screen and identity. Settings are stored as `NEONGLASS_*` environment variables.

## Languages

NeonGlass ships with translations for:

- English (`en`)
- Spanish (`es`)
- French (`fr`)
- Portuguese Brazil (`pt_BR`)
- German (`de`)

The panel locale is used automatically. Contributions for additional languages are welcome.

## Compatibility

- Requires a recent Pelican Panel that supports the plugin system (category `theme`).
- Designed for the three standard Filament panels: `admin`, `app`, `server`.
- Works alongside other theme plugins when “Apply NeonGlass component colours” is turned off.

## License

MIT © Muichiro Dev.

You are free to use, modify and redistribute. Attribution is appreciated but not required.
