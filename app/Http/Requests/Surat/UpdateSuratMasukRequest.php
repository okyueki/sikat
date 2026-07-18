<?php

namespace App\Http\Requests\Surat;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSuratMasukRequest extends FormRequest
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
            'nomor_surat' => 'required',
            'pengirim_external' => 'required',
            'tanggal_surat' => 'required|date',
            'tanggal_surat_diterima' => 'required|date',
            'lampiran' => 'required',
            'file_surat' => 'nullable|file|mimes:pdf',
            'file_lampiran' => 'nullable|file|mimes:pdf',
        ];
    }
}
