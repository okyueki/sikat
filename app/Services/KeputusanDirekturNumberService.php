<?php

namespace App\Services;

use App\Models\KeputusanDirektur;
use App\Services\Signing\DocumentNumberService;
use Carbon\CarbonInterface;

final class KeputusanDirekturNumberService
{
    private static function svc(): DocumentNumberService
    {
        return new DocumentNumberService(KeputusanDirektur::class, "RS'ASF/KEP");
    }

    public static function previewNext(CarbonInterface $tanggal): string
    {
        return self::svc()->previewNext($tanggal);
    }

    public static function assignTo(KeputusanDirektur $doc): string
    {
        return self::svc()->assignTo($doc);
    }

    public static function assignOnCreate(array $attributes): array
    {
        return self::svc()->assignOnCreate($attributes);
    }
}
