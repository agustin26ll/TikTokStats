<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->seedUsuarioDePrueba();
    }

    private function seedUsuarioDePrueba(): void
    {
        $username = 'mitiktokusername';
        \DB::table('usuarios')->updateOrInsert(
            ['tiktok_username' => $username],
            ['created_at' => now()],
        );
    }
}
