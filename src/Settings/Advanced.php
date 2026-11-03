<?php

namespace MuichiroDev\NeonGlass\Settings;

/**
 * @deprecated Kept only so a stale PHP opcache from 4.2.x cannot crash the panel after an
 *             upgrade. The real settings live in {@see Studio}.
 */
final class Advanced
{
    public const GROUPS = [];

    public const SPECS = [];

    public static function cleanText(string $value, int $max): string
    {
        return Studio::cleanText($value, $max);
    }

    public static function validAsset(string $value): bool
    {
        return Studio::validAsset($value);
    }
}
