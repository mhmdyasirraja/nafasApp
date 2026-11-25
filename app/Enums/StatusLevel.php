<?php

namespace App\Enums;

/** Level yang dilaporkan ALAT (SCS-02). Nilai batasnya ada di tabel ambang_batas. */
enum StatusLevel: string
{
    case Aman = 'aman';
    case Waspada = 'waspada';
    case Bahaya = 'bahaya';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /** SCS-03: buzzer hanya berbunyi saat Bahaya. */
    public function buzzerActive(): bool
    {
        return $this === self::Bahaya;
    }
}
