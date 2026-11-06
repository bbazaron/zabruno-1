<?php

namespace App\Services;

use App\Mail\RegistrationCode;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class RegistrationCodeService
{
    public const CODE_TTL_MINUTES = 10;

    public const RESEND_INTERVAL_SECONDS = 60;

    /**
     * Сохраняет данные регистрации и отправляет код.
     * Аккаунт создаётся только после подтверждения кода.
     * Возвращает число секунд до повторной отправки, если код уже запрошен недавно.
     *
     * @throws \RuntimeException
     * @throws \Throwable
     */
    public function requestCode(string $firstName, string $lastName, string $email): ?int
    {
        $email = $this->normalizeEmail($email);
        $name = trim($firstName.' '.$lastName);
        if ($email === '' || $name === '') {
            throw new \RuntimeException('Заполните имя и почту');
        }

        if ($this->userExists($email)) {
            throw new \RuntimeException('Этот email уже зарегистрирован');
        }

        return $this->issue($email, $name);
    }

    /**
     * Повторно отправляет код для уже начатой регистрации.
     */
    public function resend(string $email): ?int
    {
        $email = $this->normalizeEmail($email);
        if ($this->userExists($email)) {
            throw new \RuntimeException('Этот email уже зарегистрирован');
        }

        $existing = DB::table('registration_codes')->where('email', $email)->first();
        if ($existing === null || empty($existing->name)) {
            throw new \RuntimeException('Сначала заполните форму регистрации');
        }

        return $this->issue($email, (string) $existing->name);
    }

    /**
     * Проверяет код и возвращает одноразовый токен для установки пароля.
     */
    public function verify(string $email, string $code): string
    {
        $email = $this->normalizeEmail($email);
        $code = preg_replace('/\D/', '', $code) ?? '';
        if (strlen($code) !== 6) {
            throw new \RuntimeException('Код должен состоять из 6 цифр');
        }

        if ($this->userExists($email)) {
            throw new \RuntimeException('Этот email уже зарегистрирован');
        }

        $row = DB::table('registration_codes')->where('email', $email)->first();
        if ($row === null || empty($row->token) || empty($row->created_at) || empty($row->name)) {
            throw new \RuntimeException('Неверный или просроченный код');
        }

        $createdAt = Carbon::parse((string) $row->created_at);
        if ($createdAt->lt(now()->subMinutes(self::CODE_TTL_MINUTES))) {
            throw new \RuntimeException('Неверный или просроченный код');
        }

        if (! Hash::check($code, (string) $row->token)) {
            throw new \RuntimeException('Неверный или просроченный код');
        }

        $completionToken = bin2hex(random_bytes(20));
        DB::table('registration_codes')->where('email', $email)->update([
            'token' => Hash::make($completionToken),
            'completion_token' => Hash::make($completionToken),
            'created_at' => now(),
        ]);

        return $completionToken;
    }

    public function complete(string $email, string $completionToken, string $password): User
    {
        $email = $this->normalizeEmail($email);
        if ($this->userExists($email)) {
            throw new \RuntimeException('Этот email уже зарегистрирован');
        }

        $row = DB::table('registration_codes')->where('email', $email)->first();
        if ($row === null || empty($row->completion_token) || empty($row->created_at) || empty($row->name)) {
            throw new \RuntimeException('Сначала подтвердите почту');
        }

        $createdAt = Carbon::parse((string) $row->created_at);
        if ($createdAt->lt(now()->subMinutes(self::CODE_TTL_MINUTES))) {
            throw new \RuntimeException('Время подтверждения истекло. Запросите код заново');
        }

        if (! Hash::check($completionToken, (string) $row->completion_token)) {
            throw new \RuntimeException('Сначала подтвердите почту');
        }

        $user = User::create([
            'name' => (string) $row->name,
            'email' => $email,
            'password' => $password,
            'role' => 'user',
        ]);
        $user->email_verified_at = now();
        $user->save();

        DB::table('registration_codes')->where('email', $email)->delete();

        return $user;
    }

    private function issue(string $email, string $name): ?int
    {
        if ($this->userExists($email)) {
            throw new \RuntimeException('Этот email уже зарегистрирован');
        }

        $existing = DB::table('registration_codes')->where('email', $email)->first();
        if ($existing !== null && $this->isRecent((string) ($existing->created_at ?? ''))) {
            $elapsed = now()->getTimestamp() - Carbon::parse((string) $existing->created_at)->getTimestamp();

            return max(1, self::RESEND_INTERVAL_SECONDS - $elapsed);
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::table('registration_codes')->updateOrInsert(
            ['email' => $email],
            [
                'name' => $name,
                'password' => null,
                'token' => Hash::make($code),
                'completion_token' => null,
                'created_at' => now(),
            ],
        );

        try {
            $this->send($email, $name, $code);
        } catch (\Throwable $e) {
            DB::table('registration_codes')->where('email', $email)->delete();

            throw $e;
        }

        return null;
    }

    private function send(string $email, string $name, string $code): void
    {
        try {
            Mail::to($email)->send(new RegistrationCode($email, $name, $code));
        } catch (\Throwable $e) {
            Log::error('Не удалось отправить письмо с кодом регистрации', [
                'email' => $email,
                'exception' => $e,
            ]);
            report($e);

            throw $e;
        }
    }

    private function userExists(string $email): bool
    {
        return User::query()
            ->whereRaw('LOWER(TRIM(email)) = ?', [$email])
            ->exists();
    }

    private function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    private function isRecent(string $createdAt): bool
    {
        if ($createdAt === '') {
            return false;
        }

        return Carbon::parse($createdAt)->gte(now()->subSeconds(self::RESEND_INTERVAL_SECONDS));
    }
}
