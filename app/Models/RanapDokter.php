<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class RanapDokter extends Model
{
    use HasFactory;

    protected $connection = 'server_74';
    protected $table = 'rawat_inap_dr';
    public $timestamps = false;

    public static function getPivotData($startDate, $endDate)
    {
        $data = self::query()
            ->from('rawat_inap_dr as r')
            ->join('reg_periksa as rp', 'rp.no_rawat', '=', 'r.no_rawat')
            ->join('pasien as p', 'p.no_rkm_medis', '=', 'rp.no_rkm_medis')
            ->join('dokter as d', 'd.kd_dokter', '=', 'r.kd_dokter')
            ->join('jns_perawatan_inap as jpi', 'jpi.kd_jenis_prw', '=', 'r.kd_jenis_prw')
            ->join('penjab', 'penjab.kd_pj', '=', 'rp.kd_pj')
            
            // Join tambahan untuk menghitung biaya kamar, operasi, dll. secara keseluruhan per no_rawat
            ->leftJoin('kamar_inap as ki', 'ki.no_rawat', '=', 'r.no_rawat') // Left join karena mungkin tidak langsung masuk kamar
            ->leftJoin('kamar as k', 'k.kd_kamar', '=', 'ki.kd_kamar')
            ->leftJoin('bridging_sep as bs', 'bs.no_rawat', '=', 'r.no_rawat')
            ->select(
                'r.no_rawat',
                'rp.no_rkm_medis',
                'p.nm_pasien',
                'd.nm_dokter',
                'jpi.nm_perawatan',
                'r.biaya_rawat',
                'penjab.png_jawab as cara_bayar',
                'bs.no_sep',
                'k.kelas',
                // Tambahkan kolom-kolom biaya baru
                DB::raw("
                    (IFNULL((SELECT SUM(total) FROM detail_pemberian_obat WHERE no_rawat = r.no_rawat), 0) +
                     IFNULL((SELECT SUM(besar_tagihan) FROM tagihan_obat_langsung WHERE no_rawat = r.no_rawat), 0) +
                     IFNULL((SELECT SUM(hargasatuan * jumlah) FROM beri_obat_operasi WHERE no_rawat = r.no_rawat), 0)) as obat_emb_tsl
                "),
                DB::raw("
                    (-1) * IFNULL((SELECT SUM(subtotal) FROM detreturjual WHERE no_retur_jual LIKE CONCAT('%', r.no_rawat, '%')), 0) as retur_obat
                "),
                DB::raw("
                    IFNULL((SELECT SUM(total) FROM resep_pulang WHERE no_rawat = r.no_rawat), 0) as resep_pulang
                "),
                DB::raw("
                    (IFNULL((SELECT SUM(biaya) FROM periksa_lab WHERE no_rawat = r.no_rawat), 0) +
                     IFNULL((SELECT SUM(biaya_item) FROM detail_periksa_lab WHERE no_rawat = r.no_rawat), 0)) as laborat
                "),
                DB::raw("
                    IFNULL((SELECT SUM(biaya) FROM periksa_radiologi WHERE no_rawat = r.no_rawat), 0) as radiologi
                "),
                // --- Tambahkan kolom baru di sini ---
                DB::raw("
                    IFNULL((SELECT SUM(besar_pengurangan) FROM pengurangan_biaya WHERE no_rawat = r.no_rawat), 0) as potongan
                "),
                DB::raw("
                    IFNULL((SELECT SUM(besar_biaya) FROM tambahan_biaya WHERE no_rawat = r.no_rawat), 0) as tambahan
                "),
                DB::raw("
                    (IFNULL((SELECT SUM(ttl_biaya) FROM kamar_inap WHERE no_rawat = r.no_rawat), 0) +
                     IFNULL((SELECT SUM(bs.besar_biaya) FROM biaya_sekali bs INNER JOIN kamar_inap ki2 ON ki2.kd_kamar = bs.kd_kamar WHERE ki2.no_rawat = r.no_rawat), 0)) as kamar
                "),
                DB::raw("
                    IFNULL((SELECT SUM(
                        biayaoperator1+biayaoperator2+biayaoperator3+
                        biayaasisten_operator1+biayaasisten_operator2+biayaasisten_operator3+
                        biayainstrumen+biayadokter_anak+biayaperawaat_resusitas+
                        biayadokter_anestesi+biayaasisten_anestesi+biayaasisten_anestesi2+
                        biayabidan+biayabidan2+biayabidan3+
                        biayaperawat_luar+biayaalat+biayasewaok+akomodasi+
                        bagian_rs+biaya_omloop+biaya_omloop2+biaya_omloop3+
                        biaya_omloop4+biaya_omloop5+biayasarpras+
                        biaya_dokter_pjanak+biaya_dokter_umum
                    ) FROM operasi WHERE no_rawat = r.no_rawat), 0) as operasi
                "),
                DB::raw("
                    IFNULL((SELECT SUM(bh.jml * bh.besar_biaya * ki3.lama) FROM kamar_inap ki3 INNER JOIN biaya_harian bh ON ki3.kd_kamar = bh.kd_kamar WHERE ki3.no_rawat = r.no_rawat), 0) as harian
                "),
                // --- Kolom Total ---
                DB::raw("
                    (
                     IFNULL((SELECT SUM(total) FROM detail_pemberian_obat WHERE no_rawat = r.no_rawat), 0) +
                     IFNULL((SELECT SUM(besar_tagihan) FROM tagihan_obat_langsung WHERE no_rawat = r.no_rawat), 0) +
                     IFNULL((SELECT SUM(hargasatuan * jumlah) FROM beri_obat_operasi WHERE no_rawat = r.no_rawat), 0) +
                     (-1) * IFNULL((SELECT SUM(subtotal) FROM detreturjual WHERE no_retur_jual LIKE CONCAT('%', r.no_rawat, '%')), 0) +
                     IFNULL((SELECT SUM(total) FROM resep_pulang WHERE no_rawat = r.no_rawat), 0) +
                     IFNULL((SELECT SUM(biaya) FROM periksa_lab WHERE no_rawat = r.no_rawat), 0) +
                     IFNULL((SELECT SUM(biaya_item) FROM detail_periksa_lab WHERE no_rawat = r.no_rawat), 0) +
                     IFNULL((SELECT SUM(biaya) FROM periksa_radiologi WHERE no_rawat = r.no_rawat), 0) +
                     IFNULL((SELECT SUM(besar_pengurangan) FROM pengurangan_biaya WHERE no_rawat = r.no_rawat), 0) + -- Potongan
                     IFNULL((SELECT SUM(besar_biaya) FROM tambahan_biaya WHERE no_rawat = r.no_rawat), 0) + -- Tambahan
                     IFNULL((SELECT SUM(ttl_biaya) FROM kamar_inap WHERE no_rawat = r.no_rawat), 0) +
                     IFNULL((SELECT SUM(bs.besar_biaya) FROM biaya_sekali bs INNER JOIN kamar_inap ki2 ON ki2.kd_kamar = bs.kd_kamar WHERE ki2.no_rawat = r.no_rawat), 0) +
                     IFNULL((SELECT SUM(
                         biayaoperator1+biayaoperator2+biayaoperator3+
                         biayaasisten_operator1+biayaasisten_operator2+biayaasisten_operator3+
                         biayainstrumen+biayadokter_anak+biayaperawaat_resusitas+
                         biayadokter_anestesi+biayaasisten_anestesi+biayaasisten_anestesi2+
                         biayabidan+biayabidan2+biayabidan3+
                         biayaperawat_luar+biayaalat+biayasewaok+akomodasi+
                         bagian_rs+biaya_omloop+biaya_omloop2+biaya_omloop3+
                         biaya_omloop4+biaya_omloop5+biayasarpras+
                         biaya_dokter_pjanak+biaya_dokter_umum
                     ) FROM operasi WHERE no_rawat = r.no_rawat), 0) +
                     IFNULL((SELECT SUM(bh.jml * bh.besar_biaya * ki3.lama) FROM kamar_inap ki3 INNER JOIN biaya_harian bh ON ki3.kd_kamar = bh.kd_kamar WHERE ki3.no_rawat = r.no_rawat), 0) +
                     rp.biaya_reg -- Registrasi
                    ) as total
                "),
                // --- Akhir tambahan ---
                DB::raw("
                    IFNULL(
                        (SELECT bangsal.nm_bangsal 
                         FROM kamar_inap 
                         INNER JOIN kamar ON kamar_inap.kd_kamar = kamar.kd_kamar 
                         INNER JOIN bangsal ON kamar.kd_bangsal = bangsal.kd_bangsal 
                         WHERE kamar_inap.no_rawat = r.no_rawat 
                         LIMIT 1), 
                        'Ruang Terhapus'
                    ) as ruangan
                ")
            )
            ->whereBetween(DB::raw("concat(r.tgl_perawatan,' ',r.jam_rawat)"), [$startDate, $endDate])
            ->distinct() // Gunakan distinct untuk mencegah duplikasi jika join menghasilkan banyak baris
            ->get(); // Pastikan get() dijalankan di sini untuk mendapatkan hasil query


        if ($data->isEmpty()) {
            return [[], []];
        }

        // Group by no_rawat → satu baris per pasien
        $pivot = $data->groupBy('no_rawat')->map(function ($rows) {
            $first = $rows->first();

            // Data dasar + kolom baru
            $baseData = [
                'no_rawat' => $first->no_rawat,
                'no_rkm_medis' => $first->no_rkm_medis,
                'nm_pasien' => $first->nm_pasien,
                'cara_bayar' => $first->cara_bayar,
                'ruangan' => $first->ruangan,
                'no_sep' => $first->no_sep,
                'kelas' => $first->kelas,
                // Data biaya tambahan lama
                'obat_emb_tsl' => $first->obat_emb_tsl,
                'retur_obat' => $first->retur_obat,
                'resep_pulang' => $first->resep_pulang,
                'laborat' => $first->laborat,
                'radiologi' => $first->radiologi,
                'potongan' => $first->potongan, // Baru
                'tambahan' => $first->tambahan, // Baru
                'kamar' => $first->kamar, // Baru
                'operasi' => $first->operasi, // Baru
                'harian' => $first->harian, // Baru
                'total' => $first->total, // Baru
            ];

            // Group by dokter
            $dokterData = $rows->groupBy('nm_dokter')->map(function ($dr) {
                return [
                    'dokter' => $dr->first()->nm_dokter,
                    'tindakan' => $dr->pluck('nm_perawatan')->unique()->implode(', '),
                    'biaya' => $dr->sum('biaya_rawat'),
                ];
            })->values()->toArray();

            // Siapkan slot dokter 1-7
            $slot = [];
            for ($i = 0; $i < 7; $i++) {
                $dokterRow = $dokterData[$i] ?? ['dokter' => null, 'tindakan' => null, 'biaya' => 0];
                $slot["dokter" . ($i + 1)] = $dokterRow['dokter'];
                $slot["tindakan" . ($i + 1)] = $dokterRow['tindakan'];
                $slot["biaya" . ($i + 1)] = $dokterRow['biaya'];
            }

            return array_merge($baseData, $slot);
        })->values()->toArray();

        return [$data, $pivot];
    }
}