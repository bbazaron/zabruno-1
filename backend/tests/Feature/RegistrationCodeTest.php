<?php

namespace Tests\Feature;

use App\Mail\RegistrationCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegistrationCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_sends_code_and_does_not_create_user(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/register', [
            'firstName' => 'Анна',
            'lastName' => 'Иванова',
            'email' => 'anna@example.com',
            'password' => 'secret1',
        ]);

        $response->assertOk()->assertJsonPath('message', 'Код отправлен на почту');
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseHas('registration_codes', [
            'email' => 'anna@example.com',
            'name' => 'Анна Иванова',
        ]);
        Mail::assertSent(RegistrationCode::class, function (RegistrationCode $mail) {
            return $mail->email === 'anna@example.com' && strlen($mail->code) === 6;
        });
    }

    public function test_confirm_creates_user_that_can_log_in(): void
    {
        Mail::fake();

        $this->postJson('/api/register', [
            'firstName' => 'Анна',
            'lastName' => 'Иванова',
            'email' => 'anna@example.com',
            'password' => 'secret1',
        ])->assertOk();

        $code = null;
        Mail::assertSent(RegistrationCode::class, function (RegistrationCode $mail) use (&$code) {
            $code = $mail->code;

            return true;
        });

        $this->postJson('/api/register/confirm', [
            'email' => 'anna@example.com',
            'code' => $code,
        ])->assertOk()->assertJsonPath('message', 'Регистрация успешна');

        $user = User::query()->where('email', 'anna@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('secret1', $user->password));
        $this->assertNotNull($user->email_verified_at);
        $this->assertDatabaseCount('registration_codes', 0);

        $this->postJson('/api/login', [
            'email' => 'anna@example.com',
            'password' => 'secret1',
        ])->assertOk();
    }

    public function test_existing_email_is_rejected_without_mail(): void
    {
        Mail::fake();
        User::factory()->create(['email' => 'anna@example.com']);

        $this->postJson('/api/register', [
            'firstName' => 'Анна',
            'lastName' => 'Иванова',
            'email' => 'anna@example.com',
            'password' => 'secret1',
        ])->assertStatus(422)->assertJsonPath('message', 'Этот email уже зарегистрирован');

        Mail::assertNothingSent();
    }

    public function test_wrong_code_does_not_create_user(): void
    {
        Mail::fake();
        $this->postJson('/api/register', [
            'firstName' => 'Анна',
            'lastName' => 'Иванова',
            'email' => 'anna@example.com',
            'password' => 'secret1',
        ])->assertOk();

        $this->postJson('/api/register/confirm', [
            'email' => 'anna@example.com',
            'code' => '000000',
        ])->assertStatus(422);

        $this->assertDatabaseCount('users', 0);
    }
}
