<?php

namespace App\Enums;

enum StatusKirim: string
{
    case Pending = 'pending';
    case Terkirim = 'terkirim';
    case Gagal = 'gagal';
}
