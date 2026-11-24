<?php

return [
    // Interval kirim alat (detik). Selaras FA01 / SCS-01 / NF02.
    'sampling_interval' => 5,

    // Tidak ada data selama ini => alat dianggap OFFLINE.
    // Alat tidak bisa melapor "aku mati", jadi status koneksi dihitung dari last_seen, bukan disimpan.
    'stale_after' => 30,

    // DB menyimpan UTC (APP_TIMEZONE=UTC). Konversi hanya saat tampil.
    'display_timezone' => 'Asia/Jakarta',

    // ppm yang dianggap "penuh" pada gauge. Hanya visual, bukan ambang status.
    'gauge_max_ppm' => 75,

    // Cooldown Telegram (FS02). Dipakai untuk teks info saja; logika kirim ada di backend ingest.
    'notif_cooldown_minutes' => 5,

    // "Tes Alarm" & "Bisukan" butuh jalur web -> alat (downlink). Belum ada di requirement/ERD.
    'remote_commands' => false,
];
