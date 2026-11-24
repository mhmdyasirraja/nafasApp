<?php

namespace App\Http\Controllers;

use App\Models\Perangkat;
use App\Services\DashboardSnapshot;
use App\Services\RiwayatCo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request, DashboardSnapshot $snapshot): View
    {
        $devices = $request->user()->perangkat()->orderBy('nama_perangkat')->get(['id', 'nama_perangkat']);

        $selectedId = $request->integer('perangkat') ?: $devices->first()?->id;
        $perangkat = $selectedId ? $this->ownedDevice($request, $selectedId) : null;

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

    /**
     * Selalu di-scope lewat relasi milik user: perangkat orang lain => 404 (bukan 403),
     * jadi tidak membocorkan keberadaan ID dan tidak ada IDOR.
     */
    private function ownedDevice(Request $request, int $id): Perangkat
    {
        return $request->user()->perangkat()->findOrFail($id);
    }
}
