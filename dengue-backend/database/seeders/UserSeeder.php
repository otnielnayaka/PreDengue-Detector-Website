<?php
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // ADMIN default
        User::updateOrCreate(
            ['email' => 'admin@dengue.test'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        // VIEWER default
        User::updateOrCreate(
            ['email' => 'viewer@dengue.test'],
            [
                'name' => 'Viewer User',
                'password' => Hash::make('viewer123'),
                'role' => 'viewer',
                'is_active' => true,
            ]
        );
    }
}
