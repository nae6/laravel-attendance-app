<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use App\Models\User;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 認証メールが送信されるか
     */
    public function test_verification_email_is_sent(): void
    {
        Notification::fake();

        $response = $this->post(route('register'), [
            'name' => '山田太郎',
            'email' => 'taro@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $user = User::where('email', 'taro@example.com')->first();

        $this->assertNotNull($user);

        $response->assertRedirect(route('verification.notice'));

        Notification::assertSentTo($user, VerifyEmail::class);

        $this->assertAuthenticatedAs($user);
    }

    /**
     * メール認証誘導画面が表示されるか
     */
    public function test_display_verify_email_view(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get(route('verification.notice'));

        $response->assertOk();
        $response->assertSee('認証はこちらから');
    }

    /**
     * 認証完了後は勤怠登録画面へ遷移するか
     */
    public function test_user_can_redirect_to_profile_edit(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->email),
            ]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        $response->assertRedirect(route('attendance'));

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }
}
