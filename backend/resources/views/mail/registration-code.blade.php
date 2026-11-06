@php
    $profileName = trim((string) ($name ?? ''));
@endphp
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Код для подтверждения почты</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f4f5;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f5;padding:32px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:480px;background-color:#ffffff;border-radius:16px;overflow:hidden;">
                    <tr>
                        <td style="background-color:#0f172a;padding:24px 32px;">
                            <p style="margin:0;font-family:Georgia,'Times New Roman',serif;font-size:16px;letter-spacing:0.14em;color:#ffffff;">ШКОЛЬНАЯ ФОРМА</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
                            <p style="margin:0 0 8px;font-size:20px;line-height:1.4;">Здравствуйте@if($profileName !== ''), {{ $profileName }}@endif!</p>
                            <p style="margin:0 0 24px;font-size:15px;line-height:1.6;color:#475569;">Подтвердите почту, чтобы завершить регистрацию. Введите код ниже на сайте. Он действует {{ $ttlMinutes }} минут.</p>
                            @include('mail.partials.code')
                            <p style="margin:0;font-size:13px;line-height:1.6;color:#64748b;">Если вы не регистрировались на сайте, проигнорируйте это письмо.</p>
                        </td>
                    </tr>
                </table>
                <p style="margin:16px 0 0;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:1.5;color:#94a3b8;">{{ config('app.name') }}</p>
            </td>
        </tr>
    </table>
</body>
</html>
