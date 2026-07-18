<?php

namespace App\Services;

use App\Models\SuratEdaran;
use App\Services\Signing\DocumentNumberService;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Thin wrapper — logika di DocumentNumberService.
 */
final class SuratEdaranNumberService
{
    private static function svc(): DocumentNumberService
    {
        return new DocumentNumberService(SuratEdaran::class, "RS'ASF/EDR");
    }

    public static function format(int $noUrut, CarbonInterface $tanggal): string
    {
        return self::svc()->format($noUrut, $tanggal);
    }

    public static function previewNext(CarbonInterface $tanggal): string
    {
        return self::svc()->previewNext($tanggal);
    }

    public static function assignTo(SuratEdaran $surat): string
    {
        return self::svc()->assignTo($surat);
    }

    public static function assignOnCreate(array $attributes): array
    {
        return self::svc()->assignOnCreate($attributes);
    }
}
