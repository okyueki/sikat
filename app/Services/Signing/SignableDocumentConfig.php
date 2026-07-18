<?php

namespace App\Services\Signing;

final class SignableDocumentConfig
{
    public function __construct(
        public readonly string $storageFolder,
        public readonly string $verifyRouteName,
        public readonly string $auditModule,
        public readonly string $tempPrefix,
        public readonly string $numberPrefix,
        public readonly string $documentLabel,
        public readonly string $routePrefix,
        public readonly bool $hasMasaBerlaku = false,
    ) {
    }

    public function sourcePdfRelativePath(int $id): string
    {
        return $this->storageFolder . '/' . $id . '_source.pdf';
    }

    public function signedPdfRelativePath(int $id): string
    {
        return $this->storageFolder . '/' . $id . '_signed.pdf';
    }
}
