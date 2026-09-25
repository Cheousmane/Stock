<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification de votre adresse e-mail</title>
    <style>
        body { font-family: -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background: #f4f5f7; color: #17171b; margin: 0; padding: 0; }
        .container { max-width: 560px; margin: 32px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgb(0 0 0 / 0.08); }
        .header { padding: 24px 32px; background-color: #059669; background: linear-gradient(135deg, #059669, #10b981); color: #ffffff; }
        .brand-table { width: 100%; border-collapse: collapse; }
        .brand-table td { vertical-align: middle; }
        .brand-logo { width: 52px; }
        .brand-logo img { width: 44px; height: 44px; border-radius: 10px; display: block; }
        .brand-name { font-size: 16px; font-weight: 800; letter-spacing: -0.2px; }
        .brand-sub { font-size: 12px; opacity: 0.9; margin-top: 2px; }
        .header h1 { font-size: 18px; margin: 16px 0 4px; }
        .header p { margin: 0; font-size: 13px; opacity: 0.9; }
        .body { padding: 32px; }
        .code { background: #ecfdf5; border: 1px dashed #a7f3d0; border-radius: 10px; padding: 20px; margin: 20px 0; text-align: center; }
        .code strong { font-size: 32px; letter-spacing: 8px; color: #047857; }
        .note { font-size: 13px; color: #6b7280; margin-top: 8px; }
        .footer { padding: 20px 32px; font-size: 12px; color: #6b7280; border-top: 1px solid #f0f0f0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <table class="brand-table" cellpadding="0" cellspacing="0">
                <tr>
                    <td class="brand-logo">
                        <img src="{{ $message->embed(public_path('images/logo-mail.png')) }}" width="44" height="44" alt="SIDIBE CORPORATE">
                    </td>
                    <td>
                        <div class="brand-name">SIDIBE CORPORATE</div>
                        <div class="brand-sub">Facturation &amp; Stock</div>
                    </td>
                </tr>
            </table>
            <h1>Vérification de votre adresse e-mail</h1>
            <p>Confirmez votre compte en 20 minutes</p>
        </div>
        <div class="body">
            <p>Bonjour {{ $userName }},</p>
            <p>Merci d'avoir créé votre compte. Pour confirmer votre adresse e-mail <strong>{{ $email }}</strong>, utilisez le code ci-dessous :</p>

            <div class="code">
                <strong>{{ $code }}</strong>
            </div>

            <p class="note">Ce code expire dans 20 minutes. Si vous n'êtes pas à l'origine de cette inscription, ignorez simplement cet e-mail.</p>
        </div>
        <div class="footer">
            <table cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;">
                <tr>
                    <td style="vertical-align:middle;width:30px;">
                        <img src="{{ $message->embed(public_path('images/logo-mail.png')) }}" width="24" height="24" alt="" style="border-radius:6px;display:block;">
                    </td>
                    <td style="vertical-align:middle;">SIDIBE CORPORATE — Facturation &amp; Stock · © {{ date('Y') }}</td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
