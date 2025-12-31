@extends('layouts.pages-layouts')

@section('pageTitle', isset($pageTitle) ? $pageTitle : 'Laporan Ranap Per Dokter')

@section('content')
<div class="p-6 space-y-6">
    {{-- Header Section --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <h2 class="text-2xl font-bold text-gray-800">Laporan Ranap Per Dokter</h2>
        
        {{-- Filter Form --}}
        <form method="GET" class="flex flex-col sm:flex-row gap-3">
            <div class="flex flex-col sm:w-60">
                <label class="text-sm font-medium text-gray-700 mb-1">Dari Tanggal</label>
                <input
                    type="datetime-local"
                    name="start"
                    value="{{ $startDate }}"
                    class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                />
            </div>

            <div class="flex flex-col sm:w-60">
                <label class="text-sm font-medium text-gray-700 mb-1">Sampai Tanggal</label>
                <input
                    type="datetime-local"
                    name="end"
                    value="{{ $endDate }}"
                    class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                />
            </div>

            <div class="flex items-end">
                <button
                    type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-lg shadow transition flex items-center gap-2"
                >
                    Filter
                </button>
            </div>
        </form>
    </div>

    {{-- Table Section with Improved Styling --}}
    <div class="bg-white rounded-xl shadow overflow-hidden border border-gray-200">
        <div class="overflow-x-auto">
            <table class="min-w-full table table-striped table-bordered table-hover">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">No. Rawat</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">No. RM</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Nama Pasien</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Cara Bayar</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Ruangan</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">No. SEP</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Kelas Rawat</th>
                        {{-- Kolom Biaya Tambahan dengan Lebar Optimal --}}
                        <th scope="col" class="px-3 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider" style="min-width: 120px;">Obat+Emb+Tsl</th>
                        <th scope="col" class="px-3 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider" style="min-width: 100px;">Retur Obat</th>
                        <th scope="col" class="px-3 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider" style="min-width: 110px;">Resep Pulang</th>
                        <th scope="col" class="px-3 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider" style="min-width: 90px;">Laborat</th>
                        <th scope="col" class="px-3 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider" style="min-width: 90px;">Radiologi</th>
                        <th scope="col" class="px-3 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider" style="min-width: 90px;">Potongan</th> <!-- Baru -->
                        <th scope="col" class="px-3 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider" style="min-width: 90px;">Tambahan</th> <!-- Baru -->
                        <th scope="col" class="px-3 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider" style="min-width: 100px;">Kamar</th> <!-- Baru -->
                        <th scope="col" class="px-3 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider" style="min-width: 100px;">Operasi</th> <!-- Baru -->
                        <th scope="col" class="px-3 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider" style="min-width: 100px;">Harian</th> <!-- Baru -->

                        @for ($i = 1; $i <= 7; $i++)
                            <th scope="col" class="px-3 py-3 text-center text-xs font-bold text-gray-600 uppercase tracking-wider" style="min-width: 130px;">Dokter {{ $i }}</th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider" style="min-width: 200px;">Tindakan {{ $i }}</th>
                            <th scope="col" class="px-3 py-3 text-right text-xs font-bold text-gray-600 uppercase tracking-wider" style="min-width: 120px;">Biaya {{ $i }}</th>
                        @endfor
                        <th scope="col" class="px-3 py-3 text-right text-xs font-bold text-gray-600 uppercase tracking-wider" style="min-width: 120px;">Total</th> <!-- Baru -->
                    </tr>
                </thead>

                <tbody class="bg-white divide-y divide-gray-200">
                    @if ($pivot && count($pivot) > 0)
                        @foreach ($pivot as $row)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">{{ $row['no_rawat'] }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">{{ $row['no_rkm_medis'] }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">{{ $row['nm_pasien'] }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 font-medium bg-blue-50 rounded">{{ $row['cara_bayar'] }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 font-medium bg-green-50 rounded">{{ $row['ruangan'] }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 font-medium bg-green-50 rounded">{{ $row['no_sep'] ?? '-' }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 font-medium bg-green-50 rounded">{{ $row['kelas'] ?? '-' }}</td>
                                {{-- Data Biaya Tambahan --}}
                                <td class="px-3 py-3 text-sm text-gray-700 text-right font-medium bg-yellow-50">
                                    {{ number_format($row['obat_emb_tsl'] ?? 0, 0, ',', '.') }}
                                </td>
                                <td class="px-3 py-3 text-sm text-gray-700 text-right font-medium bg-red-50">
                                    {{ number_format($row['retur_obat'] ?? 0, 0, ',', '.') }}
                                </td>
                                <td class="px-3 py-3 text-sm text-gray-700 text-right font-medium bg-purple-50">
                                    {{ number_format($row['resep_pulang'] ?? 0, 0, ',', '.') }}
                                </td>
                                <td class="px-3 py-3 text-sm text-gray-700 text-right font-medium bg-indigo-50">
                                    {{ number_format($row['laborat'] ?? 0, 0, ',', '.') }}
                                </td>
                                <td class="px-3 py-3 text-sm text-gray-700 text-right font-medium bg-pink-50">
                                    {{ number_format($row['radiologi'] ?? 0, 0, ',', '.') }}
                                </td>
                                <td class="px-3 py-3 text-sm text-gray-700 text-right font-medium bg-orange-50"> <!-- Baru -->
                                    {{ number_format($row['potongan'] ?? 0, 0, ',', '.') }}
                                </td>
                                <td class="px-3 py-3 text-sm text-gray-700 text-right font-medium bg-cyan-50"> <!-- Baru -->
                                    {{ number_format($row['tambahan'] ?? 0, 0, ',', '.') }}
                                </td>
                                <td class="px-3 py-3 text-sm text-gray-700 text-right font-medium bg-teal-50"> <!-- Baru -->
                                    {{ number_format($row['kamar'] ?? 0, 0, ',', '.') }}
                                </td>
                                <td class="px-3 py-3 text-sm text-gray-700 text-right font-medium bg-amber-50"> <!-- Baru -->
                                    {{ number_format($row['operasi'] ?? 0, 0, ',', '.') }}
                                </td>
                                <td class="px-3 py-3 text-sm text-gray-700 text-right font-medium bg-emerald-50"> <!-- Baru -->
                                    {{ number_format($row['harian'] ?? 0, 0, ',', '.') }}
                                </td>

                                @for ($i = 1; $i <= 7; $i++)
                                    <td class="px-3 py-3 text-sm text-gray-700 text-center">
                                        <span class="inline-block px-2 py-1 rounded-md bg-blue-50 text-blue-800 font-medium text-xs">
                                            {{ $row["dokter$i"] ?? '—' }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-3 text-sm text-gray-700" style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        {{ $row["tindakan$i"] ?? '—' }}
                                    </td>
                                    <td class="px-3 py-3 text-sm text-gray-700 text-right font-medium bg-gray-50">
                                        {{ number_format($row["biaya$i"] ?? 0, 0, ',', '.') }}
                                    </td>
                                @endfor
                                <td class="px-3 py-3 text-sm text-gray-700 text-right font-bold bg-gray-100"> <!-- Baru -->
                                    {{ number_format($row['total'] ?? 0, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="{{ 5 + 6 + (7 * 3) + 6 }}" class="px-6 py-12 text-center text-gray-500"> <!-- Update colspan -->
                                <p class="text-lg font-medium">Tidak ada data ditemukan</p>
                                <p class="text-sm text-gray-400 mt-1">Coba ubah rentang tanggal atau periksa kembali filter.</p>
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.table {
    font-size: 0.875rem; /* Smaller font for better fit */
}
.table-striped tbody tr:nth-of-type(odd) {
    background-color: #f9fafb;
}
.table-hover tbody tr:hover {
    background-color: #f3f4f6;
}
.table-bordered {
    border: 1px solid #e5e7eb;
}
.table-bordered th,
.table-bordered td {
    border: 1px solid #e5e7eb;
}
</style>
@endsection