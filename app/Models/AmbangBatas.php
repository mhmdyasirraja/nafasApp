<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AmbangBatas extends Model
{
    protected $table = 'ambang_batas';

    protected $fillable = ['nama_level', 'batas_min_ppm', 'batas_max_ppm', 'aksi_buzzer'];

    protected function casts(): array
    {
        return [
            'batas_min_ppm' => 'float',
            'batas_max_ppm' => 'float',
            'aksi_buzzer' => 'boolean',
        ];
    }

    /**
     * Batas bawah (inklusif) level yang punya ambang: ['waspada' => 18.0, 'bahaya' => 35.1].
     * Satu sumber kebenaran untuk grafik, ringkasan, dan filter status.
     *
     * @return array<string, float>
     */
    public static function thresholds(): array
    {
        return static::query()
            ->whereIn('nama_level', ['waspada', 'bahaya'])
            ->pluck('batas_min_ppm', 'nama_level')
            ->map(fn ($v) => (float) $v)
            ->all();
    }
}
