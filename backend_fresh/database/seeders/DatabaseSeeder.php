<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (! User::where('role', 'super_admin')->exists()) {
            User::create([
                'name' => 'Edi Prasetiyo',
                'phone' => '081908226774',
                'password' => 'ganti-password-2026',
                'role' => 'super_admin',
                'is_active' => true,
            ]);
        }
    }
}
