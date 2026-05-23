# 💻 CONTOH IMPLEMENTASI SISTEM TIKETING

## 1. STATUS HISTORY TRACKING

### Migration
```php
// database/migrations/xxxx_create_permintaan_status_history_table.php
Schema::create('permintaan_status_history', function (Blueprint $table) {
    $table->id();
    $table->string('no_permintaan');
    $table->string('status_lama')->nullable();
    $table->string('status_baru');
    $table->string('changed_by'); // NIK
    $table->text('keterangan')->nullable();
    $table->timestamp('changed_at');
    $table->timestamps();
    
    $table->index('no_permintaan');
});
```

### Model
```php
// app/Models/PermintaanStatusHistory.php
class PermintaanStatusHistory extends Model
{
    protected $connection = 'sik3';
    protected $table = 'permintaan_status_history';
    
    protected $fillable = [
        'no_permintaan', 'status_lama', 'status_baru', 
        'changed_by', 'keterangan', 'changed_at'
    ];
    
    public function permintaan()
    {
        return $this->belongsTo(PermintaanPerbaikanInventaris::class, 'no_permintaan', 'no_permintaan');
    }
    
    public function changedBy()
    {
        return $this->belongsTo(Pegawai::class, 'changed_by', 'nik');
    }
}
```

### Service untuk Update Status
```php
// app/Services/PermintaanStatusService.php
class PermintaanStatusService
{
    public function updateStatus($no_permintaan, $statusBaru, $changedBy, $keterangan = null)
    {
        $permintaan = PermintaanPerbaikanInventaris::findOrFail($no_permintaan);
        $statusLama = $permintaan->status;
        
        // Update status
        $permintaan->status = $statusBaru;
        $permintaan->save();
        
        // Simpan history
        PermintaanStatusHistory::create([
            'no_permintaan' => $no_permintaan,
            'status_lama' => $statusLama,
            'status_baru' => $statusBaru,
            'changed_by' => $changedBy,
            'keterangan' => $keterangan,
            'changed_at' => now()
        ]);
        
        // Trigger event untuk notifikasi
        event(new PermintaanStatusChanged($permintaan, $statusLama, $statusBaru));
        
        return $permintaan;
    }
}
```

### Controller Method
```php
// app/Http/Controllers/Inventaris/PermintaanPerbaikanInventarisController.php
public function updateStatus(Request $request, $no_permintaan)
{
    $request->validate([
        'status' => 'required|in:Pending,In Progress,In Review,Completed,Cancelled',
        'keterangan' => 'nullable|string|max:500'
    ]);
    
    $service = new PermintaanStatusService();
    $permintaan = $service->updateStatus(
        $no_permintaan,
        $request->status,
        auth()->user()->nik,
        $request->keterangan
    );
    
    return response()->json([
        'success' => true,
        'message' => 'Status berhasil diupdate',
        'permintaan' => $permintaan
    ]);
}
```

---

## 2. KOMENTAR SYSTEM

### Migration
```php
Schema::create('permintaan_komentar', function (Blueprint $table) {
    $table->id();
    $table->string('no_permintaan');
    $table->string('nik'); // Pengirim
    $table->text('komentar');
    $table->boolean('is_internal')->default(false);
    $table->string('attachment')->nullable();
    $table->timestamps();
    
    $table->index('no_permintaan');
});
```

### Model
```php
// app/Models/PermintaanKomentar.php
class PermintaanKomentar extends Model
{
    protected $connection = 'sik3';
    protected $table = 'permintaan_komentar';
    
    protected $fillable = [
        'no_permintaan', 'nik', 'komentar', 
        'is_internal', 'attachment'
    ];
    
    public function permintaan()
    {
        return $this->belongsTo(PermintaanPerbaikanInventaris::class, 'no_permintaan', 'no_permintaan');
    }
    
    public function pengirim()
    {
        return $this->belongsTo(Pegawai::class, 'nik', 'nik');
    }
}
```

