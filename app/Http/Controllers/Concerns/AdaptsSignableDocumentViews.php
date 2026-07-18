<?php

namespace App\Http\Controllers\Concerns;

trait AdaptsSignableDocumentViews
{
    abstract protected function signableRoutePrefix(): string;

    abstract protected function signableDocumentLabel(): string;

    abstract protected function signableDocumentType(): string;

    abstract protected function hasMasaBerlakuFields(): bool;

    protected function signableViewAdapter(): array
    {
        return [
            'routePrefix' => $this->signableRoutePrefix(),
            'documentLabel' => $this->signableDocumentLabel(),
            'documentType' => $this->signableDocumentType(),
            'hasMasaBerlaku' => $this->hasMasaBerlakuFields(),
            'numberFormatHint' => "RS'ASF/{$this->signableDocumentType()}/urut/III.6.AU/I/bulan/tahun",
            'stirlingUiUrl' => config('services.stirling_pdf.url'),
            'stirlingEmbedEnabled' => (bool) config('services.stirling_pdf.embed_enabled', true),
        ];
    }

    protected function masaBerlakuValidationRules(): array
    {
        if (! $this->hasMasaBerlakuFields()) {
            return [];
        }

        return [
            'tanggal_mulai_berlaku' => 'required|date',
            'tanggal_berakhir_berlaku' => 'required|date|after_or_equal:tanggal_mulai_berlaku',
        ];
    }

    protected function masaBerlakuAttributeNames(): array
    {
        return [
            'tanggal_mulai_berlaku' => 'Tanggal mulai berlaku',
            'tanggal_berakhir_berlaku' => 'Tanggal berakhir berlaku',
        ];
    }
}
