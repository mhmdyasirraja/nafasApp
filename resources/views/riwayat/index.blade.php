<x-nafas.layout title="Riwayat" :perangkat="$perangkat" active="riwayat">
@if (! $perangkat)
    <div class="card mx-auto mt-10 max-w-lg text-center">
        <x-nafas.icon name="cpu" class="mx-auto size-8 text-muted"/>
        <h1 class="mt-3 text-xl font-semibold">Belum ada perangkat</h1>
        <p class="mt-1 text-sm text-muted">Riwayat akan muncul setelah perangkat nafas. Anda terdaftar dan mengirim data.</p>
    </div>
@else
@php
    $levelStyle = [
        'aman' => 'bg-aman/10 text-aman',
        'waspada' => 'bg-waspada/10 text-waspada',
        'bahaya' => 'bg-bahaya/10 text-bahaya',
    ];
    $stats = [
        ['Puncak CO', $ringkasan['puncak'], 'ppm'],
        ['Rata-rata CO', $ringkasan['rata'], 'ppm'],
        ['Waktu Waspada ≈', $ringkasan['durasi_waspada'], null],
        ['Waktu Bahaya ≈', $ringkasan['durasi_bahaya'], null],
    ];
@endphp
<div data-riwayat
     data-device-name="{{ $perangkat->nama_perangkat }}"
     data-timezone="{{ config('nafas.display_timezone') }}">

    <h1 class="text-2xl font-semibold tracking-tight sm:text-3xl">Riwayat CO</h1>
    <p class="mt-1 text-sm text-muted">Telusuri kadar karbon monoksida dan kejadian waspada/bahaya pada periode tertentu.</p>

    @if ($errors->any())
        <div role="alert" class="mt-4 rounded-xl border border-bahaya/20 bg-bahaya/5 px-4 py-3 text-sm text-bahaya">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    {{-- Filter --}}
    <form method="GET" action="{{ route('riwayat') }}" class="card mt-6">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-[repeat(4,minmax(0,1fr))_auto] lg:items-end">
            <div>
                <label for="perangkat" class="block text-xs font-medium text-muted">Perangkat</label>
                <select id="perangkat" name="perangkat" class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand/40">
                    @foreach ($perangkatList as $p)
                        <option value="{{ $p->id }}" @selected($p->id === $perangkat->id)>{{ $p->nama_perangkat }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="dari" class="block text-xs font-medium text-muted">Dari tanggal</label>
                <input id="dari" type="date" name="dari" value="{{ $filters['dari'] }}" max="{{ $today }}"
                       class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand/40">
            </div>
            <div>
                <label for="sampai" class="block text-xs font-medium text-muted">Sampai tanggal</label>
                <input id="sampai" type="date" name="sampai" value="{{ $filters['sampai'] }}" max="{{ $today }}"
                       class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand/40">
            </div>
            <div>
                <label for="status" class="block text-xs font-medium text-muted">Tampilkan di tabel</label>
                <select id="status" name="status" class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand/40">
                    <option value="semua" @selected($filters['status'] === 'semua')>Semua status</option>
                    <option value="waspada" @selected($filters['status'] === 'waspada')>Waspada &amp; Bahaya</option>
                    <option value="bahaya" @selected($filters['status'] === 'bahaya')>Hanya Bahaya</option>
                </select>
            </div>
            <button type="submit" class="rounded-lg bg-brand px-5 py-2 text-sm font-medium text-white hover:bg-brand/90 sm:col-span-2 lg:col-span-1">Terapkan</button>
        </div>

        <div class="mt-4 flex flex-wrap items-center gap-2 text-sm">
            <span class="text-xs text-muted">Cepat:</span>
            @foreach ($presets as $preset)
                <a href="{{ $preset['url'] }}" @if ($preset['active']) aria-current="true" @endif
                   class="rounded-full border border-slate-200 px-3 py-1 text-muted hover:bg-slate-50 aria-[current=true]:border-brand aria-[current=true]:bg-brand/10 aria-[current=true]:font-medium aria-[current=true]:text-brand">{{ $preset['label'] }}</a>
            @endforeach
        </div>
    </form>

    {{-- Ringkasan --}}
    <div class="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        @foreach ($stats as [$label, $value, $unit])
            <div class="card p-4! sm:p-5!">
                <p class="text-xs text-muted">{{ $label }}</p>
                <p class="mt-1 text-2xl font-light tabular-nums">{{ $value }}@if ($unit && $value !== '—') <span class="ml-1 text-sm text-muted">{{ $unit }}</span>@endif</p>
            </div>
        @endforeach
    </div>
    <p class="mt-2 text-xs text-muted">
        ≈ dihitung dari jumlah pembacaan × interval {{ config('nafas.sampling_interval') }} detik; periode saat perangkat offline tidak terhitung.
    </p>

    {{-- Grafik --}}
    <section class="card mt-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-medium">Grafik CO</h2>
            <div class="inline-flex gap-1.5 text-sm" role="group" aria-label="Zoom grafik">
                <button type="button" data-zoom="out" aria-label="Perkecil" class="size-9 rounded-lg border border-slate-200 font-medium hover:bg-slate-50">−</button>
                <button type="button" data-zoom="in" aria-label="Perbesar" class="size-9 rounded-lg border border-slate-200 font-medium hover:bg-slate-50">+</button>
                <button type="button" data-zoom="reset" class="rounded-lg border border-slate-200 px-3 font-medium hover:bg-slate-50">Reset</button>
            </div>
        </div>

        <p class="mt-4 text-xs text-muted">Konsentrasi CO tertinggi tiap interval (ppm)</p>
        <div class="relative mt-1 h-60 sm:h-72">
            <canvas data-chart="{{ json_encode($chart) }}" aria-label="Grafik riwayat kadar CO" role="img"></canvas>
        </div>

        @if ($ringkasan['total'] === 0)
            <p class="mt-3 text-sm text-muted">Tidak ada data pada periode ini.</p>
        @endif
    </section>

    {{-- Tabel --}}
    <section class="card mt-6">
        <div class="flex flex-wrap items-baseline justify-between gap-2">
            <h2 class="font-medium">Daftar pembacaan</h2>
            <p class="text-xs text-muted">Nilai tertinggi tiap {{ $intervalMenit }} menit · waktu WIB</p>
        </div>

        <div class="-mx-5 mt-4 overflow-x-auto sm:-mx-6">
            <table class="w-full min-w-[26rem] text-left text-sm">
                <thead class="text-xs text-muted">
                    <tr>
                        <th scope="col" class="px-5 py-2 font-medium sm:px-6">Waktu</th>
                        <th scope="col" class="px-3 py-2 font-medium">CO tertinggi</th>
                        <th scope="col" class="px-5 py-2 font-medium sm:px-6">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($rows as $row)
                        <tr>
                            <td class="whitespace-nowrap px-5 py-2.5 sm:px-6">{{ $row['waktu']->locale('id')->translatedFormat('j M Y, H:i') }}</td>
                            <td class="px-3 py-2.5 tabular-nums">{{ $row['ppm'] }} ppm</td>
                            <td class="px-5 py-2.5 sm:px-6">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium {{ $levelStyle[$row['level']->value] }}">
                                    <span class="size-1.5 rounded-full bg-current"></span>{{ $row['level']->label() }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-5 py-8 text-center text-muted sm:px-6">Tidak ada data yang cocok dengan filter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($rows->hasPages())
            <div class="mt-4">{{ $rows->links() }}</div>
        @endif
    </section>
</div>
@endif
</x-nafas.layout>
