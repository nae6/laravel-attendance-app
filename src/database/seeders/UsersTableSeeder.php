<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;
use App\Models\User;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 管理者
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('admin1234'),
            'role' => 'admin',
        ]);

        // 一般ユーザー
        User::factory()->create([
            'name' => 'テスト桃子',
            'email' => 'testmomoko@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('testmomoko'),
        ]);

        User::factory()->count(2)->create();
    }
}
