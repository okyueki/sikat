<div class="table-responsive">
    <table class="table table-striped table-bordered table-hover">
        <thead>
            <tr>
                <th>No. Rawat</th>
                <th>No. RM</th>
                <th>Nama Pasien</th>
                <th>Cara Bayar</th>
                <th>Ruangan</th>
                <th>Obat+Emb+Tsl</th>
                <th>Retur Obat</th>
                <th>Resep Pulang</th>
                <th>Laborat</th>
                <th>Radiologi</th>
                <th>Potongan</th>
                <th>Tambahan</th>
                <th>Kamar</th>
                <th>Operasi</th>
                <th>Harian</th>
                @for ($i = 1; $i <= 7; $i++)
                    <th>Dokter {{ $i }}</th>
                    <th>Tindakan {{ $i }}</th>
                    <th>Biaya {{ $i }}</th>
                @endfor
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @if ($data && count($data) > 0)
                @foreach ($data as $row)
                    <tr>
                        <td>{{ $row['no_rawat'] }}</td>
                        <td>{{ $row['no_rkm_medis'] }}</td>
                        <td>{{ $row['nm_pasien'] }}</td>
                        <td><span class="badge badge-info">{{ $row['cara_bayar'] }}</span></td>
                        <td><span class="badge badge-success">{{ $row['ruangan'] }}</span></td>
                        <td class="text-right">{{ number_format($row['obat_emb_tsl'] ?? 0, 0, ',', '.') }}</td>
                        <td class="text-right">{{ number_format($row['retur_obat'] ?? 0, 0, ',', '.') }}</td>
                        <td class="text-right">{{ number_format($row['resep_pulang'] ?? 0, 0, ',', '.') }}</td>
                        <td class="text-right">{{ number_format($row['laborat'] ?? 0, 0, ',', '.') }}</td>
                        <td class="text-right">{{ number_format($row['radiologi'] ?? 0, 0, ',', '.') }}</td>
                        <td class="text-right">{{ number_format($row['potongan'] ?? 0, 0, ',', '.') }}</td>
                        <td class="text-right">{{ number_format($row['tambahan'] ?? 0, 0, ',', '.') }}</td>
                        <td class="text-right">{{ number_format($row['kamar'] ?? 0, 0, ',', '.') }}</td>
                        <td class="text-right">{{ number_format($row['operasi'] ?? 0, 0, ',', '.') }}</td>
                        <td class="text-right">{{ number_format($row['harian'] ?? 0, 0, ',', '.') }}</td>
                        @for ($i = 1; $i <= 7; $i++)
                            <td class="text-center"><span class="badge badge-secondary">{{ $row["dokter$i"] ?? '—' }}</span></td>
                            <td>{{ $row["tindakan$i"] ?? '—' }}</td>
                            <td class="text-right">{{ number_format($row["biaya$i"] ?? 0, 0, ',', '.') }}</td>
                        @endfor
                        <td class="text-right font-weight-bold">{{ number_format($row['total'] ?? 0, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="{{ 5 + 6 + (7 * 3) + 6 }}" class="text-center">Tidak ada data ditemukan</td>
                </tr>
            @endif
        </tbody>
    </table>
</div>