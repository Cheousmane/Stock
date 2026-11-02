<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $token,
        public string $email,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appUrl = rtrim(config('app.frontend_url', config('app.url')), '/');
        $resetUrl = $appUrl.'/reset-password?'.http_build_query([
            'token' => $this->token,
            'email' => $this->email,
        ]);

        return (new MailMessage)
            ->subject('Réinitialisation de mot de passe')
            ->line('Vous recevez ce courriel car nous avons reçu une demande de réinitialisation de mot de passe pour votre compte.')
            ->action('Réinitialiser le mot de passe', $resetUrl)
            ->line('Ce lien expirera dans '.config('auth.passwords.users.expire').' minutes.')
            ->line('Si vous n\'avez pas demandé de réinitialisation, ignorez ce message.');
    }
}
