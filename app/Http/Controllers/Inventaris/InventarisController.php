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

class InventarisController extends Controller
{
    // Method index()
public function index(Request $request)
{
    // Cek apakah request berasal dari DataTables (AJAX request)
    if ($request->ajax()) {
        $query = Inventaris::with(['barang.produsen', 'barang.merk', 'ruang', 'gambar']);

        // Filter berdasarkan Nama Ruang
        if ($request->filled('ruang')) {
            $query->where('id_ruang', $request->ruang);
        }

        // Fitur Pencarian
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('no_inventaris', 'like', '%' . $request->search . '%')
                  ->orWhere('kode_barang', 'like', '%' . $request->search . '%')
                  ->orWhereHas('barang', function($q) use ($request) {
                      $q->where('nama_barang', 'like', '%' . $request->search . '%');
                  })
                  ->orWhereHas('ruang', function($q) use ($request) {
                      $q->where('nama_ruang', 'like', '%' . $request->search . '%');
                  });
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
                return $row->barang->nama_barang ?? '-';
            })
            ->addColumn('nama_produsen', function ($row) {
                return $row->barang->produsen->nama_produsen ?? 'Tidak Diketahui';
            })
            ->addColumn('nama_merk', function ($row) {
                return $row->barang->merk->nama_merk ?? 'Tidak Diketahui';
            })
            ->addColumn('nama_ruang', function ($row) {
                return $row->ruang->nama_ruang ?? '-';
            })
            ->addColumn('asal_barang', function ($row) {
                return $row->asal_barang ?? '-';
            })
            ->addColumn('tgl_pengadaan', function ($row) {
                return $row->tgl_pengadaan ? date('d/m/Y', strtotime($row->tgl_pengadaan)) : '-';
            })
            ->addColumn('harga', function ($row) {
                if (!function_exists('formatRupiah')) {
                    $formatPath = app_path('Helpers/FormatHelper.php');
                    if (file_exists($formatPath)) {
                        require_once $formatPath;
                    }
                }
                $harga = $row->harga ?? 0;
                return function_exists('formatRupiah') ? formatRupiah($harga, true) : 'Rp ' . number_format($harga, 2, ',', '.');
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
                // Handle hasMany relationship - get first image
                $gambar = $row->gambar->first();
                if ($gambar && !empty($gambar->photo)) {
                    if (function_exists('getInventarisImageBase64')) {
                        $base64Image = getInventarisImageBase64($gambar->photo);
                        if ($base64Image) {
                            return '<img src="' . $base64Image . '" class="img-thumbnail" alt="Gambar Inventaris" style="max-width: 80px; max-height: 80px; object-fit: cover; cursor: pointer;" onclick="window.open(this.src, \'_blank\')">';
                        }
                    }
                }
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
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = 'pages/upload/' . $fileName;
            $file->move(public_path('pages/upload'), $fileName);
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
        $fileName = time() . '_' . $file->getClientOriginalName();
        $filePath = 'pages/upload/' . $fileName;

        // Pindahkan file ke folder tujuan
        $file->move(public_path('pages/upload'), $fileName);

        // Hapus gambar lama jika ada
        if ($inventaris->gambar()->exists()) {
            $oldImagePath = public_path($inventaris->gambar->photo);
            if (file_exists($oldImagePath)) {
                unlink($oldImagePath);
            }

            // Update gambar lama
            $inventaris->gambar->update([
                'photo' => $filePath,
            ]);
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

