<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

// Tambahin use untuk model & observer
use App\Models\Surat;
use App\Models\DisposisiSurat;
use App\Models\VerifikasiSurat;
use App\Models\PengajuanLibur;
use App\Observers\CutiObserver;
use App\Observers\SuratObserver;
use App\Observers\DisposisiObserver;
use App\Observers\VerifikasiSuratObserver;
use App\Models\Pegawai;


class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        Carbon::setLocale('id');

        // 👉 Daftarin semua observer di sini
        Surat::observe(SuratObserver::class);
        DisposisiSurat::observe(DisposisiObserver::class);
        VerifikasiSurat::observe(VerifikasiSuratObserver::class);
        PengajuanLibur::observe(CutiObserver::class);

        // View composer buat semua halaman
        View::composer('*', function ($view) {
            $namaPegawai = 'Guest';

            if (Auth::check()) {
                $user = Auth::user();
                $pegawai = Pegawai::where('nik', $user->username)->first();
                $namaPegawai = $pegawai ? $pegawai->nama : 'Guest';
            }

            $view->with('namaPegawai', $namaPegawai);
        });
    }
}
