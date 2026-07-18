<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class StirlingPdfClient
{
    public function isConfigured(): bool
    {
        $url = (string) config('services.stirling_pdf.url', '');
        $key = (string) config('services.stirling_pdf.api_key', '');

        return $url !== '' && $key !== '';
    }

    public function getPageCount(string $absolutePdfPath): int
    {
        $response = $this->postMultipart('/api/v1/analysis/page-count', [], [
            'fileInput' => $absolutePdfPath,
        ]);

        $data = $response->json();
        if (! is_array($data) || ! isset($data['pageCount'])) {
            throw new RuntimeException('Stirling PDF tidak mengembalikan jumlah halaman yang valid.');
        }

        return (int) $data['pageCount'];
    }

    /**
     * @return list<array{width: float, height: float}>
     */
    public function getPageDimensions(string $absolutePdfPath): array
    {
        $response = $this->postMultipart('/api/v1/analysis/page-dimensions', [], [
            'fileInput' => $absolutePdfPath,
        ]);

        $data = $response->json();
        if (! is_array($data) || $data === []) {
            throw new RuntimeException('Stirling PDF tidak mengembalikan dimensi halaman yang valid.');
        }

        return array_values(array_map(static function ($page) {
            if (! is_array($page)) {
                throw new RuntimeException('Format dimensi halaman Stirling PDF tidak valid.');
            }

            return [
                'width' => (float) ($page['width'] ?? 0),
                'height' => (float) ($page['height'] ?? 0),
            ];
        }, $data));
    }

    public function assertProcessable(string $absolutePdfPath): void
    {
        if (! is_file($absolutePdfPath)) {
            throw new RuntimeException('File PDF tidak ditemukan.');
        }

        $this->getPageDimensions($absolutePdfPath);
    }

    /**
     * @param  array<string, scalar|null>  $fields
     */
    public function addTextStamp(string $absolutePdfPath, array $fields): string
    {
        $response = $this->postMultipart('/api/v1/misc/add-stamp', $fields, [
            'fileInput' => $absolutePdfPath,
        ]);

        return $response->body();
    }

    /**
     * @param  array<string, scalar|null>  $fields
     */
    public function addImageStamp(string $absolutePdfPath, string $absoluteImagePath, array $fields): string
    {
        $response = $this->postMultipart('/api/v1/misc/add-stamp', $fields, [
            'fileInput' => $absolutePdfPath,
            'stampImage' => $absoluteImagePath,
        ]);

        return $response->body();
    }

    /**
     * Overlay gambar pada halaman pertama (koordinat & ukuran pixel = PDF points).
     *
     * @param  array<string, scalar|null>  $fields
     */
    public function addOverlayImage(string $absolutePdfPath, string $absoluteImagePath, array $fields): string
    {
        $response = $this->postMultipart('/api/v1/misc/add-image', $fields, [
            'fileInput' => $absolutePdfPath,
            'imageFile' => $absoluteImagePath,
        ]);

        return $response->body();
    }

    /**
     * @param  array<string, scalar|null>  $fields
     * @param  array<string, string>  $files  key => absolute path
     */
    private function postMultipart(string $endpoint, array $fields, array $files): Response
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Stirling PDF belum dikonfigurasi (STIRLING_PDF_URL / STIRLING_PDF_API_KEY).');
        }

        $url = rtrim((string) config('services.stirling_pdf.url'), '/') . $endpoint;
        $timeout = (int) config('services.stirling_pdf.timeout', 120);

        $multipart = [];

        foreach ($files as $name => $path) {
            if (! is_file($path)) {
                throw new RuntimeException('File tidak ditemukan untuk Stirling PDF: ' . $path);
            }
            $multipart[] = [
                'name' => $name,
                'contents' => file_get_contents($path),
                'filename' => basename($path),
            ];
        }

        foreach ($fields as $name => $value) {
            if ($value === null) {
                continue;
            }
            $multipart[] = [
                'name' => $name,
                'contents' => (string) $value,
            ];
        }

        $response = Http::timeout($timeout)
            ->withHeaders([
                'X-API-KEY' => (string) config('services.stirling_pdf.api_key'),
            ])
            ->asMultipart()
            ->post($url, $multipart);

        if (! $response->successful()) {
            Log::error('Stirling PDF request gagal', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 2000),
            ]);

            $detail = $response->json('detail') ?? $response->json('message') ?? $response->body();
            throw new RuntimeException(
                'Stirling PDF gagal memproses dokumen' .
                ($detail ? ': ' . (is_string($detail) ? $detail : json_encode($detail)) : '.')
            );
        }

        $contentType = strtolower((string) $response->header('Content-Type'));
        if ($contentType !== '' && ! str_contains($contentType, 'application/pdf') && ! str_contains($contentType, 'application/json')) {
            throw new RuntimeException('Stirling PDF mengembalikan respons yang bukan PDF.');
        }

        return $response;
    }
}
