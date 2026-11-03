<?php

namespace MuichiroDev\NeonGlass\Http\Controllers;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Serves NeonGlass's own CSS, JS and fonts straight from the plugin folder.
 *
 * Replaces copying files into public/plugins/neonglass, which silently left the theme
 * unstyled whenever public/ was not writable (typical on Docker). Only a fixed
 * whitelist of files can be requested.
 */
final class AssetController
{
    private const FIXED = [
        'neonglass.css' => ['css/neonglass.css', 'text/css; charset=UTF-8'],
        'neonglass.js' => ['js/neonglass.js', 'application/javascript; charset=UTF-8'],
    ];

    public function __invoke(string $path): BinaryFileResponse
    {
        if (isset(self::FIXED[$path])) {
            [$relative, $type] = self::FIXED[$path];
            $versioned = request()->query('v') !== null;
            $cache = $versioned ? 'public, max-age=31536000, immutable' : 'public, max-age=300';
        } elseif (preg_match('#^fonts/([A-Za-z0-9._-]+\.woff2)$#', $path, $m)) {
            $relative = 'fonts/' . $m[1];
            $type = 'font/woff2';
            $cache = 'public, max-age=604800';
        } else {
            abort(404);
        }

        $file = plugin_path('neonglass', $relative);

        abort_unless(is_string($file) && is_file($file), 404);

        $response = response()->file($file, [
            'Content-Type' => $type,
            'Cache-Control' => $cache,
            'X-Content-Type-Options' => 'nosniff',
        ]);
        $response->setAutoEtag();

        return $response;
    }
}
