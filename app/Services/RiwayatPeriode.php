<?php

namespace App\Services;

use App\Enums\StatusLevel;
use App\Models\AmbangBatas;
use App\Models\DataSensor;
use App\Models\Perangkat;
use App\Support\Ppm;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Halaman Riwayat (SCS-09): grafik + ringkasan + tabel untuk satu periode.
 * Semua agregasi dilakukan di SQL; PHP tidak pernah memuat baris mentah (rentang 31 hari ≈ 535.000 baris).
 */
final class RiwayatPeriode
{
    public const MAX_DAYS = 31;

    public const PER_PAGE = 15;

    /** @return array{thresholds: array<string, float>, points: list<array{0:int,1:float}>} ±600 titik, nilai = MAX per bucket */
    public function chart(Perangkat $perangkat, CarbonInterface $from, CarbonInterface $to): array
    {
        $bucket = max(5, (int) (ceil($this->spanSeconds($from, $to) / 600 / 5) * 5));

        $points = $this->base($perangkat, $from, $to)
            ->selectRaw('FLOOR(UNIX_TIMESTAMP(waktu_baca) / ?) * ? AS bucket_ts, MAX(kadar_co_ppm) AS ppm_max', [$bucket, $bucket])
            ->groupBy('bucket_ts')
            ->orderBy('bucket_ts')
            ->get()
            ->map(fn ($r) => [(int) $r->bucket_ts * 1000, (float) $r->ppm_max]);

        return ['thresholds' => AmbangBatas::thresholds(), 'points' => $points->values()->all()];
    }

    /**
     * Durasi = jumlah pembacaan × interval sampling. Celah data (alat offline) tidak dihitung,
     * jadi ini batas bawah — label di UI memakai "≈".
     *
     * @return array<string, string|int>
     */
    public function ringkasan(Perangkat $perangkat, CarbonInterface $from, CarbonInterface $to): array
    {
        $r = $this->base($perangkat, $from, $to)
            ->selectRaw(
                'COUNT(*) AS total, MAX(kadar_co_ppm) AS puncak, AVG(kadar_co_ppm) AS rata, '
                .'COALESCE(SUM(status_level = ?), 0) AS n_waspada, COALESCE(SUM(status_level = ?), 0) AS n_bahaya',
                ['waspada', 'bahaya'],
            )
            ->first();

        $interval = (int) config('nafas.sampling_interval');
        $has = (int) $r->total > 0;

        return [
            'total' => (int) $r->total,
            'puncak' => $has ? Ppm::display((float) $r->puncak) : '—',
            'rata' => $has ? number_format((float) $r->rata, 1, ',', '.') : '—',
            'durasi_waspada' => $this->durasi((int) $r->n_waspada * $interval),
            'durasi_bahaya' => $this->durasi((int) $r->n_bahaya * $interval),
        ];
    }

    /** Interval tiap baris tabel (detik): makin panjang periode, makin kasar. */
    public function tableInterval(CarbonInterface $from, CarbonInterface $to): int
    {
        $span = $this->spanSeconds($from, $to);

        return match (true) {
            $span <= 2 * 86400 => 300,
            $span <= 14 * 86400 => 1800,
            default => 3600,
        };
    }

    /** @return LengthAwarePaginator<int, array{waktu: Carbon, ppm: string, level: StatusLevel}> */
    public function tabel(Perangkat $perangkat, CarbonInterface $from, CarbonInterface $to, string $status): LengthAwarePaginator
    {
        $interval = $this->tableInterval($from, $to);
        $t = AmbangBatas::thresholds();
        $tz = config('nafas.display_timezone');

        $query = $this->base($perangkat, $from, $to)
            ->selectRaw('FLOOR(UNIX_TIMESTAMP(waktu_baca) / ?) * ? AS bucket_ts, MAX(kadar_co_ppm) AS ppm_max', [$interval, $interval])
            ->groupBy('bucket_ts')
            ->orderByDesc('bucket_ts');

        // "waspada" = waspada ke atas (termasuk bahaya); "bahaya" = hanya bahaya.
        if ($status !== 'semua' && isset($t[$status])) {
            $query->having('ppm_max', '>=', $t[$status]);
        }

        return $query->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn ($r) => [
                'waktu' => Carbon::createFromTimestampUTC((int) $r->bucket_ts)->timezone($tz),
                'ppm' => Ppm::display((float) $r->ppm_max),
                'level' => $this->levelFor((float) $r->ppm_max, $t),
            ]);
    }

    private function base(Perangkat $perangkat, CarbonInterface $from, CarbonInterface $to): Builder
    {
        return DataSensor::query()
            ->where('perangkat_id', $perangkat->id)
            ->whereBetween('waktu_baca', [$from, $to]);
    }

    private function spanSeconds(CarbonInterface $from, CarbonInterface $to): int
    {
        return (int) abs($from->diffInSeconds($to));
    }

    /** @param array<string, float> $t */
    private function levelFor(float $ppm, array $t): StatusLevel
    {
        return match (true) {
            isset($t['bahaya']) && $ppm >= $t['bahaya'] => StatusLevel::Bahaya,
            isset($t['waspada']) && $ppm >= $t['waspada'] => StatusLevel::Waspada,
            default => StatusLevel::Aman,
        };
    }

    private function durasi(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds.' dtk';
        }

        $h = intdiv($seconds, 3600);
        $m = intdiv($seconds % 3600, 60);

        return $h > 0 ? "{$h} j {$m} mnt" : "{$m} mnt";
    }
}
