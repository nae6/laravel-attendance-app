<?php

namespace Tests\Feature\admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Tests\TestCase;

class AdminMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 一般ユーザーが管理者専用ページにアクセスすると403になることを確認
     */
    public function test_general_user_cannot_access_admin_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('admin.attendance.index'));

        $response->assertForbidden();
    }
}
