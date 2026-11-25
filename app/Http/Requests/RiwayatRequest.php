<?php

namespace App\Http\Requests;

use App\Services\RiwayatPeriode;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class RiwayatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // akses dijaga middleware auth + scoping per-user di controller
    }

    /** Filter kosong/hilang => default 7 hari terakhir (zona waktu tampil, bukan UTC). */
    protected function prepareForValidation(): void
    {
        $tz = config('nafas.display_timezone');

        $this->merge([
            'sampai' => $this->input('sampai') ?: now($tz)->toDateString(),
            'dari' => $this->input('dari') ?: now($tz)->subDays(6)->toDateString(),
            'status' => $this->input('status') ?: 'semua',
        ]);
    }

    public function rules(): array
    {
        return [
            'perangkat' => ['nullable', 'integer'],
            'dari' => ['required', 'date_format:Y-m-d'],
            'sampai' => ['required', 'date_format:Y-m-d', 'after_or_equal:dari'],
            'status' => ['required', Rule::in(['semua', 'waspada', 'bahaya'])],
        ];
    }

    public function messages(): array
    {
        return [
            'dari.date_format' => 'Tanggal awal tidak valid.',
            'sampai.date_format' => 'Tanggal akhir tidak valid.',
            'sampai.after_or_equal' => 'Tanggal akhir tidak boleh sebelum tanggal awal.',
        ];
    }

    /** Batasi rentang: query agregat atas ratusan ribu baris di shared hosting harus dibatasi. */
    public function after(): array
    {
        return [function (Validator $v) {
            if ($v->errors()->isNotEmpty()) {
                return;
            }
            $days = Carbon::parse($this->input('dari'))->diffInDays(Carbon::parse($this->input('sampai')));
            if ($days >= RiwayatPeriode::MAX_DAYS) {
                $v->errors()->add('sampai', 'Rentang maksimal '.RiwayatPeriode::MAX_DAYS.' hari.');
            }
        }];
    }

    /** @return array{0: Carbon, 1: Carbon} awal & akhir dalam UTC (sesuai penyimpanan), akhir dibatasi sampai "sekarang" */
    public function period(): array
    {
        $tz = config('nafas.display_timezone');

        $from = Carbon::createFromFormat('Y-m-d', $this->input('dari'), $tz)->startOfDay()->utc();
        $to = Carbon::createFromFormat('Y-m-d', $this->input('sampai'), $tz)->endOfDay()->utc();

        return [$from, $to->greaterThan(now()) ? now() : $to];
    }
}
