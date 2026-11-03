<?php

declare(strict_types=1);

namespace MuichiroDev\NeonGlass\Settings;

use MuichiroDev\NeonGlass\NeonGlassPlugin;

/**
 * Built-in presets ship as JSON under presets/.
 * User-saved presets live in storage/app/neonglass/presets.json.
 */
final class PresetStore
{
    public static function storagePath(): string
    {
        return storage_path('app/neonglass/presets.json');
    }

    public static function builtInDirectory(): string
    {
        return dirname(__DIR__, 2) . '/presets';
    }

    /**
     * Keys that belong inside a preset (all theme settings).
     *
     * @return list<string>
     */
    public static function presettableKeys(): array
    {
        return array_keys(NeonGlassPlugin::SETTINGS);
    }

    /**
     * @return array<string, array{label: string, builtin: bool, settings: array<string, mixed>}>
     */
    public static function all(): array
    {
        $out = [];

        foreach (self::builtIn() as $id => $preset) {
            $out[$id] = $preset;
        }

        foreach (self::custom() as $id => $preset) {
            // custom may override a same-id built-in if the admin wants
            $out[$id] = $preset;
        }

        return $out;
    }

    /**
     * @return array<string, array{label: string, builtin: bool, settings: array<string, mixed>}>
     */
    public static function builtIn(): array
    {
        $dir = self::builtInDirectory();
        if (! is_dir($dir)) {
            return [];
        }

        $out = [];
        foreach (glob($dir . '/*.json') ?: [] as $file) {
            $id = basename($file, '.json');
            $decoded = self::decodeFile($file);
            if ($decoded === null) {
                continue;
            }
            $out[$id] = [
                'label' => (string) ($decoded['label'] ?? ucfirst(str_replace(['-', '_'], ' ', $id))),
                'builtin' => true,
                'settings' => self::normalizeSettings($decoded['settings'] ?? $decoded),
            ];
        }

        ksort($out);

        return $out;
    }

    /**
     * @return array<string, array{label: string, builtin: bool, settings: array<string, mixed>}>
     */
    public static function custom(): array
    {
        $path = self::storagePath();
        if (! is_file($path)) {
            return [];
        }

        try {
            $raw = json_decode((string) file_get_contents($path), true, 32, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return [];
        }

        if (! is_array($raw)) {
            return [];
        }

        $out = [];
        foreach ($raw as $id => $entry) {
            if (! is_string($id) || ! is_array($entry)) {
                continue;
            }
            $id = self::slug($id);
            if ($id === '') {
                continue;
            }
            $out[$id] = [
                'label' => (string) ($entry['label'] ?? $id),
                'builtin' => false,
                'settings' => self::normalizeSettings($entry['settings'] ?? []),
            ];
        }

        ksort($out);

        return $out;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function get(string $id): ?array
    {
        $all = self::all();
        $id = self::slug($id);

        return $all[$id]['settings'] ?? null;
    }

    /**
     * Options for a Select: id => "Label (built-in|custom)".
     *
     * @return array<string, string>
     */
    public static function options(bool $includeEmpty = true, bool $customOnly = false): array
    {
        $source = $customOnly ? self::custom() : self::all();
        $opts = [];
        if ($includeEmpty) {
            $opts[''] = '—';
        }
        foreach ($source as $id => $preset) {
            $suffix = $preset['builtin'] ? '' : ' ★';
            $opts[$id] = $preset['label'] . $suffix;
        }

        return $opts;
    }

    /**
     * Save (or overwrite) a custom preset from the current form data.
     *
     * @param  array<string, mixed>  $settings
     */
    public static function saveCustom(string $name, array $settings): string
    {
        $id = self::slug($name);
        if ($id === '') {
            throw new \InvalidArgumentException('Invalid preset name.');
        }

        // Do not allow overwriting built-in ids with the same slug via the UI
        // — use a distinct custom id instead.
        if (isset(self::builtIn()[$id])) {
            $id = 'custom-' . $id;
        }

        $all = self::readCustomFile();
        $all[$id] = [
            'label' => trim($name) !== '' ? trim($name) : $id,
            'settings' => self::normalizeSettings($settings),
            'updated_at' => gmdate('c'),
        ];

        self::writeCustomFile($all);

        return $id;
    }

    public static function deleteCustom(string $id): bool
    {
        $id = self::slug($id);
        $all = self::readCustomFile();
        if (! isset($all[$id])) {
            return false;
        }
        unset($all[$id]);
        self::writeCustomFile($all);

        return true;
    }

    public static function slug(string $name): string
    {
        $name = strtolower(trim($name));
        $name = preg_replace('/[^a-z0-9]+/', '-', $name) ?? '';
        $name = trim($name, '-');

        return substr($name, 0, 48);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function normalizeSettings(array $input): array
    {
        $out = [];
        foreach (self::presettableKeys() as $key) {
            if (! array_key_exists($key, $input)) {
                continue;
            }
            $out[$key] = NeonGlassPlugin::coerce($key, $input[$key]);
        }

        return $out;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected static function decodeFile(string $path): ?array
    {
        try {
            $data = json_decode((string) file_get_contents($path), true, 32, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return null;
        }

        return is_array($data) ? $data : null;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected static function readCustomFile(): array
    {
        $path = self::storagePath();
        if (! is_file($path)) {
            return [];
        }
        try {
            $raw = json_decode((string) file_get_contents($path), true, 32, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return [];
        }

        return is_array($raw) ? $raw : [];
    }

    /**
     * @param  array<string, array<string, mixed>>  $data
     */
    protected static function writeCustomFile(array $data): void
    {
        $path = self::storagePath();
        $dir = dirname($path);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new \RuntimeException('Failed to encode presets.');
        }

        $tmp = $path . '.tmp';
        if (file_put_contents($tmp, $json . "\n", LOCK_EX) === false) {
            throw new \RuntimeException('Failed to write presets.');
        }
        rename($tmp, $path);
    }
}
