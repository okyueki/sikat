<?php

namespace App\Services;

use App\Models\Departemen;
use App\Models\Pegawai;
use App\Models\Surat;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardSuratChartService
{
    /**
     * @return array<int, array{label: string, total: int}>
     */
    public function klasifikasiBreakdown(): array
    {
        $rows = Surat::query()
            ->leftJoin(
                'klasifikasi_surat',
                'surat.id_klasifikasi_surat',
                '=',
                'klasifikasi_surat.id_klasifikasi_surat'
            )
            ->select(
                DB::raw("COALESCE(klasifikasi_surat.nama_klasifikasi_surat, 'Tanpa klasifikasi') as label"),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('klasifikasi_surat.id_klasifikasi_surat', 'klasifikasi_surat.nama_klasifikasi_surat')
            ->orderByDesc('total')
            ->get();

        return $this->formatRows($rows);
    }

    /**
     * Departemen diambil dari pegawai pengirim (nik_pengirim) — sama seperti di nomor surat.
     *
     * @return array<int, array{label: string, total: int}>
     */
    public function departemenBreakdown(): array
    {
        $byNik = Surat::query()
            ->whereNotNull('nik_pengirim')
            ->where('nik_pengirim', '!=', '')
            ->select('nik_pengirim', DB::raw('COUNT(*) as total'))
            ->groupBy('nik_pengirim')
            ->get();

        if ($byNik->isEmpty()) {
            return [];
        }

        $pegawaiMap = Pegawai::query()
            ->whereIn('nik', $byNik->pluck('nik_pengirim'))
            ->get(['nik', 'departemen'])
            ->keyBy('nik');

        $departemenCounts = [];
        foreach ($byNik as $row) {
            $depId = (string) ($pegawaiMap->get($row->nik_pengirim)?->departemen ?? '');
            if ($depId === '') {
                $depId = '_unknown';
            }
            $departemenCounts[$depId] = ($departemenCounts[$depId] ?? 0) + (int) $row->total;
        }

        $depIds = collect(array_keys($departemenCounts))
            ->filter(fn ($id) => $id !== '_unknown')
            ->values();

        $namaMap = $depIds->isNotEmpty()
            ? Departemen::query()->whereIn('dep_id', $depIds)->pluck('nama', 'dep_id')
            : collect();

        $result = collect($departemenCounts)
            ->map(function ($total, $depId) use ($namaMap) {
                $label = $depId === '_unknown'
                    ? 'Departemen tidak diketahui'
                    : ($namaMap->get($depId) ?: $depId);

                return ['label' => $label, 'total' => $total];
            })
            ->sortByDesc('total')
            ->values()
            ->all();

        return $result;
    }

    private function formatRows(Collection $rows): array
    {
        return $rows->map(fn ($row) => [
            'label' => (string) $row->label,
            'total' => (int) $row->total,
        ])->values()->all();
    }
}
