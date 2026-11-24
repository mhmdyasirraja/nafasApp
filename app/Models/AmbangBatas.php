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
}
