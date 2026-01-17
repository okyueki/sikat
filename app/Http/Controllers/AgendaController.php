<?php

namespace App\Http\Controllers;

use App\Models\Agenda;
use App\Models\Pegawai;
use App\Models\Petugas;
use App\Models\Surat;
use App\Models\AgendaMateri;
use Yajra\DataTables\DataTables;
use Illuminate\Http\Request;
use App\Models\AgendaToken;
use App\Models\AbsensiAgenda;
use Carbon\Carbon;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class AgendaController extends Controller
{
    /**
     * Menampilkan daftar agenda.
     */
    public function index()
    {
        
        $agendas = Agenda::with(['pimpinan', 'notulenPegawai'])->get();
    
        $events = $agendas->map(function($agenda) {
            // Konversi NIP menjadi nama pegawai
            $yangTerundangNip = is_string($agenda->yang_terundang) 
                ? json_decode($agenda->yang_terundang, true) 
                : $agenda->yang_terundang;
            
            $yangTerundangNama = '-';
            if (is_array($yangTerundangNip) && count($yangTerundangNip) > 0) {
                // Cek apakah semua pegawai aktif terundang
                $semuaNikAktif = Pegawai::where('stts_aktif', 'AKTIF')->pluck('nik')->toArray();
                $isAll = count(array_intersect($semuaNikAktif, $yangTerundangNip)) === count($semuaNikAktif) 
                         && count($yangTerundangNip) === count($semuaNikAktif);
                
                if ($isAll) {
                    $yangTerundangNama = 'Semua Pegawai Aktif';
                } else {
                    // Ambil nama pegawai berdasarkan NIP
                    $pegawaiList = Pegawai::whereIn('nik', $yangTerundangNip)
                        ->where('stts_aktif', 'AKTIF')
                        ->get(['nik', 'nama']);
                    
                    $namaList = $pegawaiList->pluck('nama')->toArray();
                    $yangTerundangNama = count($namaList) > 0 ? implode(', ', $namaList) : '-';
                }
            }
            
            return [
                'id' => $agenda->id,
                'title' => $agenda->judul,
                'start' => $agenda->mulai,
                'end' => $agenda->akhir,
                'deskripsi' => $agenda->deskripsi,
                'tempat' => $agenda->tempat,
                'pimpinan_rapat' => $agenda->pimpinan->nama ?? '-', 
                'notulen' => $agenda->notulenPegawai->nama ?? '-', 
                'keterangan' => $agenda->keterangan,
                'yang_terundang' => $yangTerundangNama, // String nama pegawai yang terundang
            ];
        });
        $pegawai = Pegawai::where('stts_aktif', 'AKTIF')->get();
    
        // Kirim data agenda dan events ke view
        return view('event.acara_index', compact('agendas', 'events'));
    }


    /**
     * Menampilkan form untuk menambah agenda baru.
     */
    public function create()
    {
        // Mengambil data pegawai untuk pilihan pimpinan, notulen, dan yang terundang
        $pegawai = Pegawai::where('stts_aktif', 'AKTIF')->get();

        // Ambil surat keluar yang sudah disetujui dan belum ada realisasi
        $suratKeluar = Surat::whereHas('verifikasi', function($q) {
            $q->where('status_surat', 'Disetujui');
        })
        ->whereDoesntHave('agenda')
        ->orderBy('tanggal_surat', 'desc')
        ->get(['id_surat', 'nomor_surat', 'perihal', 'tanggal_surat']);

        return view('event.acara_create', compact('pegawai', 'suratKeluar'));
    }

    /**
     * Menyimpan agenda baru ke database.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'judul' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'mulai' => 'required|date',
            'akhir' => 'nullable|date|after_or_equal:mulai',
            'tempat' => 'nullable|string',
            'pimpinan_rapat' => 'nullable|string',
            'notulen' => 'nullable|string',
            'yang_terundang' => 'nullable|array',
            'yang_terundang.*' => 'nullable|string',
            'foto' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'materi' => 'nullable|file|mimes:pdf,doc,docx|max:2048',
            'keterangan' => 'nullable|string',
            'is_realisasi_surat' => 'nullable|boolean',
            'id_surat_keluar' => 'nullable|exists:surat,id_surat',
        ]);
        
        // Generate nomor_agenda otomatis
        $year = Carbon::now()->year;
        $latestAgenda = Agenda::whereYear('created_at', $year)->latest()->first();
        $nextNumber = $latestAgenda ? (int)substr($latestAgenda->nomor_agenda, -3) + 1 : 1;
        $nomorAgenda = 'AGD-' . $year . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        $validatedData['nomor_agenda'] = $nomorAgenda;
    
        // ✅ Handle "all" dengan BENAR — dan HANYA sekali
        if (is_array($request->yang_terundang) && in_array('all', $request->yang_terundang)) {
            $semuaNik = Pegawai::where('stts_aktif', 'AKTIF')->pluck('nik')->toArray();
            $validatedData['yang_terundang'] = json_encode($semuaNik);
        } else {
            $validatedData['yang_terundang'] = json_encode($request->yang_terundang ?? []);
        }
    
        // Upload file
        if ($request->hasFile('foto')) {
            $validatedData['foto'] = $request->file('foto')->store('agenda_fotos', 'public');
        }
        
        // Handle multiple materi files (jika ada)
        // Note: materi lama (single file) tetap disimpan di kolom 'materi' untuk backward compatibility
        if ($request->hasFile('materi')) {
            $materiFiles = $request->file('materi');
            // Simpan file pertama ke kolom 'materi' (backward compatibility)
            if (is_array($materiFiles)) {
                $validatedData['materi'] = $materiFiles[0]->store('agenda_materis', 'public');
            } else {
                $validatedData['materi'] = $materiFiles->store('agenda_materis', 'public');
            }
        }

        // Handle relasi surat keluar
        if ($request->is_realisasi_surat && $request->id_surat_keluar) {
            $validatedData['id_surat_keluar'] = $request->id_surat_keluar;
            $validatedData['status_realisasi'] = 'sedang';
        } else {
            $validatedData['status_realisasi'] = 'belum';
        }

        // Set created_by dan status_acara
        $validatedData['created_by'] = Auth::user()->username ?? null;
        $validatedData['status_acara'] = 'draft';
    
        // ✅ JANGAN ASSIGN LAGI DI SINI!
        $agenda = Agenda::create($validatedData);

        // Upload multiple materi files ke tabel agenda_materi (jika ada lebih dari 1 file)
        if ($request->hasFile('materi')) {
            $materiFiles = $request->file('materi');
            if (is_array($materiFiles) && count($materiFiles) > 1) {
                // Skip file pertama karena sudah disimpan di kolom 'materi'
                foreach (array_slice($materiFiles, 1) as $file) {
                    AgendaMateri::create([
                        'agenda_id' => $agenda->id,
                        'nama_file' => $file->getClientOriginalName(),
                        'path_file' => $file->store('agenda_materi', 'public'),
                        'ukuran_file' => $file->getSize(),
                        'tipe_file' => $file->getMimeType(),
                        'diupload_oleh' => Auth::user()->username ?? null,
                        'diupload_pada' => now(),
                        'keterangan' => null,
                        'jenis' => 'materi'
                    ]);
                }
            }
        }
    
        return redirect()->route('acara_show', $agenda->id)->with('success', 'Agenda berhasil dibuat! Anda dapat menambahkan materi tambahan, dokumentasi, dan kesimpulan di halaman ini.');
    }
    /**
     * Menampilkan form untuk mengedit agenda.
     */
    public function edit($id)
    {
        $agenda = Agenda::findOrFail($id);
        $pegawai = Pegawai::where('stts_aktif', 'AKTIF')->get();
        
        // Cek apakah semua pegawai aktif terundang
        $yangTerundang = is_string($agenda->yang_terundang) ? json_decode($agenda->yang_terundang, true) : $agenda->yang_terundang;
        $semuaNikAktif = Pegawai::where('stts_aktif', 'AKTIF')->pluck('nik')->toArray();
        $isAll = false;
        
        if (is_array($yangTerundang) && count($yangTerundang) > 0) {
            // Cek apakah semua NIK aktif ada di yang terundang dan jumlahnya sama
            $intersect = array_intersect($semuaNikAktif, $yangTerundang);
            $isAll = count($intersect) === count($semuaNikAktif) && count($yangTerundang) === count($semuaNikAktif);
        }

        // Ambil surat keluar yang sudah disetujui dan belum ada realisasi (atau surat yang sedang digunakan)
        $suratKeluar = Surat::whereHas('verifikasi', function($q) {
            $q->where('status_surat', 'Disetujui');
        })
        ->where(function($q) use ($agenda) {
            $q->whereDoesntHave('agenda')
              ->orWhere('id_surat', $agenda->id_surat_keluar);
        })
        ->orderBy('tanggal_surat', 'desc')
        ->get(['id_surat', 'nomor_surat', 'perihal', 'tanggal_surat']);

        return view('event.acara_edit', compact('agenda', 'pegawai', 'isAll', 'suratKeluar'));
    }

    /**
     * Mengupdate data agenda.
     */
    public function update(Request $request, $id)
    {
        $agenda = Agenda::findOrFail($id);
    
        // Validasi: lewati validasi exists jika ada "all"
        $rules = [
            'judul' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'mulai' => 'required|date',
            'akhir' => 'nullable|date|after_or_equal:mulai',
            'tempat' => 'nullable|string',
            'pimpinan_rapat' => 'nullable|string|exists:pegawai,nik',
            'notulen' => 'nullable|string|exists:pegawai,nik',
            'yang_terundang' => 'nullable|array',
            'foto' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'materi' => 'nullable|file|mimes:pdf,doc,docx|max:2048',
            'keterangan' => 'nullable|string',
            'is_realisasi_surat' => 'nullable|boolean',
            'id_surat_keluar' => 'nullable|exists:surat,id_surat',
        ];
    
        // Jika tidak ada "all", baru validasi exists
        if (!in_array('all', (array) $request->yang_terundang)) {
            $rules['yang_terundang.*'] = 'exists:pegawai,nik';
        }
    
        $validatedData = $request->validate($rules);
    
        // Handle file
        if ($request->hasFile('foto')) {
            // Hapus foto lama jika ada
            if ($agenda->foto && Storage::disk('public')->exists($agenda->foto)) {
                Storage::disk('public')->delete($agenda->foto);
            }
            $validatedData['foto'] = $request->file('foto')->store('agenda_fotos', 'public');
        }
        if ($request->hasFile('materi')) {
            // Hapus materi lama jika ada
            if ($agenda->materi && Storage::disk('public')->exists($agenda->materi)) {
                Storage::disk('public')->delete($agenda->materi);
            }
            $validatedData['materi'] = $request->file('materi')->store('agenda_materis', 'public');
        }
    
        // Handle "all" di update
        if (is_array($request->yang_terundang) && in_array('all', $request->yang_terundang)) {
            $semuaNik = Pegawai::where('stts_aktif', 'AKTIF')->pluck('nik')->toArray();
            $validatedData['yang_terundang'] = json_encode($semuaNik);
        } else {
            $validatedData['yang_terundang'] = json_encode($request->yang_terundang ?? []);
        }

        // Handle relasi surat keluar di update
        if ($request->has('is_realisasi_surat') && $request->is_realisasi_surat && $request->id_surat_keluar) {
            $validatedData['id_surat_keluar'] = $request->id_surat_keluar;
            $validatedData['status_realisasi'] = 'sedang';
        } elseif (!$request->has('is_realisasi_surat') || !$request->is_realisasi_surat) {
            $validatedData['id_surat_keluar'] = null;
            $validatedData['status_realisasi'] = 'belum';
        }

        // Auto-update status acara berdasarkan waktu
        $mulai = Carbon::parse($validatedData['mulai']);
        $akhir = $validatedData['akhir'] ? Carbon::parse($validatedData['akhir']) : null;
        $sekarang = Carbon::now();
        
        if ($mulai->isFuture()) {
            $validatedData['status_acara'] = 'akan_datang';
        } elseif ($akhir && $sekarang->isAfter($akhir)) {
            $validatedData['status_acara'] = 'selesai';
        } elseif ($mulai->isPast() && (!$akhir || $sekarang->isBefore($akhir))) {
            $validatedData['status_acara'] = 'sedang_berlangsung';
        } else {
            $validatedData['status_acara'] = $agenda->status_acara ?? 'draft';
        }
    
        $agenda->update($validatedData);
    
        return redirect()->route('acara_index')->with('success', 'Agenda berhasil diperbarui');
    }
/**
 * Menghapus data agenda.
 */
    public function destroy($id)
    {
        $agenda = Agenda::findOrFail($id);
        $agenda->delete();
    
        return redirect()->route('backend_acara')->with('success', 'Agenda berhasil dihapus');
    }

    public function show($id)
    {
        $agenda = Agenda::with(['pimpinan', 'notulenPegawai', 'suratKeluar', 'materiFiles', 'dokumentasiFiles'])->findOrFail($id);
    
        // Hitung jumlah yang terundang — tangani kasus "all"
        if ($agenda->yang_terundang === 'all') {
            $jumlahTerundang = Pegawai::where('stts_aktif', 'AKTIF')->count();
            $listTerundang = []; // atau null, tergantung kebutuhan tampilan
            $isAll = true;
        } else {
            $decoded = is_array($agenda->yang_terundang) 
                ? $agenda->yang_terundang 
                : json_decode($agenda->yang_terundang, true);
            
            $listTerundang = is_array($decoded) ? $decoded : [];
            $jumlahTerundang = count($listTerundang);
            $isAll = false;
        }
        
        // Hitung statistik absensi
        $pegawaiIds = $isAll 
            ? Pegawai::where('stts_aktif', 'AKTIF')->pluck('nik')->toArray()
            : (is_array($listTerundang) ? $listTerundang : []);
        
        $jumlahHadir = 0;
        $jumlahTidakHadir = $jumlahTerundang;
        $absensiList = collect();
        $belumAbsenList = collect();
        
        if (!empty($pegawaiIds)) {
            $jumlahHadir = AbsensiAgenda::where('agenda_id', $id)
                ->whereIn('nik', $pegawaiIds)
                ->whereNotNull('waktu_kehadiran')
                ->count();
            
            $jumlahTidakHadir = $jumlahTerundang - $jumlahHadir;
            
            // Ambil daftar yang sudah absen
            $absensiList = AbsensiAgenda::with('pegawai')
                ->where('agenda_id', $id)
                ->whereIn('nik', $pegawaiIds)
                ->orderBy('waktu_kehadiran', 'desc')
                ->get();
            
            // Ambil daftar yang belum absen
            $sudahAbsenNik = $absensiList->pluck('nik')->toArray();
            $belumAbsenNik = array_diff($pegawaiIds, $sudahAbsenNik);
            
            if (!empty($belumAbsenNik)) {
                $belumAbsenList = Pegawai::whereIn('nik', $belumAbsenNik)->get();
            }
        }
    
        // Auto-update status acara berdasarkan waktu
        $this->updateStatusAcara($agenda);
        
        // Load materi dan dokumentasi
        $materiFiles = $agenda->materiFiles()->with('uploader')->orderBy('diupload_pada', 'desc')->get();
        $dokumentasiFiles = $agenda->dokumentasiFiles()->with('uploader')->orderBy('diupload_pada', 'desc')->get();
        
        return view('event.acara_show', compact(
            'agenda', 
            'jumlahTerundang', 
            'listTerundang', 
            'isAll',
            'jumlahHadir',
            'jumlahTidakHadir',
            'absensiList',
            'belumAbsenList',
            'materiFiles',
            'dokumentasiFiles'
        ));
    }

    public function backendAcara(Request $request)
    {
        if ($request->ajax()) {
            $agendas = Agenda::with(['pimpinan', 'notulenPegawai', 'suratKeluar'])
                ->select('agendas.*');

            // Filter berdasarkan tahun (default: tahun ini)
            $filterTahun = $request->get('filter_tahun');
            if ($filterTahun) {
                $agendas->whereYear('mulai', $filterTahun);
            } else {
                // Default: tahun ini jika tidak ada filter
                $agendas->whereYear('mulai', Carbon::now()->year);
            }

            // Filter berdasarkan bulan (opsional)
            $filterBulan = $request->get('filter_bulan');
            if ($filterBulan) {
                $agendas->whereMonth('mulai', $filterBulan);
            }

            // Filter berdasarkan status realisasi
            $filterStatusRealisasi = $request->get('filter_status_realisasi');
            if ($filterStatusRealisasi) {
                $agendas->where('status_realisasi', $filterStatusRealisasi);
            }
    
            return DataTables::of($agendas)
                ->addIndexColumn()
                ->addColumn('jumlah_terundang', function ($row) {
                    // Hitung jumlah yang terundang
                    if ($row->yang_terundang === 'all') {
                        return Pegawai::where('stts_aktif', 'AKTIF')->count();
                    } else {
                        $decoded = is_array($row->yang_terundang) 
                            ? $row->yang_terundang 
                            : json_decode($row->yang_terundang, true);
                        return is_array($decoded) ? count($decoded) : 0;
                    }
                })
                ->addColumn('pimpinan_nama', function ($row) {
                    return $row->pimpinan->nama ?? '-';
                })
                ->addColumn('notulen_nama', function ($row) {
                    return $row->notulenPegawai->nama ?? '-';
                })
                ->addColumn('status_realisasi', function ($row) {
                    if (!$row->id_surat_keluar) {
                        return '<span class="badge bg-secondary">-</span>';
                    }
                    
                    $badgeClass = match($row->status_realisasi) {
                        'belum' => 'bg-secondary',
                        'sedang' => 'bg-warning',
                        'selesai' => 'bg-success',
                        default => 'bg-secondary'
                    };
                    
                    $label = match($row->status_realisasi) {
                        'belum' => 'Belum',
                        'sedang' => 'Sedang',
                        'selesai' => 'Selesai',
                        default => '-'
                    };
                    
                    return '<span class="badge ' . $badgeClass . '">' . $label . '</span>';
                })
                ->rawColumns(['status_realisasi', 'aksi'])
                ->addColumn('aksi', function ($row) {
                    $detailUrl      = route('acara_show', $row->id);
                    $editUrl        = route('acara_edit', $row->id);
                    $deleteUrl      = route('acara_destroy', $row->id);
                    $qrcodeUrl      = route('agenda.qr_code', ['agendaId' => $row->id]);
                    $pdfUrl         = route('agenda.pdf', $row->id);
                    // $sendMessageUrl = route('agenda.send_message', ['agendaId' => $row->id]);
    
                    $btn = '
                        <a href="'.$detailUrl.'" class="btn btn-info btn-sm">Detail</a>
                        <a href="'.$pdfUrl.'" target="_blank" class="btn btn-danger btn-sm"><i class="fas fa-file-pdf"></i> PDF</a>
                        <a href="'.$qrcodeUrl.'" class="btn btn-info btn-sm"><i class="fa-solid fa-qrcode"></i></a>
                        <a href="'.$editUrl.'" class="btn btn-warning btn-sm">Edit</a>
                        
                    ';
    
                    // Cek langsung apakah ada absensi
                    if (!$row->absensi()->exists()) {
                        $btn .= '
                            <form action="'.$deleteUrl.'" method="POST" style="display:inline-block;">
                                '.csrf_field().'
                                '.method_field('DELETE').'
                                <button type="submit" class="btn btn-danger btn-sm"
                                    onclick="return confirm(\'Apakah Anda yakin ingin menghapus agenda ini?\')">
                                    Hapus
                                </button>
                            </form>
                        ';
                    }
    
                    return $btn;
                })
                ->rawColumns(['status_realisasi', 'aksi'])
                ->make(true);
        }
        
        // Return view untuk non-AJAX request
        return view('event.backend_acara');
    
        return view('event.backend_acara');
    }

    // Fungsi untuk mengirim pesan ke WAHA
    public function sendMessage($agendaId) 
    {
        $agenda = Agenda::findOrFail($agendaId);
         $yangTerundang = $agenda->yang_terundang;
    
        // Ambil nomor WA dari tabel petugas
        $noTelpList = Petugas::whereIn('nip', $yangTerundang)->pluck('no_telp')->toArray();
    
        foreach ($noTelpList as $noTelp) {
            $nomorWA = '62' . ltrim($noTelp, '0') . '@c.us';
    
            // Generate QR Code & simpan ke storage
            $qrCodePath = 'qrcodes/' . Str::uuid() . '.png';
            $qrCode = QrCode::size(300)->generate(route('agenda.qr_code', ['agendaId' => $agenda->id]));
            Storage::disk('public')->put($qrCodePath, $qrCode);
    
            // Link Absensi
            $link = route('agenda.qr_code', ['agendaId' => $agenda->id]);
    
            // Kirim pesan WA
            $pesan = "📢 *Undangan Agenda* 📢\n\n"
                . "Agenda: {$agenda->judul}\n"
                . "📅 Tanggal: " . Carbon::parse($agenda->mulai)->format('d M Y H:i') . "\n"
                . "📍 Lokasi: {$agenda->tempat}\n\n"
                . "🔗 Link Absensi: $link";
    
            $this->sendToWahaText($nomorWA, $pesan);
        }
    
         return view('event.backend_acara')->with('success', 'Pesan berhasil dikirim ke semua yang terundang');
    }
    // Fungsi untuk mengirim pesan WAHA dengan QR Code
    private function sendToWahaText($number, $message)
        {
            $wahaUrl = 'http://192.168.10.47:3000/api/sendText';
            $session = 'default';
        
            $client = new \GuzzleHttp\Client();
            $response = $client->post($wahaUrl, [
                'json' => [
                    'session' => $session,
                    'chatId' => $number,
                    'text' => $message
                ]
            ]);
        
            return $response->getBody();
        }
    
    public function generateQRCodeBaru($agendaId)
        {
             $agenda = Agenda::findOrFail($agendaId);
        
            $linkScan = url('/proses-scan?agendaId=' . $agenda->id);
        
            $qrcode = QrCode::size(300)->generate($linkScan);
        
            return view('event.qrcode', compact('agenda', 'qrcode'));
        }


 // Method to show the QR Code page
    public function showQRCodePage($agendaId)
    {
        $agenda = Agenda::findOrFail($agendaId);
    
        // Parse waktu mulai agenda
        $mulai = Carbon::parse($agenda->mulai);
        $sekarang = Carbon::now();
        $waktuBukaQR = $mulai->copy()->subMinutes(35); // 45 menit sebelum mulai
    
        // Cek apakah QR Code boleh di-generate
        $bolehGenerate = $sekarang->greaterThanOrEqualTo($waktuBukaQR) && $sekarang->lessThanOrEqualTo(Carbon::parse($agenda->akhir));
    
        if (!$bolehGenerate) {
            // Jika belum waktunya, tampilkan pesan
            return view('event.generate_qr_code', [
                'agenda' => $agenda,
                'agendaBerakhir' => false,
                'belumWaktunya' => true,
                'waktuBukaQR' => $waktuBukaQR,
            ]);
        }
    
        // Jika sudah waktunya, generate QR seperti biasa
        $token = Str::random(32);
        $expiry = Carbon::now()->addMinutes(2);
    
        // Hapus token lama yang sudah expired (opsional, untuk kebersihan)
        AgendaToken::where('agenda_id', $agendaId)
            ->where('expiry', '<', now())
            ->delete();
        
        // Buat token BARU (jangan replace yang lama!)
        AgendaToken::create([
            'agenda_id' => $agendaId,
            'token' => $token,
            'expiry' => $expiry
        ]);
    
        $qrData = ['agenda_id' => $agendaId, 'token' => $token];
        $fileName = 'qr_codes/qr_code_' . $agendaId . '_' . time() . '.svg';
        Storage::disk('public')->put($fileName, QrCode::size(300)->generate(json_encode($qrData)));
        $qrCodeUrl = asset('storage/' . $fileName);
    
        return view('event.generate_qr_code', [
            'qrCodeUrl' => $qrCodeUrl,
            'token' => $token,
            'agendaId' => $agendaId,
            'agendaBerakhir' => false,
            'belumWaktunya' => false,
            'agenda' => $agenda,
        ]);
    }
    // Method to generate the QR code via AJAX
    public function generateQRCode(Request $request)
    {
        $agendaId = $request->get('agenda_id');
    
        // Selalu buat token baru setiap kali permintaan AJAX dilakukan
        $token = Str::random(32);
        $expiry = Carbon::now()->addMinutes(3);
    
        // Hapus token lama yang sudah expired (opsional, untuk kebersihan)
        AgendaToken::where('agenda_id', $agendaId)
            ->where('expiry', '<', now())
            ->delete();
        
        // Buat token BARU (jangan replace yang lama!)
        AgendaToken::create([
            'agenda_id' => $agendaId,
            'token' => $token,
            'expiry' => $expiry
        ]);
    
        // Generate QR Code
        $qrData = [
            'agenda_id' => $agendaId,
            'token' => $token,
        ];
        $fileName = 'qr_codes/qr_code_' . $agendaId . '_' . time() . '.svg';
        Storage::disk('public')->put($fileName, QrCode::size(300)->generate(json_encode($qrData)));
    
        $qrCodeUrl = asset('storage/' . $fileName);
    
        return response()->json([
            'qrCodeUrl' => $qrCodeUrl,
            'token' => $token,
        ]);
    }
    public function checkTokenStatus(Request $request)
{
    $agendaId = $request->agenda_id;
    $token = $request->token;

    // cek apakah token sudah dipakai di AbsensiAgenda
    $used = AbsensiAgenda::where('agenda_id', $agendaId)
        ->where('token', $token)
        ->exists();

    return response()->json([
        'used' => $used
    ]);
}

    public function generateAgendaPDF($id)
    {
        Carbon::setLocale('id');
        $agenda = Agenda::with(['pimpinan', 'notulenPegawai'])->findOrFail($id);
        
        // Encode gambar kop surat ke base64
        $path = public_path('assets/images/logo_hitam.png');
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);
        $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        
        // Format tanggal
        $tanggalDibuat = Carbon::parse($agenda->created_at)->translatedFormat('d F Y');
        $tanggalMulai = Carbon::parse($agenda->mulai)->translatedFormat('l, d F Y');
        $waktuMulai = Carbon::parse($agenda->mulai)->translatedFormat('H:i');
        $tanggalAkhir = $agenda->akhir ? Carbon::parse($agenda->akhir)->translatedFormat('l, d F Y') : null;
        $waktuAkhir = $agenda->akhir ? Carbon::parse($agenda->akhir)->translatedFormat('H:i') : null;
        
        // Handle yang terundang
        $yangTerundang = is_string($agenda->yang_terundang) ? json_decode($agenda->yang_terundang, true) : $agenda->yang_terundang;
        $isAll = false;
        $listTerundang = [];
        
        // Cek apakah semua pegawai aktif terundang
        if (is_array($yangTerundang)) {
            $semuaNikAktif = Pegawai::where('stts_aktif', 'AKTIF')->pluck('nik')->toArray();
            $intersect = array_intersect($semuaNikAktif, $yangTerundang);
            $isAll = count($intersect) === count($semuaNikAktif) && count($yangTerundang) === count($semuaNikAktif);
            
            if (!$isAll) {
                // Ambil data pegawai yang terundang dengan relasi
                $pegawaiList = Pegawai::with('departemen_unit')->whereIn('nik', $yangTerundang)->get();
                foreach ($pegawaiList as $pegawai) {
                    $listTerundang[] = [
                        'nik' => $pegawai->nik,
                        'nama' => $pegawai->nama,
                        'jabatan' => $pegawai->jbtn ?? '-',
                        'unit' => optional($pegawai->departemen_unit)->nama ?? '-'
                    ];
                }
            } else {
                // Jika semua, ambil semua pegawai aktif dengan relasi
                $allPegawai = Pegawai::with('departemen_unit')->where('stts_aktif', 'AKTIF')->get();
                foreach ($allPegawai as $pegawai) {
                    $listTerundang[] = [
                        'nik' => $pegawai->nik,
                        'nama' => $pegawai->nama,
                        'jabatan' => $pegawai->jbtn ?? '-',
                        'unit' => optional($pegawai->departemen_unit)->nama ?? '-'
                    ];
                }
            }
        }
        
        // Sort by nama
        usort($listTerundang, function($a, $b) {
            return strcmp($a['nama'], $b['nama']);
        });
        
        $data = [
            'agenda' => $agenda,
            'kop_surat' => $base64,
            'tanggal_dibuat' => $tanggalDibuat,
            'tanggal_mulai' => $tanggalMulai,
            'waktu_mulai' => $waktuMulai,
            'tanggal_akhir' => $tanggalAkhir,
            'waktu_akhir' => $waktuAkhir,
            'list_terundang' => $listTerundang,
            'is_all' => $isAll,
            'jumlah_terundang' => count($listTerundang)
        ];
        
        $pdf = Pdf::loadView('event.agenda_pdf', $data);
        return $pdf->stream('surat_undangan_agenda_' . $agenda->nomor_agenda . '.pdf');
    }

    /**
     * Upload materi tambahan untuk agenda
     */
    public function uploadMateri(Request $request, $id)
    {
        $agenda = Agenda::findOrFail($id);
        
        // Check permission: hanya panitia (created_by) yang bisa upload
        if ($agenda->created_by && Auth::user()->username != $agenda->created_by) {
            return redirect()->back()->with('error', 'Anda tidak memiliki izin untuk mengupload materi');
        }
        
        $request->validate([
            'materi.*' => 'required|file|mimes:pdf,doc,docx|max:2048',
            'keterangan' => 'nullable|string'
        ]);
        
        if ($request->hasFile('materi')) {
            foreach ($request->file('materi') as $file) {
                AgendaMateri::create([
                    'agenda_id' => $agenda->id,
                    'nama_file' => $file->getClientOriginalName(),
                    'path_file' => $file->store('agenda_materis', 'public'),
                    'ukuran_file' => $file->getSize(),
                    'tipe_file' => $file->getMimeType(),
                    'diupload_oleh' => Auth::user()->username ?? null,
                    'diupload_pada' => now(),
                    'keterangan' => $request->keterangan,
                    'jenis' => 'materi'
                ]);
            }
        }
        
        return redirect()->back()->with('success', 'Materi berhasil diupload');
    }

    /**
     * Upload foto dokumentasi setelah acara
     */
    public function uploadDokumentasi(Request $request, $id)
    {
        $agenda = Agenda::findOrFail($id);
        
        // Check permission: hanya panitia (created_by) yang bisa upload
        if ($agenda->created_by && Auth::user()->username != $agenda->created_by) {
            return redirect()->back()->with('error', 'Anda tidak memiliki izin untuk mengupload dokumentasi');
        }
        
        $request->validate([
            'dokumentasi.*' => 'required|file|mimes:jpg,jpeg,png|max:2048',
            'keterangan' => 'nullable|string'
        ]);
        
        if ($request->hasFile('dokumentasi')) {
            foreach ($request->file('dokumentasi') as $file) {
                AgendaMateri::create([
                    'agenda_id' => $agenda->id,
                    'nama_file' => $file->getClientOriginalName(),
                    'path_file' => $file->store('agenda_dokumentasi', 'public'),
                    'ukuran_file' => $file->getSize(),
                    'tipe_file' => $file->getMimeType(),
                    'diupload_oleh' => Auth::user()->username ?? null,
                    'diupload_pada' => now(),
                    'keterangan' => $request->keterangan,
                    'jenis' => 'dokumentasi'
                ]);
            }
            
            // Update status realisasi jika ada surat keluar
            if ($agenda->id_surat_keluar) {
                $agenda->update(['status_realisasi' => 'selesai']);
            }
        }
        
        return redirect()->back()->with('success', 'Foto dokumentasi berhasil diupload');
    }

    /**
     * Simpan kesimpulan dari notulen
     */
    public function simpanKesimpulan(Request $request, $id)
    {
        $agenda = Agenda::findOrFail($id);
        
        // Check permission: hanya notulen yang bisa catat kesimpulan
        if (Auth::user()->username != $agenda->notulen) {
            return redirect()->back()->with('error', 'Anda tidak memiliki izin untuk mencatat kesimpulan');
        }
        
        $request->validate([
            'kesimpulan_notulen' => 'required|string'
        ]);
        
        $agenda->update([
            'kesimpulan_notulen' => $request->kesimpulan_notulen,
            'tanggal_selesai_notulen' => now()
        ]);
        
        return redirect()->back()->with('success', 'Kesimpulan berhasil disimpan');
    }

    /**
     * Auto-update status acara berdasarkan waktu
     */
    private function updateStatusAcara($agenda)
    {
        $mulai = Carbon::parse($agenda->mulai);
        $akhir = $agenda->akhir ? Carbon::parse($agenda->akhir) : null;
        $sekarang = Carbon::now();
        $statusBaru = null;
        
        if ($mulai->isFuture()) {
            $statusBaru = 'akan_datang';
        } elseif ($akhir && $sekarang->isAfter($akhir)) {
            $statusBaru = 'selesai';
        } elseif ($mulai->isPast() && (!$akhir || $sekarang->isBefore($akhir))) {
            $statusBaru = 'sedang_berlangsung';
        }
        
        if ($statusBaru && $agenda->status_acara != $statusBaru) {
            $agenda->update(['status_acara' => $statusBaru]);
            $agenda->refresh();
        }
    }
}