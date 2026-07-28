<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Customer;
use Illuminate\Foundation\Events\Dispatchable;

final class CustomerCreated
{
    use Dispatchable;

    public function __construct(
        public readonly Customer $customer,
    ) {}
}
