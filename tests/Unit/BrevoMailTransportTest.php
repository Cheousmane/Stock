<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Bridge\Brevo\Transport\BrevoApiTransport;

it('resolves the brevo mailer to the brevo api transport', function () {
    config([
        'mail.mailers.brevo.key' => 'xkeysib-test-key',
        'mail.mailers.brevo.timeout' => 5,
    ]);

    Mail::purge('brevo');

    $transport = Mail::mailer('brevo')->getSymfonyTransport();

    expect($transport)->toBeInstanceOf(BrevoApiTransport::class);
});

it('fails with a clear message when the brevo api key is missing', function () {
    config(['mail.mailers.brevo.key' => null]);

    Mail::purge('brevo');

    expect(fn () => Mail::mailer('brevo'))
        ->toThrow(InvalidArgumentException::class, 'BREVO_API_KEY');
});
