<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMail extends Mailable
{
    use SerializesModels;

    public string $token;
    public string $userEmail;

    public function __construct(string $userEmail, string $token)
    {
        $this->userEmail = $userEmail;
        $this->token     = $token;
    }

    public function build(): static
    {
        return $this
            ->subject('Réinitialisation de votre mot de passe — DoualaClean')
            ->html($this->buildHtml());
    }

    private function buildHtml(): string
    {
        $token = htmlspecialchars($this->token);
        $year  = date('Y');

        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,sans-serif">
  <table width="100%" cellpadding="0" cellspacing="0" style="padding:30px 0">
    <tr><td align="center">
      <table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08)">
        <tr>
          <td style="background:#2e7d32;padding:28px 40px;text-align:center">
            <h1 style="color:#fff;margin:0;font-size:24px">🌿 DoualaClean</h1>
            <p style="color:#c8e6c9;margin:6px 0 0;font-size:14px">Ensemble, rendons Douala plus propre</p>
          </td>
        </tr>
        <tr>
          <td style="padding:36px 40px">
            <h2 style="color:#2e7d32;margin:0 0 16px">Réinitialisation de mot de passe</h2>
            <p style="color:#555;line-height:1.6">Vous avez demandé la réinitialisation de votre mot de passe pour le compte <strong>{$this->userEmail}</strong>.</p>
            <p style="color:#555;line-height:1.6">Utilisez le token ci-dessous sur la page de réinitialisation :</p>
            <div style="background:#f1f8e9;border:1px solid #a5d6a7;border-radius:8px;padding:18px 20px;margin:20px 0;text-align:center">
              <code style="font-family:monospace;font-size:13px;word-break:break-all;color:#1b5e20">{$token}</code>
            </div>
            <p style="color:#888;font-size:13px">⏰ Ce token expire dans <strong>60 minutes</strong>.</p>
            <p style="color:#888;font-size:13px">Si vous n'avez pas demandé cette réinitialisation, ignorez cet email en toute sécurité.</p>
          </td>
        </tr>
        <tr>
          <td style="background:#f9f9f9;padding:18px 40px;text-align:center;border-top:1px solid #eee">
            <p style="color:#aaa;font-size:12px;margin:0">© {$year} DoualaClean — Projet citoyen</p>
          </td>
        </tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;
    }
}