### Controller
```php
// app/Http/Controllers/Inventaris/PermintaanKomentarController.php
class PermintaanKomentarController extends Controller
{
    public function store(Request $request, $no_permintaan)
    {
        $request->validate([
            'komentar' => 'required|string|max:1000',
            'is_internal' => 'boolean',
            'attachment' => 'nullable|file|max:5120|mimes:jpg,jpeg,png,pdf,doc,docx'
        ]);
        
        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/permintaan'), $filename);
            $attachmentPath = 'uploads/permintaan/' . $filename;
        }
        
        $komentar = PermintaanKomentar::create([
            'no_permintaan' => $no_permintaan,
            'nik' => auth()->user()->nik,
            'komentar' => $request->komentar,
            'is_internal' => $request->is_internal ?? false,
            'attachment' => $attachmentPath
        ]);
        
        // Notifikasi
        event(new KomentarDitambahkan($komentar));
        
        return response()->json([
            'success' => true,
            'komentar' => $komentar->load('pengirim')
        ]);
    }
    
    public function index($no_permintaan)
    {
        $komentars = PermintaanKomentar::where('no_permintaan', $no_permintaan)
            ->with('pengirim')
            ->orderBy('created_at', 'asc')
            ->get();
        
        return response()->json($komentars);
    }
}
```

---

## 3. DASHBOARD DENGAN STATISTIK

### Controller
```php
// app/Http/Controllers/Inventaris/PermintaanDashboardController.php
class PermintaanDashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total' => PermintaanPerbaikanInventaris::count(),
            'open' => PermintaanPerbaikanInventaris::where('status', 'Pending')->count(),
            'in_progress' => PermintaanPerbaikanInventaris::where('status', 'In Progress')->count(),
            'completed' => PermintaanPerbaikanInventaris::where('status', 'Completed')->count(),
            'cancelled' => PermintaanPerbaikanInventaris::where('status', 'Cancelled')->count(),
        ];
        
        $priorityStats = PermintaanPerbaikanInventaris::selectRaw('prioritas, COUNT(*) as jumlah')
            ->groupBy('prioritas')
            ->get();
        
        $statusStats = PermintaanPerbaikanInventaris::selectRaw('status, COUNT(*) as jumlah')
            ->groupBy('status')
            ->get();
        
        $recentTickets = PermintaanPerbaikanInventaris::with(['inventaris', 'pegawai'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        $avgResponseTime = $this->calculateAvgResponseTime();
        
        return view('inventaris.permintaan_dashboard', compact(
            'stats', 'priorityStats', 'statusStats', 
            'recentTickets', 'avgResponseTime'
        ));
    }
    
    private function calculateAvgResponseTime()
    {
        // Hitung average response time dari tickets yang sudah di-respon
        $tickets = PermintaanPerbaikanInventaris::with('perbaikan')
            ->whereHas('perbaikan')
            ->get();
        
        $totalMinutes = 0;
        $count = 0;
        
        foreach ($tickets as $ticket) {
            if ($ticket->perbaikan && $ticket->perbaikan->waktu_mulai) {
                $responseTime = $ticket->tanggal->diffInMinutes($ticket->perbaikan->waktu_mulai);
                $totalMinutes += $responseTime;
                $count++;
            }
        }
        
        return $count > 0 ? round($totalMinutes / $count, 2) : 0;
    }
}
```

