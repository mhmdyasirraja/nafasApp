<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Perangkat extends Model
{
    protected $table = 'perangkat';

    // Sengaja TIDAK fillable: api_token_hash, status_sensor, last_seen, user_id.
    // Field itu hanya boleh diisi server secara eksplisit (mass assignment).
    protected $fillable = ['kode_perangkat', 'nama_perangkat', 'versi_firmware'];

    protected $hidden = ['api_token_hash'];

    protected function casts(): array
    {
        return ['last_seen' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dataSensor(): HasMany
    {
        return $this->hasMany(DataSensor::class);
    }

    /** Pembacaan terbaru. Pakai PK (id naik monoton) agar murah; waktu_baca dicap server. */
    public function dataTerakhir(): HasOne
    {
        return $this->hasOne(DataSensor::class)->latestOfMany();
    }
}
