<?php

namespace App\Support;

final class Ppm
{
    /**
     * Dibulatkan KE BAWAH: 17,9 ppm berstatus Aman (< 18) tidak boleh tampil "18".
     * Selisih <1 ppm jauh di bawah akurasi MQ-7 yang belum dikalibrasi.
     */
    public static function display(?float $ppm): string
    {
        return $ppm === null ? '—' : (string) (int) floor($ppm);
    }
}
