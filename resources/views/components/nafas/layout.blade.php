@props(['title' => 'Dashboard', 'perangkat' => null, 'active' => 'dashboard'])
@php
    $user = auth()->user();
    $initials = collect(explode(' ', trim($user?->name ?? 'U')))->filter()->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->take(2)->implode('');
    $deviceQuery = array_filter(['perangkat' => $perangkat?->id]);
    $nav = [
        ['dashboard', 'grid', 'Dashboard', route('dashboard', $deviceQuery)],
        ['riwayat', 'trend', 'Riwayat', route('riwayat', $deviceQuery)],
    ];
    $today = now()->timezone(config('nafas.display_timezone'))->locale('id')->translatedFormat('l, j F Y');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} · nafas.</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-canvas font-sans text-ink antialiased">
<div class="flex min-h-screen">
    <div data-sidebar-backdrop class="fixed inset-0 z-30 hidden bg-black/40 lg:hidden"></div>

    <aside data-sidebar id="sidebar"
           class="fixed inset-y-0 left-0 z-40 flex w-62 shrink-0 -translate-x-full flex-col bg-sidebar px-4 py-5 text-white transition-transform duration-200 data-[open=true]:translate-x-0 lg:sticky lg:top-0 lg:h-screen lg:translate-x-0">
        <div class="flex items-start gap-2.5 px-2">
            <x-nafas.icon name="shield" class="mt-1 size-6 text-white/80"/>
            <div>
                <div class="text-2xl font-semibold leading-none tracking-tight">nafas.</div>
                <div class="mt-1 text-[0.65rem] tracking-wider text-white/60">MONITOR KARBON MONOKSIDA</div>
            </div>
        </div>

        <nav class="mt-8 space-y-1 text-sm" aria-label="Menu utama">
            @foreach ($nav as [$key, $icon, $label, $url])
                <a href="{{ $url }}" @if ($active === $key) aria-current="page" @endif
                   class="flex items-center gap-3 rounded-lg px-3 py-2.5 {{ $active === $key ? 'bg-sidebar-hover font-medium' : 'text-white/80 hover:bg-white/5' }}">
                    <x-nafas.icon :name="$icon"/> {{ $label }}
                </a>
            @endforeach
            {{-- Halaman berikutnya (notifikasi). Sementara non-aktif. --}}
            <span aria-disabled="true" title="Segera hadir" class="flex cursor-not-allowed items-center gap-3 rounded-lg px-3 py-2.5 text-white/50">
                <x-nafas.icon name="bell"/> Pemberitahuan
            </span>
        </nav>

        <div class="mt-auto space-y-3 border-t border-white/15 px-2 pt-4 text-xs text-white/60">
            @if (Route::has('logout'))
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex items-center gap-2 text-sm text-white/80 hover:text-white">
                        <x-nafas.icon name="logout" class="size-4"/> Keluar
                    </button>
                </form>
            @endif
            <div>nafas. web • v1.0</div>
        </div>
    </aside>

    <div class="min-w-0 flex-1">
        <header class="sticky top-0 z-20 flex h-15 items-center gap-3 border-b border-slate-200 bg-white px-4 sm:px-6 lg:px-8">
            <button type="button" data-sidebar-toggle aria-controls="sidebar" aria-expanded="false" aria-label="Buka menu"
                    class="-ml-1 rounded-lg p-2 text-muted hover:bg-slate-100 lg:hidden">
                <x-nafas.icon name="menu"/>
            </button>

            <div class="flex min-w-0 items-center gap-2 text-sm text-muted">
                <x-nafas.icon name="cpu" class="size-4 shrink-0"/>
                <span class="hidden sm:inline">Perangkat /</span>
                <span class="truncate font-medium text-ink">{{ $perangkat?->nama_perangkat ?? '—' }}</span>
            </div>

            <div class="ml-auto flex items-center gap-4 text-sm text-muted">
                <span class="hidden sm:inline">{{ $today }}</span>
                <span class="grid size-9 place-items-center rounded-full bg-brand/10 text-xs font-semibold text-brand" title="{{ $user?->name }}">{{ $initials }}</span>
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-4 py-6 sm:px-6 lg:px-8">
            {{ $slot }}
        </main>
    </div>
</div>
</body>
</html>
