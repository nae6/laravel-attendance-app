<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ログイン画面の表示
     */
    public function test_display_admin_login_view(): void
    {
        $response = $this->get('/admin/login');

        $response->assertOk();
        $response->assertViewIs('admin.login');
    }

    /**
     * メールアドレス入力のエラー確認
     */
    public function test_email_is_required_for_admin_login(): void
    {
        User::factory()->create([
            'password' => bcrypt('password'),
        ]);

        $response = $this->from('/login')
            ->post('/login', [
                'email' => '',
                'password' => 'password',
            ]);

        $response->assertRedirect('/login');

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください'
        ]);
    }

    /**
     * パスワード入力のエラー確認
     */
    public function test_password_is_required_for_admin_login(): void
    {
        $user = User::factory()->create();

        $response = $this->from('/login')
            ->post('/login', [
                'email' => $user->email,
                'password' => '',
            ]);

        $response->assertRedirect('/login');

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください'
        ]);
    }

    /**
     * メールアドレス入力間違いのエラー確認
     */
    public function test_admin_email_input_is_invalid(): void
    {
        User::factory()->create([
            'password' => bcrypt('password'),
        ]);

        $response = $this->from('/login')
            ->post('/login', [
                'email' => 'notfound@example.com',
                'password' => 'password',
            ]);

        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません'
        ]);

        $this->assertGuest();
    }
}
