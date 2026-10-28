<?php

declare(strict_types=1);

namespace App\Domains\Mail\Http\Controllers;

use App\Domains\Contacts\Services\Public\ContactsService;
use App\Domains\Mail\Support\UnsubscribeToken;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class UnsubscribeController extends Controller
{
    /**
     * Public, unauthenticated. Invalid / expired / tampered tokens render
     * the same generic "invalid link" page so nothing leaks.
     *
     * GET (the link in the email body) only renders a confirmation page:
     * mail security scanners fetch every link in a message on delivery,
     * and unsubscribing on GET silently opts real customers out — the
     * very problem RFC 8058 exists to solve. The unsubscribe happens on
     * POST: the confirmation button, or the one-click List-Unsubscribe-
     * Post request mail clients send. Idempotent.
     */
    public function __invoke(Request $request, string $token, ContactsService $contacts): View
    {
        $contactId = UnsubscribeToken::decode($token);

        if ($contactId === null) {
            return view('mail.unsubscribe-invalid');
        }

        if (! $request->isMethod('POST')) {
            return view('mail.unsubscribe-confirm', ['action' => $request->url()]);
        }

        $contacts->flagDoNotEmail($contactId, 'unsubscribe-link');

        return view('mail.unsubscribe-success');
    }
}
