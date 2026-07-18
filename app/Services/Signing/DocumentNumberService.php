<?php

namespace App\Services\Signing;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class DocumentNumberService
{
    private const KLASIFIKASI = 'III.6.AU/I';

    public function __construct(
        private readonly string $modelClass,
        private readonly string $prefix,
    ) {
    }

    public function format(int $noUrut, CarbonInterface $tanggal): string
    {
        $urut = str_pad((string) max(1, $noUrut), 3, '0', STR_PAD_LEFT);
        $bulanRomawi = self::romanMonth((int) $tanggal->format('n'));
        $tahun = $tanggal->format('Y');

        return $this->prefix . '/' . $urut . '/' . self::KLASIFIKASI . '/' . $bulanRomawi . '/' . $tahun;
    }

    public function previewNext(CarbonInterface $tanggal): string
    {
        $year = (int) $tanggal->format('Y');
        $lastNo = (int) ($this->query()->whereYear('tanggal', $year)->max('no_urut') ?? 0);

        return $this->format($lastNo + 1, $tanggal);
    }

    public function assignTo(Model $document): string
    {
        if ($document->nomor_surat) {
            return (string) $document->nomor_surat;
        }

        return DB::transaction(function () use ($document) {
            $document->refresh();

            if ($document->nomor_surat) {
                return (string) $document->nomor_surat;
            }

            $tanggal = $document->tanggal ?? now();
            $year = (int) $tanggal->format('Y');

            $lastNo = (int) $this->query()
                ->whereYear('tanggal', $year)
                ->whereNotNull('no_urut')
                ->lockForUpdate()
                ->max('no_urut');

            $nextNo = $lastNo + 1;
            $nomor = $this->format($nextNo, $tanggal);

            $document->update([
                'no_urut' => $nextNo,
                'nomor_surat' => $nomor,
            ]);

            return $nomor;
        });
    }

    public function assignOnCreate(array $attributes): array
    {
        if (! empty($attributes['nomor_surat'])) {
            return $attributes;
        }

        $tanggal = isset($attributes['tanggal'])
            ? \Carbon\Carbon::parse($attributes['tanggal'])
            : now();
        $year = (int) $tanggal->format('Y');

        $lastNo = (int) $this->query()
            ->whereYear('tanggal', $year)
            ->whereNotNull('no_urut')
            ->lockForUpdate()
            ->max('no_urut');

        $nextNo = $lastNo + 1;
        $attributes['no_urut'] = $nextNo;
        $attributes['nomor_surat'] = $this->format($nextNo, $tanggal);

        return $attributes;
    }

    private function query()
    {
        return $this->modelClass::query();
    }

    private static function romanMonth(int $month): string
    {
        return match ($month) {
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII',
            default => throw new RuntimeException('Bulan tidak valid: ' . $month),
        };
    }
}
