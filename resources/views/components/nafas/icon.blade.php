@props(['name'])
@php
    // Path ikon bergaya Feather (24x24, stroke). Satu tempat, tidak ada SVG yang diulang-ulang di view.
    $paths = [
        'menu' => 'M3 12h18M3 6h18M3 18h18',
        'grid' => 'M3 3h7v7H3zM14 3h7v7h-7zM14 14h7v7h-7zM3 14h7v7H3z',
        'trend' => 'M23 6l-9.5 9.5-5-5L1 18M17 6h6v6',
        'bell' => 'M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 0 1-3.46 0',
        'shield' => 'M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z',
        'alert-triangle' => 'M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0zM12 9v4M12 17h.01',
        'alert-octagon' => 'M7.86 2h8.28L22 7.86v8.28L16.14 22H7.86L2 16.14V7.86L7.86 2zM12 8v4M12 16h.01',
        'wifi' => 'M5 12.55a11 11 0 0 1 14.08 0M1.42 9a16 16 0 0 1 21.16 0M8.53 16.11a6 6 0 0 1 6.95 0M12 20h.01',
        'wifi-off' => 'M1 1l22 22M16.72 11.06A10.94 10.94 0 0 1 19 12.55M5 12.55a10.94 10.94 0 0 1 5.17-2.39M10.71 5.05A16 16 0 0 1 22.58 9M1.42 9a15.91 15.91 0 0 1 4.7-2.88M8.53 16.11a6 6 0 0 1 6.95 0M12 20h.01',
        'volume' => 'M11 5L6 9H2v6h4l5 4V5zM19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07',
        'volume-x' => 'M11 5L6 9H2v6h4l5 4V5zM23 9l-6 6M17 9l6 6',
        'bulb' => 'M9 18h6M10 22h4M12 2a7 7 0 0 0-4 12.7V17h8v-2.3A7 7 0 0 0 12 2z',
        'cpu' => 'M4 4h16v16H4zM9 9h6v6H9zM9 1v3M15 1v3M9 20v3M15 20v3M20 9h3M20 14h3M1 9h3M1 14h3',
        'refresh' => 'M23 4v6h-6M1 20v-6h6M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15',
        'send' => 'M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z',
        'logout' => 'M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9',
        'chevron-down' => 'M6 9l6 6 6-6',
        'hand' => 'M9 11V5a2 2 0 0 1 4 0v6M13 10V3.5a2 2 0 0 1 4 0V10M17 10V6.5a2 2 0 0 1 4 0V15a7 7 0 0 1-7 7h-1a7 7 0 0 1-5.66-2.9L3 14.5a2 2 0 0 1 3-2.6L9 15',
    ];
    // Default size-5 hanya kalau pemanggil tidak menentukan ukurannya sendiri (hindari size-5 + size-6 bentrok).
    $hasSize = str_contains((string) $attributes->get('class', ''), 'size-');
@endphp
<svg {{ $attributes->class(['size-5' => ! $hasSize]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $paths[$name] ?? '' }}"/></svg>
