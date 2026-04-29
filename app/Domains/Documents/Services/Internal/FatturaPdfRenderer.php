<?php

declare(strict_types=1);

namespace App\Domains\Documents\Services\Internal;

use App\Domains\Documents\DTOs\FatturaPayloadDTO;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Renders a fattura PDF from its payload DTO.
 *
 * Uses dompdf via barryvdh/laravel-dompdf. DejaVu Sans (the package's
 * default font) renders € and accented characters correctly out of the
 * box — no extra font config needed.
 */
class FatturaPdfRenderer
{
    public function render(FatturaPayloadDTO $payload): string
    {
        $pdf = Pdf::loadView('documents.fattura', [
            'fattura' => $payload,
            'companyName' => config('app.name'),
            'locale' => app()->getLocale(),
        ])->setPaper('A4');

        return (string) $pdf->output();
    }
}
