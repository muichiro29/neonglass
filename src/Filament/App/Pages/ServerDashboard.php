<?php

namespace MuichiroDev\NeonGlass\Filament\App\Pages;

use App\Enums\SubuserPermission;
use App\Models\Server;
use App\Repositories\Daemon\DaemonServerRepository;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use MuichiroDev\NeonGlass\Services\WingsServers;

/**
 * The NeonGlass server dashboard: a real Filament page (Livewire + Blade) instead of
 * decorating Pelican's server-list table from JavaScript, so it no longer depends on
 * Filament's internal CSS classes.
 */
class ServerDashboard extends Page
{
    protected static ?string $slug = 'neonglass';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'neonglass::dashboard';

    /** Admins only: show every server instead of just their own. */
    public string $scope = 'mine';

    public static function canAccess(): bool
    {
        return auth()->check();
    }

    public function getTitle(): string|Htmlable
    {
        return trans('neonglass::strings.js_dashboard');
    }

    public function getHeading(): string|Htmlable
    {
        return '';
    }

    /** Pelican's own server list route name (differs between Pelican releases). */
    public static function nativeListRoute(): string
    {
        try {
            return \App\Filament\App\Resources\Servers\Pages\ListServers::getRouteName(Filament::getPanel('app'));
        } catch (\Throwable) {
            return 'filament.app.resources.servers.index';
        }
    }

    public function updatedScope(): void
    {
        if (! in_array($this->scope, ['mine', 'all'], true) || ! $this->isAdmin()) {
            $this->scope = 'mine';
        }
    }

    /**
     * NeonGlass's resolved settings (layout, hero...). Falls back to the config defaults.
     *
     * @return array<string, mixed>
     */
    public function neonglassSettings(): array
    {
        try {
            $plugin = Filament::getPanel('app')->getPlugin('neonglass');

            return $plugin->settings();
        } catch (\Throwable) {
            return (array) config('neonglass', []);
        }
    }

    public function isAdmin(): bool
    {
        try {
            return (bool) user()?->isAdmin();
        } catch (\Throwable) {
            return false;
        }
    }

    protected function query(): Builder
    {
        $user = user();

        $query = Server::query()->with(['allocation', 'egg', 'node'])->orderBy('name');

        if (! ($this->isAdmin() && $this->scope === 'all')) {
            $query->where(function (Builder $q) use ($user) {
                $q->where('owner_id', $user->id)
                    ->orWhereHas('subusers', fn (Builder $sub) => $sub->where('user_id', $user->id));
            });
        }

        return $query;
    }

