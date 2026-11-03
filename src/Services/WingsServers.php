<?php

namespace MuichiroDev\NeonGlass\Services;

use App\Enums\ContainerStatus;
use App\Models\Node;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * One Wings request per node instead of one or two per server.
 *
 * Wings lists every server it runs, with state and utilization, at /api/servers.
 * That single list feeds Pelican's own per-server status/resources caches, so the
 * dashboard (and anything else calling retrieveStatus()/retrieveResources()) does not
 * fan out into N requests. An unreachable node costs one short timeout, not one per server.
 *
 * Approach adapted from Pelican Simplify (MIT).
 */
final class WingsServers
{
    /** Pelican keeps a server's status and resources for 15 seconds; so does the node list. */
    public const TTL = 15;

    /**
     * @return array{at: float, servers: array<string, array{state: mixed, utilization: array<string, mixed>}>}|null
     */
    public static function forNode(Node $node): ?array
    {
        try {
            if ($node->isUnderMaintenance()) {
                return null;
            }
        } catch (\Throwable) {
            return null;
        }

        $key = 'neonglass.wings-servers.' . $node->id;
        $stored = Cache::get($key);

        if (is_array($stored) && array_key_exists('value', $stored)) {
            return $stored['value'];
        }

        $value = null;

        try {
            $response = Http::daemon($node)->connectTimeout(1)->timeout(2)->get('/api/servers');
            $list = $response->successful() ? $response->json() : null;

            if (is_array($list) && array_is_list($list)) {
                $servers = [];
                foreach ($list as $index => $entry) {
                    $uuid = $entry['configuration']['uuid'] ?? $entry['uuid'] ?? null;
                    $servers[is_string($uuid) ? $uuid : '#' . $index] = [
                        'state' => $entry['state'] ?? null,
                        'utilization' => is_array($entry['utilization'] ?? null) ? $entry['utilization'] : [],
                    ];
                }
                $value = ['at' => microtime(true), 'servers' => $servers];
            }
        } catch (\Throwable) {
            $value = null;
        }

        // Misses are cached too, so a dead node is not retried on every request.
        Cache::put($key, ['value' => $value], self::TTL);

        return $value;
    }

    /**
     * Fills Pelican's per-server status and resource caches for these servers from their nodes' lists.
     *
     * @param  iterable<\App\Models\Server>  $servers
     */
    public static function warm(iterable $servers): void
    {
        $byNode = [];

        foreach ($servers as $server) {
            if ($server->node) {
                $byNode[(int) $server->node_id][] = $server;
            }
        }

        foreach ($byNode as $group) {
            $snapshot = self::forNode($group[0]->node);

            if ($snapshot === null) {
                continue;
            }

            $ttl = max(1, self::TTL - (int) (microtime(true) - (float) $snapshot['at']));

            foreach ($group as $server) {
                $entry = $snapshot['servers'][$server->uuid] ?? null;

                if ($entry === null) {
                    continue; // Unknown to Wings: Pelican asks about it itself.
                }

                Cache::put(
                    "servers.$server->uuid.status",
                    ContainerStatus::tryFrom((string) $entry['state']) ?? ContainerStatus::Missing,
                    $ttl
                );
                Cache::put("servers.$server->uuid.resources", $entry['utilization'], $ttl);
            }
        }
    }
}
