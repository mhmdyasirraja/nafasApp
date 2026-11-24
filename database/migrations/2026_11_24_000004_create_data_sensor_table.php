<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel paling besar: 5 detik/baca = ~17.280 baris/hari/alat. Siapkan retensi (hapus > N hari) nanti.
        Schema::create('data_sensor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('perangkat_id')->constrained('perangkat')->cascadeOnDelete();
            $table->foreignId('ambang_batas_id')->nullable()->constrained('ambang_batas')->nullOnDelete();
            $table->unsignedSmallInteger('nilai_adc');
            $table->decimal('kadar_co_ppm', 6, 1);
            $table->string('status_level', 10);
            $table->timestamp('waktu_baca'); // dicap SERVER saat terima (ESP32 tanpa RTC)

            // Semua query dashboard/grafik: WHERE perangkat_id = ? AND waktu_baca >= ? ORDER BY waktu_baca
            $table->index(['perangkat_id', 'waktu_baca']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_sensor');
    }
};
