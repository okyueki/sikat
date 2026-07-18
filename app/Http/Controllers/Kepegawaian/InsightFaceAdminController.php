<?php

namespace App\Http\Controllers\Kepegawaian;

use App\Http\Controllers\Controller;
use App\Models\FaceVerificationLog;
use App\Models\Pegawai;
use App\Models\PegawaiFaceProfile;
use App\Services\FaceVerificationService;
use App\Services\InsightFaceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class InsightFaceAdminController extends Controller
{
    /**
     * Dashboard: status layanan, statistik, daftar enroll, log terbaru.
     */
    public function index(Request $request)
    {
        $this->assertTablesReady();

        $search = trim((string) $request->input('q', ''));
        $enrollFilter = $request->input('enroll', 'enrolled'); // enrolled | all

        $profilesQuery = PegawaiFaceProfile::query()->orderByDesc('enrolled_at');
        if ($search !== '') {
            $profilesQuery->where(function ($q) use ($search) {
                $q->where('nik', 'like', '%' . $search . '%');
                $pegawaiIds = Pegawai::query()
                    ->where('nama', 'like', '%' . $search . '%')
                    ->orWhere('nik', 'like', '%' . $search . '%')
                    ->pluck('id');
                if ($pegawaiIds->isNotEmpty()) {
                    $q->orWhereIn('pegawai_id', $pegawaiIds);
                }
            });
        }

        $profiles = $profilesQuery->paginate(20)->withQueryString();
        $pegawaiMap = Pegawai::whereIn('id', $profiles->pluck('pegawai_id'))
            ->get()
            ->keyBy('id');

        $profiles->getCollection()->transform(function (PegawaiFaceProfile $profile) use ($pegawaiMap) {
            $pegawai = $pegawaiMap->get($profile->pegawai_id);
            return [
                'profile' => $profile,
                'nama' => $pegawai->nama ?? '-',
                'departemen' => $pegawai->departemen ?? '-',
                'stts_aktif' => $pegawai->stts_aktif ?? '-',
            ];
        });

        $recentLogs = FaceVerificationLog::query()
            ->orderByDesc('created_at')
            ->limit(15)
            ->get();

        $logPegawaiMap = Pegawai::whereIn('id', $recentLogs->pluck('pegawai_id'))
            ->get()
            ->keyBy('id');

        $insightFace = app(InsightFaceService::class);

        return view('insightface.index', [
            'profiles' => $profiles,
            'recentLogs' => $recentLogs,
            'logPegawaiMap' => $logPegawaiMap,
            'search' => $search,
            'enrollFilter' => $enrollFilter,
            'stats' => $this->buildStats(),
            'serviceStatus' => $this->buildServiceStatus($insightFace),
            'config' => [
                'enabled' => (bool) config('insightface.enabled'),
                'base_url' => config('insightface.base_url'),
                'min_score' => (float) config('insightface.min_score', 70),
                'verify_mode' => config('insightface.verify_mode', 'soft'),
            ],
        ]);
    }

    /**
     * Semua log verifikasi wajah dengan filter.
     */
    public function logs(Request $request)
    {
        $this->assertTablesReady();

        $startDate = $request->input('start_date', Carbon::today('Asia/Jakarta')->toDateString());
        $endDate = $request->input('end_date', Carbon::today('Asia/Jakarta')->toDateString());
        $status = trim((string) $request->input('status', ''));
        $tipe = trim((string) $request->input('tipe', ''));
        $search = trim((string) $request->input('q', ''));

        try {
            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->endOfDay();
        } catch (\Throwable $e) {
            $start = Carbon::today('Asia/Jakarta')->startOfDay();
            $end = Carbon::today('Asia/Jakarta')->endOfDay();
            $startDate = $start->toDateString();
            $endDate = $end->toDateString();
        }

        $query = FaceVerificationLog::query()
            ->whereBetween('created_at', [$start, $end])
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($tipe !== '', fn ($q) => $q->where('tipe', $tipe))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($w) use ($search) {
                    $w->where('nik', 'like', '%' . $search . '%');
                    $ids = Pegawai::query()
                        ->where('nama', 'like', '%' . $search . '%')
                        ->pluck('id');
                    if ($ids->isNotEmpty()) {
                        $w->orWhereIn('pegawai_id', $ids);
                    }
                });
            })
            ->orderByDesc('created_at');

        $logs = $query->paginate(30)->withQueryString();
        $pegawaiMap = Pegawai::whereIn('id', $logs->pluck('pegawai_id'))
            ->get()
            ->keyBy('id');

        return view('insightface.logs', [
            'logs' => $logs,
            'pegawaiMap' => $pegawaiMap,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'status' => $status,
            'tipe' => $tipe,
            'search' => $search,
            'statusOptions' => ['match', 'mismatch', 'skipped', 'error'],
            'tipeOptions' => ['datang', 'pulang', 'verify', 'unknown'],
            'minScore' => (float) config('insightface.min_score', 70),
        ]);
    }

    /**
     * Detail satu pegawai: profil enroll + riwayat log + form re-enroll.
     */
    public function show(int $pegawaiId)
    {
        $this->assertTablesReady();

        $pegawai = Pegawai::findOrFail($pegawaiId);
        $profile = PegawaiFaceProfile::where('pegawai_id', $pegawaiId)->first();
        $logs = FaceVerificationLog::where('pegawai_id', $pegawaiId)
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('insightface.show', [
            'pegawai' => $pegawai,
            'profile' => $profile,
            'logs' => $logs,
            'faceStatus' => app(FaceVerificationService::class)->statusPayload($pegawai),
            'minScore' => (float) config('insightface.min_score', 70),
        ]);
    }

    /**
     * Form cari pegawai untuk enroll pertama kali (belum punya profil).
     */
    public function enrollSearch(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        $results = collect();

        if (strlen($q) >= 2) {
            $results = Pegawai::query()
                ->where('stts_aktif', 'AKTIF')
                ->where(function ($w) use ($q) {
                    $w->where('nama', 'like', '%' . $q . '%')
                        ->orWhere('nik', 'like', '%' . $q . '%');
                })
                ->orderBy('nama')
                ->limit(20)
                ->get();
        }

        $enrolledIds = PegawaiFaceProfile::pluck('pegawai_id')->flip();

        return view('insightface.enroll_search', [
            'q' => $q,
            'results' => $results,
            'enrolledIds' => $enrolledIds,
        ]);
    }

    /**
     * POST re-enroll / enroll wajah (admin upload foto).
     */
    public function enroll(Request $request, int $pegawaiId)
    {
        $this->assertTablesReady();

        $pegawai = Pegawai::findOrFail($pegawaiId);

        $request->validate([
            'image' => 'required',
        ], [
            'image.required' => 'Foto wajah wajib diunggah.',
        ]);

        $result = app(FaceVerificationService::class)->enroll($pegawai, $request);

        if (!$result['success']) {
            return back()->withInput()->with('error', $result['message']);
        }

        return redirect()
            ->route('insightface.show', $pegawaiId)
            ->with('success', $result['message']);
    }

    /**
     * Hapus enroll: hapus di InsightFace + baris pegawai_face_profiles.
     */
    public function destroy(int $pegawaiId)
    {
        $this->assertTablesReady();

        $pegawai = Pegawai::findOrFail($pegawaiId);
        $profile = PegawaiFaceProfile::where('pegawai_id', $pegawaiId)->first();

        if (!$profile) {
            return back()->with('error', 'Pegawai belum terdaftar wajah.');
        }

        app(InsightFaceService::class)->deleteFace($pegawai->nik);
        $profile->delete();

        return redirect()
            ->route('insightface.index')
            ->with('success', "Data wajah {$pegawai->nama} ({$pegawai->nik}) telah dihapus dari sistem.");
    }

    /**
     * Detail satu baris log (JSON response untuk modal).
     */
    public function logDetail(int $logId)
    {
        $this->assertTablesReady();

        $log = FaceVerificationLog::findOrFail($logId);
        $pegawai = Pegawai::find($log->pegawai_id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $log->id,
                'nik' => $log->nik,
                'nama' => $pegawai->nama ?? '-',
                'tipe' => $log->tipe,
                'status' => $log->status,
                'score' => $log->score,
                'shift' => $log->shift,
                'jam_datang' => $log->jam_datang?->format('d-m-Y H:i:s'),
                'created_at' => $log->created_at?->format('d-m-Y H:i:s'),
                'insightface_response' => $this->formatRawResponse($log->insightface_response),
            ],
        ]);
    }

    private function assertTablesReady(): void
    {
        if (!Schema::hasTable('pegawai_face_profiles') || !Schema::hasTable('face_verification_logs')) {
            abort(503, 'Tabel InsightFace belum tersedia. Jalankan migrasi database.');
        }
    }

  /**
     * @return array<string, int|float>
     */
    private function buildStats(): array
    {
        $today = Carbon::today('Asia/Jakarta');

        return [
            'enrolled_total' => PegawaiFaceProfile::count(),
            'logs_total' => FaceVerificationLog::count(),
            'logs_today' => FaceVerificationLog::whereDate('created_at', $today)->count(),
            'match_today' => FaceVerificationLog::whereDate('created_at', $today)->where('status', 'match')->count(),
            'mismatch_today' => FaceVerificationLog::whereDate('created_at', $today)->where('status', 'mismatch')->count(),
            'error_today' => FaceVerificationLog::whereDate('created_at', $today)->where('status', 'error')->count(),
        ];
    }

    /**
     * @return array{enabled: bool, ping: bool|null, ready: bool}
     */
    private function buildServiceStatus(InsightFaceService $service): array
    {
        $enabled = $service->isEnabled();
        $ping = null;
        if ($enabled) {
            try {
                $ping = $service->ping();
            } catch (\Throwable $e) {
                $ping = false;
            }
        }

        return [
            'enabled' => $enabled,
            'ping' => $ping,
            'ready' => app(FaceVerificationService::class)->isReady(),
        ];
    }

    private function formatRawResponse(?string $raw): ?string
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }
        $decoded = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }

        return Str::limit($raw, 2000);
    }
}
