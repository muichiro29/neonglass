<?php

namespace MuichiroDev\NeonGlass\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use MuichiroDev\NeonGlass\Filament\App\Pages\ServerDashboard;

/**
 * Sends the native server list to the NeonGlass dashboard.
 *
 * Escape hatch: append ?neonglass_legacy=1 to the server list URL to see Pelican's own list.
 * Any failure falls through to the native list.
 */
final class DashboardHome
{
    public function handle(Request $request, Closure $next): mixed
    {
        try {
            if (
                $request->isMethod('GET')
                && ! $request->boolean('neonglass_legacy')
                && ! $request->ajax()
                && ! $request->header('X-Livewire')
                && auth()->check()
                && $request->routeIs(ServerDashboard::nativeListRoute())
            ) {
                return redirect(ServerDashboard::getUrl(panel: 'app'));
            }
        } catch (\Throwable $e) {
            try {
                logger()->warning('[NeonGlass] dashboard redirect failed: ' . $e->getMessage());
            } catch (\Throwable) {
                // logging must not fail either
            }
        }

        return $next($request);
    }
}
