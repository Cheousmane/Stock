<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Diagnostic e-mail provisoire : php artisan mail:test destinataire@exemple.com
 * Affiche OK ou la classe + message d'exception. À SUPPRIMER après diagnostic.
 */
class TestMailCommand extends Command
{
    protected $signature = 'mail:test {email} {--mailer= : Mailer à tester (ex: brevo, smtp). Défaut: MAIL_MAILER}';

    protected $description = 'Teste l\'envoi d\'e-mail via un mailer (diagnostic provisoire).';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $mailer = $this->option('mailer');

        try {
            Mail::mailer($mailer ?: null)->raw('E-mail de test diagnostic.', function ($message) use ($email) {
                $message->to($email)->subject('Test diagnostic');
            });
        } catch (\Throwable $e) {
            $this->error(get_class($e).': '.mb_substr($e->getMessage(), 0, 500));

            return self::FAILURE;
        }

        $this->info('OK : e-mail accepté par le transport.');

        return self::SUCCESS;
    }
}
