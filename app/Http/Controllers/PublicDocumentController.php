<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\DeliveryNote;
use App\Models\Invoice;
use App\Models\Quote;

/**
 * Unauthenticated document verification (QR code target).
 */
class PublicDocumentController extends Controller
{
    public function verify(string $type, string $uuid)
    {
        $model = match ($type) {
            'invoice' => Invoice::class,
            'quote' => Quote::class,
            'delivery-note' => DeliveryNote::class,
            default => null,
        };

        if ($model === null) {
            abort(404);
        }

        $document = $model::query()->with('company')->where('uuid', $uuid)->first();

        if (! $document) {
            abort(404);
        }

        return view('public-verify', [
            'type' => $type,
            'docTypeKey' => match ($type) {
                'delivery-note' => 'delivery_note',
                default => $type,
            },
            'document' => $document,
        ]);
    }
}
