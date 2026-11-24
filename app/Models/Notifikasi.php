<?php

namespace App\Models;

use App\Enums\StatusKirim;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notifikasi extends Model
{
    protected $table = 'notifikasi';

    protected $fillable = ['user_id', 'data_sensor_id', 'pesan', 'kanal', 'waktu_kirim', 'status_kirim'];

    protected function casts(): array
    {
        return [
            'status_kirim' => StatusKirim::class,
            'waktu_kirim' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dataSensor(): BelongsTo
    {
        return $this->belongsTo(DataSensor::class);
    }
}
