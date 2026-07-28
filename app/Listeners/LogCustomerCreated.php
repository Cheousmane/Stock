<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\CustomerCreated;
use App\Services\WebhookDispatcherService;

final class LogCustomerCreated
{
    public function handle(CustomerCreated $event): void
    {
        $customer = $event->customer;

        activity()
            ->performedOn($customer)
            ->withProperties([
                'customer_name' => $customer->name,
                'customer_email' => $customer->email,
                'customer_code' => $customer->code,
            ])
            ->event('created')
            ->log('Customer created');

        app(WebhookDispatcherService::class)->dispatch('customer.created', [
            'id' => $customer->id,
            'name' => $customer->name,
            'email' => $customer->email,
            'code' => $customer->code,
        ]);
    }
}
