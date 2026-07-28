<?php

declare(strict_types=1);

namespace App\Actions\Quote;

use App\Enums\QuoteStatus;
use App\Events\QuoteAccepted;
use App\Models\Quote;
use Illuminate\Support\Facades\DB;

class UpdateQuoteStatusAction
{
    public function execute(Quote $quote, QuoteStatus $status): Quote
    {
        return DB::transaction(function () use ($quote, $status) {
            $oldStatus = $quote->status;

            $quote->update([
                'status' => $status,
            ]);

            $fresh = $quote->fresh();

            if ($oldStatus !== QuoteStatus::Accepted && $status === QuoteStatus::Accepted) {
                event(new QuoteAccepted($fresh));
            }

            return $fresh;
        });
    }
}
