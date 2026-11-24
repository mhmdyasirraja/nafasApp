<?php

// Semua teks yang dikontrol server ada di sini (bukan di controller/JS), supaya gampang diedit
// dan konsisten. Aktifkan dengan APP_LOCALE=id di .env.
return [
    'state' => [
        'aman' => 'Aman',
        'waspada' => 'Waspada',
        'bahaya' => 'Bahaya',
        'offline' => 'Offline',
        'sensor_error' => 'Sensor error',
        'belum_ada_data' => 'Belum ada data',
    ],

    'banner' => [
        'aman' => ['title' => 'Pemantauan aktif, kondisi terpantau aman', 'body' => 'Tidak ada alarm aktif dari perangkat. Tetap pastikan ventilasi dan perangkat pembakaran terawat.'],
        'waspada' => ['title' => 'Waspada — kadar CO meningkat', 'body' => 'Periksa kemungkinan sumber CO. Bila merasa pusing atau mual, segera tinggalkan area.'],
        'bahaya' => ['title' => 'Bahaya — segera menuju udara segar', 'body' => 'Tinggalkan area dan hubungi bantuan darurat bila perlu. Jangan kembali sebelum dinyatakan aman.'],
        'offline' => ['title' => 'Perangkat tidak mengirim data', 'body' => 'Kondisi saat ini tidak dapat dipastikan. Alarm lokal di perangkat tetap bekerja tanpa internet. Periksa daya dan WiFi perangkat.'],
        'sensor_error' => ['title' => 'Sensor bermasalah — data mungkin tidak akurat', 'body' => 'Jangan jadikan angka ini acuan keselamatan. Periksa sambungan sensor lalu nyalakan ulang perangkat.'],
        'belum_ada_data' => ['title' => 'Menunggu data pertama', 'body' => 'Nyalakan perangkat dan pastikan tersambung ke WiFi.'],
    ],

    'headline' => [
        'aman' => ['title' => 'Udara dalam pemantauan', 'body' => 'Status Aman dilaporkan perangkat. Angka ini bukan jaminan keselamatan area.'],
        'waspada' => ['title' => 'Perlu perhatian Anda', 'body' => 'Status mengikuti konfigurasi perangkat. Perhatikan tren dan kondisi di sekitar Anda.'],
        'bahaya' => ['title' => 'Alarm perangkat aktif', 'body' => 'Buzzer berbunyi dan LED merah menyala. Utamakan evakuasi dari area.'],
        'offline' => ['title' => 'Tidak ada data terbaru', 'body' => 'Angka terakhir mungkin sudah tidak berlaku.'],
        'sensor_error' => ['title' => 'Pembacaan tidak dapat diandalkan', 'body' => 'Sensor melaporkan gangguan.'],
        'belum_ada_data' => ['title' => 'Belum ada pembacaan', 'body' => 'Data akan muncul setelah perangkat mengirim pembacaan pertama.'],
    ],

    'wifi' => ['online' => 'Terhubung', 'offline' => 'Terputus'],
    'buzzer' => ['on' => 'Berbunyi', 'off' => 'Tidak berbunyi', 'unknown' => 'Tidak diketahui'],
    'led' => ['aman' => 'Hijau · menyala', 'waspada' => 'Kuning · menyala', 'bahaya' => 'Merah · menyala', 'unknown' => 'Tidak diketahui'],

    'link' => [
        'live' => 'Langsung dari perangkat',
        'stale' => 'Bukan data langsung',
        'lost' => 'Terputus dari server · data bisa usang',
    ],

    'last_known' => 'Pembacaan terakhir :ppm ppm · :level · :waktu WIB',

    'activity' => [
        'title' => ['aman' => 'Kembali Aman', 'waspada' => 'Waspada · CO meningkat', 'bahaya' => 'Bahaya · Alarm diaktifkan'],
        'detail' => ':ppm ppm · Telegram :status',
    ],
    'kirim' => ['pending' => 'menunggu', 'terkirim' => 'terkirim', 'gagal' => 'gagal'],
    'cooldown' => 'Jeda minimum :menit menit untuk status yang sama. Perubahan ke Bahaya tetap memicu pesan.',
    'telegram' => ['linked' => 'Telegram: terhubung', 'unlinked' => 'Telegram: belum dihubungkan'],
];
