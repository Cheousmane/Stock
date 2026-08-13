<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class LowStockDigestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /** @param \Illuminate\Support\Collection<int, \App\Models\Product> $products */
    public function __construct(
        public readonly Company $company,
        public readonly \Illuminate\Support\Collection $products,
        public readonly int $lowStockCount,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $lines = $this->products
            ->map(fn ($p) => "- {$p->name} : {$p->quantity} restant(s) (seuil {$p->min_stock})")
            ->take(15)
            ->all();

        $mail = (new MailMessage)
            ->subject("Alerte stock bas — {$this->lowStockCount} produit(s) concerné(s)")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("{$this->company->name} a {$this->lowStockCount} produit(s) sous le seuil minimum.")
            ->line(implode("\n", $lines));

        if ($this->products->count() > 15) {
            $mail->line('… et ' . ($this->products->count() - 15) . ' autre(s) produit(s).');
        }

        return $mail->action('Voir le stock', config('app.frontend_url', config('app.url')).'/stock');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'company_id' => $this->company->id,
            'low_stock_count' => $this->lowStockCount,
            'products' => $this->products->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'quantity' => $p->quantity,
                'min_stock' => $p->min_stock,
            ])->all(),
            'message' => "Stock bas : {$this->lowStockCount} produit(s) sous le seuil minimum.",
        ];
    }
}