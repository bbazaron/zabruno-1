<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PasswordResetService
{
    public const CODE_TTL_MINUTES = 10;

    public const RESEND_INTERVAL_SECONDS = 60;

    public function __construct(
        private PasswordResetMailService $mail,
    ) {}

    /**
     * Сохраняет хеш кода и отправляет письмо.
     * Возвращает число секунд до повторной отправки, если код уже запрошен недавно.
     *
     * @throws \RuntimeException если аккаунта нет
     * @throws \Throwable если письмо не удалось отправить
     */
    public function requestCode(string $email): ?int
    {
        $user = $this->findUserByEmail($email);
        if ($user === null) {
            throw new \RuntimeException('Аккаунт с такой почтой не найден');
        }

        $existing = DB::table('password_reset_tokens')->where('email', $user->email)->first();
        if ($existing !== null && $this->isRecent((string) ($existing->created_at ?? ''))) {
            $elapsed = now()->getTimestamp() - Carbon::parse((string) $existing->created_at)->getTimestamp();

            return max(1, self::RESEND_INTERVAL_SECONDS - $elapsed);
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token' => Hash::make($code),
                'created_at' => now(),
            ],
        );

        try {
            $this->mail->send($user, $code);
        } catch (\Throwable $e) {
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();

            throw $e;
        }

        return null;
    }

    public function verifyCode(string $email, string $code): bool
    {
        return $this->matchStoredCode($email, $code) !== null;
    }

    public function resetPassword(string $email, string $code, string $password): bool
    {
        $matched = $this->matchStoredCode($email, $code);
        if ($matched === null) {
            return false;
        }

        [$user, $tokenEmail] = $matched;

        $user->password = $password;
        $user->save();
        $user->tokens()->delete();

        DB::table('password_reset_tokens')->where('email', $tokenEmail)->delete();

        return true;
    }

    /**
     * @return array{0: User, 1: string}|null
     */
    private function matchStoredCode(string $email, string $code): ?array
    {
        $code = preg_replace('/\D/', '', $code) ?? '';
        if (strlen($code) !== 6) {
            return null;
        }

        $user = $this->findUserByEmail($email);
        if ($user === null) {
            return null;
        }

        $row = DB::table('password_reset_tokens')->where('email', $user->email)->first();
        if ($row === null || empty($row->token) || empty($row->created_at)) {
            return null;
        }

        $createdAt = Carbon::parse((string) $row->created_at);
        if ($createdAt->lt(now()->subMinutes(self::CODE_TTL_MINUTES))) {
            return null;
        }

        if (! Hash::check($code, (string) $row->token)) {
            return null;
        }

        return [$user, (string) $row->email];
    }

    private function findUserByEmail(string $email): ?User
    {
        $normalized = mb_strtolower(trim($email));
        if ($normalized === '') {
            return null;
        }

        return User::query()
            ->whereRaw('LOWER(email) = ?', [$normalized])
            ->first();
    }

    private function isRecent(string $createdAt): bool
    {
        if ($createdAt === '') {
            return false;
        }

        return Carbon::parse($createdAt)->gte(now()->subSeconds(self::RESEND_INTERVAL_SECONDS));
    }
}
