<?php

namespace Tests\Feature\admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Tests\TestCase;

class StaffListTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 全スタッフの一覧が一覧が正しく確認出来る
     */
    public function test_staff_list_page_shows_all_staffs(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        User::factory()->create([
            'name' => 'テスト太郎',
            'email' => 'tarou@test.com'
        ]);

        User::factory()->create([
            'name' => 'テスト花子',
            'email' => 'hanako@test.com'
        ]);

        $response = $this->actingAs($admin)->get(route('staff.list'));

        $response->assertOk();
        $response->assertSee('テスト太郎');
        $response->assertSee('tarou@test.com');
        $response->assertSee('テスト花子');
        $response->assertSee('hanako@test.com');
    }
}
