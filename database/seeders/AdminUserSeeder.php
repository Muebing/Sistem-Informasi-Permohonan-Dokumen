<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed the default admin account.
     */
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@siperdo.test'],
            [
                'name' => 'Admin SIPERDO',
                'password' => 'password',
            ],
        );

        $admin->assignRole('admin');
    }
}
