<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perangkat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('kode_perangkat', 32)->unique();
            $table->string('nama_perangkat', 100);
            // Token API alat disimpan sebagai hash (sha256), seperti password. Dipakai endpoint ingest ESP32.
            $table->char('api_token_hash', 64);
            $table->string('status_sensor', 20)->default('normal'); // normal | error (FA07 / SCS-06)
            $table->timestamp('last_seen')->nullable();
            $table->string('versi_firmware', 20)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perangkat');
    }
};
