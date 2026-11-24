<?php

namespace Database\Seeders;

use App\Models\AmbangBatas;
use App\Models\Perangkat;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Ambang = data referensi (aman juga untuk production). Spesifikasi: aman <18, waspada 18–35, bahaya >35 ppm.
        // Kadar disimpan 1 desimal, jadi ">35" ditulis sebagai batas_min 35,1 (35,0 masih waspada).
        $ambang = [
            ['aman', 0.0, 17.9, false],
            ['waspada', 18.0, 35.0, false],
            ['bahaya', 35.1, null, true],
        ];

        foreach ($ambang as [$nama, $min, $max, $buzzer]) {
            AmbangBatas::updateOrCreate(
                ['nama_level' => $nama],
                ['batas_min_ppm' => $min, 'batas_max_ppm' => $max, 'aksi_buzzer' => $buzzer],
            );
        }

        if (! app()->isLocal()) {
            return; // akun & alat demo hanya untuk lokal
        }

        $user = User::firstOrCreate(
            ['email' => 'demo@nafas.test'],
            ['name' => 'Demo Pengguna', 'password' => 'password', 'chat_id_telegram' => '123456789'],
        );

        Perangkat::unguarded(fn () => $user->perangkat()->firstOrCreate(
            ['kode_perangkat' => 'NAFAS-CO-01'],
            ['nama_perangkat' => 'Nafas CO-01', 'api_token_hash' => hash('sha256', 'dev-token')],
        ));
    }
}
