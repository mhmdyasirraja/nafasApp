<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Perangkat;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

trait ResolvesPerangkat
{
    /**
     * Selalu di-scope lewat relasi milik user: perangkat orang lain => 404 (bukan 403),
     * jadi tidak membocorkan keberadaan ID dan tidak ada IDOR.
     */
    private function ownedDevice(Request $request, int $id): Perangkat
    {
        return $request->user()->perangkat()->findOrFail($id);
    }

    /** @return array{0: Collection<int, Perangkat>, 1: ?Perangkat} daftar pilihan + perangkat terpilih (?perangkat=ID, default yang pertama) */
    private function devicesWithSelection(Request $request): array
    {
        $devices = $request->user()->perangkat()->orderBy('nama_perangkat')->get(['id', 'nama_perangkat']);
        $id = $request->integer('perangkat') ?: $devices->first()?->id;

        return [$devices, $id ? $this->ownedDevice($request, $id) : null];
    }
}