    /**
     * Everything the view needs, computed once per render.
     *
     * @return array<int, array<string, mixed>>
     */
    public function servers(): array
    {
        $servers = $this->query()->get();

        // One request per node feeds retrieveStatus() / retrieveResources() below.
        try {
            WingsServers::warm($servers);
        } catch (\Throwable $e) {
            report($e);
        }

        $out = [];

        foreach ($servers as $server) {
            // Panel-side state (installing, suspended...) wins over the daemon's container state.
            $state = null;
            try {
                $raw = $server->status;
                $state = is_object($raw) ? ($raw->value ?? null) : (is_string($raw) ? $raw : null);
            } catch (\Throwable) {
                $state = null;
            }

            if ($state !== null && $state !== '') {
                $status = (string) $state;
            } else {
                try {
                    $status = (string) ($server->retrieveStatus()->value ?? 'offline');
                } catch (\Throwable) {
                    $status = 'unknown';
                }
            }

            $resources = [];
            if (in_array($status, ['running', 'starting', 'stopping'], true)) {
                try {
                    $resources = (array) $server->retrieveResources();
                } catch (\Throwable) {
                    $resources = [];
                }
            }

            $known = $status !== 'unknown' && ($state === null || $state === '');
            $kind = $this->kind($status);

            $can = [
                'start' => $this->allowed(SubuserPermission::ControlStart, $server),
                'stop' => $this->allowed(SubuserPermission::ControlStop, $server),
                'restart' => $this->allowed(SubuserPermission::ControlRestart, $server),
            ];

            // One primary power action, like a phone remote: stop while it runs, start while it is off.
            $power = null;
            if (in_array($status, ['running', 'starting'], true) && $can['stop']) {
                $power = 'stop';
            } elseif ($status === 'offline' && $can['start']) {
                $power = 'start';
            }

            // Meter fill (0-100). Unlimited limits fall back to the raw value, clamped.
            $cpuPct = 0.0;
            $ramPct = 0.0;
            if ($known) {
                try {
                    $cpuAbs = (float) ($resources['cpu_absolute'] ?? 0);
                    $cpuLimit = (float) ($server->cpu ?? 0);
                    $cpuPct = $cpuLimit > 0 ? ($cpuAbs / $cpuLimit) * 100 : $cpuAbs;

                    $memUsed = (float) ($resources['memory_bytes'] ?? 0);
                    $memLimit = (float) ($resources['memory_limit_bytes'] ?? 0);
                    if ($memLimit <= 0) {
                        $memLimit = (float) ($server->memory ?? 0) * 1048576;
                    }
                    $ramPct = $memLimit > 0 ? ($memUsed / $memLimit) * 100 : 0.0;
                } catch (\Throwable) {
                    $cpuPct = $ramPct = 0.0;
                }
            }

            $allocation = $server->allocation;
            $game = (string) ($server->egg?->name ?? '');

            $out[] = [
                'uuid' => $server->uuid,
                'name' => (string) $server->name,
                'game' => $game,
                'node' => (string) ($server->node?->name ?? ''),
                'address' => $allocation ? (($allocation->ip_alias ?: $allocation->ip) . ':' . $allocation->port) : null,
                'status' => $status,
                'kind' => $kind,
                'url' => $this->serverUrl($server),
                'mine' => (int) $server->owner_id === (int) user()->id,
                'hue' => abs(crc32($game !== '' ? $game : (string) $server->name)) % 360,
                'icon' => $this->icon($server),
                'cpu' => $known ? number_format((float) ($resources['cpu_absolute'] ?? 0), 1) . '%' : '—',
                'ram' => $known ? $this->bytes((float) ($resources['memory_bytes'] ?? 0)) : '—',
                'cpu_pct' => (int) round(max(0, min(100, $cpuPct))),
                'ram_pct' => (int) round(max(0, min(100, $ramPct))),
                'power' => $power,
                'can' => $can,
            ];
        }

        return $out;
    }

    /** ok | busy | bad | unknown — drives the status colour. */
    protected function kind(string $status): string
    {
        return match ($status) {
            'running' => 'ok',
            'starting', 'stopping', 'installing', 'restoring_backup' => 'busy',
            'offline', 'suspended', 'install_failed', 'reinstall_failed' => 'bad',
            default => 'unknown',
        };
    }

    protected function bytes(float $bytes): string
    {
        $mb = $bytes / 1048576;

        return $mb >= 1024 ? number_format($mb / 1024, 1) . ' GB' : number_format($mb, 0) . ' MB';
    }

    /** The egg's icon when it is a plain image URL / data URI; otherwise the view draws a tinted cube. */
    protected function icon(Server $server): ?string
    {
        try {
            $icon = $server->egg?->icon;
        } catch (\Throwable) {
            return null;
        }

        if (is_string($icon) && preg_match('#^(https?://|/)[^\s"\'<>]+$#i', $icon)) {
            return $icon;
        }

        if (is_string($icon) && preg_match('#^data:image/(png|jpeg|webp|gif|svg\+xml);base64,[A-Za-z0-9+/=]+$#', $icon)) {
            return $icon;
        }

        return null;
    }

    /**
     * Brand logo URL for the dashboard hero (Filament panel logo when set).
     */
    public function brandLogo(): ?string
    {
        try {
            $plugin = Filament::getPanel('app')->getPlugin('neonglass');
            if (method_exists($plugin, 'brandLogoUrl')) {
                return $plugin->brandLogoUrl();
            }
        } catch (\Throwable) {
            //
        }

        return null;
    }

