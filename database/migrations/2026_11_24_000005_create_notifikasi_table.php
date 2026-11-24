<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifikasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('data_sensor_id')->nullable()->constrained('data_sensor')->nullOnDelete();
            $table->text('pesan');
            $table->string('kanal', 20)->default('telegram');
            $table->timestamp('waktu_kirim')->nullable();
            $table->string('status_kirim', 10)->default('pending');
            $table->timestamps();

            $table->index(['user_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifikasi');
    }
};
