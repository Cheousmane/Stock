<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WebhookEndpointRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('webhook_endpoint');

        return [
            'name' => 'required|string|max:255',
            'url' => ['required', 'url', 'max:500', $this->denyPrivateIp()],
            'secret' => 'nullable|string|max:255',
            'events' => 'required|array|min:1',
            'events.*' => ['required', 'string', Rule::in($this->availableEvents())],
            'is_active' => 'boolean',
        ];
    }

    public function availableEvents(): array
    {
        return [
            'invoice.created',
            'invoice.paid',
            'invoice.overdue',
            'invoice.cancelled',
            'payment.received',
            'customer.created',
            'quote.accepted',
            'stock.low',
            'stock.transferred',
            'delivery_note.shipped',
            'delivery_note.delivered',
        ];
    }

    private function denyPrivateIp(): callable
    {
        return function (string $attribute, mixed $value, callable $fail): void {
            $host = parse_url($value, PHP_URL_HOST);
            if (!$host) {
                return;
            }

            $ip = gethostbyname($host);
            if ($ip === $host) {
                $ip = $host;
            }

            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                $fail('Les adresses vers des réseaux internes ou privés ne sont pas autorisées.');
            }
        };
    }
}
