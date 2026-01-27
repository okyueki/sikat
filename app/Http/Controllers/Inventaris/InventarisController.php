<?php

namespace App\Http\Controllers\Inventaris;
use App\Http\Controllers\Controller; // Import Controller utama
use App\Models\Inventaris;
use App\Models\InventarisBarang;
use App\Models\InventarisGambar;
use App\Models\InventarisRuang;
use Yajra\DataTables\DataTables;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Log;

class InventarisController extends Controller
{
    // Method index()
public function index(Request $request)
{
    // Cek apakah request berasal dari DataTables (AJAX request)
    if ($request->ajax()) {
        // Load helper function sekali saja
        if (!function_exists('formatRupiah')) {
            $formatPath = app_path('Helpers/FormatHelper.php');
            if (file_exists($formatPath)) {
                require_once $formatPath;
            }
        }

        // Optimasi: Gunakan join untuk menghindari N+1 query dan mempercepat pencarian
        $query = Inventaris::select([
                'inventaris.*',
                'inventaris_barang.nama_barang',
                'inventaris_produsen.nama_produsen',
                'inventaris_merk.nama_merk',
                'inventaris_ruang.nama_ruang'
            ])
            ->leftJoin('inventaris_barang', 'inventaris.kode_barang', '=', 'inventaris_barang.kode_barang')
            ->leftJoin('inventaris_produsen', 'inventaris_barang.kode_produsen', '=', 'inventaris_produsen.kode_produsen')
            ->leftJoin('inventaris_merk', 'inventaris_barang.id_merk', '=', 'inventaris_merk.id_merk')
            ->leftJoin('inventaris_ruang', 'inventaris.id_ruang', '=', 'inventaris_ruang.id_ruang');

        // Filter berdasarkan Nama Ruang
        if ($request->filled('ruang')) {
            $query->where('inventaris.id_ruang', $request->ruang);
        }

        // Optimasi pencarian: Gunakan join langsung, lebih cepat dari orWhereHas
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('inventaris.no_inventaris', 'like', '%' . $search . '%')
                  ->orWhere('inventaris.kode_barang', 'like', '%' . $search . '%')
                  ->orWhere('inventaris_barang.nama_barang', 'like', '%' . $search . '%')
                  ->orWhere('inventaris_ruang.nama_ruang', 'like', '%' . $search . '%');
            });
        }

        // Mengembalikan data dalam format yang dapat digunakan oleh DataTables
        return DataTables::of($query)
            ->addColumn('no_inventaris', function ($row) {
                return '<a href="'.route('inventaris.barcode', $row->no_inventaris).'" target="_blank"><span class="badge text-bg-primary">'.$row->no_inventaris.'</span></a>';
            })
            ->addColumn('kode_barang', function ($row) {
                return $row->kode_barang ?? '-';
            })
            ->addColumn('nama_barang', function ($row) {
                return $row->nama_barang ?? '-';
            })
            ->addColumn('nama_produsen', function ($row) {
                return $row->nama_produsen ?? 'Tidak Diketahui';
            })
            ->addColumn('nama_merk', function ($row) {
                return $row->nama_merk ?? 'Tidak Diketahui';
            })
            ->addColumn('nama_ruang', function ($row) {
                return $row->nama_ruang ?? '-';
            })
            ->addColumn('asal_barang', function ($row) {
                return $row->asal_barang ?? '-';
            })
            ->addColumn('tgl_pengadaan', function ($row) {
                return $row->tgl_pengadaan ? date('d/m/Y', strtotime($row->tgl_pengadaan)) : '-';
            })
            ->addColumn('harga', function ($row) {
                $harga = $row->harga ?? 0;
                return function_exists('formatRupiah') ? formatRupiah($harga, true) : 'Rp ' . number_format($harga, 0, ',', '.');
            })
            ->addColumn('status_barang', function ($row) {
                $status = $row->status_barang ?? '-';
                $badgeClass = 'bg-secondary';
                switch(strtolower($status)) {
                    case 'ada':
                        $badgeClass = 'bg-success';
                        break;
                    case 'rusak':
                        $badgeClass = 'bg-danger';
                        break;
                    case 'hilang':
                        $badgeClass = 'bg-warning';
                        break;
                    case 'perbaikan':
                        $badgeClass = 'bg-info';
                        break;
                    case 'dipinjam':
                        $badgeClass = 'bg-primary';
                        break;
                }
                return '<span class="badge ' . $badgeClass . '">' . $status . '</span>';
            })
            ->addColumn('photo', function ($row) {
                // Skip loading gambar untuk performa - kolom ini hidden di mobile anyway
                // Jika perlu gambar, bisa lazy load via AJAX terpisah
                return '<span class="text-muted">-</span>';
            })
            ->addColumn('action', function ($row) {
                $btn = '<div class="btn-group" role="group">';
                $btn .= '<a href="'.route('inventaris.show', $row->no_inventaris).'" class="btn btn-info btn-sm" title="View"><i class="fa fa-eye"></i></a>';
                $btn .= '<a href="'.route('inventaris.edit', $row->no_inventaris).'" class="btn btn-warning btn-sm" title="Edit"><i class="fa fa-edit"></i></a>';
                $btn .= '<form action="'.route('inventaris.destroy', $row->no_inventaris).'" method="POST" style="display:inline;" onsubmit="return confirm(\'Yakin ingin menghapus?\');">
                            '.csrf_field().'
                            '.method_field("DELETE").'
                            <button type="submit" class="btn btn-danger btn-sm" title="Hapus"><i class="fa fa-trash"></i></button>
                         </form>';
                $btn .= '</div>';
                return $btn;
            })
            ->rawColumns(['no_inventaris', 'status_barang', 'photo', 'action']) // Agar kolom dapat mengandung HTML
            ->make(true);
    }

    // Memuat view jika bukan request AJAX
    $ruang = InventarisRuang::all(); // Ambil semua data ruang
    return view('inventaris.index_inventaris', compact('ruang'));
}
    // Method create()
    public function create()
    {
        $barang = InventarisBarang::all();
        $ruang = InventarisRuang::all();  // Ambil data ruang

        $latestInventaris = Inventaris::orderBy('no_inventaris', 'desc')
                                      ->where('no_inventaris', 'like', '%INV%')
                                      ->first();
        $nextNumber = $latestInventaris ? ((int)substr($latestInventaris->no_inventaris, 3)) + 1 : 1;
        $noInventaris = 'INV' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);

        return view('inventaris.create_inventaris', compact('barang', 'noInventaris', 'ruang'));
    }

    // Method store()
    public function store(Request $request)
    {
        $request->validate([
            'no_inventaris' => 'required|string|max:255',
            'kode_barang' => 'required|string|max:255',
            'asal_barang' => 'required|in:Beli,Bantuan,Hibah,-',
            'tgl_pengadaan' => 'required|date',
            'harga' => 'required|numeric',
            'status_barang' => 'required|in:Ada,Rusak,Hilang,Perbaikan,Dipinjam,-',
            'id_ruang' => 'required|string|min:1|max:100',
            'no_rak' => 'required|string|min:1|max:100',
            'no_box' => 'required|string|min:1|max:100',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg|max:9048'
        ]);

        $filePath = null;
        if ($request->hasFile('gambar')) {
            $file = $request->file('gambar');
            $safeOriginal = preg_replace('/[^A-Za-z0-9._-]/', '_', (string) $file->getClientOriginalName());
            $fileName = time() . '_' . $safeOriginal;
            $filePath = 'pages/upload/' . $fileName;

            // ===== Prefer: simpan ke filesystem webapps SIMRS (agar bisa diakses via SIMRS_WEBAPPS_BASE_URL) =====
            $webappsFsRoot = trim((string) env('SIMRS_WEBAPPS_FS_PATH', ''));
            $module = trim((string) env('INVENTARIS_WEBAPPS_PREFIX', 'inventaris'), '/');
            $webappsRelDir = ($module !== '' ? $module . '/' : '') . 'pages/upload';

            $saved = false;
            if ($webappsFsRoot !== '' && is_dir($webappsFsRoot)) {
                $targetDir = rtrim($webappsFsRoot, "/\\") . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $webappsRelDir);
                if (!is_dir($targetDir)) {
                    @mkdir($targetDir, 0775, true);
                }
                try {
                    $file->move($targetDir, $fileName);
                    $saved = true;
                } catch (\Throwable $e) {
                    Log::warning('Upload inventaris ke webapps gagal, fallback ke local', [
                        'targetDir' => $targetDir,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // ===== Fallback: simpan ke public Laravel =====
            if (!$saved) {
                $localDir = public_path('pages/upload');
                if (!is_dir($localDir)) {
                    @mkdir($localDir, 0775, true);
                }
                $file->move($localDir, $fileName);
            }
        }

        $inventaris = Inventaris::create([
            'no_inventaris' => $request->no_inventaris,
            'kode_barang' => $request->kode_barang,
            'asal_barang' => $request->asal_barang,
            'tgl_pengadaan' => $request->tgl_pengadaan,
            'harga' => $request->harga,
            'status_barang' => $request->status_barang,
            'id_ruang' => $request->id_ruang,
            'no_rak' => $request->no_rak,
            'no_box' => $request->no_box,
        ]);

        if ($filePath) {
            InventarisGambar::create([
                'no_inventaris' => $request->no_inventaris,
                'photo' => $filePath
            ]);
        }

        return redirect()->route('inventaris.index')->with('success', 'Inventaris berhasil ditambahkan.');
    }

    // Method edit() - Gabungkan dua method edit() menjadi satu
    public function edit($no_inventaris)
{
    $inventaris = Inventaris::findOrFail($no_inventaris);
    $barang = InventarisBarang::all();
    $ruang = InventarisRuang::all();

    return view('inventaris.edit_inventaris', compact('inventaris', 'barang', 'ruang'));
}

    // Method update()
 public function update(Request $request, $no_inventaris)
{
    $request->validate([
        'kode_barang' => 'required|string|max:255',
        'asal_barang' => 'required|in:Beli,Bantuan,Hibah,-',
        'tgl_pengadaan' => 'required|date',
        'harga' => 'required|numeric',
        'status_barang' => 'required|in:Ada,Rusak,Hilang,Perbaikan,Dipinjam,-',
        'id_ruang' => 'required|string|min:1|max:100',
        'no_rak' => 'required|string|min:1|max:100',
        'no_box' => 'required|string|min:1|max:100',
        'gambar' => 'nullable|image|mimes:jpeg,png,jpg|max:9048',
    ]);

    // Temukan data inventaris berdasarkan no_inventaris
    $inventaris = Inventaris::findOrFail($no_inventaris);

    // Handle upload gambar jika ada file baru
    if ($request->hasFile('gambar')) {
        $file = $request->file('gambar');
        $safeOriginal = preg_replace('/[^A-Za-z0-9._-]/', '_', (string) $file->getClientOriginalName());
        $fileName = time() . '_' . $safeOriginal;
        $filePath = 'pages/upload/' . $fileName;

        // Prefer: simpan ke filesystem webapps SIMRS (agar bisa diakses via SIMRS_WEBAPPS_BASE_URL)
        $webappsFsRoot = trim((string) env('SIMRS_WEBAPPS_FS_PATH', ''));
        $module = trim((string) env('INVENTARIS_WEBAPPS_PREFIX', 'inventaris'), '/');
        $webappsRelDir = ($module !== '' ? $module . '/' : '') . 'pages/upload';

        $saved = false;
        if ($webappsFsRoot !== '' && is_dir($webappsFsRoot)) {
            $targetDir = rtrim($webappsFsRoot, "/\\") . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $webappsRelDir);
            if (!is_dir($targetDir)) {
                @mkdir($targetDir, 0775, true);
            }
            try {
                $file->move($targetDir, $fileName);
                $saved = true;
            } catch (\Throwable $e) {
                Log::warning('Update inventaris: upload ke webapps gagal, fallback ke local', [
                    'targetDir' => $targetDir,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Fallback: simpan ke public Laravel
        if (!$saved) {
            $localDir = public_path('pages/upload');
            if (!is_dir($localDir)) {
                @mkdir($localDir, 0775, true);
            }
            $file->move($localDir, $fileName);
        }

        // Hapus gambar lama jika ada
        if ($inventaris->gambar()->exists()) {
            // Relasi gambar() adalah hasMany → ambil 1 record yang dipakai sebagai "cover" (terakhir).
            $oldImage = $inventaris->gambar()->orderByDesc('photo')->first();
            $oldRel = ltrim((string) ($oldImage->photo ?? ''), '/');

            // Hapus local jika ada
            if ($oldRel !== '') {
                $oldLocal = public_path($oldRel);
                if (file_exists($oldLocal)) {
                    @unlink($oldLocal);
                }
            }

            // Hapus di webapps jika env SIMRS_WEBAPPS_FS_PATH ada
            $webappsFsRootDelete = trim((string) env('SIMRS_WEBAPPS_FS_PATH', ''));
            if ($oldRel !== '' && $webappsFsRootDelete !== '' && is_dir($webappsFsRootDelete)) {
                $moduleDelete = trim((string) env('INVENTARIS_WEBAPPS_PREFIX', 'inventaris'), '/');
                // oldRel umumnya: pages/upload/xxx.jpg → webapps: <module>/pages/upload/xxx.jpg
                $oldWebappsRel = ($moduleDelete !== '' ? $moduleDelete . '/' : '') . $oldRel;
                $oldWebappsAbs = rtrim($webappsFsRootDelete, "/\\") . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $oldWebappsRel);
                if (file_exists($oldWebappsAbs)) {
                    @unlink($oldWebappsAbs);
                }
            }

            // Update gambar lama
            if ($oldImage) {
                $oldImage->update(['photo' => $filePath]);
            } else {
                InventarisGambar::create([
                    'no_inventaris' => $no_inventaris,
                    'photo' => $filePath,
                ]);
            }
        } else {
            // Simpan gambar baru jika belum ada gambar
            InventarisGambar::create([
                'no_inventaris' => $no_inventaris,
                'photo' => $filePath,
            ]);
        }
    }

    // Update data inventaris lainnya
    $inventaris->update($request->except(['gambar']));

    return redirect()->route('inventaris.index')->with('success', 'Inventaris berhasil diperbarui.');
}

    // Method show()
    public function show($no_inventaris)
    {
        $inventaris = Inventaris::with(['barang.produsen', 'barang.merk', 'ruang', 'gambar'])
            ->findOrFail($no_inventaris);

        return view('inventaris.show_inventaris', compact('inventaris'));
    }
    
    public function detail($no_inventaris)
    {
        $inventaris = Inventaris::with(['barang.produsen', 'barang.merk', 'ruang', 'gambar'])
            ->findOrFail($no_inventaris);

        return view('inventaris.show_inventaris', compact('inventaris'));
    }
    public function generateBarcode($no_inventaris)
{
    // Fetch the Inventaris data based on 'no_inventaris'
    $inventaris = Inventaris::with(['barang.produsen', 'barang.merk', 'ruang', 'gambar'])->where('no_inventaris', $no_inventaris)->firstOrFail();

    // Generate the URL for the detail page
    $detailUrl = route('inventaris.detail', ['no_inventaris' => $no_inventaris]);

    // Generate the QR Code with the URL
    $barcodeBase64 = base64_encode(
        QrCode::format('png')
            ->size(200)
            ->generate($detailUrl)
    );

    // Return the view with barcode, no_inventaris, and detailUrl
    return view('inventaris.barcode', compact('barcodeBase64', 'no_inventaris', 'detailUrl','inventaris'));
}
}

