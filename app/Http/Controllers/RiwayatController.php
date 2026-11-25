<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesPerangkat;
use App\Http\Requests\RiwayatRequest;
use App\Services\RiwayatPeriode;
use Illuminate\View\View;

class RiwayatController extends Controller
{
    use ResolvesPerangkat;

    public function index(RiwayatRequest $request, RiwayatPeriode $periode): View
    {
        [$devices, $perangkat] = $this->devicesWithSelection($request);

        if (! $perangkat) {
            return view('riwayat.index', ['perangkatList' => $devices, 'perangkat' => null]);
        }

        [$from, $to] = $request->period();
        $status = $request->validated('status');
        $tz = config('nafas.display_timezone');
        $today = now($tz)->toDateString();

        return view('riwayat.index', [
            'perangkatList' => $devices,
            'perangkat' => $perangkat,
            'filters' => $request->safe()->only(['dari', 'sampai', 'status']),
            'today' => $today,
            'presets' => $this->presets($perangkat->id, $today),
            'chart' => $periode->chart($perangkat, $from, $to),
            'ringkasan' => $periode->ringkasan($perangkat, $from, $to),
            'rows' => $periode->tabel($perangkat, $from, $to, $status),
            'intervalMenit' => intdiv($periode->tableInterval($from, $to), 60),
        ]);
    }

    /** @return list<array{label: string, url: string, active: bool}> */
    private function presets(int $perangkatId, string $today): array
    {
        $tz = config('nafas.display_timezone');
        $current = [request('dari'), request('sampai')];

        return collect(['Hari ini' => 0, '7 hari' => 6, '30 hari' => 29])
            ->map(function (int $back, string $label) use ($perangkatId, $today, $tz, $current) {
                $dari = now($tz)->subDays($back)->toDateString();

                return [
                    'label' => $label,
                    'url' => route('riwayat', ['perangkat' => $perangkatId, 'dari' => $dari, 'sampai' => $today]),
                    'active' => $current === [$dari, $today],
                ];
            })
            ->values()
            ->all();
    }
}