### View Dashboard
```blade
{{-- resources/views/inventaris/permintaan_dashboard.blade.php --}}
@extends('layouts.pages-layouts')

@section('pageTitle', 'Dashboard Permintaan Perbaikan')

@section('content')
<div class="container-fluid">
    <!-- Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1">Total Tickets</p>
                            <h3 class="mb-0">{{ $stats['total'] }}</h3>
                        </div>
                        <div class="flex-shrink-0">
                            <div class="avatar avatar-md bg-primary-gradient">
                                <i class="fa fa-ticket"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1">Open</p>
                            <h3 class="mb-0 text-warning">{{ $stats['open'] }}</h3>
                        </div>
                        <div class="flex-shrink-0">
                            <div class="avatar avatar-md bg-warning-gradient">
                                <i class="fa fa-clock"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1">In Progress</p>
                            <h3 class="mb-0 text-info">{{ $stats['in_progress'] }}</h3>
                        </div>
                        <div class="flex-shrink-0">
                            <div class="avatar avatar-md bg-info-gradient">
                                <i class="fa fa-cog"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1">Completed</p>
                            <h3 class="mb-0 text-success">{{ $stats['completed'] }}</h3>
                        </div>
                        <div class="flex-shrink-0">
                            <div class="avatar avatar-md bg-success-gradient">
                                <i class="fa fa-check"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Charts -->
    <div class="row g-3 mb-4">
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Distribusi Prioritas</h5>
                </div>
                <div class="card-body">
                    <div id="chart-priority" style="min-height: 300px;"></div>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Distribusi Status</h5>
                </div>
                <div class="card-body">
                    <div id="chart-status" style="min-height: 300px;"></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Tickets -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Recent Tickets</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>No Permintaan</th>
                                    <th>Inventaris</th>
                                    <th>Pegawai</th>
                                    <th>Prioritas</th>
                                    <th>Status</th>
                                    <th>Tanggal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentTickets as $ticket)
                                <tr>
                                    <td>{{ $ticket->no_permintaan }}</td>
                                    <td>{{ $ticket->inventaris->no_inventaris ?? '-' }}</td>
                                    <td>{{ $ticket->pegawai->nama ?? '-' }}</td>
                                    <td>
                                        <span class="badge bg-{{ $ticket->prioritas == 'High' ? 'danger' : ($ticket->prioritas == 'Medium' ? 'warning' : 'success') }}">
                                            {{ $ticket->prioritas }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $ticket->status == 'Pending' ? 'warning' : ($ticket->status == 'In Progress' ? 'info' : 'success') }}">
                                            {{ $ticket->status }}
                                        </span>
                                    </td>
                                    <td>{{ $ticket->tanggal->format('d/m/Y H:i') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// ApexCharts untuk Priority
var optionsPriority = {
    series: @json($priorityStats->pluck('jumlah')),
    chart: { type: 'donut', height: 300 },
    labels: @json($priorityStats->pluck('prioritas')),
    colors: ['#10b759', '#f59e0b', '#ef4444']
};
new ApexCharts(document.querySelector("#chart-priority"), optionsPriority).render();

// ApexCharts untuk Status
var optionsStatus = {
    series: @json($statusStats->pluck('jumlah')),
    chart: { type: 'pie', height: 300 },
    labels: @json($statusStats->pluck('status')),
    colors: ['#0162e8', '#0db2de', '#10b759', '#f59e0b', '#ef4444']
};
new ApexCharts(document.querySelector("#chart-status"), optionsStatus).render();
</script>
@endsection
```

---

## 4. NOTIFIKASI SYSTEM

### Event
```php
// app/Events/PermintaanStatusChanged.php
class PermintaanStatusChanged
{
    public $permintaan;
    public $statusLama;
    public $statusBaru;
    
    public function __construct($permintaan, $statusLama, $statusBaru)
    {
        $this->permintaan = $permintaan;
        $this->statusLama = $statusLama;
        $this->statusBaru = $statusBaru;
    }
}
```

### Listener
```php
// app/Listeners/SendStatusChangeNotification.php
class SendStatusChangeNotification
{
    public function handle(PermintaanStatusChanged $event)
    {
        $permintaan = $event->permintaan;
        
        // Notify requester
        if ($permintaan->pegawai && $permintaan->pegawai->email) {
            Mail::to($permintaan->pegawai->email)->send(
                new StatusChangedMail($permintaan, $event->statusBaru)
            );
        }
        
        // Notify technician if assigned
        if ($permintaan->nik_teknisi) {
            $teknisi = Pegawai::find($permintaan->nik_teknisi);
            if ($teknisi && $teknisi->email) {
                Mail::to($teknisi->email)->send(
                    new StatusChangedMail($permintaan, $event->statusBaru)
                );
            }
        }
        
        // Create in-app notification
        PermintaanNotification::create([
            'no_permintaan' => $permintaan->no_permintaan,
            'nik' => $permintaan->nik,
            'type' => 'status_changed',
            'title' => 'Status Ticket Berubah',
            'message' => "Ticket {$permintaan->no_permintaan} status berubah menjadi {$event->statusBaru}",
            'is_read' => false
        ]);
    }
}
```

### Register Event
```php
// app/Providers/EventServiceProvider.php
protected $listen = [
    PermintaanStatusChanged::class => [
        SendStatusChangeNotification::class,
    ],
];
```

---

## 5. IMPROVED INDEX PAGE DENGAN DATATABLES

