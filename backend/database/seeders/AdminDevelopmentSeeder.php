<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminDevelopmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (!app()->environment(['local', 'testing'])) {
            abort(403, 'Seeder ini hanya untuk environment lokal atau testing.');
        }

        $username = env('DEV_ADMIN_USERNAME');
        $password = env('DEV_ADMIN_PASSWORD');

        if (!$username || !$password) {
            $this->command->warn('DEV_ADMIN_USERNAME atau DEV_ADMIN_PASSWORD belum diatur di .env. Seeder diabaikan.');
            return;
        }

        User::updateOrCreate(
            ['username' => $username],
            [
                'name' => 'Administrator',
                'email' => 'admin@example.com',
                'password' => Hash::make($password),
            ]
        );

        $this->command->info('Admin user seeded successfully.');
    }
}
