<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesPerangkat;
use App\Services\DashboardSnapshot;
use App\Services\RiwayatCo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DashboardController extends Controller
{
    use ResolvesPerangkat;

    public function index(Request $request, DashboardSnapshot $snapshot): View
    {
        [$devices, $perangkat] = $this->devicesWithSelection($request);

        return view('dashboard.index', [
            'perangkatList' => $devices,
            'perangkat' => $perangkat,
            'snapshot' => $perangkat ? $snapshot->build($perangkat) : null,
        ]);
    }

    public function snapshot(Request $request, int $perangkat, DashboardSnapshot $snapshot): JsonResponse
    {
        return response()
            ->json($snapshot->build($this->ownedDevice($request, $perangkat)))
            ->header('Cache-Control', 'no-store'); // data keselamatan: jangan pernah di-cache
    }

    public function riwayat(Request $request, int $perangkat, RiwayatCo $riwayat): JsonResponse
    {
        $data = $request->validate(['rentang' => ['required', Rule::in(RiwayatCo::ranges())]]);

        return response()
            ->json($riwayat->forRange($this->ownedDevice($request, $perangkat), $data['rentang']))
            ->header('Cache-Control', 'no-store');
    }
}
