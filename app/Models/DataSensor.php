<?php

namespace App\Models;

use App\Enums\StatusLevel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataSensor extends Model
{
    protected $table = 'data_sensor';

    public $timestamps = false;

    protected $fillable = ['perangkat_id', 'ambang_batas_id', 'nilai_adc', 'kadar_co_ppm', 'status_level', 'waktu_baca'];

    protected function casts(): array
    {
        return [
            'kadar_co_ppm' => 'float',
            'status_level' => StatusLevel::class,
            'waktu_baca' => 'datetime',
        ];
    }

    public function perangkat(): BelongsTo
    {
        return $this->belongsTo(Perangkat::class);
    }

    public function ambangBatas(): BelongsTo
    {
        return $this->belongsTo(AmbangBatas::class);
    }
}
