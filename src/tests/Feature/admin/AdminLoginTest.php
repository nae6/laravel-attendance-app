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

    /**
     * ログイン成功時、管理者勤怠一覧画面にリダイレクトされることを確認
     */
    public function test_admin_can_login_and_is_redirected_to_admin_attendance_index(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/login', [
            'login_type' => 'admin',
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.attendance.index'));
        $this->assertAuthenticatedAs($admin);
    }

    /**
     * 一般ユーザーアカウントで管理者ログイン画面からログインするとエラーになることを確認
     */
    public function test_user_account_cannot_login_from_admin_login_screen(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password'),
        ]);

        $response = $this->from('/admin/login')
            ->post('/login', [
                'login_type' => 'admin',
                'email' => $user->email,
                'password' => 'password',
            ]);

        $response->assertSessionHasErrors([
            'role' => 'この画面からはログインできません'
        ]);

        $this->assertGuest();
    }
}
