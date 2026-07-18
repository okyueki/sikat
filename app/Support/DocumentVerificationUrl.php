<?php

namespace App\Support;

use App\Contracts\SignableDocument;
use Illuminate\Database\Eloquent\Model;

/**
 * URL pendek untuk QR verifikasi agar lebih mudah discan.
 * Cont: https://domain/v/pd/4  (bukan /peraturan_direktur/4/verifikasi)
 */
final class DocumentVerificationUrl
{
    /** @var array<string, string> */
    private const SHORT_PREFIX = [
        'surat_edaran' => 'se',
        'peraturan_direktur' => 'pd',
        'keputusan_direktur' => 'kd',
        'spo' => 'spo',
        'memo_internal' => 'mi',
    ];

    /** @var array<string, string> */
    private const VERIFY_ROUTE = [
        'se' => 'surat_edaran.verify',
        'pd' => 'peraturan_direktur.verify',
        'kd' => 'keputusan_direktur.verify',
        'spo' => 'spo.verify',
        'mi' => 'memo_internal.verify',
    ];

    public static function shortForSignable(SignableDocument $document, string $routePrefix): string
    {
        $code = self::SHORT_PREFIX[$routePrefix] ?? $routePrefix;

        return route('document.verify.short', [
            'doc' => $code,
            'id' => $document->getKey(),
        ]);
    }

    public static function shortFor(string $routePrefix, int|string $id): string
    {
        $code = self::SHORT_PREFIX[$routePrefix] ?? $routePrefix;

        return route('document.verify.short', ['doc' => $code, 'id' => $id]);
    }

    public static function qrVerifyUrl(string $routePrefix, Model $model): string
    {
        return self::shortFor($routePrefix, $model->getKey());
    }

    public static function qrImageUrl(string $routePrefix, Model $model): string
    {
        $t = $model->updated_at?->getTimestamp() ?? time();

        return route($routePrefix . '.verifyQr', $model) . '?t=' . $t;
    }

    public static function resolveAndRedirect(string $doc, int|string $id)
    {
        $routeName = self::VERIFY_ROUTE[$doc] ?? null;
        if (! $routeName) {
            abort(404);
        }

        $modelClass = self::modelClassForShort($doc);
        if (! $modelClass) {
            abort(404);
        }

        $record = $modelClass::query()->findOrFail($id);

        return redirect()->route($routeName, $record);
    }

    private static function modelClassForShort(string $doc): ?string
    {
        return match ($doc) {
            'se' => \App\Models\SuratEdaran::class,
            'pd' => \App\Models\PeraturanDirektur::class,
            'kd' => \App\Models\KeputusanDirektur::class,
            'spo' => \App\Models\Spo::class,
            'mi' => \App\Models\MemoInternal::class,
            default => null,
        };
    }
}
