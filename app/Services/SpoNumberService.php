<?php

namespace App\Services;

use App\Models\Spo;
use App\Services\Signing\DocumentNumberService;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

final class SpoNumberService
{
    private static function svc(): DocumentNumberService
    {
        return new DocumentNumberService(Spo::class, "RS'ASF/SPO");
    }

    public static function previewNext(CarbonInterface $tanggal): string
    {
        return self::svc()->previewNext($tanggal);
    }

    public static function assignTo(Spo $doc): string
    {
        if ($doc->nomor_dokumen) {
            return (string) $doc->nomor_dokumen;
        }

        return DB::transaction(function () use ($doc) {
            $doc->refresh();

            if ($doc->nomor_dokumen) {
                return (string) $doc->nomor_dokumen;
            }

            $tanggal = $doc->tanggal ?? now();
            $year = (int) $tanggal->format('Y');

            $lastNo = (int) Spo::query()
                ->whereYear('tanggal', $year)
                ->whereNotNull('no_urut')
                ->lockForUpdate()
                ->max('no_urut');

            $nextNo = $lastNo + 1;
            $nomor = self::svc()->format($nextNo, $tanggal);

            $doc->update([
                'no_urut' => $nextNo,
                'nomor_dokumen' => $nomor,
            ]);

            return $nomor;
        });
    }

    public static function assignOnCreate(array $attributes): array
    {
        if (! empty($attributes['nomor_dokumen'])) {
            return $attributes;
        }

        $payload = self::svc()->assignOnCreate($attributes);

        if (isset($payload['nomor_surat'])) {
            $payload['nomor_dokumen'] = $payload['nomor_surat'];
            unset($payload['nomor_surat']);
        }

        return $payload;
    }
}
