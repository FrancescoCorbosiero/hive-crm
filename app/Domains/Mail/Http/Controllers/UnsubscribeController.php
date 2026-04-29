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
     * Public, unauthenticated. Idempotent: hitting the same valid token
     * twice is a no-op. Invalid / expired / tampered tokens render the
     * same generic "invalid link" page so nothing leaks.
     *
     * Supports GET (browser click) and POST (RFC 8058 List-Unsubscribe-
     * Post one-click flow).
     */
    public function __invoke(Request $request, string $token, ContactsService $contacts): View
    {
        $contactId = UnsubscribeToken::decode($token);

        if ($contactId === null) {
            return view('mail.unsubscribe-invalid');
        }

        $contacts->flagDoNotEmail($contactId, 'unsubscribe-link');

        return view('mail.unsubscribe-success');
    }
}