    /**
     * Lightweight panel status strip for the dashboard (safe, read-only).
     *
     * @return array{brand: string, version: string, php: string, servers: int, online: int, offline: int, admin: bool}
     */
    public function panelStatus(): array
    {
        $servers = [];
        try {
            $servers = $this->servers();
        } catch (\Throwable) {
            $servers = [];
        }

        $online = 0;
        $offline = 0;
        foreach ($servers as $s) {
            if (($s['kind'] ?? '') === 'ok') {
                $online++;
            } elseif (($s['kind'] ?? '') === 'bad') {
                $offline++;
            }
        }

        $version = '—';
        try {
            if (class_exists(\App\Services\Helpers\SoftwareVersionService::class)) {
                $svc = app(\App\Services\Helpers\SoftwareVersionService::class);
                if (method_exists($svc, 'currentPanelVersion')) {
                    $version = (string) ($svc->currentPanelVersion() ?: '—');
                }
            }
        } catch (\Throwable) {
            $version = '—';
        }

        return [
            'brand' => (string) config('app.name', 'Pelican'),
            'version' => $version,
            'php' => PHP_VERSION,
            'servers' => count($servers),
            'online' => $online,
            'offline' => $offline,
            'admin' => $this->isAdmin(),
        ];
    }

    /**
     * Links for the mobile bottom bar and menu sheet.
     *
     * @return array{new: ?string, account: ?string, admin: ?string, logout: ?string}
     */
    public function chrome(): array
    {
        $new = $account = $admin = $logout = null;

        try {
            if (\App\Filament\Admin\Resources\Servers\ServerResource::canCreate()) {
                $new = \App\Filament\Admin\Resources\Servers\ServerResource::getUrl('create', panel: 'admin');
            }
        } catch (\Throwable) {
        }

        try {
            $account = \App\Filament\Pages\Auth\EditProfile::getUrl(panel: 'app');
        } catch (\Throwable) {
        }

        try {
            if (user()->canAccessPanel(Filament::getPanel('admin'))) {
                $admin = Filament::getPanel('admin')->getUrl();
            }
        } catch (\Throwable) {
        }

        try {
            $logout = Filament::getPanel('app')->getLogoutUrl();
        } catch (\Throwable) {
        }

        return ['new' => $new, 'account' => $account, 'admin' => $admin, 'logout' => $logout];
    }

    protected function allowed(SubuserPermission $permission, Server $server): bool
    {
        try {
            return (bool) user()->can($permission, $server);
        } catch (\Throwable) {
            return false;
        }
    }

    protected function serverUrl(Server $server): ?string
    {
        try {
            return Filament::getPanel('server')->getUrl($server);
        } catch (\Throwable) {
            return null;
        }
    }

    public function power(string $uuid, string $action): void
    {
        $permission = match ($action) {
            'start' => SubuserPermission::ControlStart,
            'stop', 'kill' => SubuserPermission::ControlStop,
            'restart' => SubuserPermission::ControlRestart,
            default => null,
        };

        if ($permission === null) {
            return;
        }

        // The server must be one this user may see, and they must hold the permission.
        $server = $this->query()->where('uuid', $uuid)->first();

        if (! $server || ! $this->allowed($permission, $server)) {
            Notification::make()->title(trans('neonglass::strings.dash_power_denied'))->danger()->send();

            return;
        }

        try {
            if ($server->isInConflictState()) {
                Notification::make()->title(trans('neonglass::strings.dash_power_busy'))->warning()->send();

                return;
            }

            app(DaemonServerRepository::class)->setServer($server)->power($action)->throw();

            Notification::make()->title(trans('neonglass::strings.dash_power_sent'))->success()->send();
        } catch (\Throwable $e) {
            report($e);
            Notification::make()->title(trans('neonglass::strings.dash_power_failed'))->danger()->send();
        }
    }
}
