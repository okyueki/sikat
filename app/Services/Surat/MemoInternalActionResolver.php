<?php

namespace App\Services\Surat;

use App\Models\Surat;

class MemoInternalActionResolver
{
    public function render(Surat $row): string
    {
        $encrypted = encrypt($row->kode_surat);
        $detailUrl = route('memo_internal.detail', $encrypted);
        $finalized = ! empty($row->memo_finalized);

        if ($row->first_verifikasi_status === 'Disetujui') {
            return '<a class="btn btn-primary waves-effect waves-light" href="' . $detailUrl . '"><i class="far fa-eye"></i></a>';
        }

        $buttons = '';
        if ($finalized) {
            $buttons .= '<a class="btn btn-info waves-effect waves-light edit" href="' . route('memo_internal.kirimsurat', $encrypted) . '"><i class="far fa-edit"></i></a> ';
        } else {
            $memo = $row->memoInternal;
            if ($memo) {
                $buttons .= '<a class="btn btn-warning waves-effect waves-light" href="' . route('memo_internal.tandaTangani', $memo) . '"><i class="fas fa-signature"></i></a> ';
            }
        }

        if ($row->first_verifikasi_status !== 'Disetujui') {
            $buttons .= '<form action="' . route('memo_internal.destroy', $row->memoInternal) . '" method="POST" style="display:inline;">'
                . '<input type="hidden" name="_token" value="' . csrf_token() . '">'
                . '<input type="hidden" name="_method" value="DELETE">'
                . '<button type="submit" class="btn btn-danger waves-effect waves-light deletesurat"><i class="far fa-trash-alt"></i></button></form> ';
        }

        $buttons .= '<a class="btn btn-primary waves-effect waves-light" href="' . $detailUrl . '"><i class="far fa-eye"></i></a>';

        return $buttons;
    }
}
