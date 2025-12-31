<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\JadwalBudayaKerja;
use App\Models\Petugas;
use App\Models\Dokter;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class JadwalBudayaKerjaController extends Controller
{
    /**
     * Menampilkan data Jadwal Budaya Kerja dengan form input untuk memilih petugas dan hari.
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = JadwalBudayaKerja::with('petugas')->select('jadwal_budaya_kerja.*');
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('nama', function($row) {
                        // Mengakses nama pegawai dari relasi
                        return $row->petugas ? $row->petugas->nama : '-';
                    })
                ->addColumn('no_telp', function($row) {
                        // Mengakses nama pegawai dari relasi
                        return $row->petugas ? $row->petugas->no_telp : '-';
                    })
                 ->addColumn('hari', function($row) {
                    // Konversi tanggal_bertugas ke nama hari dalam bahasa Indonesia
                    return Carbon::parse($row->tanggal_bertugas)->translatedFormat('l');
                })
                ->addColumn('action', function ($row) {
                    return '<a href="'.route('jadwalbudayakerja.edit', $row->id_jadwal_budaya_kerja).'" class="btn btn-sm btn-success">Edit</a>
                            <a href="'.route('jadwalbudayakerja.destroy', $row->id_jadwal_budaya_kerja).'" class="btn btn-sm btn-danger delete-btn">Delete</a>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('jadwal_budaya_kerja.index');
    }

    public function create(Request $request)
    {
        $petugas = Petugas::where('status','1')->get();
        $dokter = Dokter::where('status','1')->get(); // ambil dokter aktif juga
        $tanggal_bertugas = $request->query('tanggal', ''); 
    
        return view('jadwal_budaya_kerja.create', compact('petugas', 'dokter', 'tanggal_bertugas'));
    }


    
    public function store(Request $request)
    {
        $request->validate([
            'nik' => 'required|max:20',
            'tanggal_bertugas' => 'required|date',
            'shift' => 'required',
        ]);

        JadwalBudayaKerja::create($request->all());
        return redirect()->route('jadwalbudayakerja.index')->with('success', 'Data Jadwal Budaya Kerja berhasil ditambahkan');
    }

       public function edit($id)
    {
        $jadwal = JadwalBudayaKerja::findOrFail($id);
        $petugas = Petugas::all(); // Ambil data petugas untuk dropdown
        return view('jadwal_budaya_kerja.edit', compact('jadwal', 'petugas'));
    }

    /**
     * Memperbarui data jadwal budaya kerja.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'nik' => 'required|max:20',
            'tanggal_bertugas' => 'required|date',
            'shift' => 'required',
        ]);

        $jadwal = JadwalBudayaKerja::findOrFail($id);
        $jadwal->update($request->all());

        return redirect()->route('jadwalbudayakerja.index')->with('success', 'Data Jadwal Budaya Kerja berhasil diperbarui');
    }

    /**
     * Menghapus data jadwal budaya kerja.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        JadwalBudayaKerja::destroy($id);
        return response()->json(['success' => 'Data berhasil dihapus']);

    }
    
   public function kirimOtomatis()
{
    $curl = curl_init();
    $token = 'XcrvvfSwJ7m6RwCG6xtf'; // Token API WhatsApp
    $tanggal_besok = Carbon::tomorrow()->toDateString(); // Ambil tanggal besok

    // Ambil jadwal budaya kerja untuk BESOK dengan relasi ke tabel petugas
    $jadwal = JadwalBudayaKerja::with('petugas')
        ->where('tanggal_bertugas', $tanggal_besok)
        ->get();

    $hari_besok = Carbon::tomorrow()->isoFormat('dddd, D MMMM Y');
    $jam_sekarang = Carbon::now()->isoFormat('HH:mm:ss');

    foreach ($jadwal as $j) {
        if (!$j->petugas || empty($j->petugas->no_telp)) {
            continue; // Skip jika tidak ada data petugas atau nomor telepon kosong
        }

        // Normalisasi nilai shift: hilangkan spasi dan kecilkan huruf
        $shift = strtolower(trim($j->shift));

        // Menentukan jam dan emoji berdasarkan shift
        if ($shift === 'pagi') {
            $jam_shift = '06:30';
            $emoji_shift = '🌅';
        } elseif ($shift === 'sore') {
            $jam_shift = '13:30';
            $emoji_shift = '🌇';
        } else {
            $jam_shift = '-';
            $emoji_shift = '❓';
        }

        // Buat pesan WhatsApp
        $pesan = "📢 *Pengingat Jadwal Jaga Besok!* 📢

Halo, *{$j->petugas->nama}* 👋

Kami ingin mengingatkan jadwal jaga budaya kerja Anda untuk BESOK:

📅 *Hari/Tanggal:* $hari_besok  
⏰ *Shift:* {$j->shift} $emoji_shift  
🕒 *Jam Masuk:* $jam_shift  

Mohon datang tepat waktu dan tetap semangat dalam bekerja! 💪😊

Terima kasih. 🙏";

        // Kirim pesan via API Fonnte
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.fonnte.com/send',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => array(
                'target' => $j->petugas->no_telp,
                'message' => $pesan
            ),
            CURLOPT_HTTPHEADER => array(
                'Authorization: ' . $token
            ),
        ));

        $response = curl_exec($curl);

        echo "✅ Pesan terkirim ke *{$j->petugas->nama}* ({$j->petugas->no_telp})  
        Shift: {$j->shift} - Jam Masuk: {$jam_shift}  
        Tanggal: {$hari_besok} Jam kirim: {$jam_sekarang}<br/><br/>";
    }

    curl_close($curl);
}
    

    public function getEvents()
    {
        $events = JadwalBudayaKerja::with('petugas')->get()->map(function ($jadwal) {
            return [
                'title' => $jadwal->petugas ? $jadwal->petugas->nama : 'Tidak Diketahui',
                'start' => $jadwal->tanggal_bertugas,
                'description' => "Shift: " . $jadwal->shift . "<br>No Telp: " . ($jadwal->petugas ? $jadwal->petugas->no_telp : '-'),
                'color' => $jadwal->shift == 'Pagi' ? '#007bff' : '#dc3545', // Biru untuk Pagi, Merah untuk Sore
            ];
        });
    
        return response()->json($events);
    }
    
    
    public function kalender()
    {
        return view('jadwal_budaya_kerja.kalender');
    }
}
