<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Quote;
use Illuminate\Foundation\Events\Dispatchable;

final class QuoteAccepted
{
    use Dispatchable;

    public function __construct(
        public readonly Quote $quote,
    ) {}
}
