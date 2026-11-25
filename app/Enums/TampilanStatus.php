<?php

namespace App\Enums;

/**
 * State yang DITAMPILKAN dashboard. Lebih banyak dari StatusLevel karena dashboard
 * harus jujur saat alat offline / sensor rusak / belum ada data (tidak boleh "Aman" dari data basi).
 */
enum TampilanStatus: string
{
    case Aman = 'aman';
    case Waspada = 'waspada';
    case Bahaya = 'bahaya';
    case Offline = 'offline';
    case SensorError = 'sensor_error';
    case BelumAdaData = 'belum_ada_data';

    public static function fromLevel(StatusLevel $level): self
    {
        return self::from($level->value);
    }

    public function isMeasured(): bool
    {
        return in_array($this, [self::Aman, self::Waspada, self::Bahaya], true);
    }
}
