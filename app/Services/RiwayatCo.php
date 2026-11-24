<?php

namespace App\Services;

use App\Models\AmbangBatas;
use App\Models\DataSensor;
use App\Models\Perangkat;

/**
 * Data grafik. 5 detik/baca => 7 hari = ~120.000 baris, jadi rentang panjang WAJIB di-bucket di SQL.
 * Bucket memakai MAX (bukan AVG): lonjakan CO singkat tidak boleh "tertelan" rata-rata.
 */
final class RiwayatCo
{
    /** rentang => [detik ke belakang, ukuran bucket detik (0 = data mentah)] */
    private const RANGES = [
        '1j' => [3600, 0],
        '24j' => [86400, 300],
        '7h' => [604800, 1800],
    ];

    /** @return list<string> */
    public static function ranges(): array
    {
        return array_keys(self::RANGES);
    }

    /** @return array{rentang: string, thresholds: array<string, float>, points: list<array{0:int,1:float}>} */
    public function forRange(Perangkat $perangkat, string $rentang): array
    {
        [$seconds, $bucket] = self::RANGES[$rentang];

        $query = DataSensor::query()
            ->where('perangkat_id', $perangkat->id)
            ->where('waktu_baca', '>=', now()->subSeconds($seconds));

        if ($bucket === 0) {
            $points = $query->orderBy('waktu_baca')
                ->get(['waktu_baca', 'kadar_co_ppm'])
                ->map(fn ($r) => [$r->waktu_baca->getTimestamp() * 1000, $r->kadar_co_ppm]);
        } else {
            // Butuh time_zone koneksi MySQL = +00:00 (lihat README) agar UNIX_TIMESTAMP konsisten.
            $points = $query
                ->selectRaw('FLOOR(UNIX_TIMESTAMP(waktu_baca) / ?) * ? AS bucket_ts, MAX(kadar_co_ppm) AS ppm', [$bucket, $bucket])
                ->groupBy('bucket_ts')
                ->orderBy('bucket_ts')
                ->get()
                ->map(fn ($r) => [(int) $r->bucket_ts * 1000, (float) $r->ppm]);
        }

        return [
            'rentang' => $rentang,
            'thresholds' => AmbangBatas::whereIn('nama_level', ['waspada', 'bahaya'])
                ->pluck('batas_min_ppm', 'nama_level')
                ->map(fn ($v) => (float) $v)
                ->all(),
            'points' => $points->values()->all(),
        ];
    }
}
