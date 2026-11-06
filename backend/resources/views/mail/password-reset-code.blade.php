@php
    $profileName = trim((string) ($user->name ?? ''));
@endphp

<p>Здравствуйте@if($profileName !== ''), {{ $profileName }}@endif!</p>

<p>Вы запросили восстановление пароля. Ваш код подтверждения:</p>

<p style="font-size:28px;letter-spacing:0.35em;font-weight:bold;margin:16px 0;">{{ $code }}</p>

<p>Код действует 15 минут. Если вы не запрашивали восстановление пароля, проигнорируйте это письмо.</p>

<p>С уважением,<br>{{ config('app.name') }}</p>
