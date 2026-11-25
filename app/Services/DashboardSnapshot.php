<?php

namespace App\Services;

use App\Enums\StatusLevel;
use App\Enums\TampilanStatus;
use App\Models\DataSensor;
use App\Models\Notifikasi;
use App\Models\Perangkat;
use App\Support\Ppm;

/**
 * Satu-satunya tempat yang menerjemahkan data mentah -> apa yang ditampilkan dashboard.
 * Dipakai untuk render awal DAN endpoint polling, jadi keduanya pasti konsisten.
 */
final class DashboardSnapshot
{
    /** @return array<string, mixed> */
    public function build(Perangkat $perangkat): array
    {
        $perangkat->loadMissing('dataTerakhir', 'user:id,chat_id_telegram');

        $reading = $perangkat->dataTerakhir;
        $state = $this->resolveState($perangkat, $reading);
        $measured = $state->isMeasured();
        $level = $measured ? $reading->status_level : null;
        $tz = config('nafas.display_timezone');

        $banner = trans("dashboard.banner.{$state->value}");
        $headline = trans("dashboard.headline.{$state->value}");

        $lastKnown = '';
        if ($state === TampilanStatus::Offline && $reading) {
            $lastKnown = trans('dashboard.last_known', [
                'ppm' => $this->displayPpm($reading->kadar_co_ppm),
                'level' => $reading->status_level->label(),
                'waktu' => $reading->waktu_baca->timezone($tz)->format('H:i:s'),
            ]);
        }

        return [
            'state' => $state->value,
            'fraction' => $measured ? min(1.0, $reading->kadar_co_ppm / config('nafas.gauge_max_ppm')) : 0.0,
            'text' => [
                'state_label' => trans("dashboard.state.{$state->value}"),
                'banner_title' => $banner['title'],
                'banner_body' => $banner['body'],
                'headline' => $headline['title'],
                'headline_body' => $headline['body'],
                'ppm' => $measured ? $this->displayPpm($reading->kadar_co_ppm) : '—',
                'wifi_label' => trans($this->isOnline($state) ? 'dashboard.wifi.online' : 'dashboard.wifi.offline'),
                'buzzer_label' => trans('dashboard.buzzer.'.($level ? ($level->buzzerActive() ? 'on' : 'off') : 'unknown')),
                'led_label' => trans('dashboard.led.'.($level?->value ?? 'unknown')),
                'last_seen' => $perangkat->last_seen?->timezone($tz)->format('H:i:s') ?? '—',
                'last_known' => $lastKnown,
                'server_link' => trans('dashboard.link.'.($measured ? 'live' : 'stale')),
                'telegram' => trans('dashboard.telegram.'.($perangkat->user?->chat_id_telegram ? 'linked' : 'unlinked')),
            ],
            'activities' => $this->activities($perangkat),
        ];
    }

    /**
     * Urutan penting: jangan pernah menampilkan "Aman" dari data basi.
     * Offline mengalahkan sensor_error karena status sensor yang tersimpan sudah tidak bisa dipercaya.
     */
    private function resolveState(Perangkat $perangkat, ?DataSensor $reading): TampilanStatus
    {
        if ($reading === null) {
            return TampilanStatus::BelumAdaData;
        }

        $stale = $perangkat->last_seen === null
            || $perangkat->last_seen->lt(now()->subSeconds(config('nafas.stale_after')));

        if ($stale) {
            return TampilanStatus::Offline;
        }

        if ($perangkat->status_sensor !== 'normal') {
            return TampilanStatus::SensorError;
        }

        return TampilanStatus::fromLevel($reading->status_level);
    }

    private function isOnline(TampilanStatus $state): bool
    {
        return ! in_array($state, [TampilanStatus::Offline, TampilanStatus::BelumAdaData], true);
    }

    private function displayPpm(float $ppm): string
    {
        return Ppm::display($ppm);
    }

    /** @return list<array{title: string, detail: string, time: string, level: string}> */
    private function activities(Perangkat $perangkat): array
    {
        $tz = config('nafas.display_timezone');

        return Notifikasi::query()
            ->with('dataSensor:id,kadar_co_ppm,status_level')
            ->where('user_id', $perangkat->user_id)
            ->whereHas('dataSensor', fn ($q) => $q->where('perangkat_id', $perangkat->id))
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(function (Notifikasi $n) use ($tz) {
                $level = $n->dataSensor?->status_level ?? StatusLevel::Waspada;
                $at = ($n->waktu_kirim ?? $n->created_at)->timezone($tz);

                return [
                    'title' => trans("dashboard.activity.title.{$level->value}"),
                    'detail' => trans('dashboard.activity.detail', [
                        'ppm' => $this->displayPpm($n->dataSensor?->kadar_co_ppm ?? 0),
                        'status' => trans("dashboard.kirim.{$n->status_kirim->value}"),
                    ]),
                    'time' => $at->isToday() ? $at->format('H:i') : $at->format('d M'),
                    'level' => $level->value,
                ];
            })
            ->all();
    }
}
