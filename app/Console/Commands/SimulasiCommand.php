<?php

namespace App\Console\Commands;

use App\Enums\StatusKirim;
use App\Models\AmbangBatas;
use App\Models\DataSensor;
use App\Models\Notifikasi;
use App\Models\Perangkat;
use Illuminate\Console\Command;

class SimulasiCommand extends Command
{
    protected $signature = 'nafas:simulasi
        {skenario=aman : aman|waspada|bahaya|offline|sensor_error}
        {--perangkat= : ID perangkat (default: perangkat pertama)}';

    protected $description = 'Isi 1 jam data sensor palsu supaya tiap state dashboard bisa dicoba (non-production saja).';

    /** [menit yang lalu, ppm] — diinterpolasi linear. */
    private const KEYFRAMES = [
        'aman' => [[60, 11], [30, 13], [0, 12]],
        'waspada' => [[60, 12], [30, 13], [15, 19], [0, 27]],
        'bahaya' => [[60, 12], [30, 13], [20, 27], [10, 60], [0, 128]],
        'offline' => [[60, 11], [30, 13], [0, 12]],
        'sensor_error' => [[60, 11], [30, 13], [0, 12]],
    ];

    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->error('Dilarang dijalankan di production.');

            return self::FAILURE;
        }

        $skenario = (string) $this->argument('skenario');
        if (! isset(self::KEYFRAMES[$skenario])) {
            $this->error('Skenario tidak dikenal. Pilih: '.implode(', ', array_keys(self::KEYFRAMES)));

            return self::FAILURE;
        }

        $perangkat = $this->option('perangkat')
            ? Perangkat::find((int) $this->option('perangkat'))
            : Perangkat::orderBy('id')->first();

        if (! $perangkat) {
            $this->error('Belum ada perangkat. Jalankan: php artisan db:seed');

            return self::FAILURE;
        }

        $ambang = AmbangBatas::orderByDesc('batas_min_ppm')->get();
        if ($ambang->isEmpty()) {
            $this->error('Tabel ambang_batas kosong. Jalankan: php artisan db:seed');

            return self::FAILURE;
        }

        // Bersihkan data lama perangkat ini (notifikasi dulu, karena FK ke data_sensor).
        Notifikasi::whereIn('data_sensor_id', DataSensor::where('perangkat_id', $perangkat->id)->select('id'))->delete();
        DataSensor::where('perangkat_id', $perangkat->id)->delete();

        $interval = (int) config('nafas.sampling_interval');
        $end = now()->subSeconds($skenario === 'offline' ? 300 : 0);

        mt_srand(42); // noise deterministik
        $rows = [];
        for ($i = intdiv(3600, $interval); $i >= 0; $i--) {
            $secAgo = $i * $interval;
            $ppm = $this->ppmAt($skenario, $secAgo / 60);
            $level = $ambang->first(fn ($a) => $ppm >= $a->batas_min_ppm);

            $rows[] = [
                'perangkat_id' => $perangkat->id,
                'ambang_batas_id' => $level?->id,
                'nilai_adc' => (int) min(4095, 400 + $ppm * 15),
                'kadar_co_ppm' => $ppm,
                'status_level' => $level?->nama_level ?? 'aman',
                'waktu_baca' => $end->copy()->subSeconds($secAgo)->format('Y-m-d H:i:s'),
            ];
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DataSensor::insert($chunk);
        }

        $perangkat->forceFill([
            'last_seen' => $end,
            'status_sensor' => $skenario === 'sensor_error' ? 'error' : 'normal',
        ])->save();

        $this->buatNotifikasi($perangkat, $rows);

        $this->info("Skenario '{$skenario}' dibuat untuk {$perangkat->nama_perangkat} (".count($rows).' baris).');

        return self::SUCCESS;
    }

    private function ppmAt(string $skenario, float $minutesAgo): float
    {
        if ($skenario === 'sensor_error' && $minutesAgo < 10) {
            return 0.0; // "stuck di 0 ppm" (FA07)
        }

        $frames = self::KEYFRAMES[$skenario];
        $ppm = (float) end($frames)[1];

        for ($k = 0; $k < count($frames) - 1; $k++) {
            [$m1, $p1] = $frames[$k];
            [$m2, $p2] = $frames[$k + 1];
            if ($minutesAgo <= $m1 && $minutesAgo >= $m2) {
                $ppm = $p1 + ($p2 - $p1) * (($m1 - $minutesAgo) / ($m1 - $m2));
                break;
            }
        }

        return max(0.0, round($ppm + mt_rand(-4, 4) / 10, 1));
    }

    /** Satu notifikasi tiap kali level naik ke waspada/bahaya (meniru FS02, tanpa cooldown). */
    private function buatNotifikasi(Perangkat $perangkat, array $rows): void
    {
        $previous = 'aman';

        foreach ($rows as $row) {
            $level = $row['status_level'];

            if ($level !== $previous && in_array($level, ['waspada', 'bahaya'], true)) {
                $id = DataSensor::where('perangkat_id', $perangkat->id)->where('waktu_baca', $row['waktu_baca'])->value('id');

                Notifikasi::create([
                    'user_id' => $perangkat->user_id,
                    'data_sensor_id' => $id,
                    'pesan' => ucfirst($level).": CO {$row['kadar_co_ppm']} ppm.",
                    'kanal' => 'telegram',
                    'waktu_kirim' => $row['waktu_baca'],
                    'status_kirim' => StatusKirim::Terkirim,
                ]);
            }

            $previous = $level;
        }
    }
}
