<?php

namespace App\Http\Controllers\Kepegawaian;

use App\Http\Controllers\Controller;
use App\Models\Pegawai;
use App\Models\RekapPresensi;
use App\Models\TemporaryPresensi;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class FotoAbsensiController extends Controller
{
    /**
     * Halaman HRD: lihat absensi aplikasi (temporary + rekap) beserta foto.
     * View-only — tanpa approve/reject.
     */
    public function index(Request $request)
    {
        $today = Carbon::today('Asia/Jakarta')->toDateString();
        $startDate = $request->input('start_date', $today);
        $endDate = $request->input('end_date', $today);
        $departemen = trim((string) $request->input('departemen', ''));
        $search = trim((string) $request->input('q', ''));
        $sumber = strtolower(trim((string) $request->input('sumber', 'semua')));
        $hasPhoto = $request->input('has_photo', '') === '1';

        if (!in_array($sumber, ['semua', 'sementara', 'rekap'], true)) {
            $sumber = 'semua';
        }

        try {
            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->endOfDay();
        } catch (\Throwable $e) {
            $start = Carbon::today('Asia/Jakarta')->startOfDay();
            $end = Carbon::today('Asia/Jakarta')->endOfDay();
            $startDate = $start->toDateString();
            $endDate = $end->toDateString();
        }

        if ($start->gt($end)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
            $startDate = $start->toDateString();
            $endDate = $end->toDateString();
        }

        $temporary = collect();
        $rekap = collect();

        if ($sumber === 'semua' || $sumber === 'sementara') {
            $temporary = $this->baseQuery(TemporaryPresensi::query(), $start, $end, $departemen, $search)->get();
        }

        if ($sumber === 'semua' || $sumber === 'rekap') {
            $rekap = $this->baseQuery(RekapPresensi::query(), $start, $end, $departemen, $search)->get();
        }

        $rows = $this->mapRows($temporary, 'Sementara')
            ->concat($this->mapRows($rekap, 'Rekap'))
            ->sortByDesc(function ($row) {
                return $row['jam_datang_sort'] ?? '';
            })
            ->values();

        $totalWithPhoto = $rows->where('has_photo', true)->count();
        $totalWithoutPhoto = $rows->where('has_photo', false)->count();

        if ($hasPhoto) {
            $rows = $rows->where('has_photo', true)->values();
        }

        $page = max(1, (int) $request->input('page', 1));
        $perPage = 20;
        $paginator = new LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        $departemenList = Pegawai::query()
            ->where('stts_aktif', 'AKTIF')
            ->whereNotNull('departemen')
            ->where('departemen', '!=', '')
            ->distinct()
            ->orderBy('departemen')
            ->pluck('departemen');

        return view('presensi.foto_absensi', [
            'rows' => $paginator,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'departemen' => $departemen,
            'search' => $search,
            'sumber' => $sumber,
            'hasPhoto' => $hasPhoto,
            'departemenList' => $departemenList,
            'totalTemporary' => $temporary->count(),
            'totalRekap' => $rekap->count(),
            'totalWithPhoto' => $totalWithPhoto,
            'totalWithoutPhoto' => $totalWithoutPhoto,
            'today' => $today,
        ]);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function baseQuery($query, Carbon $start, Carbon $end, string $departemen, string $search)
    {
        return $query->with('pegawai')
            ->whereBetween('jam_datang', [$start, $end])
            ->when($departemen !== '' || $search !== '', function ($q) use ($departemen, $search) {
                $q->whereHas('pegawai', function ($pq) use ($departemen, $search) {
                    if ($departemen !== '') {
                        $pq->where('departemen', $departemen);
                    }
                    if ($search !== '') {
                        $pq->where(function ($w) use ($search) {
                            $w->where('nama', 'like', '%' . $search . '%')
                                ->orWhere('nik', 'like', '%' . $search . '%');
                        });
                    }
                });
            });
    }

    /**
     * @param  \Illuminate\Support\Collection<int, TemporaryPresensi|RekapPresensi>  $items
     * @return Collection<int, array<string, mixed>>
     */
    private function mapRows(Collection $items, string $sumber): Collection
    {
        return $items->map(function ($row) use ($sumber) {
            $pegawai = $row->pegawai;
            $jamDatang = $row->jam_datang;
            $jamPulang = $row->jam_pulang;
            $photoUrl = self::photoUrl($row->photo);
            $hasPhoto = $photoUrl !== null;

            return [
                'key' => $sumber . '-' . $row->id . '-' . ($jamDatang ? $jamDatang->format('YmdHis') : '0'),
                'pegawai_id' => $row->id,
                'nama' => $pegawai->nama ?? '-',
                'nik' => $pegawai->nik ?? '-',
                'departemen' => $pegawai->departemen ?? '-',
                'shift' => $row->shift ?? '-',
                'jam_datang' => $jamDatang ? $jamDatang->format('d-m-Y H:i') : '-',
                'jam_pulang' => $jamPulang ? $jamPulang->format('d-m-Y H:i') : '-',
                'status' => $row->status ?? '-',
                'status_class' => $this->statusBadgeClass($row->status ?? ''),
                'keterangan' => $sumber === 'Rekap' ? ($row->keterangan ?? '') : '',
                'sumber' => $sumber,
                'photo' => $row->photo,
                'photo_url' => $photoUrl,
                'has_photo' => $hasPhoto,
                'jam_datang_sort' => $jamDatang ? $jamDatang->format('Y-m-d H:i:s') : '',
            ];
        });
    }

    private function statusBadgeClass(string $status): string
    {
        $normalized = strtolower(trim($status));

        if ($normalized === '' || $normalized === '-') {
            return 'bg-secondary';
        }

        if (Str::contains($normalized, 'tepat')) {
            return 'bg-success';
        }

        if (Str::contains($normalized, ['telat', 'terlambat', 'psw'])) {
            // PSW sering digabung "Tepat Waktu & PSW" — sudah ditangani di cabang tepat di atas
            // jika tidak mengandung tepat, anggap peringatan
            return 'bg-danger';
        }

        return 'bg-secondary';
    }

    /**
     * Bangun URL publik untuk foto absensi di public/presensi.
     * Null jika kosong atau file lokal tidak ada.
     */
    public static function photoUrl(?string $photo): ?string
    {
        if ($photo === null || trim($photo) === '') {
            return null;
        }

        $photo = trim($photo);

        if (Str::startsWith($photo, ['http://', 'https://'])) {
            return $photo;
        }

        $relative = $photo;
        if (Str::startsWith($photo, '/presensi/')) {
            $relative = ltrim($photo, '/');
        } elseif (Str::startsWith($photo, 'presensi/')) {
            $relative = $photo;
        } else {
            $relative = 'presensi/' . basename($photo);
        }

        $absolute = public_path($relative);
        if (!is_file($absolute)) {
            return null;
        }

        return asset($relative);
    }
}
