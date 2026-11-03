@php
    $cfg = $this->neonglassSettings();
    $servers = $this->servers();
    $chrome = $this->chrome();
    $brandLogo = $this->brandLogo();
    $status = ($cfg['dashboard_status'] ?? true) ? $this->panelStatus() : null;
    $counts = ['ok' => 0, 'busy' => 0, 'bad' => 0];
    foreach ($servers as $s) {
        if (isset($counts[$s['kind']])) {
            $counts[$s['kind']]++;
        }
    }
    $total = count($servers);
    $games = collect($servers)->pluck('game')->filter()->unique()->values()->all();
    $name = (string) (auth()->user()?->username ?? '');
    $brand = (string) config('app.name', 'Pelican');
    $t = fn (string $key) => trans('neonglass::strings.' . $key);
    $svg = fn (string $d, int $size = 16) => '<svg viewBox="0 0 24 24" width="' . $size . '" height="' . $size . '" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $d . '</svg>';
    $ico = [
        'grid' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        'list' => '<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>',
        'compact' => '<path d="M4 6h16M4 10h16M4 14h16M4 18h16"/>',
        'cpu' => '<rect x="5" y="5" width="14" height="14" rx="2"/><rect x="9" y="9" width="6" height="6"/><path d="M9 2v3M15 2v3M9 19v3M15 19v3M2 9h3M2 15h3M19 9h3M19 15h3"/>',
        'ram' => '<rect x="2" y="7" width="20" height="10" rx="2"/><path d="M6 11v2M10 11v2M14 11v2M18 11v2M6 17v2M18 17v2"/>',
        'game' => '<path d="M6 11h4M8 9v4"/><circle cx="15" cy="12" r="1"/><circle cx="18" cy="10" r="1"/><path d="M17.3 5H6.7a4 4 0 0 0-3.9 3.2L1.6 14a3 3 0 0 0 5.2 2.3L8 15h8l1.2 1.3A3 3 0 0 0 22.4 14l-1.2-5.8A4 4 0 0 0 17.3 5z"/>',
        'cube' => '<path d="M21 8 12 3 3 8v8l9 5 9-5z"/><path d="m3 8 9 5 9-5M12 13v8"/>',
        'play' => '<path d="M6 4l14 8-14 8z"/>',
        'stop' => '<rect x="6" y="6" width="12" height="12" rx="2"/>',
        'restart' => '<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/>',
        'servers' => '<rect x="3" y="3" width="18" height="7" rx="2"/><rect x="3" y="14" width="18" height="7" rx="2"/><path d="M7 6.5h.01M7 17.5h.01"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'shield' => '<path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
    ];
@endphp

<div
    class="ng-native"
    data-ng-native="1"
    x-data="{
        q: '',
        f: 'all',
        g: 'all',
        menu: false,
        sort: (() => { try { return localStorage.getItem('ng-sort') === 'status' ? 'status' : 'name'; } catch (e) { return 'name'; } })(),
        pins: (() => { try { const v = JSON.parse(localStorage.getItem('ng-pins') || '[]'); return Array.isArray(v) ? v : []; } catch (e) { return []; } })(),
        layout: (() => { try { const v = localStorage.getItem('ng-layout'); return ['grid','list','compact'].includes(v) ? v : @js($cfg['dashboard_layout'] ?? 'grid'); } catch (e) { return 'grid'; } })(),
        setSort(v) { this.sort = v; try { localStorage.setItem('ng-sort', v); } catch (e) {} },
        setLayout(v) { this.layout = v; try { localStorage.setItem('ng-layout', v); } catch (e) {} document.documentElement.setAttribute('data-ng-layout', v); },
        pinned(id) { return this.pins.includes(id); },
        togglePin(id) {
            this.pins = this.pins.includes(id) ? this.pins.filter((p) => p !== id) : [...this.pins, id];
            try { localStorage.setItem('ng-pins', JSON.stringify(this.pins)); } catch (e) {}
        },
        order(id, rank) { return (this.pinned(id) ? -1000 : 0) + (this.sort === 'status' ? rank : 0); },
        show(name, game, kind) {
            const q = this.q.trim().toLowerCase();
            if (q && !(name + ' ' + game).toLowerCase().includes(q)) return false;
            if (this.g !== 'all' && this.g !== game) return false;
            return this.f === 'all' || this.f === kind;
        },
        greeting() {
            const h = new Date().getHours();
            return h < 5 ? @js($t('js_greet_night')) : h < 12 ? @js($t('js_greet_morning')) : h < 19 ? @js($t('js_greet_afternoon')) : @js($t('js_greet_evening'));
        },
    }"
    x-init="document.documentElement.setAttribute('data-ng-layout', layout)"
    @keydown.window="if ($event.key === '/' && !$event.ctrlKey && !$event.metaKey && !$event.altKey && !/^(input|textarea|select)$/i.test($event.target.tagName) && !$event.target.isContentEditable) { $event.preventDefault(); $refs.search.focus(); } if ($event.key === 'Escape') menu = false;"
