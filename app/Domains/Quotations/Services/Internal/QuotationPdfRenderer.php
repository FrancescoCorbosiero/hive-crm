<?php

declare(strict_types=1);

namespace App\Domains\Quotations\Services\Internal;

use App\Domains\Contacts\Services\Public\ContactsService;
use App\Domains\Quotations\Models\Quotation;
use Barryvdh\DomPDF\Facade\Pdf;

class QuotationPdfRenderer
{
    public function __construct(private readonly ContactsService $contacts) {}

    public function render(Quotation $q): string
    {
        $pdf = Pdf::loadView('quotations.preventivo', [
            'q' => $q,
            'client' => $this->contacts->find($q->client_contact_id),
            'companyName' => config('app.name'),
            'locale' => app()->getLocale(),
        ])->setPaper('A4');

        return (string) $pdf->output();
    }
}
