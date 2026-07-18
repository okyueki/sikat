<?php

namespace App\Http\Requests\Surat;

use Illuminate\Foundation\Http\FormRequest;

class StoreSuratKeluarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_klasifikasi_surat' => 'required',
            'id_sifat_surat' => 'required',
            'perihal' => 'required',
            'tanggal_surat' => 'required|date',
            'lampiran' => 'required',
            'file_surat' => 'required|file|mimes:docx',
            'file_lampiran' => 'nullable|file|mimes:pdf',
            'ttd_utama' => 'required',
            'ttd_2' => 'nullable',
            'ttd_3' => 'nullable',
            'ttd_4' => 'nullable',
        ];
    }
}
