<x-nafas.layout title="Dashboard" :perangkat="$perangkat">
@if (! $perangkat)
    <div class="card mx-auto mt-10 max-w-lg text-center">
        <x-nafas.icon name="cpu" class="mx-auto size-8 text-muted"/>
        <h1 class="mt-3 text-xl font-semibold">Belum ada perangkat</h1>
        <p class="mt-1 text-sm text-muted">Daftarkan perangkat nafas. Anda untuk mulai memantau kadar CO.</p>
    </div>
@else
<div data-dashboard
     data-status="{{ $snapshot['state'] }}"
     style="--frac: {{ $snapshot['fraction'] }}"
     data-initial="{{ json_encode($snapshot, JSON_UNESCAPED_UNICODE) }}"
     data-snapshot-url="{{ route('perangkat.snapshot', $perangkat) }}"
     data-riwayat-url="{{ route('perangkat.riwayat', $perangkat) }}"
     data-interval="{{ config('nafas.sampling_interval') * 1000 }}"
     data-timezone="{{ config('nafas.display_timezone') }}"
     data-msg-lost="{{ trans('dashboard.link.lost') }}"
     data-device-name="{{ $perangkat->nama_perangkat }}">

    {{-- Judul + pemilih perangkat --}}
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight sm:text-3xl">Ringkasan pemantauan</h1>
            <p class="mt-1 text-sm text-muted">Satu pandangan untuk udara yang lebih terpantau.</p>
        </div>

        <form method="GET" action="{{ route('dashboard') }}" data-autosubmit>
            <label for="perangkat" class="sr-only">Pilih perangkat</label>
            <div class="relative">
                <x-nafas.icon name="cpu" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-brand"/>
                <select id="perangkat" name="perangkat"
                        class="appearance-none rounded-lg border border-slate-200 bg-white py-2 pl-9 pr-9 text-sm font-medium shadow-sm focus:outline-none focus:ring-2 focus:ring-brand/40">
                    @foreach ($perangkatList as $p)
                        <option value="{{ $p->id }}" @selected($p->id === $perangkat->id)>{{ $p->nama_perangkat }}</option>
                    @endforeach
                </select>
                <x-nafas.icon name="chevron-down" class="pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 text-muted"/>
            </div>
        </form>
    </div>

    {{-- Banner status --}}
    <section role="status" class="mt-6 flex items-start gap-3 rounded-xl border border-status/20 bg-status-soft p-4 text-status sm:items-center sm:gap-4 sm:px-5">
        <span class="mt-0.5 shrink-0 sm:mt-0">
            <x-nafas.icon name="shield" data-state-icon="aman" class="hidden size-6"/>
            <x-nafas.icon name="alert-triangle" data-state-icon="waspada sensor_error" class="hidden size-6"/>
            <x-nafas.icon name="alert-octagon" data-state-icon="bahaya" class="hidden size-6"/>
            <x-nafas.icon name="wifi-off" data-state-icon="offline belum_ada_data" class="hidden size-6"/>
        </span>
        <div class="min-w-0 flex-1">
            <p class="font-medium" data-bind="banner_title"></p>
            <p class="mt-0.5 text-sm opacity-90" data-bind="banner_body"></p>
        </div>
        <span class="hidden shrink-0 items-center gap-1.5 rounded-full bg-white px-3 py-1 text-xs font-medium sm:inline-flex">
            <span class="size-1.5 rounded-full bg-current"></span><span data-bind="state_label"></span>
        </span>
    </section>

    <div class="mt-6 grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">

        {{-- Kartu CO saat ini --}}
        <section class="card">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="font-medium">Karbon monoksida saat ini</h2>
                <span class="chip" data-bind="server_link"></span>
            </div>

            <div class="mt-6 flex flex-col items-center gap-6 sm:flex-row sm:gap-10">
                <div class="relative size-52 shrink-0">
                    <svg viewBox="0 0 100 100" class="size-full" role="img" aria-label="Indikator kadar CO">
                        <circle cx="50" cy="50" r="42" fill="none" stroke="#e0e8eb" stroke-width="7" stroke-linecap="round"
                                stroke-dasharray="197.92 263.89" transform="rotate(135 50 50)"/>
                        <circle class="gauge-value text-status" cx="50" cy="50" r="42" fill="none" stroke="currentColor" stroke-width="7"
                                stroke-linecap="round" transform="rotate(135 50 50)"/>
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-5xl font-light tabular-nums" data-bind="ppm"></span>
                        <span class="text-sm text-muted">ppm</span>
                        <span class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-status-soft px-2.5 py-0.5 text-xs font-medium text-status">
                            <span class="size-1.5 rounded-full bg-current"></span><span data-bind="state_label"></span>
                        </span>
                    </div>
                </div>

                <div class="min-w-0 text-center sm:text-left">
                    <h3 class="text-lg" data-bind="headline"></h3>
                    <p class="mt-2 text-sm text-muted" data-bind="headline_body"></p>
                    <p class="mt-2 text-sm font-medium text-status" data-bind="last_known" data-hide-empty></p>
                    <p class="mt-3 flex items-center justify-center gap-2 text-sm text-muted sm:justify-start">
                        <x-nafas.icon name="volume-x" class="size-4"/> Buzzer: <span data-bind="buzzer_label"></span>
                    </p>

                    @if (config('nafas.remote_commands'))
                        {{-- Belum ada di requirement: butuh jalur web -> alat. Lihat config/nafas.php --}}
                        <button type="button" data-command="mute" disabled
                                class="mt-4 inline-flex items-center gap-2 rounded-lg bg-bahaya px-4 py-2 text-sm font-medium text-white disabled:opacity-60">
                            <x-nafas.icon name="volume-x" class="size-4"/> Bisukan 5 menit
                        </button>
                    @endif
                </div>
            </div>

            <div class="mt-6 flex flex-wrap items-center justify-between gap-2 border-t border-slate-200 pt-4 text-xs text-muted">
                <span class="inline-flex items-center gap-2"><x-nafas.icon name="refresh" class="size-3.5 text-brand"/> Diperbarui setiap {{ config('nafas.sampling_interval') }} detik</span>
                <span>Terakhir <span data-bind="last_seen"></span> WIB</span>
            </div>
        </section>

        {{-- Status perangkat --}}
        <section class="card">
            <div class="flex items-center justify-between">
                <h2 class="font-medium">Status perangkat</h2>
                <x-nafas.icon name="wifi" class="size-4 text-brand"/>
            </div>

            <div class="mt-4 flex items-center gap-3">
                <span class="grid size-11 place-items-center rounded-xl bg-brand/10 text-brand"><x-nafas.icon name="cpu"/></span>
                <div class="min-w-0">
                    <p class="truncate font-medium">{{ $perangkat->nama_perangkat }}</p>
                    <p class="truncate text-xs text-muted">{{ $perangkat->kode_perangkat }}</p>
                </div>
            </div>

            <dl class="mt-5 space-y-3 border-b border-slate-200 pb-5 text-sm">
                <div class="flex items-center justify-between gap-3">
                    <dt class="flex items-center gap-2 text-muted"><x-nafas.icon name="wifi" class="size-4"/> Koneksi Wi-Fi</dt>
                    <dd class="font-medium text-status" data-bind="wifi_label"></dd>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <dt class="flex items-center gap-2 text-muted"><x-nafas.icon name="volume" class="size-4"/> Buzzer</dt>
                    <dd class="font-medium text-status" data-bind="buzzer_label"></dd>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <dt class="flex items-center gap-2 text-muted"><x-nafas.icon name="bulb" class="size-4"/> LED indikator</dt>
                    <dd class="font-medium text-status" data-bind="led_label"></dd>
                </div>
            </dl>

            @if (config('nafas.remote_commands'))
                <div class="mt-5 flex items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-medium">Tes Alarm</p>
                        <p class="text-xs text-muted">Buzzer selama 3 detik</p>
                    </div>
                    <button type="button" data-command="test" disabled
                            class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium disabled:opacity-60">
                        <x-nafas.icon name="volume" class="size-4"/> Tes Alarm
                    </button>
                </div>
            @endif
        </section>

        {{-- Grafik --}}
        <section class="card">
            <div class="flex items-center justify-between">
                <h2 class="font-medium">Riwayat CO</h2>
                <a href="{{ route('riwayat', ['perangkat' => $perangkat->id]) }}" class="text-sm font-medium text-brand hover:underline">Lihat riwayat ↗</a>
            </div>

            <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                <div class="inline-flex rounded-lg bg-slate-100 p-0.5 text-sm" role="group" aria-label="Rentang waktu">
                    @foreach (['1j' => '1 jam', '24j' => '24 jam', '7h' => '7 hari'] as $key => $label)
                        <button type="button" data-range="{{ $key }}" aria-pressed="{{ $key === '1j' ? 'true' : 'false' }}"
                                class="rounded-md px-3 py-1.5 text-muted aria-pressed:bg-white aria-pressed:font-medium aria-pressed:text-brand aria-pressed:shadow-sm">{{ $label }}</button>
                    @endforeach
                </div>

                <div class="inline-flex gap-1.5 text-sm" role="group" aria-label="Zoom grafik">
                    <button type="button" data-zoom="out" aria-label="Perkecil" class="size-9 rounded-lg border border-slate-200 font-medium hover:bg-slate-50">−</button>
                    <button type="button" data-zoom="in" aria-label="Perbesar" class="size-9 rounded-lg border border-slate-200 font-medium hover:bg-slate-50">+</button>
                    <button type="button" data-zoom="reset" class="rounded-lg border border-slate-200 px-3 font-medium hover:bg-slate-50">Reset</button>
                </div>
            </div>

            <p class="mt-4 text-xs text-muted">Konsentrasi CO (ppm)</p>
            <div class="relative mt-1 h-56 sm:h-64"><canvas data-chart aria-label="Grafik riwayat kadar CO" role="img"></canvas></div>

            <p class="mt-3 flex items-center gap-2 text-xs text-muted">
                <x-nafas.icon name="hand" class="size-3.5"/> Geser untuk menjelajah · + / − untuk zoom · Tekan &amp; tahan atau sentuh lama untuk detail
            </p>
        </section>

        {{-- Aktivitas terbaru --}}
        <section class="card">
            <div class="flex items-center justify-between">
                <h2 class="font-medium">Aktivitas terbaru</h2>
                <x-nafas.icon name="bell" class="size-4 text-muted"/>
            </div>

            <ul class="mt-4 space-y-4" data-list="activities"></ul>
            <p class="mt-4 text-sm text-muted" data-empty="activities" hidden>Belum ada notifikasi.</p>

            <div class="mt-5 border-t border-slate-200 pt-4 text-xs text-muted">
                <p class="flex items-center gap-2 text-brand"><x-nafas.icon name="send" class="size-4"/> <span data-bind="telegram"></span></p>
                <p class="mt-2">{{ trans('dashboard.cooldown', ['menit' => config('nafas.notif_cooldown_minutes')]) }}</p>
            </div>
        </section>
    </div>

    <footer class="mt-6 flex flex-wrap justify-between gap-2 text-xs text-muted">
        <span>Zona waktu WIB</span>
        <span>Pembacaan terakhir <span data-bind="last_seen"></span></span>
    </footer>
</div>
@endif
</x-nafas.layout>
