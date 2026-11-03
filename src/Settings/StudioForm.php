<?php

namespace MuichiroDev\NeonGlass\Settings;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;

/**
 * Builds the "Studio" sections of the plugin settings from Studio::SPECS.
 */
final class StudioForm
{
    /** @return array<int, Section> */
    public static function sections(): array
    {
        $byGroup = [];
        foreach (Studio::SPECS as $key => $spec) {
            $byGroup[$spec['group']][] = self::component($key, $spec);
        }

        $out = [];
        foreach (Studio::GROUPS as $group) {
            if (empty($byGroup[$group])) {
                continue;
            }

            $section = Section::make(self::text('st_group_' . $group))
                ->description(self::text('st_group_' . $group . '_help', ''))
                ->columns(['default' => 1, 'md' => 2])
                ->collapsible()
                ->collapsed()
                ->schema($byGroup[$group]);

            $out[] = $section;
        }

        return $out;
    }

    /** @param array<string, mixed> $spec */
    private static function component(string $key, array $spec): mixed
    {
        $component = match ($spec['type']) {
            'bool' => Toggle::make($key)->inline(false),
            'color' => ColorPicker::make($key),
            'enum' => Select::make($key)
                ->options(self::choices($spec['values']))
                ->native(false)
                ->selectablePlaceholder(false),
            'int' => TextInput::make($key)
                ->numeric()
                ->minValue($spec['min'])
                ->maxValue($spec['max'])
                ->placeholder(self::text('st_auto')),
            'asset' => TextInput::make($key)
                ->maxLength($spec['max'])
                ->placeholder('/storage/file  ·  https://…'),
            default => TextInput::make($key)->maxLength($spec['max']),
        };

        $component->label(self::text('st_' . $key));

        $help = self::text('st_' . $key . '_help', '');
        if ($help !== '') {
            $component->helperText($help);
        }

        return $component;
    }

    /**
     * @param  array<int, string>  $values
     * @return array<string, string>
     */
    private static function choices(array $values): array
    {
        $out = [];
        foreach ($values as $value) {
            $out[$value] = self::text('st_opt_' . $value);
        }

        return $out;
    }

    private static function text(string $key, ?string $fallback = null): string
    {
        $full = 'neonglass::strings.' . $key;
        $value = trans($full);

        return is_string($value) && $value !== $full ? $value : ($fallback ?? $key);
    }
}