>
    {{-- Hero: full on desktop, a compact title on phones --}}
    @if ($cfg['dashboard_hero'] ?? true)
        <section id="ng-native-hero" class="ng-hero" aria-label="{{ $t('js_dashboard') }}">
            <div class="ng-hero__main">
                <div class="ng-hero__brand">
                    @if ($brandLogo)
                        <img class="ng-hero__logo" src="{{ $brandLogo }}" alt="{{ $brand }}" width="40" height="40" loading="lazy" decoding="async">
                    @endif
                    <div class="ng-hero__brand-text">
                        <div class="ng-hero__eyebrow" x-text="greeting()">{{ $t('js_welcome') }}</div>
                        <h2 class="ng-hero__title">
                            <span class="ng-hero__desk">{{ $name !== '' ? str_replace(':name', $name, $t('js_hello')) : $t('js_welcome') }}</span>
                            <span class="ng-hero__mob">{{ $brand }}</span>
                        </h2>
                    </div>
                </div>
                <p class="ng-hero__sub">
                    {{ $total > 0 ? $total . ' ' . ($total === 1 ? $t('js_server_one') : $t('js_servers')) : $t('js_empty') }}
                </p>
            </div>
            <div class="ng-hero__side">
                <div class="ng-chips">
                    @if ($total > 0)
                        <button type="button" class="ng-chip" :class="f === 'all' && 'is-active'" @click="f = 'all'"><i></i><b>{{ $total }}</b><span>{{ $t('js_servers') }}</span></button>
                    @endif
                    @if ($counts['ok'] > 0)
                        <button type="button" class="ng-chip ng-chip--ok" :class="f === 'ok' && 'is-active'" @click="f = f === 'ok' ? 'all' : 'ok'"><i></i><b>{{ $counts['ok'] }}</b><span>{{ $t('js_online') }}</span></button>
                    @endif
                    @if ($counts['busy'] > 0)
                        <button type="button" class="ng-chip ng-chip--busy" :class="f === 'busy' && 'is-active'" @click="f = f === 'busy' ? 'all' : 'busy'"><i></i><b>{{ $counts['busy'] }}</b><span>{{ $t('js_busy') }}</span></button>
                    @endif
                    @if ($counts['bad'] > 0)
                        <button type="button" class="ng-chip ng-chip--bad" :class="f === 'bad' && 'is-active'" @click="f = f === 'bad' ? 'all' : 'bad'"><i></i><b>{{ $counts['bad'] }}</b><span>{{ $t('js_offline') }}</span></button>
                    @endif
                </div>
                <div class="ng-tools">
                    <span class="ng-kbd"><kbd>/</kbd>{{ $t('js_search_hint') }}</span>
                    <div class="ng-seg" role="group" aria-label="{{ $t('js_layout') }}">
                        @foreach (['grid', 'list', 'compact'] as $mode)
                            <button type="button" title="{{ $t('js_' . $mode) }}" aria-label="{{ $t('js_' . $mode) }}"
                                    :aria-pressed="layout === '{{ $mode }}' ? 'true' : 'false'" @click="setLayout('{{ $mode }}')">{!! $svg($ico[$mode]) !!}</button>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

    @if ($status)
        <div class="ng-status" role="region" aria-label="{{ $t('status_label') }}">
            <div class="ng-status__item">
                <span class="ng-status__k">{{ $t('status_panel') }}</span>
                <span class="ng-status__v">{{ $status['brand'] }}</span>
            </div>
            <div class="ng-status__item">
                <span class="ng-status__k">{{ $t('status_version') }}</span>
                <span class="ng-status__v">{{ $status['version'] }}</span>
            </div>
            <div class="ng-status__item">
                <span class="ng-status__k">{{ $t('status_servers') }}</span>
                <span class="ng-status__v">{{ $status['servers'] }} <small>({{ $status['online'] }} {{ $t('js_online') }})</small></span>
            </div>
            @if ($status['admin'])
                <div class="ng-status__item">
                    <span class="ng-status__k">{{ $t('status_php') }}</span>
                    <span class="ng-status__v">PHP {{ $status['php'] }}</span>
                </div>
            @endif
        </div>
    @endif

    {{-- Game filter (scrolls sideways on phones) --}}
    @if (count($games) > 1)
        <div class="ng-games" role="group" aria-label="{{ $t('dash_filter_game') }}">
            <button type="button" class="ng-gchip" :class="g === 'all' && 'is-active'" @click="g = 'all'">{{ $t('dash_filter_all') }}</button>
            @foreach ($games as $game)
                <button type="button" class="ng-gchip" :class="g === @js($game) && 'is-active'" @click="g = g === @js($game) ? 'all' : @js($game)">{{ $game }}</button>
            @endforeach
        </div>
    @endif

    <div class="ng-bar">
        <input type="search" x-ref="search" x-model="q" class="ng-bar__search" placeholder="{{ $t('dash_search') }}" aria-label="{{ $t('dash_search') }}">
        <select class="ng-bar__select" x-model="sort" @change="setSort($event.target.value)" aria-label="{{ $t('dash_sort') }}">
            <option value="name">{{ $t('dash_sort_name') }}</option>
            <option value="status">{{ $t('dash_sort_status') }}</option>
        </select>
        @if ($this->isAdmin())
            <select wire:model.live="scope" class="ng-bar__select" aria-label="{{ $t('dash_scope') }}">
                <option value="mine">{{ $t('dash_scope_mine') }}</option>
                <option value="all">{{ $t('dash_scope_all') }}</option>
            </select>
        @endif
    </div>

    <div wire:poll.15s.visible>
        @if ($total === 0)
            <div class="ng-empty">{{ $t('js_empty') }}</div>
        @else
            <div class="ng-srv-grid" :data-layout="layout">
                @foreach ($servers as $s)
                    <article
                        wire:key="ng-srv-{{ $s['uuid'] }}"
                        class="ng-srv ng-srv--{{ $s['kind'] }}"
                        style="--h: {{ $s['hue'] }}"
                        x-show="show(@js($s['name']), @js($s['game']), @js($s['kind']))"
                        :style="{ order: order(@js($s['uuid']), {{ ['ok' => 0, 'busy' => 1, 'unknown' => 2, 'bad' => 3][$s['kind']] ?? 2 }}) }"
                        :class="pinned(@js($s['uuid'])) && 'is-pinned'"
                    >
                        <span class="ng-srv__avatar" aria-hidden="true">
                            @if ($s['icon'])
                                <img src="{{ $s['icon'] }}" alt="" loading="lazy" referrerpolicy="no-referrer">
                            @else
                                {!! $svg($ico['cube'], 26) !!}
                            @endif
                        </span>

                        <div class="ng-srv__body">
                            <div class="ng-srv__namerow">
                                @if ($s['url'])
                                    <a href="{{ $s['url'] }}" wire:navigate class="ng-srv__name">{{ $s['name'] }}</a>
                                @else
                                    <span class="ng-srv__name">{{ $s['name'] }}</span>
                                @endif
                                <button type="button" class="ng-pin" @click="togglePin(@js($s['uuid']))"
                                        :aria-pressed="pinned(@js($s['uuid'])) ? 'true' : 'false'"
                                        :title="pinned(@js($s['uuid'])) ? @js($t('dash_unpin')) : @js($t('dash_pin'))"
                                        :aria-label="pinned(@js($s['uuid'])) ? @js($t('dash_unpin')) : @js($t('dash_pin'))">★</button>
                            </div>

                            <div class="ng-srv__pills">
                                @if ($s['game'] !== '')
                                    <span class="ng-pill ng-pill--game">{!! $svg($ico['game'], 15) !!}{{ $s['game'] }}</span>
                                @endif
                                <span class="ng-pill ng-pill--{{ $s['kind'] }}" role="status"><i></i>{{ \Illuminate\Support\Facades\Lang::has('neonglass::strings.dash_status_' . $s['status']) ? $t('dash_status_' . $s['status']) : ucfirst(str_replace('_', ' ', $s['status'])) }}</span>
                            </div>

                            <div class="ng-srv__meters">
                                @foreach ([['cpu', 'CPU', $s['cpu'], $s['cpu_pct']], ['ram', 'RAM', $s['ram'], $s['ram_pct']]] as [$mk, $ml, $mv, $mp])
                                    <div class="ng-meter {{ $mp >= 90 ? 'is-hot' : '' }} {{ ($mv === '—' || ($s['kind'] !== 'ok' && $mp === 0)) ? 'is-idle' : '' }}">
                                        <div class="ng-meter__top"><em>{!! $svg($ico[$mk], 13) !!} {{ $ml }}</em><b>{{ $mv }}</b></div>
                                        <div class="ng-meter__bar" role="progressbar" aria-label="{{ $ml }}" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $mp }}"><i style="width: {{ $mp }}%"></i></div>
                                    </div>
                                @endforeach
                            </div>

                            @if ($s['address'])
                                <button type="button" class="ng-srv__addr" title="{{ $t('dash_copy') }}"
                                        x-data="{ ok: false }"
                                        @click="navigator.clipboard && navigator.clipboard.writeText(@js($s['address'])).then(() => { ok = true; setTimeout(() => ok = false, 1200) })">
                                    <code>{{ $s['address'] }}</code>
                                    <span x-show="ok" x-cloak>{{ $t('dash_copied') }}</span>
                                </button>
                            @endif
                        </div>

                        <div class="ng-srv__side">
                            @if ($s['power'] === 'stop')
                                <button type="button" class="ng-power ng-power--stop" wire:click="power(@js($s['uuid']), 'stop')" wire:loading.attr="disabled" wire:confirm="{{ $t('dash_confirm_stop') }}" title="{{ $t('dash_stop') }}" aria-label="{{ $t('dash_stop') }}">{!! $svg($ico['stop'], 22) !!}</button>
                            @elseif ($s['power'] === 'start')
                                <button type="button" class="ng-power ng-power--start" wire:click="power(@js($s['uuid']), 'start')" wire:loading.attr="disabled" title="{{ $t('dash_start') }}" aria-label="{{ $t('dash_start') }}">{!! $svg($ico['play'], 22) !!}</button>
                            @endif
                            @if ($s['kind'] === 'ok' && $s['can']['restart'])
                                <button type="button" class="ng-power ng-power--ghost" wire:click="power(@js($s['uuid']), 'restart')" wire:loading.attr="disabled" title="{{ $t('dash_restart') }}" aria-label="{{ $t('dash_restart') }}">{!! $svg($ico['restart'], 18) !!}</button>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Phone bottom bar + menu sheet --}}
    <nav class="ng-tabbar" aria-label="{{ $t('js_dashboard') }}">
        <a href="{{ \MuichiroDev\NeonGlass\Filament\App\Pages\ServerDashboard::getUrl(panel: 'app') }}" class="ng-tab is-active" aria-current="page">{!! $svg($ico['servers'], 22) !!}<span>{{ $t('dash_tab_servers') }}</span></a>
        @if ($chrome['new'])
            <a href="{{ $chrome['new'] }}" class="ng-tab ng-tab--new">{!! $svg($ico['plus'], 24) !!}<span>{{ $t('dash_tab_new') }}</span></a>
        @endif
        <button type="button" class="ng-tab" @click="menu = true" :aria-expanded="menu ? 'true' : 'false'">{!! $svg($ico['menu'], 22) !!}<span>{{ $t('dash_tab_menu') }}</span></button>
    </nav>

    <div class="ng-sheet" x-show="menu" x-cloak @click.self="menu = false" x-transition.opacity>
        <div class="ng-sheet__panel" role="dialog" aria-modal="true" aria-label="{{ $t('dash_tab_menu') }}">
            <div class="ng-sheet__grip" aria-hidden="true"></div>
            @if ($name !== '')
                <div class="ng-sheet__who">{{ $name }}</div>
            @endif
            @if ($chrome['account'])
                <a href="{{ $chrome['account'] }}" class="ng-sheet__item">{!! $svg($ico['user'], 20) !!}{{ $t('dash_account') }}</a>
            @endif
            @if ($chrome['admin'])
                <a href="{{ $chrome['admin'] }}" class="ng-sheet__item">{!! $svg($ico['shield'], 20) !!}{{ $t('dash_admin') }}</a>
            @endif
            @if ($chrome['logout'])
                <form method="POST" action="{{ $chrome['logout'] }}">
                    @csrf
                    <button type="submit" class="ng-sheet__item ng-sheet__item--danger">{!! $svg($ico['logout'], 20) !!}{{ $t('dash_logout') }}</button>
                </form>
            @endif
            <button type="button" class="ng-sheet__close" @click="menu = false">{{ $t('dash_close') }}</button>
        </div>
    </div>
</div>