### Controller
```php
public function index(Request $request)
{
    if ($request->ajax()) {
        $query = PermintaanPerbaikanInventaris::with(['inventaris', 'pegawai', 'perbaikan']);
        
        // Filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('prioritas')) {
            $query->where('prioritas', $request->prioritas);
        }
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('no_permintaan', 'like', '%' . $request->search . '%')
                  ->orWhereHas('inventaris', function($q) use ($request) {
                      $q->where('no_inventaris', 'like', '%' . $request->search . '%');
                  })
                  ->orWhereHas('pegawai', function($q) use ($request) {
                      $q->where('nama', 'like', '%' . $request->search . '%');
                  });
            });
        }
        
        return DataTables::of($query)
            ->addColumn('no_permintaan', function ($row) {
                return '<a href="'.route('permintaan.show', $row->no_permintaan).'">'.$row->no_permintaan.'</a>';
            })
            ->addColumn('inventaris', function ($row) {
                return $row->inventaris->no_inventaris ?? '-';
            })
            ->addColumn('pegawai', function ($row) {
                return $row->pegawai->nama ?? '-';
            })
            ->addColumn('prioritas', function ($row) {
                $badge = $row->prioritas == 'High' ? 'danger' : ($row->prioritas == 'Medium' ? 'warning' : 'success');
                return '<span class="badge bg-'.$badge.'">'.$row->prioritas.'</span>';
            })
            ->addColumn('status', function ($row) {
                $badge = match($row->status) {
                    'Pending' => 'warning',
                    'In Progress' => 'info',
                    'Completed' => 'success',
                    'Cancelled' => 'secondary',
                    default => 'light'
                };
                return '<span class="badge bg-'.$badge.'">'.$row->status.'</span>';
            })
            ->addColumn('response_time', function ($row) {
                if ($row->perbaikan && $row->perbaikan->waktu_mulai) {
                    $minutes = $row->tanggal->diffInMinutes($row->perbaikan->waktu_mulai);
                    return $minutes . ' menit';
                }
                return '<span class="text-muted">Belum direspon</span>';
            })
            ->addColumn('action', function ($row) {
                return view('inventaris.partials.permintaan_action', compact('row'))->render();
            })
            ->rawColumns(['no_permintaan', 'prioritas', 'status', 'response_time', 'action'])
            ->make(true);
    }
    
    return view('inventaris.permintaan_index');
}
```

---

## 6. ROUTES

```php
// routes/web.php
Route::middleware(['auth'])->prefix('permintaan')->name('permintaan.')->group(function () {
    Route::get('/', [PermintaanPerbaikanInventarisController::class, 'index'])->name('index');
    Route::get('/dashboard', [PermintaanDashboardController::class, 'index'])->name('dashboard');
    Route::get('/create', [PermintaanPerbaikanInventarisController::class, 'create'])->name('create');
    Route::post('/', [PermintaanPerbaikanInventarisController::class, 'store'])->name('store');
    Route::get('/{no_permintaan}', [PermintaanPerbaikanInventarisController::class, 'show'])->name('show');
    Route::get('/{no_permintaan}/edit', [PermintaanPerbaikanInventarisController::class, 'edit'])->name('edit');
    Route::put('/{no_permintaan}', [PermintaanPerbaikanInventarisController::class, 'update'])->name('update');
    Route::put('/{no_permintaan}/status', [PermintaanPerbaikanInventarisController::class, 'updateStatus'])->name('updateStatus');
    Route::delete('/{no_permintaan}', [PermintaanPerbaikanInventarisController::class, 'destroy'])->name('destroy');
    
    // Komentar
    Route::get('/{no_permintaan}/komentar', [PermintaanKomentarController::class, 'index'])->name('komentar.index');
    Route::post('/{no_permintaan}/komentar', [PermintaanKomentarController::class, 'store'])->name('komentar.store');
});
```

---

## 📝 CATATAN PENTING

1. **Pastikan semua migration dijalankan**
2. **Register events di EventServiceProvider**
3. **Setup queue untuk email notifications**
4. **Test semua fitur sebelum deploy**
5. **Backup database sebelum migration**

---

**Selamat mengembangkan sistem tiketing yang lebih baik! 🚀**
