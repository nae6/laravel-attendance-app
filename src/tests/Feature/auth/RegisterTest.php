<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ユーザー登録画面が表示されるか
     */
    public function test_display_register_view(): void
    {
        $response = $this->get('/register');

        $response->assertOk();
        $response->assertViewIs('auth.register');
    }

    /**
     * 名前入力のエラー確認
     */
    public function test_name_is_required_for_registration(): void
    {
        $response = $this->from('/register')
            ->post('/register', [
                'name' => '',
                'email' => 'test@example.com',
                'password' => 'password',
                'password_confirmation' => 'password'
            ]);

        $response->assertRedirect('/register');

        $response->assertSessionHasErrors([
            'name' => 'お名前を入力してください'
        ]);
    }

    /**
     * メールアドレス入力のエラー確認
     */
    public function test_email_is_required_for_registration(): void
    {
        $response = $this->from('/register')
            ->post('/register', [
                'name' => 'test user',
                'email' => '',
                'password' => 'password',
                'password_confirmation' => 'password'
            ]);

        $response->assertRedirect('/register');

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください'
        ]);
    }

    /**
     * パスワード入力のエラー確認
     */
    public function test_password_is_required_for_registration(): void
    {
        $response = $this->from('/register')
            ->post('/register', [
                'name' => 'test user',
                'email' => 'test@example.com',
                'password' => '',
                'password_confirmation' => ''
            ]);

        $response->assertRedirect('/register');

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください'
        ]);
    }

    /**
     * パスワード文字数のエラー確認
     */
    public function test_password_is_less_than_8_characters(): void
    {
        $response = $this->from('/register')
            ->post('/register', [
                'name' => 'test user',
                'email' => 'test@example.com',
                'password' => 'pass12',
                'password_confirmation' => 'pass12'
            ]);

        $response->assertRedirect('/register');

        $response->assertSessionHasErrors([
            'password' => 'パスワードは8文字以上で入力してください'
        ]);
    }

    /**
     * パスワード不一致のエラー確認
     */
    public function test_password_confirmation_must_match(): void
    {
        $response = $this->from('/register')
            ->post('/register', [
                'name' => 'test user',
                'email' => 'test@example.com',
                'password' => 'password123',
                'password_confirmation' => 'different123',
            ]);

        $response->assertRedirect('/register');

        $response->assertSessionHasErrors([
            'password_confirmation' => 'パスワードと一致しません'
        ]);
    }

    /**
     * ユーザー登録が成功しログインするか
     */
    public function test_user_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => '山田太郎',
            'email' => 'taro@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('verification.notice'));

        $this->assertDatabaseHas('users', [
            'email' => 'taro@example.com'
        ]);

        $this->assertAuthenticated();
    }
}
