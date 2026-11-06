<?php

namespace Tests\Feature;

use App\Mail\PasswordResetCode;
use App\Models\User;
use App\Services\PasswordResetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_queues_mail_for_existing_user(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email' => 'parent@example.com']);

        $response = $this->postJson('/api/password/forgot', [
            'email' => 'parent@example.com',
        ]);

        $response->assertOk()->assertJsonPath('message', 'Код отправлен на почту');
        Mail::assertSent(PasswordResetCode::class, function (PasswordResetCode $mail) use ($user) {
            return $mail->user->is($user) && strlen($mail->code) === 6;
        });
        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => 'parent@example.com',
        ]);
    }

    public function test_forgot_reports_missing_account(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/password/forgot', [
            'email' => 'nobody@example.com',
        ]);

        $response->assertStatus(422)->assertJsonPath('message', 'Аккаунт с такой почтой не найден');
        Mail::assertNothingSent();
        $this->assertDatabaseCount('password_reset_tokens', 0);
    }

    public function test_failed_mail_does_not_start_resend_cooldown(): void
    {
        $user = User::factory()->create(['email' => 'parent@example.com']);
        Mail::shouldReceive('to')->twice()->andReturnUsing(function () {
            $pending = \Mockery::mock();
            $pending->shouldReceive('send')->once()->andThrow(new \RuntimeException('SMTP connection refused'));

            return $pending;
        });

        $this->postJson('/api/password/forgot', ['email' => $user->email])
            ->assertStatus(422)
            ->assertJsonPath('message', 'SMTP connection refused');

        $this->assertDatabaseCount('password_reset_tokens', 0);

        $this->postJson('/api/password/forgot', ['email' => $user->email])
            ->assertStatus(422)
            ->assertJsonPath('message', 'SMTP connection refused');
    }

    public function test_forgot_rejects_resend_within_interval(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email' => 'parent@example.com']);

        $this->postJson('/api/password/forgot', ['email' => $user->email])->assertOk();

        $this->postJson('/api/password/forgot', ['email' => $user->email])
            ->assertStatus(429)
            ->assertJsonStructure(['message', 'retry_after']);

        Mail::assertSent(PasswordResetCode::class, 1);
    }

    public function test_user_can_reset_password_with_valid_code(): void
    {
        Mail::fake();
        $user = User::factory()->create([
            'email' => 'parent@example.com',
            'password' => 'old-password',
        ]);

        $this->postJson('/api/password/forgot', ['email' => $user->email])->assertOk();

        $code = null;
        Mail::assertSent(PasswordResetCode::class, function (PasswordResetCode $mail) use (&$code) {
            $code = $mail->code;

            return true;
        });
        $this->assertNotNull($code);

        $this->postJson('/api/password/verify', [
            'email' => $user->email,
            'code' => $code,
        ])->assertOk();

        $this->postJson('/api/password/reset', [
            'email' => $user->email,
            'code' => $code,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertOk();

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => $user->email,
        ]);
    }

    public function test_invalid_code_is_rejected(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email' => 'parent@example.com']);
        $this->postJson('/api/password/forgot', ['email' => $user->email])->assertOk();

        $this->postJson('/api/password/verify', [
            'email' => $user->email,
            'code' => '000000',
        ])->assertStatus(422);
    }

    public function test_expired_code_is_rejected(): void
    {
        $user = User::factory()->create(['email' => 'parent@example.com']);
        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => Hash::make('123456'),
            'created_at' => now()->subMinutes(PasswordResetService::CODE_TTL_MINUTES + 1),
        ]);

        $this->postJson('/api/password/reset', [
            'email' => $user->email,
            'code' => '123456',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertStatus(422);

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }
}
